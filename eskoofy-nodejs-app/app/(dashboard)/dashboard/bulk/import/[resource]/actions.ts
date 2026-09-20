"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { BulkImportError, runBulkImport, type BulkResource } from "@/lib/bulk";
import { IMPORT_UPSERTERS } from "@/lib/bulk-db";
import { BULK_RESOURCES } from "@/lib/bulk";

/**
 * Bulk import action — the Node equivalent of the app's
 * `DashboardBulkController::importStore`. Reads the uploaded UTF-8 CSV and
 * upserts students/teachers using the shared portable CSV contract.
 */

export async function importCsvAction(formData: FormData): Promise<void> {
  const user = await currentUser();
  const rawResource = String(formData.get("resource") ?? "");
  const resource = rawResource as BulkResource;

  if (!can(user?.role, "import_student_data")) {
    redirect(`/login?redirect=${encodeURIComponent(`/dashboard/bulk/import/${rawResource}`)}`);
  }
  if (!(resource in BULK_RESOURCES) || !(resource in IMPORT_UPSERTERS)) {
    redirect(`/dashboard/bulk?error=resource`);
  }
  const upsertKey = resource as "students" | "teachers";

  const file = formData.get("file");
  if (!(file instanceof File) || file.size === 0) {
    redirect(`/dashboard/bulk/import/${resource}?error=file`);
  }
  if (file.size > 5 * 1024 * 1024) {
    redirect(`/dashboard/bulk/import/${resource}?error=size`);
  }

  const dryRun = formData.get("dry_run") === "1" || formData.get("dry_run") === "on";

  try {
    const text = await file.text();
    const outcome = await runBulkImport(
      text,
      resource,
      IMPORT_UPSERTERS[upsertKey],
      { dryRun },
    );
    revalidatePath("/dashboard/bulk");
    const params = new URLSearchParams({
      created: String(outcome.created),
      updated: String(outcome.updated),
      skipped: String(outcome.skipped),
      parsed: String(outcome.parsed),
      dry: dryRun ? "1" : "0",
    });
    for (const error of outcome.errors.slice(0, 50)) params.append("errors", error);
    redirect(`/dashboard/bulk/import/${resource}?${params.toString()}`);
  } catch (error) {
    if (error instanceof BulkImportError) {
      redirect(`/dashboard/bulk/import/${resource}?error=missing&cols=${encodeURIComponent(error.missing.join(", "))}`);
    }
    redirect(`/dashboard/bulk/import/${resource}?error=parse`);
  }
}