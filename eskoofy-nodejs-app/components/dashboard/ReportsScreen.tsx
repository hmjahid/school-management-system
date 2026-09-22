import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";

/**
 * Bespoke report screens — mirror the app's `dashboard/reports/*.blade.php`:
 * date filter + stat cards + breakdown tables.
 */

function fmt(value: unknown): string {
  const n = Number(value);
  return Number.isFinite(n) ? n.toLocaleString("en", { minimumFractionDigits: 2 }) : "—";
}

function monthKey(date: Date | string | null): string {
  if (!date) return "—";
  const d = new Date(String(date));
  return d.toLocaleDateString("en", { year: "numeric", month: "short" });
}

export async function FeesReport({ from, to }: { from?: string; to?: string }) {
  const f = from ? new Date(from) : new Date(Date.now() - 30 * 86400000);
  const tt = to ? new Date(to) : new Date();

  const payments = await prisma.fee_payments.findMany({
    where: {
      payment_date: { gte: f, lte: tt },
    },
    orderBy: { payment_date: "desc" },
  });

  const total = payments.reduce((sum, p) => sum + Number(p.paid_amount ?? p.amount ?? 0), 0);

  const byMonth = new Map<string, { total: number; count: number }>();
  for (const p of payments) {
    const k = monthKey(p.payment_date);
    const cur = byMonth.get(k) ?? { total: 0, count: 0 };
    cur.total += Number(p.paid_amount ?? p.amount ?? 0);
    cur.count += 1;
    byMonth.set(k, cur);
  }

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Link href="/dashboard/reports" className="text-sm font-medium text-brand-600 hover:text-brand-800">← {t("dashboard.reports")}</Link>
          <h1 className="mt-1 text-2xl font-bold text-slate-900">Financial report</h1>
          <p className="mt-1 text-sm text-slate-600">{f.toISOString().slice(0, 10)} → {tt.toISOString().slice(0, 10)}</p>
        </div>
        <form method="get" className="flex flex-wrap items-end gap-2">
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">From</label>
            <input type="date" name="from" defaultValue={f.toISOString().slice(0, 10)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-slate-500">To</label>
            <input type="date" name="to" defaultValue={tt.toISOString().slice(0, 10)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>
          <button type="submit" className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Apply</button>
          <Link href="/dashboard/reports/export/fees" download className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Export CSV</Link>
        </form>
      </div>

      <div className="mb-6 grid gap-3 sm:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-medium uppercase text-slate-500">Total collected</p>
          <p className="mt-1 text-3xl font-bold text-emerald-600">{fmt(total)}</p>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p className="text-xs font-medium uppercase text-slate-500">Payments</p>
          <p className="mt-1 text-3xl font-bold text-slate-900">{payments.length}</p>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="mb-4 text-lg font-semibold text-slate-900">By month</h2>
        <div className="space-y-2">
          {Array.from(byMonth.entries()).map(([k, v]) => (
            <div key={k} className="flex items-center justify-between border-b border-slate-100 py-2 text-sm">
              <span className="font-medium text-slate-700">{k}</span>
              <span className="text-slate-900">{fmt(v.total)} · <span className="text-slate-500">({v.count})</span></span>
            </div>
          ))}
          {byMonth.size === 0 ? <p className="text-sm text-slate-400">No payments in this period.</p> : null}
        </div>
      </div>
    </div>
  );
}

export async function StudentsReport() {
  const [total, classes, genders] = await Promise.all([
    prisma.students.count({ where: { deleted_at: null } }),
    prisma.students.groupBy({ by: ["class_id"], _count: { _all: true } }),
    prisma.students.groupBy({ by: ["gender"], _count: { _all: true } }),
  ]);

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Link href="/dashboard/reports" className="text-sm font-medium text-brand-600 hover:text-brand-800">← {t("dashboard.reports")}</Link>
          <h1 className="mt-1 text-2xl font-bold text-slate-900">Students report</h1>
          <p className="mt-1 text-sm text-slate-600">Live breakdown of all enrolled students.</p>
        </div>
        <Link href="/dashboard/reports/export/students" download className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Export CSV</Link>
      </div>

      <div className="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p className="text-xs font-medium uppercase text-slate-500">Total students</p>
        <p className="mt-1 text-3xl font-bold text-slate-900">{total}</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 text-lg font-semibold text-slate-900">By class</h2>
          <div className="space-y-2">
            {classes.map((row) => (
              <div key={String(row.class_id)} className="flex items-center justify-between border-b border-slate-100 py-2 text-sm">
                <span className="font-medium text-slate-700">Class #{row.class_id}</span>
                <span className="text-slate-900">{row._count._all}</span>
              </div>
            ))}
            {classes.length === 0 ? <p className="text-sm text-slate-400">No students.</p> : null}
          </div>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 text-lg font-semibold text-slate-900">By gender</h2>
          <div className="space-y-2">
            {genders.map((row) => (
              <div key={String(row.gender)} className="flex items-center justify-between border-b border-slate-100 py-2 text-sm">
                <span className="font-medium capitalize text-slate-700">{String(row.gender)}</span>
                <span className="text-slate-900">{row._count._all}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
