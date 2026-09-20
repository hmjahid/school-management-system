import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default async function AnalyticsPage() {
  const user = await currentUser();
  if (!can(user?.role, "view_reports")) {
    return <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">403 — {t("dashboard.analytics")}</p>;
  }

  const [students, teachers, classes, fees, feeTarget] = await Promise.all([
    prisma.students.count(),
    prisma.teachers.count().catch(() => 0),
    prisma.school_classes.count().catch(() => 0),
    prisma.fee_payments.aggregate({ _sum: { paid_amount: true } }),
    prisma.fees.aggregate({ _sum: { amount: true } }),
  ]);

  const collected = Number(feeTarget._sum.amount ?? 0) > 0 ? Number(fees._sum.paid_amount ?? 0) : Number(fees._sum.paid_amount ?? 0);
  const target = Number(feeTarget._sum.amount ?? 0);
  const pct = target > 0 ? Math.round((collected / target) * 100) : 0;

  const cards = [
    { label: t("dashboard.fee_target_monthly"), value: target.toFixed(2), tone: "text-slate-900" },
    { label: t("dashboard.collected_this_month"), value: collected.toFixed(2), tone: "text-slate-900" },
    { label: t("dashboard.insights"), value: `${pct}%`, tone: pct >= 75 ? "text-emerald-600" : "text-amber-600" },
    { label: "Students", value: String(students), tone: "text-slate-900" },
  ];

  return (
    <div>
      <PageHeader title={t("dashboard.analytics")} description={t("dashboard.insights")} />
      <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {cards.map((c) => (
          <div key={c.label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{c.label}</p>
            <p className={`mt-2 text-2xl font-bold ${c.tone}`}>{c.value}</p>
          </div>
        ))}
      </div>
      <div className="grid gap-4 lg:grid-cols-3">
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
          <h2 className="font-semibold text-slate-900">{t("dashboard.insights")}</h2>
          <p className="mt-1 text-sm text-slate-500">Students {students} · Teachers {teachers} · Classes {classes}</p>
        </div>
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 className="font-semibold text-slate-900">Fee collection</h2>
          <p className="mt-1 text-sm text-slate-500">Collected {collected.toFixed(2)} against target {target.toFixed(2)}</p>
          <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
            <div className="h-full rounded-full bg-emerald-500" style={{ width: `${Math.min(100, pct)}%` }} />
          </div>
        </div>
      </div>
    </div>
  );
}
