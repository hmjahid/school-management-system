import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";
import { Hero, Section, formatDate } from "@/components/site/Sections";
import { StatusBadge } from "@/components/ui/Badge";

export const dynamic = "force-dynamic";

const inputClass =
  "w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default async function AdmissionStatusPage({
  searchParams,
}: {
  searchParams: Promise<{ application_number?: string }>;
}) {
  const params = await searchParams;
  const applicationNumber = (params.application_number ?? "").trim();

  let application: Record<string, unknown> | null = null;
  if (applicationNumber) {
    try {
      application = (await prisma.admissions.findFirst({
        where: { application_number: applicationNumber },
      })) as unknown as Record<string, unknown> | null;
    } catch {
      application = null;
    }
  }

  return (
    <>
      <Hero eyebrow={t("site.nav.admissions")} title="Application status" subtitle="Track your application with the number you received." />

      <Section>
        <div className="mx-auto max-w-2xl space-y-6">
          <form method="get" className="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5">
            <div className="flex-1">
              <label htmlFor="application_number" className="mb-1 block text-sm font-semibold">
                Application number
              </label>
              <input
                id="application_number"
                name="application_number"
                defaultValue={applicationNumber}
                placeholder="APP-20260101-1234"
                className={inputClass}
              />
            </div>
            <button className="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">Check</button>
          </form>

          {applicationNumber ? (
            application ? (
              <div className="rounded-2xl border border-slate-200 bg-white p-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <h2 className="font-bold text-slate-900">
                    {String(application.first_name ?? "")} {String(application.last_name ?? "")}
                  </h2>
                  <StatusBadge value={application.status} />
                </div>
                <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                  <Row label="Application no." value={application.application_number} />
                  <Row label="Submitted" value={formatDate(application.submitted_at ?? application.created_at)} />
                  <Row label="Email" value={application.email} />
                  <Row label="Phone" value={application.phone} />
                </dl>
                {application.rejection_reason ? (
                  <p className="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{String(application.rejection_reason)}</p>
                ) : null}
              </div>
            ) : (
              <p className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
                No application found for that number.
              </p>
            )
          ) : null}
        </div>
      </Section>
    </>
  );
}

function Row({ label, value }: { label: string; value: unknown }) {
  return (
    <div>
      <dt className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</dt>
      <dd className="mt-0.5 text-slate-700">{value ? String(value) : "—"}</dd>
    </div>
  );
}
