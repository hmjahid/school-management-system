import Link from "next/link";
import { prisma } from "@/lib/prisma";

/**
 * Events calendar + ledger books — mirror the app's
 * dashboard/events/calendar.blade.php and dashboard/ledger/{cashbook,
 * bankbook, journal}.blade.php.
 */

export async function EventsCalendar({ month }: { month?: string }) {
  const anchor = month ? new Date(`${month}-01T00:00:00`) : new Date();
  const year = anchor.getFullYear();
  const mon = anchor.getMonth();
  const firstDay = new Date(year, mon, 1);
  const startWeekday = firstDay.getDay(); // 0=Sun
  const daysInMonth = new Date(year, mon + 1, 0).getDate();

  const from = new Date(year, mon, 1);
  const to = new Date(year, mon + 1, 0);
  const events = await prisma.events.findMany({
    where: { start_date: { gte: from, lte: to } },
    orderBy: { start_date: "asc" },
  });
  const byDay = new Map<number, typeof events>();
  for (const e of events) {
    const day = new Date(String(e.start_date)).getDate();
    const arr = byDay.get(day) ?? [];
    arr.push(e);
    byDay.set(day, arr);
  }

  const prev = new Date(year, mon - 1, 1);
  const next = new Date(year, mon + 1, 1);
  const cells = Array.from({ length: Math.ceil((startWeekday + daysInMonth) / 7) * 7 }, (_, i) => {
    const dayNum = i - startWeekday + 1;
    return { dayNum, inMonth: dayNum >= 1 && dayNum <= daysInMonth };
  });

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Link href="/dashboard/events" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Events</Link>
          <h1 className="mt-1 text-2xl font-bold text-slate-900">School Calendar — {anchor.toLocaleString("en", { month: "long", year: "numeric" })}</h1>
        </div>
        <div className="flex gap-2">
          <Link href={`/dashboard/events/calendar?month=${prev.toISOString().slice(0, 7)}`} className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Prev</Link>
          <Link href="/dashboard/events/calendar" className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Today</Link>
          <Link href={`/dashboard/events/calendar?month=${next.toISOString().slice(0, 7)}`} className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next →</Link>
        </div>
      </div>

      <div className="grid grid-cols-7 gap-1">
        {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((d) => (
          <div key={d} className="py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{d}</div>
        ))}
        {cells.map((cell, i) => (
          <div key={i} className={`min-h-20 rounded-lg border p-1.5 text-sm ${cell.inMonth ? "border-slate-200 bg-white" : "border-transparent bg-slate-50/50"}`}>
            {cell.inMonth ? (
              <>
                <div className="font-semibold text-slate-700">{cell.dayNum}</div>
                {(byDay.get(cell.dayNum) ?? []).map((e) => (
                  <div key={e.id} className="mt-1 truncate rounded bg-brand-50 px-1 py-0.5 text-[10px] font-medium text-brand-700">
                    {String(e.title ?? "")}
                  </div>
                ))}
              </>
            ) : null}
          </div>
        ))}
      </div>
    </div>
  );
}

export async function LedgerBook({ kind }: { kind: "cashbook" | "bankbook" | "journal" }) {
  const entries = await prisma.ledger_entries.findMany({
    orderBy: { date: "desc" },
    take: 100,
  });

  const title = kind === "cashbook" ? "Cash Book" : kind === "bankbook" ? "Bank Book" : "Journal";

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Link href="/dashboard/ledger" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Ledger</Link>
          <h1 className="mt-1 text-2xl font-bold text-slate-900">{title}</h1>
        </div>
      </div>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Date</th>
              <th className="px-4 py-3">Note</th>
              <th className="px-4 py-3 text-right">Debit</th>
              <th className="px-4 py-3 text-right">Credit</th>
              <th className="px-4 py-3">Reference</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {entries.map((e) => (
              <tr key={e.id} className="admin-table-row">
                <td className="px-4 py-3 text-slate-700">{e.date ? new Date(String(e.date)).toISOString().slice(0, 10) : "—"}</td>
                <td className="px-4 py-3 text-slate-900">{String(e.note ?? "—")}</td>
                <td className="px-4 py-3 text-right text-slate-900">{Number(e.debit ?? 0).toLocaleString()}</td>
                <td className="px-4 py-3 text-right text-slate-900">{Number(e.credit ?? 0).toLocaleString()}</td>
                <td className="px-4 py-3 text-xs text-slate-500">{String(e.reference_type ?? "")}{e.reference_id ? ` #${e.reference_id}` : ""}</td>
              </tr>
            ))}
            {entries.length === 0 ? <tr><td colSpan={5} className="px-4 py-8 text-center text-slate-400">No ledger entries yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}
