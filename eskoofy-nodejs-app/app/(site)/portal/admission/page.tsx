import Link from "next/link";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const fmt = (value: unknown, withTime = false) => {
  if (!value) return "—";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "—";
  return withTime
    ? d.toLocaleString("en-US", { month: "short", day: "numeric", year: "numeric", hour: "numeric", minute: "2-digit" })
    : d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
};

export default async function PortalAdmissionPage() {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/portal/admission");

  const admission = user.email
    ? await prisma.admissions.findFirst({
        where: { email: user.email, deleted_at: null },
        include: {
          admission_documents: true,
          admission_tests: { orderBy: { scheduled_at: "desc" } },
          academic_sessions: true,
          batches: true,
        },
        orderBy: { id: "desc" },
      })
    : null;

  const academicSession = admission?.academic_sessions as unknown as Record<string, unknown> | undefined;
  const batch = admission?.batches as unknown as Record<string, unknown> | undefined;
  const sessionName = academicSession?.name ? String(academicSession.name) : String(admission?.academic_session_id ?? "—");
  const batchName = batch?.name ? String(batch.name) : String(admission?.batch_id ?? "—");

  const tests = (admission?.admission_tests as unknown[] | undefined) ?? [];
  const documents = (admission?.admission_documents as unknown[] | undefined) ?? [];

  const now = Date.now();
  const upcomingTests = tests.filter((tt) => {
    const raw = (tt as Record<string, unknown>).scheduled_at;
    if (!raw) return false;
    const d = new Date(String(raw));
    return !Number.isNaN(d.getTime()) && d.getTime() >= now;
  });
  const pastTests = tests.filter((tt) => {
    const raw = (tt as Record<string, unknown>).scheduled_at;
    if (!raw) return true;
    const d = new Date(String(raw));
    return Number.isNaN(d.getTime()) || d.getTime() < now;
  });

  return (
    <div className="mx-auto max-w-5xl px-4 py-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">{t("site.portal_admission.page_title")}</h1>
          <p className="mt-1 text-sm text-slate-600">{t("site.portal_admission.intro")}</p>
        </div>
        <Link href="/portal" className="text-sm font-semibold text-slate-700 hover:text-slate-900">
          {t("site.portal_admission.back_portal")}
        </Link>
      </div>

      {!admission ? (
        <div className="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <p className="text-slate-700">{t("site.portal_admission.no_app", { email: user.email || "—" })}</p>
          <p className="mt-2 text-sm text-slate-600">{t("site.portal_admission.no_app_hint")}</p>
          <Link href="/admissions/status" className="mt-4 inline-block text-sm font-semibold text-blue-700 hover:underline">
            {t("site.portal_admission.track_status")}
          </Link>
        </div>
      ) : (
        <div className="mt-8 grid gap-6 lg:grid-cols-3">
          <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <div className="text-sm text-slate-500">{t("site.portal_admission.application_number")}</div>
                <div className="font-mono text-lg font-semibold text-slate-900">{String(admission.application_number ?? "")}</div>
              </div>
              <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold capitalize text-blue-700">
                {String(admission.status ?? "")}
              </span>
            </div>

            <dl className="mt-6 grid gap-3 text-sm sm:grid-cols-2">
              <dt className="text-slate-500">{t("site.portal_admission.name")}</dt>
              <dd className="text-slate-900">
                {String(admission.first_name ?? "")} {String(admission.last_name ?? "")}
              </dd>
              <dt className="text-slate-500">{t("site.portal_admission.email")}</dt>
              <dd className="text-slate-900">{String(admission.email ?? "")}</dd>
              <dt className="text-slate-500">{t("site.portal_admission.phone")}</dt>
              <dd className="text-slate-900">{String(admission.phone ?? "")}</dd>
              <dt className="text-slate-500">{t("site.portal_admission.session_batch")}</dt>
              <dd className="text-slate-900">
                {sessionName} / {batchName}
              </dd>
              <dt className="text-slate-500">{t("site.portal_admission.submitted")}</dt>
              <dd className="text-slate-900">{fmt(admission.submitted_at ?? admission.created_at, true)}</dd>
            </dl>

            {String(admission.status ?? "").toLowerCase() === "rejected" && admission.rejection_reason ? (
              <div className="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                <div className="font-semibold">{t("site.portal_admission.rejection_reason")}</div>
                <div className="mt-1">{String(admission.rejection_reason)}</div>
              </div>
            ) : null}
          </section>

          <aside className="space-y-6">
            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 className="text-base font-semibold text-slate-900">{t("site.portal_admission.test_schedule")}</h2>
              {tests.length === 0 ? (
                <p className="mt-3 text-sm text-slate-600">{t("site.portal_admission.no_test")}</p>
              ) : (
                <div className="mt-3 space-y-2 text-sm">
                  {upcomingTests.map((test) => {
                    const row = test as Record<string, unknown>;
                    return (
                      <div key={String(row.id ?? "")} className="rounded-lg border border-blue-100 bg-blue-50 p-3">
                        <div className="font-semibold text-slate-900">{fmt(row.scheduled_at, true)}</div>
                        <div className="mt-1 text-slate-700">
                          {t("site.portal_admission.venue_line", { venue: String(row.venue ?? "—") })}
                        </div>
                        <div className="mt-1 text-slate-700">
                          {t("site.portal_admission.status_line", { status: String(row.status ?? "—") })}
                        </div>
                      </div>
                    );
                  })}
                  {pastTests.length > 0 ? (
                    <div className="pt-2">
                      <div className="text-xs font-semibold uppercase tracking-wide text-slate-400">Past</div>
                      {pastTests.map((test) => {
                        const row = test as Record<string, unknown>;
                        return (
                          <div key={String(row.id ?? "")} className="mt-2 rounded-lg border border-slate-100 bg-slate-50 p-3">
                            <div className="font-semibold text-slate-900">{fmt(row.scheduled_at, true)}</div>
                            <div className="mt-1 text-slate-700">
                              {t("site.portal_admission.venue_line", { venue: String(row.venue ?? "—") })}
                            </div>
                            <div className="mt-1 text-slate-700">
                              {t("site.portal_admission.status_line", { status: String(row.status ?? "—") })}
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  ) : null}
                </div>
              )}
            </section>

            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 className="text-base font-semibold text-slate-900">{t("site.portal_admission.documents")}</h2>
              {documents.length === 0 ? (
                <p className="mt-3 text-sm text-slate-600">{t("site.portal_admission.no_documents")}</p>
              ) : (
                <ul className="mt-3 space-y-2 text-sm">
                  {documents.map((doc) => {
                    const row = doc as Record<string, unknown>;
                    return (
                      <li key={String(row.id ?? "")} className="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <div className="truncate pr-3">
                          <div className="truncate font-medium text-slate-900">{String(row.name ?? "")}</div>
                          <div className="text-xs text-slate-500">
                            {String(row.type ?? "")}
                            {row.file_size ? ` · ${String(row.file_size)}` : ""}
                          </div>
                        </div>
                        <a
                          className="shrink-0 text-blue-600 hover:underline"
                          href={`/storage/${String(row.file_path ?? "")}`}
                          target="_blank"
                          rel="noreferrer"
                        >
                          {t("site.portal_admission.view")}
                        </a>
                      </li>
                    );
                  })}
                </ul>
              )}
            </section>
          </aside>
        </div>
      )}
    </div>
  );
}