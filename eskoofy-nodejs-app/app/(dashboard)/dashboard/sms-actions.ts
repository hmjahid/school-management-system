"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";

/**
 * Server actions for the SMS dashboard — mirror `DashboardSmsController`
 * (`preview`, `send`, `dueReminder`). Recipients are resolved the same way as
 * the app (`phone_1 ?: father_phone ?: mother_phone`, staff by `users.role`).
 */

const AUDIENCE_TYPES = [
  "all_users",
  "students_class",
  "students_section",
  "students_shift",
  "students_individual",
  "staff_role",
  "staff_individual",
] as const;

type AudienceType = (typeof AUDIENCE_TYPES)[number];

export interface Recipient {
  phone: string;
  user_type: "student" | "staff";
  user_id?: number;
  name?: string;
  due?: number;
  student_id?: number;
}

function studentPhone(student: {
  phone_1: string | null;
  phone_2: string | null;
  phone: string | null;
  father_phone: string | null;
  mother_phone: string | null;
}): string | null {
  return student.phone_1 || student.phone || student.father_phone || student.mother_phone;
}

async function requireBulkSmsUser(basePath: string) {
  const user = await currentUser();
  if (!user) redirect(`/login?redirect=${encodeURIComponent(basePath)}`);
  if (!can(user.role, "bulk_sms")) redirect(`${basePath}?error=1`);
  return user;
}

/** Resolve recipients for an audience selection (mirrors resolveRecipients). */
async function resolveRecipients(data: {
  audience_type: string;
  school_class_id?: string | null;
  section_id?: string | null;
  shift?: string | null;
  role_name?: string | null;
  user_ids?: string[];
}): Promise<Recipient[]> {
  const recipients: Recipient[] = [];

  const studentQuery = (where: Record<string, unknown>): Promise<Array<{ id: number; user_id: number; phone_1: string | null; phone_2: string | null; phone: string | null; father_phone: string | null; mother_phone: string | null; users: { name: string } | null }>> =>
    prisma.students.findMany({
      where: { ...where, deleted_at: null },
      include: { users: true },
      orderBy: { id: "asc" },
    }).catch(() => []);

  const pushStudents = (students: Awaited<ReturnType<typeof studentQuery>>, userIds?: number[]) => {
    for (const s of students) {
      if (userIds && !userIds.includes(s.user_id)) continue;
      if (s.user_id === 0 || !s.user_id) continue;
      const phone = studentPhone(s);
      if (!phone) continue;
      recipients.push({
        phone,
        user_type: "student",
        user_id: s.id,
        name: s.users?.name ?? `Student #${s.id}`,
      });
    }
  };

  const pushStaff = (users: Array<{ id: number; name: string; phone: string | null; role: string }>) => {
    for (const u of users) {
      if (!u.phone) continue;
      recipients.push({ phone: u.phone, user_type: "staff", user_id: u.id, name: u.name });
    }
  };

  switch (data.audience_type) {
    case "all_users": {
      const students = await studentQuery({});
      pushStudents(students);
      const staff = await prisma.users
        .findMany({ where: { role: { in: ["admin", "teacher", "staff"] }, deleted_at: null }, select: { id: true, name: true, phone: true, role: true }, orderBy: { id: "asc" } })
        .catch(() => []);
      pushStaff(staff);
      break;
    }
    case "students_class": {
      const school_class_id = data.school_class_id ? Number(data.school_class_id) : undefined;
      const students = await studentQuery(school_class_id ? { class_id: school_class_id } : {});
      pushStudents(students);
      break;
    }
    case "students_section": {
      const section_id = data.section_id ? Number(data.section_id) : undefined;
      const students = await studentQuery(section_id ? { section_id } : {});
      pushStudents(students);
      break;
    }
    case "students_shift": {
      const shift = data.shift ?? null;
      const klassIds = shift
        ? await prisma.school_classes.findMany({ where: { shift }, select: { id: true } }).then((rows) => rows.map((r) => r.id))
        : null;
      const students = await studentQuery(klassIds ? { class_id: { in: klassIds } } : {});
      pushStudents(students);
      break;
    }
    case "students_individual": {
      const userIds = (data.user_ids ?? []).map((id) => Number(id)).filter((n) => n > 0);
      if (userIds.length === 0) break;
      const students = await studentQuery({ user_id: { in: userIds } });
      pushStudents(students, userIds);
      break;
    }
    case "staff_role": {
      const roleNames = data.role_name ? [data.role_name] : ["admin", "teacher", "staff"];
      const staff = await prisma.users
        .findMany({ where: { role: { in: roleNames }, deleted_at: null }, select: { id: true, name: true, phone: true, role: true }, orderBy: { id: "asc" } })
        .catch(() => []);
      pushStaff(staff);
      break;
    }
    case "staff_individual": {
      const userIds = (data.user_ids ?? []).map((id) => Number(id)).filter((n) => n > 0);
      if (userIds.length === 0) break;
      const staff = await prisma.users
        .findMany({ where: { id: { in: userIds }, deleted_at: null }, select: { id: true, name: true, phone: true, role: true }, orderBy: { id: "asc" } })
        .catch(() => []);
      pushStaff(staff);
      break;
    }
    default:
      break;
  }

  const seen = new Set<string>();
  return recipients.filter((r) => {
    if (seen.has(r.phone)) return false;
    seen.add(r.phone);
    return true;
  });
}

/**
 * First step of the wizard: validate the compose form, resolve recipients and
 * persist a `draft` campaign so the preview step can show real data. Redirects
 * to `/dashboard/sms/preview?campaign={id}`.
 */
export async function previewSmsCampaign(formData: FormData): Promise<void> {
  await requireBulkSmsUser("/dashboard/sms/compose");

  const name = String(formData.get("name") ?? "").trim();
  const audience_type = String(formData.get("audience_type") ?? "");
  const message = String(formData.get("message") ?? "").trim();
  if (!name || !audience_type || !message || !(AUDIENCE_TYPES as readonly string[]).includes(audience_type)) {
    redirect("/dashboard/sms/compose?error=1");
  }

  const data = {
    audience_type,
    school_class_id: (formData.get("school_class_id") as string) || null,
    section_id: (formData.get("section_id") as string) || null,
    shift: (formData.get("shift") as string) || null,
    role_name: (formData.get("role_name") as string) || null,
    user_ids: formData.getAll("user_ids").map((v) => String(v)),
  };

  const recipients = await resolveRecipients(data);
  if (recipients.length === 0) {
    redirect("/dashboard/sms/compose?error=norecipients");
  }

  const scheduled_raw = String(formData.get("scheduled_at") ?? "");
  const scheduled_at = scheduled_raw ? new Date(scheduled_raw) : null;

  const user = await currentUser();
  const campaign = await prisma.sms_campaigns.create({
    data: {
      name,
      audience_type,
      school_class_id: data.school_class_id ? Number(data.school_class_id) : null,
      section_id: data.section_id ? Number(data.section_id) : null,
      message,
      scheduled_at,
      status: "draft",
      created_by: user?.id ?? null,
      sms_campaign_recipients: {
        create: recipients.map((r) => ({
          phone: r.phone,
          user_type: r.user_type,
          user_id: r.user_id,
        })),
      },
    },
  });

  revalidatePath("/dashboard/sms");
  redirect(`/dashboard/sms/preview?campaign=${campaign.id}`);
}

/**
 * Second step of the wizard: finalize a draft campaign. Mirrors the app's
 * `send`: queued for later when `scheduled_at` is in the future, otherwise
 * dispatched immediately (`queued`).
 */
export async function sendSmsCampaign(formData: FormData): Promise<void> {
  await requireBulkSmsUser("/dashboard/sms");

  const id = Number(formData.get("campaign_id") ?? 0);
  if (!id) redirect("/dashboard/sms?error=1");

  const campaign = await prisma.sms_campaigns.findUnique({ where: { id } }).catch(() => null);
  if (!campaign) redirect("/dashboard/sms?error=1");

  const scheduled = campaign.scheduled_at ? new Date(campaign.scheduled_at) : null;
  const future = scheduled && scheduled.getTime() > Date.now();

  await prisma.sms_campaigns.update({
    where: { id },
    data: {
      status: future ? "scheduled" : "queued",
      sent_at: future ? null : new Date(),
    },
  });

  revalidatePath("/dashboard/sms");
  redirect(future ? "/dashboard/sms?status=scheduled" : "/dashboard/sms?status=queued");
}

/** Delete a draft campaign (the wizard's "back" / discard path). */
export async function discardSmsCampaign(formData: FormData): Promise<void> {
  await requireBulkSmsUser("/dashboard/sms");
  const id = Number(formData.get("campaign_id") ?? 0);
  if (id) await prisma.sms_campaigns.delete({ where: { id } }).catch(() => {});
  revalidatePath("/dashboard/sms");
  redirect("/dashboard/sms");
}

/** Due-fee reminder — mirror `DashboardSmsController@dueReminder` (POST). */
export async function sendDueFeeReminder(formData: FormData): Promise<void> {
  await requireBulkSmsUser("/dashboard/sms/due-reminder");

  const message = String(formData.get("message") ?? "").trim();
  if (!message) redirect("/dashboard/sms/due-reminder?error=1");

  const recipients = await dueFeeRecipients();
  if (recipients.length === 0) redirect("/dashboard/sms/due-reminder?error=norecipients");

  const user = await currentUser();
  const campaign = await prisma.sms_campaigns.create({
    data: {
      name: `Due Fee Reminder ${new Date().toISOString().slice(0, 10)}`,
      audience_type: "due_reminder",
      message,
      status: "queued",
      sent_at: new Date(),
      created_by: user?.id ?? null,
      sms_campaign_recipients: {
        create: recipients.map((r) => ({
          phone: r.phone,
          user_type: "student",
          user_id: r.user_id,
        })),
      },
    },
  });

  void campaign;
  revalidatePath("/dashboard/sms");
  redirect(`/dashboard/sms?status=${encodeURIComponent(`Due reminder campaign queued for ${recipients.length} recipients.`)}`);
}

/** Students with outstanding fee balances (mirrors the app helpers). */
export async function dueFeeRecipients(): Promise<Recipient[]> {
  const payments = await prisma.fee_payments
    .findMany({
      where: { balance: { gt: 0 }, status: { notIn: ["paid", "cancelled", "refunded"] }, deleted_at: null },
      include: { students: { include: { users: true } } },
      orderBy: { id: "asc" },
    })
    .catch(() => []);

  const byStudent = new Map<number, Recipient>();
  for (const payment of payments) {
    const student = payment.students;
    const phone = studentPhone(student);
    if (!phone) continue;
    const existing = byStudent.get(student.id);
    const due = Number(payment.balance ?? 0);
    if (existing) {
      existing.due = (existing.due ?? 0) + due;
      continue;
    }
    byStudent.set(student.id, {
      student_id: student.id,
      name: student.users?.name ?? `Student #${student.id}`,
      phone,
      due,
      user_type: "student",
    });
  }

  return [...byStudent.values()];
}