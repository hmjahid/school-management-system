import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { t } from "@/lib/i18n";
import { markAllNotificationsRead, markNotificationRead } from "@/app/(dashboard)/dashboard/screen-actions";
import { EmptyState } from "@/components/ui/EmptyState";
import { PageHeader } from "@/components/ui/PageHeader";

/**
 * Notification screens — mirror the app's
 * dashboard/notifications/templates.blade.php and preferences.blade.php:
 * template list + create form, and the per-type per-channel preference matrix.
 */

/**
 * Notification inbox — mirrors `dashboard/notifications/index.blade.php`
 * (unread rows highlighted, mark-all-read, empty state).
 */
export async function NotificationsInbox() {
  const user = await currentUser();
  const rows = user
    ? await prisma.notifications
        .findMany({ where: { notifiable_id: user.id }, orderBy: { created_at: "desc" }, take: 100 })
        .catch(() => [])
    : [];

  const unread = rows.filter((row) => !row.read_at).length;

  function parseData(raw: string): { title?: string; message?: string; url?: string } {
    try {
      return JSON.parse(raw) as { title?: string; message?: string; url?: string };
    } catch {
      return {};
    }
  }

  return (
    <div>
      <PageHeader
        title={t("dashboard.inbox")}
        description={`${rows.length} notification(s), ${unread} unread.`}
        actions={
          unread > 0 ? (
            <form action={markAllNotificationsRead}>
              <button type="submit" className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200">
                Mark all read
              </button>
            </form>
          ) : null
        }
      />

      <div className="admin-card">
        {rows.length === 0 ? (
          <div className="admin-card-body">
            <EmptyState title={t("common.empty")} message="You have no notifications yet." />
          </div>
        ) : (
          <ul className="divide-y divide-slate-100 dark:divide-slate-700">
            {rows.map((row) => {
              const data = parseData(row.data);
              const isUnread = !row.read_at;
              return (
                <li key={row.id}>
                  <form action={markNotificationRead}>
                    <input type="hidden" name="id" value={row.id} />
                    <button
                      type="submit"
                      className={`flex w-full items-start gap-3 px-5 py-4 text-left transition ${
                        isUnread ? "bg-brand-50/60 hover:bg-brand-50 dark:bg-brand-900/10" : "hover:bg-slate-50 dark:hover:bg-slate-700/40"
                      }`}
                    >
                      {isUnread ? <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500" /> : <span className="mt-1.5 h-2 w-2 shrink-0" />}
                      <span className="min-w-0 flex-1">
                        <span className="block text-sm font-medium text-slate-900 dark:text-slate-100">{data.title ?? row.type}</span>
                        {data.message ? <span className="mt-0.5 block text-sm text-slate-600 dark:text-slate-400">{data.message}</span> : null}
                        <span className="mt-1 block text-xs text-slate-400">
                          {row.created_at ? new Date(row.created_at).toLocaleString() : ""}
                        </span>
                      </span>
                      {data.url ? (
                        <Link href={data.url} className="shrink-0 text-xs font-medium text-brand-600 hover:underline">
                          {t("common.view")}
                        </Link>
                      ) : null}
                    </button>
                  </form>
                </li>
              );
            })}
          </ul>
        )}
      </div>
    </div>
  );
}

const CHANNELS = ["email", "sms", "in_app"];
const TYPES = [
  "fee_due",
  "fee_receipt",
  "exam_result",
  "assignment_upload",
  "attendance_summary",
  "leave_approved",
  "event_reminder",
  "notice_published",
];

export async function NotificationTemplates() {
  const templates = await prisma.notification_templates.findMany({ orderBy: { name: "asc" } });

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Notification templates</h1>
          <p className="mt-1 text-sm text-slate-600">Reusable email, SMS and in-app messages for the notifications your school sends.</p>
        </div>
        <a href="#new-template" className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
          New template
        </a>
      </div>

      <div id="new-template" className="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 className="mb-4 text-base font-semibold text-slate-900">Create a template</h2>
        <form method="post" action="/dashboard/notifications/templates" className="grid gap-4 lg:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Name</label>
            <input name="name" required maxLength={191} className="admin-input w-full" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Key</label>
            <input name="key" required maxLength={191} placeholder="e.g. fee_due" className="admin-input w-full" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Subject</label>
            <input name="subject" maxLength={255} className="admin-input w-full" />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">SMS content</label>
            <textarea name="sms_content" rows={2} className="admin-input w-full" />
          </div>
          <div className="lg:col-span-2">
            <label className="mb-1 block text-sm font-medium text-slate-700">Email content</label>
            <textarea name="content" rows={3} className="admin-input w-full" />
          </div>
          <div className="flex items-center gap-3 lg:col-span-2">
            <button className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">{t("common.save")}</button>
          </div>
        </form>
      </div>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Name</th>
              <th className="px-4 py-3">Key</th>
              <th className="px-4 py-3">Subject</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {templates.map((row) => (
              <tr key={row.id} className="admin-table-row">
                <td className="px-4 py-3 font-medium text-slate-900">{String(row.name)}</td>
                <td className="px-4 py-3 font-mono text-xs text-slate-600">{String(row.key)}</td>
                <td className="px-4 py-3 text-slate-700">{String(row.subject ?? "—")}</td>
                <td className="px-4 py-3">{row.is_active ? <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Active</span> : <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Inactive</span>}</td>
              </tr>
            ))}
            {templates.length === 0 ? <tr><td colSpan={4} className="px-4 py-8 text-center text-slate-400">No templates yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function NotificationPreferences() {
  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Notification preferences</h1>
        <p className="mt-1 text-sm text-slate-600">Choose how and when you want to be notified.</p>
      </div>

      <form method="post" action="/dashboard/notifications/preferences">
        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
          <table className="min-w-full divide-y divide-slate-200 text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
              <tr>
                <th className="px-4 py-3">Event</th>
                {CHANNELS.map((ch) => <th key={ch} className="px-4 py-3 text-center">{ch.replace("_", " ")}</th>)}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {TYPES.map((type) => (
                <tr key={type} className="admin-table-row">
                  <td className="px-4 py-3 font-medium capitalize text-slate-900">{type.replace(/_/g, " ")}</td>
                  {CHANNELS.map((ch) => (
                    <td key={ch} className="px-4 py-3 text-center">
                      <input type="hidden" name={`preferences[${type}][${ch}]`} value="0" />
                      <input type="checkbox" name={`preferences[${type}][${ch}]`} value="1" defaultChecked className="h-4 w-4 rounded border-slate-300 text-brand-600" />
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="mt-4">
          <button className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">{t("common.save")}</button>
        </div>
      </form>
    </div>
  );
}
