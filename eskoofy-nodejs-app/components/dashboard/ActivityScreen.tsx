import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { PageHeader } from "@/components/ui/PageHeader";

const PRESENT_COLS: Array<{ key: string; label: string }> = [
  { key: "created_at", label: "When" },
  { key: "log_name", label: "Log" },
  { key: "causer", label: "Causer" },
  { key: "subject", label: "Subject" },
  { key: "description", label: "Description" },
];

function classBaseName(subjectType: string | null): string {
  if (!subjectType) return "—";
  const parts = subjectType.split("\\");
  return parts[parts.length - 1] ?? subjectType;
}

function formatWhen(value: Date | null): string {
  if (!value) return "—";
  const d = new Date(value);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

/**
 * Activity log — mirrors `dashboard/activity/index.blade.php`: log-name filter,
 * from/to date range and an audit table.
 */
export async function ActivityScreen({
  filters,
}: {
  filters?: { log_name?: string; from?: string; to?: string };
}) {
  const q = filters ?? {};
  const logName = q.log_name || undefined;
  const from = q.from ? new Date(q.from) : undefined;
  const to = q.to ? new Date(q.to) : undefined;

  const [logNames, rows] = await Promise.all([
    prisma.activity_log.findMany({ distinct: ["log_name"], select: { log_name: true } }).catch(() => []),
    prisma.activity_log
      .findMany({
        where: {
          ...(logName ? { log_name: logName } : {}),
          ...(from ? { created_at: { gte: from } } : {}),
          ...(to ? { created_at: { lte: new Date(to.getTime() + 86399999) } } : {}),
        },
        orderBy: { created_at: "desc" },
        take: 200,
      })
      .catch(() => []),
  ]);

  const names = logNames.map((r) => r.log_name).filter((v): v is string => Boolean(v));

  const causerIds = new Set(
    rows.filter((r) => r.causer_type?.includes("User") && r.causer_id).map((r) => r.causer_id as number)
  );
  const causers = causerIds.size
    ? await prisma.users.findMany({ where: { id: { in: [...causerIds] } }, select: { id: true, name: true } }).catch(() => [])
    : [];
  const causerName = new Map(causers.map((c) => [c.id, c.name]));

  return (
    <div>
      <PageHeader
        title="Activity log"
        description="Audit trail of model changes and admin actions."
        breadcrumbs={
          <div className="mb-3 flex items-center gap-1.5 text-xs text-slate-500">
            <Link href="/dashboard" className="hover:text-brand-600">Dashboard</Link>
            <span>/</span>
            <span className="text-slate-700">Activity</span>
          </div>
        }
      />

      <form method="get" className="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <select name="log_name" className="admin-select" defaultValue={logName ?? ""}>
          <option value="">All logs</option>
          {names.map((ln) => (
            <option key={ln} value={ln}>
              {ln}
            </option>
          ))}
        </select>
        <input type="date" name="from" defaultValue={q.from} className="admin-input" />
        <input type="date" name="to" defaultValue={q.to} className="admin-input" />
        <button type="submit" className="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Filter
        </button>
      </form>

      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              {PRESENT_COLS.map((col) => (
                <th key={col.key} className="px-4 py-3">{col.label}</th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-16 text-center text-sm text-slate-500">
                  No activity recorded yet — activity will appear here once actions are performed.
                </td>
              </tr>
            ) : (
              rows.map((a) => (
                <tr key={a.id} className="admin-table-row">
                  <td className="px-4 py-3 font-mono text-xs text-slate-500">{formatWhen(a.created_at)}</td>
                  <td className="px-4 py-3 text-xs text-slate-500">{a.log_name || "—"}</td>
                  <td className="px-4 py-3 text-slate-700">{causerName.get(a.causer_id ?? 0) ?? "System"}</td>
                  <td className="px-4 py-3 text-xs text-slate-500">
                    {a.subject_type ? `${classBaseName(a.subject_type)} #${a.subject_id ?? "?"}` : "—"}
                  </td>
                  <td className="px-4 py-3 text-slate-700">{a.description || "—"}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
        {rows.length === 200 ? <p className="border-t border-slate-200 px-4 py-3 text-xs text-slate-500">Showing the latest 200 entries.</p> : null}
      </div>
    </div>
  );
}