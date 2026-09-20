import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default function CommunicationsPage() {
  return (
    <div>
      <PageHeader title={t("dashboard.communications")} description="Messages, broadcasts and announcements" />
      <div className="grid gap-4 sm:grid-cols-3">
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="font-semibold text-slate-900">Messages</h3>
          <p className="mt-1 text-sm text-slate-500">One-to-one and group messages with parents, staff and students.</p>
        </div>
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="font-semibold text-slate-900">Broadcasts</h3>
          <p className="mt-1 text-sm text-slate-500">Send announcements to selected groups in one click.</p>
        </div>
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="font-semibold text-slate-900">Templates</h3>
          <p className="mt-1 text-sm text-slate-500">Reusable message templates for common notices.</p>
        </div>
      </div>
    </div>
  );
}
