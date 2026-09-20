import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default function SmsPage() {
  return (
    <div>
      <PageHeader title={t("dashboard.bulk_sms")} description="Compose and send bulk SMS to parents and staff" />
      <form action="/dashboard/sms/compose" method="post" className="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
          <label className="mb-1 block text-sm font-semibold">Recipients</label>
          <select className="w-full rounded-lg border border-slate-300 px-4 py-2.5">
            <option>All parents</option>
            <option>All staff</option>
            <option>Custom list</option>
          </select>
        </div>
        <div>
          <label className="mb-1 block text-sm font-semibold">Message</label>
          <textarea rows={5} className="w-full rounded-lg border border-slate-300 px-4 py-2.5" placeholder="Type your SMS…" />
        </div>
        <button className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Send</button>
      </form>
    </div>
  );
}
