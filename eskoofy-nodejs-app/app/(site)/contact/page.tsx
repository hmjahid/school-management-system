import Link from "next/link";
import { t, tOr } from "@/lib/i18n";
import { getPageContent } from "@/lib/site-data";
import { Section, PageHero } from "@/components/site/Sections";
import { submitContact } from "./actions";

export const dynamic = "force-dynamic";

const inputClass =
  "w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default async function ContactPage({
  searchParams,
}: {
  searchParams: Promise<{ sent?: string; error?: string }>;
}) {
  const params = await searchParams;
  const page = await getPageContent("contact");
  const cp = (key: string, fallback: string) => tOr(`site.contact_page.${key}`, fallback, {}, "en");

  return (
    <>
      <PageHero title={page?.title ? String(page.title) : cp("title", "Contact us")} subtitle={page?.content ? String(page.content) : cp("subtitle", "We would love to hear from you.")} />

      <Section>
        <div className="mx-auto grid max-w-5xl gap-8 lg:grid-cols-3">
          <div className="space-y-4 lg:col-span-1">
            <div className="rounded-2xl border border-slate-200 bg-white p-5">
              <h2 className="font-semibold text-slate-900">{cp("reach_title", "Reach us")}</h2>
              <p className="mt-3 text-sm text-slate-600">{cp("reach_body", "Send us a message and we will get back to you within one working day.")}</p>
            </div>
            <div className="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
              <h2 className="font-semibold text-slate-900">{cp("links_title", "Quick links")}</h2>
              <ul className="mt-3 space-y-2">
                <li>
                  <Link className="text-blue-600 hover:underline" href="/admissions/apply">
                    {t("site.nav.admissions")}
                  </Link>
                </li>
                <li>
                  <Link className="text-blue-600 hover:underline" href="/results">
                    {t("site.nav.results")}
                  </Link>
                </li>
                <li>
                  <Link className="text-blue-600 hover:underline" href="/notices">
                    {t("site.nav.notices")}
                  </Link>
                </li>
              </ul>
            </div>
          </div>

          <div className="lg:col-span-2">
            {params.sent ? (
              <p className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">
                {cp("success", "Thank you — your message has been received.")}
              </p>
            ) : null}
            {params.error ? (
              <p className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                {cp("error", "Please check the form and try again.")}
              </p>
            ) : null}

            <form action={submitContact} className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
              <input type="hidden" name="type" value="contact" />
              <div className="grid gap-4 sm:grid-cols-2">
                <Field id="name" label={cp("name", "Name")} required />
                <Field id="email" label={cp("email", "Email")} type="email" required />
                <Field id="phone" label={cp("phone", "Phone")} />
                <Field id="subject" label={cp("subject", "Subject")} />
              </div>
              <div>
                <label htmlFor="message" className="mb-1 block text-sm font-semibold">
                  {cp("message", "Message")}
                </label>
                <textarea id="message" name="message" rows={6} required className={inputClass} />
              </div>
              <button type="submit" className="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-500">
                {cp("send", "Send message")}
              </button>
            </form>
          </div>
        </div>
      </Section>
    </>
  );
}

function Field({
  id,
  label,
  type = "text",
  required = false,
}: {
  id: string;
  label: string;
  type?: string;
  required?: boolean;
}) {
  return (
    <div>
      <label htmlFor={id} className="mb-1 block text-sm font-semibold">
        {label}
      </label>
      <input id={id} name={id} type={type} required={required} className={inputClass} />
    </div>
  );
}
