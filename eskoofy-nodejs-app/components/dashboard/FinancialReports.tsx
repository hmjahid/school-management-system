import Link from "next/link";
import { prisma } from "@/lib/prisma";

/**
 * Financial reports — mirror the app's dashboard/reports/{balance-sheet,
 * income-statement, cash-flow}.blade.php using real ledger data.
 */

function fmt(n: number): string {
  return n.toLocaleString("en", { minimumFractionDigits: 2 });
}

export async function BalanceSheet({ asOf }: { asOf?: string }) {
  const date = asOf ?? new Date().toISOString().slice(0, 10);
  const entries = await prisma.ledger_entries.findMany({ where: { date: { lte: new Date(date) } } });

  const debit = entries.reduce((s, e) => s + Number(e.debit ?? 0), 0);
  const credit = entries.reduce((s, e) => s + Number(e.credit ?? 0), 0);
  const totalAssets = debit;      // cash/bank on hand from debits
  const totalLiabilities = credit; // obligations from credits

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Link href="/dashboard/reports" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Reports</Link>
          <h1 className="mt-1 text-2xl font-bold text-slate-900">Balance Sheet</h1>
        </div>
        <form method="get" className="flex items-end gap-2">
          <input type="date" name="as_of" defaultValue={date} className="admin-input" />
          <button className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Apply</button>
        </form>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-700">Assets</h2>
          <table className="mt-2 w-full text-sm">
            <tbody>
              <tr className="border-b border-slate-100"><td className="py-1.5 text-slate-700">Cash on hand / bank</td><td className="py-1.5 text-right font-mono">{fmt(totalAssets)}</td></tr>
              <tr className="font-semibold"><td className="py-2 text-slate-900">Total assets</td><td className="py-2 text-right font-mono">{fmt(totalAssets)}</td></tr>
            </tbody>
          </table>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-700">Liabilities</h2>
          <table className="mt-2 w-full text-sm">
            <tbody>
              <tr className="border-b border-slate-100"><td className="py-1.5 text-slate-700">Payables / credits</td><td className="py-1.5 text-right font-mono">{fmt(totalLiabilities)}</td></tr>
              <tr className="font-semibold"><td className="py-2 text-slate-900">Total liabilities</td><td className="py-2 text-right font-mono">{fmt(totalLiabilities)}</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

export async function IncomeStatement({ from, to }: { from?: string; to?: string }) {
  const f = from ? new Date(from) : new Date(Date.now() - 30 * 86400000);
  const tt = to ? new Date(to) : new Date();
  const entries = await prisma.ledger_entries.findMany({ where: { date: { gte: f, lte: tt } } });

  const revenue = entries.reduce((s, e) => s + Number(e.credit ?? 0), 0);
  const expenses = entries.reduce((s, e) => s + Number(e.debit ?? 0), 0);
  const net = revenue - expenses;

  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/reports" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Reports</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">Income Statement</h1>
      </div>
      <div className="max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <table className="w-full text-sm">
          <tbody>
            <tr className="border-b border-slate-100"><td className="py-2 text-slate-700">Revenue</td><td className="py-2 text-right font-mono text-emerald-600">{fmt(revenue)}</td></tr>
            <tr className="border-b border-slate-100"><td className="py-2 text-slate-700">Expenses</td><td className="py-2 text-right font-mono text-red-600">{fmt(expenses)}</td></tr>
            <tr className="font-semibold"><td className="py-2 text-slate-900">Net income</td><td className="py-2 text-right font-mono text-slate-900">{fmt(net)}</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function CashFlow({ from, to }: { from?: string; to?: string }) {
  const f = from ? new Date(from) : new Date(Date.now() - 30 * 86400000);
  const tt = to ? new Date(to) : new Date();
  const entries = await prisma.ledger_entries.findMany({ where: { date: { gte: f, lte: tt } }, orderBy: { date: "asc" } });

  let running = 0;
  const rows = entries.map((e) => {
    const delta = Number(e.debit ?? 0) - Number(e.credit ?? 0);
    running += delta;
    return { date: e.date, note: e.note, delta, running };
  });

  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/reports" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Reports</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">Cash Flow</h1>
      </div>
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th className="px-4 py-3">Date</th><th className="px-4 py-3">Note</th><th className="px-4 py-3 text-right">Change</th><th className="px-4 py-3 text-right">Balance</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((r, i) => (
              <tr key={i} className="admin-table-row">
                <td className="px-4 py-3 text-slate-700">{r.date ? new Date(String(r.date)).toISOString().slice(0, 10) : "—"}</td>
                <td className="px-4 py-3 text-slate-900">{String(r.note ?? "—")}</td>
                <td className="px-4 py-3 text-right font-mono text-slate-700">{fmt(r.delta)}</td>
                <td className="px-4 py-3 text-right font-mono text-slate-900">{fmt(r.running)}</td>
              </tr>
            ))}
            {rows.length === 0 ? <tr><td colSpan={4} className="px-4 py-8 text-center text-slate-400">No cash movements.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}
