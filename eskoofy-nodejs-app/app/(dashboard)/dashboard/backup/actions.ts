"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { currentUser } from "@/lib/auth";
import { canAny, can } from "@/lib/permissions";
import { createBackupZip, deleteBackup, restoreBackupZip } from "@/lib/backup";
import { PrismaBackupDb } from "@/lib/portable-backup";
import { TABLE_NAMES } from "@/lib/schema";

/**
 * Backup actions — the Node equivalent of the app's `DashboardBackupController`
 * (`create` / `restore` / `destroy`) + `backup:run` / `backup:restore`.
 */

function deny(base: string): never {
  redirect(`/login?redirect=${encodeURIComponent(base)}`);
}

export async function createBackupAction(): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "backup_database")) deny("/dashboard/backup");

  try {
    await createBackupZip(new PrismaBackupDb(), TABLE_NAMES);
    redirect("/dashboard/backup?status=created");
  } catch {
    redirect("/dashboard/backup?error=create");
  }
}

export async function restoreBackupAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "restore_database")) deny("/dashboard/backup");

  const file = String(formData.get("file") ?? "");
  try {
    await restoreBackupZip(file, new PrismaBackupDb());
    revalidatePath("/dashboard/backup");
    redirect("/dashboard/backup?status=restored");
  } catch {
    redirect("/dashboard/backup?error=restore");
  }
}

export async function destroyBackupAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!canAny(user?.role, ["restore_database", "backup_database"])) deny("/dashboard/backup");

  const file = String(formData.get("file") ?? "");
  try {
    await deleteBackup(file);
    revalidatePath("/dashboard/backup");
    redirect("/dashboard/backup?status=deleted");
  } catch {
    redirect("/dashboard/backup?error=delete");
  }
}