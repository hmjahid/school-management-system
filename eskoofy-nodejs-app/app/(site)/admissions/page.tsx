import Link from "next/link";
import { t } from "@/lib/i18n";
import { getAdmissionsOpen, getPageContent, getSectionVisibility } from "@/lib/site-data";
import { CMSContentSections } from "@/components/site/CMSContentSections";
import { submitScholarship } from "./actions";

export const dynamic = "force-dynamic";

const inputClass =
  "mt-1 w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default async function AdmissionsPage({
  searchParams,
}: {
  searchParams: Promise<{ sent?: string; error?: string }>;
}) {
  const params = await searchParams;
  const sent = Boolean(params.sent);
  const error = Boolean(params.error);

  const [page, isOpen, visibility] = await Promise.all([getPageContent("admissions"), getAdmissionsOpen(), getSectionVisibility()]);
  const admissionsClosed = !isOpen;
  const showHero = visibility.adm_hero !== false;
  const showProcess = visibility.adm_process !== false;
  const showFee = visibility.adm_fee !== false;
  const showProspectus = visibility.adm_prospectus !== false;
  const showFaq = visibility.adm_faq !== false;
  const showCta = visibility.adm_cta !== false;
  const showScholarship = visibility.adm_scholarship !== false;

  const title = page?.title ? String(page.title) : t("site.nav.admissions");
  const year = new Date().getFullYear();

  return (
    <div className="bg-white">
      {showHero ? (
        admissionsClosed ? (
          <div className="relative overflow-hidden bg-gradient-to-r from-slate-700 via-slate-800 to-slate-900 py-20 text-white">
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
              <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl" />
              <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-white/5 blur-3xl" />
            </div>
            <div className="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
              <span className="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold backdrop-blur-sm">
                {t("site.admissions_closed.title")}
              </span>
              <h1 className="mt-6 text-4xl font-bold md:text-5xl lg:text-6xl">{title}</h1>
              <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-300">{t("site.admissions_closed.default_message")}</p>
              <div className="mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                <Link href="/admissions/status" className="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-slate-800 shadow-lg transition-all hover:bg-slate-50 hover:shadow-xl">
                  {t("site.admissions_closed.check_status")}
                </Link>
                <Link href="/contact" className="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                  {t("site.admissions_closed.contact_us")}
                </Link>
              </div>
            </div>
          </div>
        ) : (
          <div className="relative overflow-hidden bg-gradient-to-r from-orange-500 via-orange-600 to-red-600 py-20 text-white">
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
              <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl" />
              <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-white/5 blur-3xl" />
            </div>
            <div className="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
              <span className="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold backdrop-blur-sm">
                <span className="h-2 w-2 animate-pulse rounded-full bg-white" />
                {t("site.admissions_landing.badge", { year: String(year) })}
              </span>
              <h1 className="mt-6 text-4xl font-bold md:text-5xl lg:text-6xl">{title}</h1>
              {page?.meta_description ? (
                <p className="mx-auto mt-4 max-w-2xl text-lg text-orange-100">{String(page.meta_description)}</p>
              ) : null}
              <div className="mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                <Link href="/admissions/apply" className="inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-semibold text-orange-700 shadow-lg transition-all hover:bg-orange-50 hover:shadow-xl">
                  <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  {t("site.admissions_landing.cta_apply")}
                </Link>
                <Link href="/contact" className="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                  {t("site.home.cta_contact")}
                </Link>
              </div>
            </div>
          </div>
        )
      ) : null}

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <CMSContentSections page="admissions" />

        {sent ? (
          <div role="status" className="mb-8 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100">
              <svg className="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <div className="text-sm">
              <p className="font-semibold text-emerald-900">Scholarship request received.</p>
              <p className="mt-0.5 leading-relaxed text-emerald-800">Our office will contact you.</p>
            </div>
          </div>
        ) : null}

        {admissionsClosed ? (
          <section className="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <div className="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
              <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                <svg className="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z" />
                </svg>
              </div>
              <h1 className="text-2xl font-bold text-amber-900 sm:text-3xl">{t("site.admissions_closed.title")}</h1>
              <p className="mt-3 text-base text-amber-800">{t("site.admissions_closed.default_message")}</p>
              <div className="mt-6 flex flex-wrap justify-center gap-3">
                <Link href="/admissions/status" className="inline-flex rounded-md border border-amber-300 bg-white px-5 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                  {t("site.admissions_closed.check_status")}
                </Link>
                <Link href="/contact" className="inline-flex rounded-md bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700">
                  {t("site.admissions_closed.contact_us")}
                </Link>
              </div>
            </div>
          </section>
        ) : (
          <>
            {error ? (
              <p className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                Please check the form and try again.
              </p>
            ) : null}

            <div className="flex flex-wrap gap-3 reveal">
              <Link href="/admissions/apply" className="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600 hover:shadow-xl">
                {t("site.admissions_landing.cta_apply")}
              </Link>
              <Link href="/admissions/status" className="inline-flex items-center gap-2 rounded-xl border-2 border-blue-600 bg-white px-6 py-3 text-sm font-semibold text-blue-700 transition-all hover:bg-blue-50">
                {t("site.admissions_landing.cta_status")}
              </Link>
              <Link href="/payments" className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition-all hover:bg-slate-50">
                {t("site.admissions_landing.cta_payments")}
              </Link>
            </div>

            {showProcess ? (
              <section className="mt-16 reveal">
                <h2 className="text-2xl font-bold text-slate-900">Admission Process</h2>
                <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
                <div className="mt-10 grid gap-8 md:grid-cols-4">
                  {[
                    { n: "1", title: "Submit Application", body: "Fill in the online form with student and guardian details." },
                    { n: "2", title: "Document Review", body: "Upload required documents for verification by our team." },
                    { n: "3", title: "Entrance Test", body: "Candidates may be called for a written test and interview." },
                    { n: "4", title: "Confirmation", body: "Pay fees and confirm admission. Welcome to the family!" },
                  ].map((step) => (
                    <div key={step.n} className="relative text-center">
                      <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-100 to-orange-200 text-2xl font-bold text-orange-700 shadow-md">
                        {step.n}
                      </div>
                      <div className="mt-4">
                        <h3 className="text-base font-semibold text-slate-900">{step.title}</h3>
                        <p className="mt-1 text-sm text-slate-500">{step.body}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </section>
            ) : null}

            {showFee ? (
              <section className="mt-16 reveal">
                <h2 className="text-2xl font-bold text-slate-900">Fee Structure</h2>
                <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
                <div className="mt-8 overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                  <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50">
                      <tr>
                        <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Class</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tuition Fee</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Admission Fee</th>
                        <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Annual Charge</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white">
                      {[
                        ["Play to KG-2", "৳ 2,500", "৳ 5,000", "৳ 3,000"],
                        ["Primary (Class 1-5)", "৳ 3,000", "৳ 6,000", "৳ 3,500"],
                        ["Junior (Class 6-8)", "৳ 3,500", "৳ 7,000", "৳ 4,000"],
                        ["Secondary (Class 9-10)", "৳ 4,000", "৳ 8,000", "৳ 4,500"],
                      ].map((row, i) => (
                        <tr key={i} className="hover:bg-slate-50">
                          <td className="px-6 py-4 font-medium text-slate-900">{row[0]}</td>
                          <td className="px-6 py-4 text-slate-600">{row[1]}</td>
                          <td className="px-6 py-4 text-slate-600">{row[2]}</td>
                          <td className="px-6 py-4 text-slate-600">{row[3]}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </section>
            ) : null}

            {showProspectus ? (
              <section className="mt-16 rounded-2xl border border-blue-100 bg-gradient-to-br from-slate-50 to-blue-50 p-8 text-center reveal">
                <svg className="mx-auto h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h2 className="mt-4 text-xl font-bold text-slate-900">Download Prospectus</h2>
                <p className="mt-2 text-sm text-slate-600">Get detailed information about our programs, facilities, and admission policies.</p>
                <a href="#" className="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700">
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  Download PDF (2.5 MB)
                </a>
              </section>
            ) : null}

            {showFaq ? (
              <section className="mt-16 reveal">
                <h2 className="text-center text-2xl font-bold text-slate-900">Admission FAQs</h2>
                <div className="mx-auto mt-8 max-w-3xl space-y-3">
                  {[
                    {
                      q: "What is the minimum age for admission?",
                      a: "For Play/Nursery, the minimum age is 3 years as of January 1 of the admission year. For KG-1 it is 4 years, and for KG-2 it is 5 years.",
                    },
                    {
                      q: "Is there an entrance test?",
                      a: "Yes, students applying for Class 1 and above must take a written entrance test in English, Mathematics, and Bengali. An oral interview may also be conducted.",
                    },
                    {
                      q: "Can I apply for a scholarship?",
                      a: "Merit-based and need-based scholarships are available. Please contact the admissions office or fill out the scholarship inquiry form on this page.",
                    },
                  ].map((faq, i) => (
                    <details key={i} className="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                      <summary className="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                        {faq.q}
                        <svg className="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                        </svg>
                      </summary>
                      <p className="mt-3 text-sm text-slate-600">{faq.a}</p>
                    </details>
                  ))}
                </div>
              </section>
            ) : null}

            {showCta ? (
              <section className="mt-16 rounded-2xl bg-gradient-to-r from-orange-500 to-red-600 p-10 text-center text-white shadow-xl reveal">
                <h2 className="text-3xl font-bold">Ready to Join Us?</h2>
                <p className="mx-auto mt-3 max-w-2xl text-orange-100">
                  Take the first step towards quality education. Apply now for the academic year {year}-{year + 1}.
                </p>
                <Link href="/admissions/apply" className="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-orange-700 shadow-lg transition-all hover:bg-orange-50">
                  {t("site.admissions_landing.cta_apply")}
                  <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                  </svg>
                </Link>
              </section>
            ) : null}

            {showScholarship ? (
              <section className="mt-16 rounded-2xl border border-slate-200 bg-slate-50 p-8 shadow-md reveal">
                <h2 className="text-lg font-bold text-slate-900">{t("site.admissions_landing.scholarship_title")}</h2>
                <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
                <p className="mt-4 text-sm text-slate-600">{t("site.admissions_landing.scholarship_intro")}</p>
                <form action={submitScholarship} className="mt-6 grid gap-4 sm:grid-cols-2">
                  <div className="sm:col-span-2">
                    <label className="block text-sm font-medium text-slate-700">{t("site.admissions_landing.scholarship_full_name")}</label>
                    <input type="text" name="name" required className={inputClass} />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700">{t("site.admissions_landing.scholarship_email")}</label>
                    <input type="email" name="email" required className={inputClass} />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700">{t("site.admissions_landing.scholarship_phone")}</label>
                    <input type="text" name="phone" className={inputClass} />
                  </div>
                  <div className="sm:col-span-2">
                    <label className="block text-sm font-medium text-slate-700">{t("site.admissions_landing.scholarship_message")}</label>
                    <textarea name="message" rows={4} required className={inputClass} />
                  </div>
                  <div className="sm:col-span-2">
                    <button type="submit" className="rounded-xl bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/20 transition-all hover:bg-orange-600">
                      {t("site.admissions_landing.scholarship_submit")}
                    </button>
                  </div>
                </form>
              </section>
            ) : null}
          </>
        )}
      </div>
    </div>
  );
}