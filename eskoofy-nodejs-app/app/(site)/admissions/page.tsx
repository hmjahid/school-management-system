import Link from "next/link";
import { t } from "@/lib/i18n";
import { getPageContent } from "@/lib/site-data";
import { CardGrid, Hero, InfoCard, Section } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function AdmissionsPage() {
  const page = await getPageContent("admissions");

  const steps = [
    { title: "Apply online", body: "Fill in the application form with the student and guardian details." },
    { title: "Submit documents", body: "Upload or hand in the birth certificate, photo and previous records." },
    { title: "Review", body: "The admissions team reviews the application and may schedule a test." },
    { title: "Enrol", body: "Once approved, complete the admission fee and the student is enrolled." },
  ];

  return (
    <>
      <Hero
        eyebrow={t("site.nav.admissions")}
        title={page?.title ? String(page.title) : t("site.nav.admissions")}
        subtitle={page?.content ? String(page.content) : "Join our school — the whole process takes a few minutes online."}
        primary={{ label: t("site.home.cta_apply"), href: "/admissions/apply" }}
        secondary={{ label: "Check application status", href: "/admissions/status" }}
      />

      <Section title="How admission works">
        <CardGrid columns={4}>
          {steps.map((step, index) => (
            <InfoCard key={step.title} meta={`Step ${index + 1}`} title={step.title} body={step.body} />
          ))}
        </CardGrid>
      </Section>

      <Section tone="muted">
        <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6">
          <div>
            <h2 className="font-bold text-slate-900">Ready to apply?</h2>
            <p className="mt-1 text-sm text-slate-500">Start the online application — you can track it afterwards.</p>
          </div>
          <div className="flex gap-3">
            <Link href="/admissions/apply" className="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">
              {t("site.home.cta_apply")}
            </Link>
            <Link href="/admissions/status" className="rounded-xl border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:border-slate-400">
              Check status
            </Link>
          </div>
        </div>
      </Section>
    </>
  );
}
