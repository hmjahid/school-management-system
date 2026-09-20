import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default async function PortalAdmissionPage() {
  const user = await currentUser();
  const email = user?.email ?? "";

  const admission = email
    ? await prisma.admissions.findFirst({ where: { email }, include: { admission_documents: true, admission_tests: true } })
    : null;

  return (
    <div className="mx-auto max-w-5xl px-4 py-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">{t("site.portal_admission.page_title")}</h1>
          <p className="mt-1 text-sm text-slate-600">{t("site.portal_admission.intro")}</p>
        </div>
        <Link href="/portal" className="text-sm font-semibold text-slate-700 hover:text-slate-900">{t("site.portal_admission.back_portal")}</Link>
      </div>

      {!admission ? (
        <div className="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <p className="text-slate-700">{t("site.portal_admission.no_app", { email: email || "—" })}</p>
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
              <span className="rounded-full px-3 py-1 text-xs font-semibold capitalize text-blue-700">
                {String(admission.status ?? "")}
              </span>
            </div>

            <dl className="mt-6 grid gap-3 text-sm sm:grid-cols-2">
              <dt className="text-slate-500">{t("site.portal_admission.name")}</dt>
              <dd className="text-slate-900">{String(admission.first_name ?? "")} {String(admission.last_name ?? "")}</dd>
              <dt className="text-slate-500">{t("site.portal_admission.email")}</dt>
              <dd className="text-slate-900">{String(admission.email ?? "")}</dd>
              <dt className="text-slate-500">{t("site.portal_admission.phone")}</dt>
              <dd className="text-slate-900">{String(admission.phone ?? "")}</dd>
              <dt className="text-slate-500">{t("site.portal_admission.session_batch")}</dt>
              <dd className="text-slate-900">{String(admission.academic_session_id ?? "—")} / {String(admission.batch_id ?? "—")}</dd>
              <dt className="text-slate-500">{t("site.portal_admission.submitted")}</dt>
              <dd className="text-slate-900">{admission.submitted_at ? new Date(admission.submitted_at).toISOString().slice(0, 16).replace("T", " ") : "—"}</dd>
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
              {admission.admission_tests.length === 0 ? (
                <p className="mt-3 text-sm text-slate-600">{t("site.portal_admission.no_test")}</p>
              ) : (
                <div className="mt-3 space-y-2 text-sm">
                  {admission.admission_tests.map((test) => (
                    <div key={test.id} className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                      <div className="font-semibold text-slate-900">
                        {test.scheduled_at ? new Date(test.scheduled_at).toISOString().slice(0, 16).replace("T", " ") : "—"}
                      </div>
                      <div className="mt-1 text-slate-700">
                        {t("site.portal_admission.venue_line").replace(":venue", String(test.venue ?? "—"))}
                      </div>
                      <div className="mt-1 text-slate-700">
                        {t("site.portal_admission.status_line").replace(":status", String(test.status ?? "—"))}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </section>

            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
              <h2 className="text-base font-semibold text-slate-900">{t("site.portal_admission.documents")}</h2>
              {admission.admission_documents.length === 0 ? (
                <p className="mt-3 text-sm text-slate-600">{t("site.portal_admission.no_documents")}</p>
              ) : (
                <ul className="mt-3 space-y-2 text-sm">
                  {admission.admission_documents.map((doc) => (
                    <li key={doc.id} className="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                      <div className="truncate pr-3">
                        <div className="truncate font-medium text-slate-900">{String(doc.name ?? "")}</div>
                        <div className="text-xs text-slate-500">{String(doc.type ?? "")}</div>
                      </div>
                      <a className="shrink-0 text-blue-600 hover:underline" href={`/storage/${String(doc.file_path ?? "")}`} target="_blank" rel="noreferrer">
                        {t("site.portal_admission.view")}
                      </a>
                    </li>
                  ))}
                </ul>
              )}
            </section>
          </aside>
        </div>
      )}
    </div>
  );
}
