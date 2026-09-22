import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { PageHeader } from "@/components/ui/PageHeader";
import { Badge } from "@/components/ui/Badge";
import { discardSmsCampaign, sendSmsCampaign } from "@/app/(dashboard)/dashboard/sms-actions";

export const dynamic = "force-dynamic";

/**
 * Campaign preview — mirrors `dashboard/sms/preview.blade.php`: final message,
 * sample recipients and the confirm send action.
 */
export default async function SmsPreviewPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const sp = await searchParams;
  const user = await currentUser();
  if (!can(user?.role, "bulk_sms")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — Campaign preview
      </p>
    );
  }

  const id = Number(sp.campaign ?? 0);
  const campaign = id ? await prisma.sms_campaigns.findUnique({
    where: { id },
    include: {
      sms_campaign_recipients: { orderBy: { id: "asc" }, take: 50 },
      _count: { select: { sms_campaign_recipients: true } },
    },
  }).catch(() => null) : null;

  if (!campaign) {
    return (
      <div>
        <PageHeader title="Campaign preview" description="Review before sending." />
        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
          Campaign not found.{" "}
          <Link href="/dashboard/sms/compose" className="font-semibold underline">
            Start a new campaign
          </Link>
          .
        </div>
      </div>
    );
  }

  const scheduled = campaign.scheduled_at ? new Date(campaign.scheduled_at) : null;
  const isScheduled = scheduled && scheduled.getTime() > Date.now();

  return (
    <div>
      <PageHeader
        title="Campaign preview"
        description="Review the message and recipients, then confirm the send."
        actions={
          <Link href="/dashboard/sms" className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
            ← Bulk SMS
          </Link>
        }
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-3">
              <h2 className="text-base font-semibold text-slate-900">{String(campaign.name)}</h2>
              <Badge variant={String(campaign.status) === "draft" ? "default" : "info"}>{String(campaign.status)}</Badge>
            </div>

            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-sm leading-relaxed text-slate-800 whitespace-pre-wrap">{String(campaign.message)}</p>
            </div>

            <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
              <div>
                <span className="block text-xs uppercase tracking-wide text-slate-400">Audience</span>
                <span className="capitalize">{String(campaign.audience_type).replace(/_/g, " ")}</span>
              </div>
              <div>
                <span className="block text-xs uppercase tracking-wide text-slate-400">Recipients</span>
                <span>{campaign._count.sms_campaign_recipients}</span>
              </div>
              <div>
                <span className="block text-xs uppercase tracking-wide text-slate-400">{isScheduled ? "Scheduled for" : "Send"}</span>
                <span>{isScheduled ? scheduled?.toLocaleString() : "Immediately on confirm"}</span>
              </div>
            </div>

            <div className="mt-6 flex flex-wrap items-center gap-3">
              <form action={sendSmsCampaign}>
                <input type="hidden" name="campaign_id" value={campaign.id} />
                <button type="submit" className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                  {isScheduled ? "Schedule campaign" : "Send campaign"}
                </button>
              </form>
              <form action={discardSmsCampaign}>
                <input type="hidden" name="campaign_id" value={campaign.id} />
                <button type="submit" className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                  Discard draft
                </button>
              </form>
              <Link href="/dashboard/sms/compose" className="text-sm font-semibold text-brand-600 hover:underline">
                ← Edit
              </Link>
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-sm font-semibold text-slate-900">
            Recipients <span className="text-slate-400">({campaign._count.sms_campaign_recipients} total)</span>
          </h3>
          <div className="mt-3 max-h-96 overflow-y-auto">
            <table className="min-w-full text-sm">
              <tbody className="divide-y divide-slate-100">
                {campaign.sms_campaign_recipients.map((r) => (
                  <tr key={r.id} className="admin-table-row">
                    <td className="py-2 pr-3 font-mono text-xs text-slate-700">{String(r.phone)}</td>
                    <td className="py-2 text-right text-xs capitalize text-slate-500">{String(r.user_type)}</td>
                  </tr>
                ))}
                {campaign.sms_campaign_recipients.length === 0 && (
                  <tr>
                    <td className="py-6 text-center text-sm text-slate-400">No recipients.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          {campaign._count.sms_campaign_recipients > 50 ? (
            <p className="mt-2 text-xs text-slate-500">Showing first 50 of {campaign._count.sms_campaign_recipients}.</p>
          ) : null}
        </div>
      </div>
    </div>
  );
}