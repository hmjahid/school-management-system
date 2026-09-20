import Link from "next/link";
import { prisma } from "@/lib/prisma";

/**
 * SMS screens — mirror the app's dashboard/sms/{templates,due-reminder,
 * preview,compose}.blade.php. Templates reuse notification_templates.sms_content.
 */

export async function SmsTemplates() {
  const templates = await prisma.notification_templates.findMany({ orderBy: { name: "asc" } });

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">SMS templates</h1>
          <p className="mt-1 text-sm text-slate-600">Reusable message templates.</p>
        </div>
        <Link href="/dashboard/sms" className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← Bulk SMS</Link>
      </div>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Name</th>
              <th className="px-4 py-3">Key</th>
              <th className="px-4 py-3">Body</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {templates.map((row) => (
              <tr key={row.id} className="admin-table-row">
                <td className="px-4 py-3 font-medium text-slate-900">{String(row.name)}</td>
                <td className="px-4 py-3 font-mono text-xs text-slate-600">{String(row.key)}</td>
                <td className="px-4 py-3 text-slate-700">{String(row.sms_content ?? row.content ?? "—")}</td>
              </tr>
            ))}
            {templates.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No SMS templates yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function DueFeeReminder() {
  const students = await prisma.students.findMany({
    where: { deleted_at: null },
    take: 200,
  });
  const invoices = await prisma.invoices.findMany({ where: { status: "unpaid" } });

  const total = invoices.length;
  const totalDue = invoices.reduce((s, i) => s + Number(i.amount ?? 0), 0);

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Due Fee Reminder</h1>
        <p className="mt-1 text-sm text-slate-600">Notify students with outstanding fee balances via SMS.</p>
      </div>

      <div className="mb-4 grid gap-4 sm:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="text-xs uppercase tracking-wide text-slate-500">Recipients with outstanding dues</div>
          <div className="mt-1 text-3xl font-semibold text-slate-900">{total}</div>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="text-xs uppercase tracking-wide text-slate-500">Total outstanding</div>
          <div className="mt-1 text-3xl font-semibold text-slate-900">{totalDue.toLocaleString("en", { minimumFractionDigits: 2 })}</div>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="text-xs uppercase tracking-wide text-slate-500">SMS count (160 chars each)</div>
          <div className="mt-1 text-3xl font-semibold text-slate-900">{total}</div>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="mb-4 text-base font-semibold text-slate-900">Message template</h2>
        <textarea rows={4} className="admin-input w-full" defaultValue="Dear parent, your child has an outstanding fee balance. Please clear it at your earliest convenience. Thank you." />
        <div className="mt-4">
          <button className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Send reminders</button>
        </div>
      </div>
    </div>
  );
}
