import Link from "next/link";
import { t, tOr } from "@/lib/i18n";
import { CardGrid, Hero, InfoCard, Section } from "@/components/site/Sections";
import { loginAction } from "@/app/(site)/login/actions";

export const dynamic = "force-dynamic";

const inputClass =
  "w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default function PortalPage() {
  return (
    <>
      <Hero eyebrow={t("site.nav.portal")} title={t("site.nav.portal")} subtitle="Parents and students sign in here to follow attendance, results and fees." />

      <Section>
        <div className="mx-auto grid max-w-5xl gap-8 lg:grid-cols-2">
          <div className="space-y-4">
            <CardGrid columns={2}>
              <InfoCard title={t("dashboard.attendance")} body="See daily attendance and absences." />
              <InfoCard title={t("dashboard.exams")} body="Published results as soon as they are out." />
              <InfoCard title={t("dashboard.fees")} body="Dues, receipts and online payments." />
              <InfoCard title={t("site.nav.routine")} body="The weekly class routine." />
            </CardGrid>
            <p className="text-sm text-slate-500">
              New here?{" "}
              <Link href="/portal/register" className="font-semibold text-blue-600 hover:underline">
                {t("site.nav.register")}
              </Link>
            </p>
          </div>

          <form action={loginAction} className="space-y-4 self-start rounded-2xl border border-slate-200 bg-white p-6">
            <h2 className="font-bold text-slate-900">{t("site.nav.login")}</h2>
            <div>
              <label htmlFor="email" className="mb-1 block text-sm font-semibold">
                {tOr("site.contact_page.email", "Email", {}, "en")}
              </label>
              <input id="email" name="email" type="email" required autoComplete="email" className={inputClass} />
            </div>
            <div>
              <label htmlFor="password" className="mb-1 block text-sm font-semibold">
                Password
              </label>
              <input id="password" name="password" type="password" required autoComplete="current-password" className={inputClass} />
            </div>
            <input type="hidden" name="redirect" value="/dashboard" />
            <button type="submit" className="w-full rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500">
              {t("site.nav.login")}
            </button>
          </form>
        </div>
      </Section>
    </>
  );
}
