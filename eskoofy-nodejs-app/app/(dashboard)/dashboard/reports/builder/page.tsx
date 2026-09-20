import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default function ReportBuilderPage() {
  return (
    <div>
      <PageHeader title={t("dashboard.report_builder")} description={t("dashboard.build_report")} />
      <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="font-semibold text-slate-900">Build a custom report</h2>
        <p className="mt-1 text-sm text-slate-500">Pick the metrics you need and export a summary — the report engine is wired in the Laravel variant.</p>
        <button className="mt-4 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Export</button>
      </div>
    </div>
  );
}
