import { t } from "@/lib/i18n";
import {
  cloudSettings,
  recentRuns,
  listRemote,
  isConfigured,
  providerLabel,
} from "@/lib/cloud-backup";
import {
  deleteCloudBackupAction,
  restoreCloudBackupAction,
  runCloudBackupAction,
  saveCloudBackupSettingsAction,
  testCloudBackupAction,
} from "@/app/(dashboard)/dashboard/cloud-backup/actions";

/**
 * The cloud-backup panel, rendered inside the Backups page (Local / Cloud
 * tabs). Mirrors the app's `partials/dashboard/cloud-backup-panel.blade.php`.
 */

const PROVIDERS = [
  { key: "local", labelKey: "cloud_backup.local" },
  { key: "google_drive", labelKey: "cloud_backup.google_drive" },
  { key: "dropbox", labelKey: "cloud_backup.dropbox" },
  { key: "4shared", labelKey: "cloud_backup.4shared" },
  { key: "s3", labelKey: "cloud_backup.s3" },
];

const FIELD_DEFS: Record<string, Array<{ field: string; label: string }>> = {
  google_drive: [
    { field: "client_id", label: "Client ID" },
    { field: "client_secret", label: "Client secret" },
    { field: "refresh_token", label: "Refresh token" },
    { field: "service_account_email", label: "Service account email" },
    { field: "service_account_private_key", label: "Service account private key" },
    { field: "scope", label: "OAuth scope (optional)" },
  ],
  dropbox: [
    { field: "app_key", label: "App key" },
    { field: "app_secret", label: "App secret" },
    { field: "refresh_token", label: "Refresh token" },
    { field: "access_token", label: "Access token (short-lived alternative)" },
  ],
  "4shared": [
    { field: "api_key", label: "API key" },
    { field: "username", label: "Username" },
    { field: "password", label: "Password" },
  ],
  s3: [
    { field: "endpoint", label: "Endpoint" },
    { field: "bucket", label: "Bucket" },
    { field: "key", label: "Access key" },
    { field: "secret", label: "Secret key" },
    { field: "region", label: "Region" },
  ],
};

function formatSize(size: number): string {
  return `${(size / 1024).toFixed(1)} KB`;
}

function formatModified(ms: number): string {
  const d = new Date(ms);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")} ${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
}

function inputClass(): string {
  return "w-full rounded-lg border-slate-300 text-sm";
}

export default async function CloudBackupPanel({
  searchParams,
}: {
  searchParams?: Promise<Record<string, string | undefined>>;
}) {
  const sp = searchParams ? await searchParams : {};
  const settings = await cloudSettings();
  const remote = await listRemote(settings).catch(() => []);
  const runs = await recentRuns(15);
  const configured = isConfigured(settings);

  const statusByKey: Record<string, string> = {
    saved: t("cloud_backup.settings_saved"),
    ran: t("cloud_backup.run_success"),
    tested: t("cloud_backup.test_success"),
    restored: t("cloud_backup.restored"),
    deleted: t("cloud_backup.remote_deleted"),
  };

  return (
    <div>
      {sp.status && statusByKey[sp.status] && (
        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{statusByKey[sp.status]}</div>
      )}
      {sp.error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{t("cloud_backup.failed")}</div>
      )}

      {!configured && (
        <div className="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
          {t("cloud_backup.not_configured")}
        </div>
      )}

      <div className="mb-6 flex justify-end">
        <form action={runCloudBackupAction}>
          <button className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" disabled={!configured}>
            {t("cloud_backup.back_up_now")}
          </button>
        </form>
      </div>

      <form action={saveCloudBackupSettingsAction} className="mb-6 rounded-xl border border-slate-200 bg-white p-6">
        <h2 className="mb-4 text-lg font-semibold text-slate-900">{t("cloud_backup.provider")}</h2>

        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">{t("cloud_backup.provider")}</label>
            <select name="provider" defaultValue={settings.provider} className={inputClass()}>
              {PROVIDERS.map((p) => (
                <option key={p.key} value={p.key}>
                  {t(p.labelKey)}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">{t("cloud_backup.remote_folder")}</label>
            <input name="folder" type="text" defaultValue={settings.folder} className={inputClass()} autoComplete="off" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">{t("cloud_backup.interval_minutes")}</label>
            <input name="interval_minutes" type="number" min={5} max={10080} defaultValue={settings.intervalMinutes} className={inputClass()} />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">{t("cloud_backup.keep_newest_files")}</label>
            <input name="keep" type="number" min={1} max={365} defaultValue={settings.keep} className={inputClass()} />
          </div>
        </div>

        {Object.entries(FIELD_DEFS).map(([provider, fields]) => (
          <div
            key={provider}
            className="mt-4 border-t border-slate-100 pt-4"
            hidden={settings.provider !== provider}
          >
            <h3 className="mb-2 text-sm font-semibold text-slate-700">{providerLabel(provider)}</h3>
            <div className="grid gap-4 sm:grid-cols-2">
              {fields.map(({ field, label }) => {
                const hasValue = Boolean(settings.credentials[field]);
                return (
                  <div key={field}>
                    <label className="mb-1 block text-sm font-medium text-slate-700">
                      {label}
                      {hasValue && <span className="ml-1 text-xs font-normal text-emerald-700">{t("cloud_backup.saved")}</span>}
                    </label>
                    <input
                      name={`credentials[${field}]`}
                      type="password"
                      placeholder={hasValue ? t("cloud_backup.unchanged") : ""}
                      className={inputClass()}
                      autoComplete="new-password"
                    />
                  </div>
                );
              })}
            </div>
          </div>
        ))}

        <label className="mt-4 flex items-center gap-2 text-sm text-slate-700">
          <input name="is_enabled" type="checkbox" defaultChecked={settings.isEnabled} />
          {t("cloud_backup.enabled")}
        </label>
        <label className="flex items-center gap-2 text-sm text-slate-700">
          <input name="auto_enabled" type="checkbox" defaultChecked={settings.autoEnabled} />
          {t("cloud_backup.auto_enabled")}
        </label>

        <div className="mt-4 flex flex-wrap gap-2">
          <button type="submit" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600">
            {t("cloud_backup.save_settings")}
          </button>
          <button
            formAction={testCloudBackupAction}
            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            {t("cloud_backup.test_settings")}
          </button>
        </div>
      </form>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <div className="border-b border-slate-200 px-6 py-4 text-lg font-semibold text-slate-900">{t("cloud_backup.remote_files")}</div>
          {remote.length === 0 ? (
            <p className="px-6 py-10 text-sm text-slate-500">{t("cloud_backup.no_remote")}</p>
          ) : (
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
                {remote.map((file) => (
                  <tr key={file.id}>
                    <td className="px-4 py-3 font-medium text-slate-900">{file.name}</td>
                    <td className="px-4 py-3 text-slate-500">{formatSize(file.size)}</td>
                    <td className="px-4 py-3 text-slate-500">{formatModified(file.modified)}</td>
                    <td className="px-4 py-3">
                      <div className="flex justify-end gap-2">
                        <form action={restoreCloudBackupAction}>
                          <input type="hidden" name="file" value={file.id} />
                          <button className="text-sm font-semibold text-amber-700 hover:underline">{t("cloud_backup.restore")}</button>
                        </form>
                        <form action={deleteCloudBackupAction}>
                          <input type="hidden" name="file" value={file.id} />
                          <button className="text-sm font-semibold text-red-700 hover:underline">{t("cloud_backup.delete")}</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
          <div className="border-b border-slate-200 px-6 py-4 text-lg font-semibold text-slate-900">{t("cloud_backup.history")}</div>
          {runs.length === 0 ? (
            <p className="px-6 py-10 text-sm text-slate-500">{t("cloud_backup.no_runs")}</p>
          ) : (
            <ul className="divide-y divide-slate-100">
              {runs.map((run) => (
                <li key={run.id} className="px-6 py-4">
                  <div className="flex items-center justify-between gap-2">
                    <span
                      className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ${
                        run.status === "success"
                          ? "bg-emerald-100 text-emerald-800"
                          : run.status === "failed"
                            ? "bg-red-100 text-red-800"
                            : "bg-slate-100 text-slate-700"
                      }`}
                    >
                      {run.status}
                    </span>
                    <span className="text-xs text-slate-500">
                      {run.created_at ? formatModified(run.created_at.getTime()) : ""}
                    </span>
                  </div>
                  <p className="mt-1 text-sm text-slate-700">{run.message}</p>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}