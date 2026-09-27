import { redirect } from "next/navigation";

/**
 * The cloud-backup admin lives inside the Backups page (Local / Cloud tabs).
 * This route is kept for old links and redirects to the Cloud tab.
 */
export const dynamic = "force-dynamic";

export default async function CloudBackupPage() {
  redirect("/dashboard/backup?tab=cloud");
}