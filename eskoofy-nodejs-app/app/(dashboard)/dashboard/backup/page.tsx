import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { listBackups } from "@/lib/backup";
import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";
import { createBackupAction, destroyBackupAction, restoreBackupAction } from "./actions";

export const dynamic = "force-dynamic";

function formatSize(bytes: number): string {
  return `${(bytes / 1024).toFixed(1)} KB`;
}

function formatModified(ms: number): string {
  const d = new Date(ms);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")} ${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
}

export default async function BackupPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const sp = await searchParams;
  const user = await currentUser();
  const files = await listBackups();

  if (!can(user?.role, "backup_database")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — {t("dashboard.backups")}
      </p>
    );
  }

  const messageByStatus: Record<string, string> = {
    created: t("backup.created"),
    restored: t("backup.restored"),
    deleted: t("backup.deleted"),
  };

  return (
    <div>
      <PageHeader
        title={t("dashboard.backups")}
        description={t("backup.page_description")}
        actions={
          <form action={createBackupAction}>
            <button className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600">
              {t("backup.create_now")}
            </button>
          </form>
        }
      />

      {sp.status && messageByStatus[sp.status] && (
        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{messageByStatus[sp.status]}</div>
      )}
      {sp.error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{t("backup.failed")}</div>
      )}

      {can(user?.role, "restore_database") && files.length > 0 && (
        <p className="mb-4 text-xs text-slate-500">{t("backup.restore_warning")}</p>
      )}

      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">{t("backup.file")}</th>
              <th className="px-4 py-3">{t("backup.size")}</th>
              <th className="px-4 py-3">{t("backup.modified")}</th>
              <th className="px-4 py-3 text-right">{t("common.actions")}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {files.length === 0 ? (
              <tr>
                <td colSpan={4} className="px-4 py-16 text-center text-sm text-slate-500">
                  {t("backup.none")}
                </td>
              </tr>
            ) : (
              files.map((file) => (
                <tr key={file.name}>
                  <td className="px-4 py-3 font-mono text-xs">{file.name}</td>
                  <td className="px-4 py-3">{formatSize(file.size)}</td>
                  <td className="px-4 py-3 text-slate-500">{formatModified(file.modified)}</td>
                  <td className="px-4 py-3 text-right">
                    <a
                      href={`/dashboard/backup/download/${encodeURIComponent(file.name)}`}
                      className="mr-3 text-xs font-semibold text-slate-700 hover:text-blue-600"
                    >
                      {t("backup.download")}
                    </a>
                    {can(user?.role, "restore_database") && (
                      <form action={restoreBackupAction} className="mr-3 inline">
                        <input type="hidden" name="file" value={file.name} />
                        <button className="text-xs font-semibold text-amber-700 hover:underline">{t("backup.restore")}</button>
                      </form>
                    )}
                    <form action={destroyBackupAction} className="inline">
                      <input type="hidden" name="file" value={file.name} />
                      <button className="text-xs font-semibold text-red-700 hover:underline">{t("backup.delete")}</button>
                    </form>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}