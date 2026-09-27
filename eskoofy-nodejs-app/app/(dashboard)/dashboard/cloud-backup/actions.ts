"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import {
  runCloudBackup,
  saveCloudSettings,
  restoreRemote,
  deleteRemote,
  testConnection,
  cloudSettings,
  listRemote,
} from "@/lib/cloud-backup";

function deny(base: string): never {
  redirect(`/login?redirect=${encodeURIComponent(base)}`);
}

function formString(formData: FormData, key: string): string {
  return String(formData.get(key) ?? "").trim();
}

export async function saveCloudBackupSettingsAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "manage_cloud_backup")) deny("/dashboard/backup?tab=cloud");

  const credentials: Record<string, string> = {};
  for (const [key, value] of formData.entries()) {
    if (key.startsWith("credentials[")) {
      const field = key.slice("credentials[".length, -1);
      credentials[field] = String(value);
    }
  }

  await saveCloudSettings({
    provider: formString(formData, "provider") || undefined,
    folder: formString(formData, "folder") || undefined,
    isEnabled: formData.get("is_enabled") === "1",
    autoEnabled: formData.get("auto_enabled") === "1",
    intervalMinutes: Number(formData.get("interval_minutes") ?? undefined) || undefined,
    keep: Number(formData.get("keep") ?? undefined) || undefined,
    credentials,
  });

  revalidatePath("/dashboard/backup");
  redirect("/dashboard/backup?tab=cloud&status=saved");
}

export async function runCloudBackupAction(): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "manage_cloud_backup")) deny("/dashboard/backup?tab=cloud");

  const result = await runCloudBackup();
  revalidatePath("/dashboard/backup");
  redirect(result.status === "success" ? "/dashboard/backup?tab=cloud&status=ran" : "/dashboard/backup?tab=cloud&error=run");
}

export async function testCloudBackupAction(): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "manage_cloud_backup")) deny("/dashboard/backup?tab=cloud");

  const result = await testConnection(await cloudSettings());
  revalidatePath("/dashboard/backup");
  redirect(result.ok ? "/dashboard/backup?tab=cloud&status=tested" : "/dashboard/backup?tab=cloud&error=test");
}

export async function restoreCloudBackupAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "manage_cloud_backup") || !can(user?.role, "restore_database")) {
    deny("/dashboard/backup?tab=cloud");
  }

  const file = formString(formData, "file");
  const result = await restoreRemote(await cloudSettings(), file);
  revalidatePath("/dashboard/backup");
  redirect(result.ok ? "/dashboard/backup?tab=cloud&status=restored" : "/dashboard/backup?tab=cloud&error=restore");
}

export async function deleteCloudBackupAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!can(user?.role, "manage_cloud_backup")) deny("/dashboard/backup?tab=cloud");

  const file = formString(formData, "file");
  await deleteRemote(await cloudSettings(), file);
  revalidatePath("/dashboard/backup");
  redirect("/dashboard/backup?tab=cloud&status=deleted");
}