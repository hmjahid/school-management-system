import Link from "next/link";
import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const LINKS = [
  { href: "/dashboard/analytics", labelKey: "dashboard.analytics" },
  { href: "/dashboard/reports/fees", labelKey: "dashboard.reports" },
  { href: "/dashboard/reports/attendance", labelKey: "dashboard.reports" },
  { href: "/dashboard/reports/students", labelKey: "dashboard.reports" },
  { href: "/dashboard/reports/income-statement", labelKey: "dashboard.reports" },
  { href: "/dashboard/reports/balance-sheet", labelKey: "dashboard.reports" },
  { href: "/dashboard/reports/cash-flow", labelKey: "dashboard.reports" },
  { href: "/dashboard/reports/builder", labelKey: "dashboard.report_builder" },
];

export default function ReportsPage() {
  return (
    <div>
      <PageHeader title={t("dashboard.reports")} description={t("dashboard.insights")} />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {LINKS.map((l) => (
          <Link key={l.href} href={l.href} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-blue-400">
            <span className="font-semibold text-slate-900">{t(l.labelKey)}</span>
          </Link>
        ))}
      </div>
    </div>
  );
}
