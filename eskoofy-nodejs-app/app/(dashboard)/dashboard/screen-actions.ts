"use server";

import { mkdir, writeFile, unlink } from "node:fs/promises";
import path from "node:path";
import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";

/**
 * Server actions behind the bespoke dashboard screens: notification inbox,
 * media library and the admissions review workflow. Each mirrors the
 * corresponding Laravel controller action.
 */

async function requireUser(basePath: string) {
  const user = await currentUser();
  if (!user) redirect(`/login?redirect=${encodeURIComponent(basePath)}`);
  return user;
}

// ── Notifications ────────────────────────────────────────────────────────────

export async function markNotificationRead(formData: FormData): Promise<void> {
  const user = await requireUser("/dashboard/notifications");
  const id = String(formData.get("id") ?? "");
  if (id) {
    await prisma.notifications.update({ where: { id }, data: { read_at: new Date() } }).catch(() => {});
  }
  void user;
  revalidatePath("/dashboard/notifications");
}

export async function markAllNotificationsRead(): Promise<void> {
  const user = await requireUser("/dashboard/notifications");
  await prisma.notifications
    .updateMany({ where: { notifiable_id: user.id, read_at: null }, data: { read_at: new Date() } })
    .catch(() => {});
  revalidatePath("/dashboard/notifications");
}

// ── Media library ────────────────────────────────────────────────────────────

const MEDIA_DIR = path.join(process.cwd(), "public", "uploads", "media");
const MAX_BYTES = 10 * 1024 * 1024;

export async function uploadMedia(formData: FormData): Promise<void> {
  const user = await requireUser("/dashboard/media");
  if (!can(user.role, "manage_cms")) redirect("/dashboard/media?error=1");

  const file = formData.get("file");
  if (!(file instanceof File) || file.size === 0) redirect("/dashboard/media?error=1");
  if (file.size > MAX_BYTES) redirect("/dashboard/media?error=size");

  const safeName = file.name.replace(/[^a-zA-Z0-9._-]/g, "_");
  const filename = `${Date.now()}-${safeName}`;
  await mkdir(MEDIA_DIR, { recursive: true });
  await writeFile(path.join(MEDIA_DIR, filename), Buffer.from(await file.arrayBuffer()));

  await prisma.website_media.create({
    data: {
      title: String(formData.get("title") ?? "") || safeName,
      category: String(formData.get("category") ?? "") || "general",
      file_path: `/uploads/media/${filename}`,
      mime_type: file.type || "application/octet-stream",
      file_size: file.size,
    },
  });

  revalidatePath("/dashboard/media");
  redirect("/dashboard/media?saved=1");
}

export async function deleteMedia(formData: FormData): Promise<void> {
  const user = await requireUser("/dashboard/media");
  if (!can(user.role, "manage_cms")) redirect("/dashboard/media?error=1");

  const id = Number(formData.get("id") ?? 0);
  if (!id) redirect("/dashboard/media");
  const row = await prisma.website_media.findUnique({ where: { id } }).catch(() => null);
  if (row) {
    if (row.file_path.startsWith("/uploads/media/")) {
      await unlink(path.join(MEDIA_DIR, path.basename(row.file_path))).catch(() => {});
    }
    await prisma.website_media.delete({ where: { id } }).catch(() => {});
  }
  revalidatePath("/dashboard/media");
  redirect("/dashboard/media?saved=1");
}

// ── Admissions review ────────────────────────────────────────────────────────

export async function updateAdmissionStatus(formData: FormData): Promise<void> {
  const user = await requireUser("/dashboard/admissions");
  if (!can(user.role, "manage_students")) redirect("/dashboard/admissions?error=1");

  const id = Number(formData.get("id") ?? 0);
  const status = String(formData.get("status") ?? "");
  if (!id || !status) redirect("/dashboard/admissions");

  const allowed = ["under_review", "approved", "rejected", "waitlisted", "cancelled"];
  if (!allowed.includes(status)) redirect(`/dashboard/admissions/${id}`);

  const data: Record<string, unknown> = { status, updated_by: user.id };
  if (status === "approved") {
    data.approved_at = new Date();
    data.approved_by = user.id;
  }
  if (status === "rejected") {
    data.rejected_at = new Date();
    data.rejected_by = user.id;
    data.rejection_reason = String(formData.get("rejection_reason") ?? "") || null;
  }
  const notes = String(formData.get("admission_notes") ?? "");
  if (notes) data.admission_notes = notes;

  await prisma.admissions.update({ where: { id }, data });
  revalidatePath(`/dashboard/admissions/${id}`);
  redirect(`/dashboard/admissions/${id}?saved=1`);
}

export async function scheduleAdmissionTest(formData: FormData): Promise<void> {
  const user = await requireUser("/dashboard/admissions");
  if (!can(user.role, "manage_students")) redirect("/dashboard/admissions?error=1");

  const admissionId = Number(formData.get("admission_id") ?? 0);
  const scheduledAt = String(formData.get("scheduled_at") ?? "");
  if (!admissionId || !scheduledAt) redirect(`/dashboard/admissions/${admissionId}`);

  await prisma.admission_tests.create({
    data: {
      admission_id: admissionId,
      scheduled_at: new Date(scheduledAt),
      venue: String(formData.get("venue") ?? "") || null,
      notes: String(formData.get("notes") ?? "") || null,
      status: "scheduled",
      created_by: user.id,
    },
  });
  revalidatePath(`/dashboard/admissions/${admissionId}`);
  redirect(`/dashboard/admissions/${admissionId}?saved=1`);
}

export async function removeAdmissionTest(formData: FormData): Promise<void> {
  await requireUser("/dashboard/admissions");
  const id = Number(formData.get("id") ?? 0);
  const admissionId = Number(formData.get("admission_id") ?? 0);
  if (id) await prisma.admission_tests.delete({ where: { id } }).catch(() => {});
  revalidatePath(`/dashboard/admissions/${admissionId}`);
  redirect(`/dashboard/admissions/${admissionId}?saved=1`);
}

export async function verifyAdmissionPayment(formData: FormData): Promise<void> {
  const user = await requireUser("/dashboard/admissions");
  if (!can(user.role, "manage_payments")) redirect("/dashboard/admissions?error=1");

  const id = Number(formData.get("id") ?? 0);
  if (!id) redirect("/dashboard/admissions");
  await prisma.admissions.update({
    where: { id },
    data: { payment_status: "verified", updated_by: user.id },
  });
  revalidatePath(`/dashboard/admissions/${id}`);
  redirect(`/dashboard/admissions/${id}?saved=1`);
}
