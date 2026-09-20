import Link from "next/link";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { BULK_RESOURCES, headersFor, isImportable, labelFor, requiredColumnsFor, sampleRowFor, type BulkResource } from "@/lib/bulk";
import { t } from "@/lib/i18n";
import { importCsvAction } from "./actions";

export const dynamic = "force-dynamic";

export default async function BulkImportPage({
  params,
  searchParams,
}: {
  params: Promise<{ resource: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}): Promise<React.ReactElement> {
  const { resource } = await params;
  const sp = await searchParams;

  const resourceKey = resource as BulkResource;
  if (!(resourceKey in BULK_RESOURCES) || !isImportable(resourceKey)) {
    return <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700">404 — {resource}</p>;
  }

  const user = await currentUser();
  if (!can(user?.role, "import_student_data")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — {labelFor(resourceKey)}
      </p>
    );
  }

  const created = Number(sp.created ?? 0);
  const updated = Number(sp.updated ?? 0);
  const skipped = Number(sp.skipped ?? 0);
  const parsed = Number(sp.parsed ?? 0);
  const isDry = sp.dry === "1";
  const errors = typeof sp.errors === "string" ? [sp.errors] : Array.isArray(sp.errors) ? sp.errors : [];
  const headerList = headersFor(resourceKey);
  const sample = sampleRowFor(resourceKey);

  return (
    <div>
      <Link href="/dashboard/bulk" className="text-sm font-medium text-blue-600 hover:text-blue-800">
        ← {t("dashboard.bulk_import_export")}
      </Link>
      <h1 className="mt-1 text-2xl font-bold text-slate-900">{t("bulk.import_title", { res: labelFor(resourceKey) })}</h1>
      <p className="mt-1 text-sm text-slate-600">{t("bulk.import_hint")}</p>

      {sp.error === "file" && (
        <p className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{t("bulk.error.file")}</p>
      )}
      {sp.error === "size" && (
        <p className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{t("bulk.error.size")}</p>
      )}
      {sp.error === "parse" && (
        <p className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{t("bulk.error.parse")}</p>
      )}
      {sp.error === "missing" && (
        <p className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {t("bulk.error.missing", { cols: String(sp.cols ?? "") })}
        </p>
      )}
      {(sp.status || isDry || created + updated + skipped + parsed > 0) && (
        <div className="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
          {isDry
            ? t("bulk.dry_result", { n: parsed })
            : t("bulk.import_result", {
                created: String(created),
                updated: String(updated),
                skipped: String(skipped),
              })}
        </div>
      )}
      {errors.length > 0 && (
        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          <p className="font-medium">{t("bulk.row_errors")}</p>
          <ul className="mt-1 list-inside list-disc">
            {errors.map((error, index) => (
              <li key={index}>{error}</li>
            ))}
          </ul>
        </div>
      )}

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <form action={importCsvAction} encType="multipart/form-data" className="rounded-xl border border-slate-200 bg-white p-6">
          <input type="hidden" name="resource" value={resourceKey} />
          <div>
            <label className="block text-sm font-medium text-slate-700">{t("bulk.csv_file")} *</label>
            <input
              name="file"
              type="file"
              accept=".csv,text/csv"
              required
              className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
            <p className="mt-1 text-xs text-slate-500">{t("bulk.file_hint")}</p>
          </div>
          <div className="mt-4 flex items-center gap-2">
            <input id="dry_run" name="dry_run" type="checkbox" value="1" className="size-4 rounded border-slate-300" />
            <label htmlFor="dry_run" className="text-sm text-slate-700">
              {t("bulk.dry_run")}
            </label>
          </div>
          <div className="mt-6">
            <button type="submit" className="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-600">
              {t("bulk.upload_and_import")}
            </button>
          </div>
        </form>

        <div className="rounded-xl border border-slate-200 bg-white p-6">
          <h2 className="text-lg font-semibold text-slate-900">{t("bulk.required_columns")}</h2>
          <p className="mt-1 text-sm text-slate-600">{t("bulk.headers_hint")}</p>
          {requiredColumnsFor(resourceKey).length > 0 ? (
            <div className="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3">
              <p className="font-mono text-xs text-blue-700">{requiredColumnsFor(resourceKey).join(", ")}</p>
            </div>
          ) : null}
          <div className="mt-4 overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-xs">
              <thead className="bg-slate-50">
                <tr>
                  {headerList.map((header) => (
                    <th key={header} className="px-3 py-2 text-left font-mono font-semibold text-slate-700">
                      {header}
                    </th>
                  ))}
                </tr>
              </thead>
            </table>
          </div>

          <h2 className="mt-6 text-lg font-semibold text-slate-900">{t("bulk.sample_row")}</h2>
          <div className="mt-2 overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3 font-mono text-xs text-slate-700">
            {sample.map((row, index) => (
              <div key={index}>{row.join(", ")}</div>
            ))}
          </div>

          <a href={`/dashboard/bulk/export/${encodeURIComponent(resourceKey)}`} className="mt-6 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">
            {t("bulk.download_template")} →
          </a>
        </div>
      </div>
    </div>
  );
}