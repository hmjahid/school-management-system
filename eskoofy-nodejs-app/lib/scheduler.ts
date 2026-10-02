/**
 * Due-job runner behind the cron endpoint — the Node counterpart of the app's
 * scheduler (`routes/console.php`): cloud-backup dispatch, scheduled
 * notifications, recurring payments and scheduled SMS campaigns. Each job is
 * called on a cheap fixed tick and decides internally whether it is due.
 */
import { randomUUID } from "node:crypto";
import { prisma } from "@/lib/prisma";
import { dispatchCloudBackup } from "@/lib/cloud-backup";

export interface JobResult {
  job: string;
  status: "ok" | "error";
  detail?: unknown;
}

function parseObject(raw: unknown): Record<string, unknown> {
  if (raw && typeof raw === "object" && !Array.isArray(raw)) return raw as Record<string, unknown>;
  if (typeof raw !== "string" || raw === "") return {};
  try {
    const parsed: unknown = JSON.parse(raw);
    if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) return parsed as Record<string, unknown>;
  } catch {
    /* malformed → empty */
  }
  return {};
}

/** Pull recipient user ids out of a stored recipients payload (port of `resolveRecipients`). */
export function resolveRecipientIds(recipients: unknown): number[] {
  const list = Array.isArray(recipients) ? recipients : [recipients];
  const ids = new Set<number>();

  for (const value of list) {
    if (typeof value === "number" && Number.isFinite(value)) ids.add(value);
    else if (typeof value === "string" && value.trim() !== "" && Number.isFinite(Number(value))) ids.add(Number(value));
    else if (value && typeof value === "object") {
      const row = value as Record<string, unknown>;
      const id = row.id ?? row.user_id;
      if (typeof id === "number" || (typeof id === "string" && Number.isFinite(Number(id)))) ids.add(Number(id));
    }
  }

  return [...ids];
}

/** Next occurrence for a recurring schedule (port of `calculateScheduledAt` for repeats). */
export function nextOccurrence(schedule: Record<string, unknown>, from = new Date()): Date {
  const next = new Date(from);
  const type = String(schedule.type ?? "once");

  switch (type) {
    case "daily":
      next.setDate(next.getDate() + 1);
      break;
    case "weekly":
      next.setDate(next.getDate() + 7);
      break;
    case "monthly":
      next.setMonth(next.getMonth() + 1);
      break;
    case "custom": {
      const interval = Number(schedule.interval ?? 1) || 1;
      const unit = String(schedule.unit ?? "day");
      if (unit === "minute") next.setMinutes(next.getMinutes() + interval);
      else if (unit === "hour") next.setHours(next.getHours() + interval);
      else if (unit === "week") next.setDate(next.getDate() + 7 * interval);
      else if (unit === "month") next.setMonth(next.getMonth() + interval);
      else if (unit === "year") next.setFullYear(next.getFullYear() + interval);
      else next.setDate(next.getDate() + interval);
      break;
    }
    default:
      break;
  }

  return next;
}

/** Deliver and advance due `scheduled_notifications` rows. */
export async function processDueScheduledNotifications(limit = 10): Promise<number> {
  const due = await prisma.scheduled_notifications.findMany({
    where: { status: "pending", scheduled_at: { lte: new Date() }, deleted_at: null },
    orderBy: { scheduled_at: "asc" },
    take: limit,
  });

  let processed = 0;

  for (const item of due) {
    const claimed = await prisma.scheduled_notifications.updateMany({
      where: { id: item.id, status: "pending" },
      data: { status: "processing", updated_at: new Date() },
    });
    if (claimed.count === 0) continue;

    try {
      const recipientIds = resolveRecipientIds(parseObject(item.recipients));
      if (recipientIds.length === 0) throw new Error("No valid recipients");

      const channels = parseObject(item.channels);
      const payload = parseObject(item.data);

      for (const userId of recipientIds) {
        await prisma.notifications.create({
          data: {
            id: randomUUID(),
            type: item.type,
            notifiable_type: "App\\Models\\User",
            notifiable_id: userId,
            data: JSON.stringify({ ...payload, channels }),
            created_at: new Date(),
            updated_at: new Date(),
          },
        });
      }

      const schedule = parseObject(item.schedule);
      const recurring = typeof schedule.type === "string" && schedule.type !== "once";

      await prisma.scheduled_notifications.update({
        where: { id: item.id },
        data: recurring
          ? { status: "pending", sent_at: new Date(), scheduled_at: nextOccurrence(schedule), updated_at: new Date() }
          : { status: "sent", sent_at: new Date(), updated_at: new Date() },
      });

      processed++;
    } catch (error) {
      await prisma.scheduled_notifications
        .update({
          where: { id: item.id },
          data: { status: "failed", error_message: error instanceof Error ? error.message : "delivery failed", updated_at: new Date() },
        })
        .catch(() => {});
    }
  }

  return processed;
}

async function guard(job: string, fn: () => Promise<unknown>): Promise<JobResult> {
  try {
    return { job, status: "ok", detail: await fn() };
  } catch (error) {
    return { job, status: "error", detail: error instanceof Error ? error.message : String(error) };
  }
}

/** Run every due job once. Safe to call repeatedly (jobs are idempotent/guarded). */
export async function runDueJobs(): Promise<JobResult[]> {
  return [
    await guard("cloud-backup", () => dispatchCloudBackup()),
    await guard("scheduled-notifications", () => processDueScheduledNotifications()),
  ];
}
