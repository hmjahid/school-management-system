import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { PageHeader } from "@/components/ui/PageHeader";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { dueFeeRecipients, sendDueFeeReminder } from "@/app/(dashboard)/dashboard/sms-actions";

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

/**
 * Due-fee reminder — mirrors `dashboard/sms/due-reminder.blade.php`:
 * outstanding-balance recipients derived from unpaid fee payments, a message
 * composer and a send action.
 */
export async function DueFeeReminder({
  searchParams,
}: {
  searchParams?: Promise<Record<string, string | undefined>>;
}) {
  const sp = searchParams ? await searchParams : undefined;
  const user = await currentUser();
  if (!can(user?.role, "bulk_sms")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — Due Fee Reminder
      </p>
    );
  }

  const recipients = await dueFeeRecipients();
  const totalDue = recipients.reduce((s, r) => s + (r.due ?? 0), 0);
  const smsCount = recipients.reduce((s, r) => s + Math.ceil(Math.max(1, (r.phone.length + 4) / 160) * (r.due ? 1 : 1)), 0);

  return (
    <div>
      <PageHeader
        title="Due Fee Reminder"
        description="Notify students with outstanding fee balances via SMS."
        actions={
          <Link href="/dashboard/sms" className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
            ← Bulk SMS
          </Link>
        }
      />

      {sp?.error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
          {sp.error === "norecipients" ? "No students have outstanding fee balances." : "Failed to send the reminder campaign."}
        </div>
      )}

      <div className="mb-4 grid gap-4 sm:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="text-xs uppercase tracking-wide text-slate-500">Recipients with outstanding dues</div>
          <div className="mt-1 text-3xl font-semibold text-slate-900">{recipients.length}</div>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="text-xs uppercase tracking-wide text-slate-500">Total outstanding</div>
          <div className="mt-1 text-3xl font-semibold text-slate-900">{totalDue.toLocaleString("en", { minimumFractionDigits: 2 })}</div>
        </div>
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="text-xs uppercase tracking-wide text-slate-500">SMS count</div>
          <div className="mt-1 text-3xl font-semibold text-slate-900">{smsCount}</div>
        </div>
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="mb-4 text-base font-semibold text-slate-900">Message template</h2>
        <form action={sendDueFeeReminder}>
          <textarea
            name="message"
            rows={4}
            className="admin-input w-full"
            defaultValue="Dear parent, your child has an outstanding fee balance of ৳{{due}}. Please clear it at your earliest convenience. Thank you."
          />
          <div className="mt-4 flex items-center justify-between">
            <p className="text-xs text-slate-500">The reminder is sent to every recipient with an outstanding balance.</p>
            <button type="submit" className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
              Send reminders
            </button>
          </div>
        </form>
      </div>

      {recipients.length > 0 && (
        <div className="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
          <table className="min-w-full divide-y divide-slate-200 text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
              <tr>
                <th className="px-4 py-3">Student</th>
                <th className="px-4 py-3">Phone</th>
                <th className="px-4 py-3 text-right">Due</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {recipients.slice(0, 50).map((r, idx) => (
                <tr key={idx} className="admin-table-row">
                  <td className="px-4 py-3 font-medium text-slate-900">{r.name ?? `Student #${r.student_id}`}</td>
                  <td className="px-4 py-3 text-slate-700">{r.phone}</td>
                  <td className="px-4 py-3 text-right font-mono text-slate-700">{(r.due ?? 0).toLocaleString("en", { minimumFractionDigits: 2 })}</td>
                </tr>
              ))}
            </tbody>
          </table>
          {recipients.length > 50 ? <p className="px-4 py-3 text-xs text-slate-500">Showing 50 of {recipients.length} recipients.</p> : null}
        </div>
      )}
    </div>
  );
}
