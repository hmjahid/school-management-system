import Link from "next/link";
import { PageHeader } from "@/components/ui/PageHeader";
import { Badge, type BadgeVariant } from "@/components/ui/Badge";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const STATUS_VARIANT: Record<string, BadgeVariant> = {
  sent: "success",
  scheduled: "info",
  sending: "brand",
  failed: "danger",
  queued: "info",
  draft: "default",
};

function formatDate(value: Date | null): string {
  if (!value) return "—";
  const d = new Date(value);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/**
 * Bulk SMS campaign list — mirrors the app's `dashboard/sms/index.blade.php`:
 * campaign name + creator, audience type, recipient count, status badge and
 * sent timestamp.
 */
export default async function SmsIndexPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const sp = await searchParams;
  const user = await currentUser();
  if (!can(user?.role, "bulk_sms")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — {t("dashboard.bulk_sms")}
      </p>
    );
  }

  const rows = await prisma.sms_campaigns
    .findMany({
      orderBy: { id: "desc" },
      take: 20,
      include: { _count: { select: { sms_campaign_recipients: true } }, users: true },
    })
    .catch(() => []);

  return (
    <div>
      <PageHeader
        title={t("dashboard.bulk_sms")}
        description="Compose, target, and send SMS messages."
        actions={
          <>
            <Link
              href="/dashboard/sms/due-reminder"
              className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50"
            >
              Due reminder
            </Link>
            <Link
              href="/dashboard/sms/templates"
              className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50"
            >
              Templates
            </Link>
            <Link
              href="/dashboard/sms/compose"
              className="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
            >
              New campaign
            </Link>
          </>
        }
      />

      {sp.status && (
        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
          {sp.status === "queued" ? "Campaign queued." : sp.status === "scheduled" ? "Campaign scheduled." : sp.status === "sent" ? "Campaign sent." : sp.status === "draft" ? "Campaign saved as draft." : sp.status}
        </div>
      )}
      {sp.error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {sp.error === "norecipients" ? "No recipients match the selected audience." : "Something went wrong. Please try again."}
        </div>
      )}

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Campaign</th>
              <th className="px-4 py-3">Audience</th>
              <th className="px-4 py-3">Recipients</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Sent</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-16 text-center text-sm text-slate-500">
                  No campaigns yet — send your first SMS campaign to get started.
                </td>
              </tr>
            ) : (
              rows.map((campaign) => (
                <tr key={campaign.id} className="admin-table-row">
                  <td className="px-4 py-3">
                    <div className="font-medium text-slate-900">{String(campaign.name)}</div>
                    <div className="text-xs text-slate-500">{campaign.users ? String(campaign.users.name) : "—"}</div>
                  </td>
                  <td className="px-4 py-3 capitalize text-slate-700">{String(campaign.audience_type).replace(/_/g, " ")}</td>
                  <td className="px-4 py-3 text-slate-700">{campaign._count.sms_campaign_recipients}</td>
                  <td className="px-4 py-3">
                    <Badge variant={STATUS_VARIANT[String(campaign.status)] ?? "default"}>{String(campaign.status).replace(/^./, (c) => c.toUpperCase())}</Badge>
                  </td>
                  <td className="px-4 py-3 text-xs text-slate-500">{formatDate(campaign.sent_at)}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}