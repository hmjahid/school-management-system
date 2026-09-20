import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { PageHeader } from "@/components/ui/PageHeader";
import { BULK_RESOURCES, isImportable, labelFor, type BulkResource } from "@/lib/bulk";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const RESOURCES = Object.keys(BULK_RESOURCES) as BulkResource[];

export default async function BulkIndexPage(): Promise<React.ReactElement> {
  const user = await currentUser();

  if (!can(user?.role, "export_student_data") && !can(user?.role, "import_student_data")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — {t("dashboard.bulk_import_export")}
      </p>
    );
  }

  return (
    <div>
      <PageHeader title={t("dashboard.bulk_import_export")} description={t("bulk.page_description")} />
      <div className="grid gap-4 sm:grid-cols-2">
        {RESOURCES.map((resource) => (
          <div key={resource} className="rounded-xl border border-slate-200 bg-white p-6">
            <h2 className="text-lg font-semibold text-slate-900">{t(`bulk.resource.${resource}`)}</h2>
            <p className="mt-1 text-sm text-slate-600">{t("bulk.resource_hint")}</p>
            <div className="mt-4 flex flex-wrap gap-2">
              <a
                href={`/dashboard/bulk/export/${encodeURIComponent(resource)}`}
                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              >
                {t("bulk.export_csv")}
              </a>
              {isImportable(resource) && (
                <a
                  href={`/dashboard/bulk/import/${encodeURIComponent(resource)}`}
                  className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600"
                >
                  {t("bulk.import_csv")}
                </a>
              )}
              <span className="self-center text-xs text-slate-400">{labelFor(resource)}</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}