import { t } from "@/lib/i18n";
import { getSiteSettings } from "@/lib/site-settings";
import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { submitContact } from "./actions";

export const dynamic = "force-dynamic";

type SettingsRow = { opening_hours: string | null };
type ContentRow = { title: string | null; content: string | null };

const inputClass =
  "mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

interface EmergencyContact {
  label?: string;
  phone?: string;
}

function parseJson<T>(raw: unknown, fallback: T): T {
  if (typeof raw !== "string") return fallback;
  try {
    return JSON.parse(raw) as T;
  } catch {
    return fallback;
  }
}

export default async function ContactPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const params = await searchParams;
  const sent = Array.isArray(params.sent) ? params.sent[0] : params.sent;
  const error = Array.isArray(params.error) ? params.error[0] : params.error;

  const settings = await getSiteSettings();
  const [settingsRow, content] = await Promise.all([
    safe(() => prisma.website_settings.findFirst(), null as SettingsRow | null),
    safe(
      () => prisma.website_contents.findFirst({ where: { page: "contact", is_active: true } }),
      null as ContentRow | null,
    ),
  ]);

  const openingHours = parseJson<Record<string, { open?: string; close?: string }>>(settingsRow?.opening_hours, {});
  const contentPayload = parseJson<Record<string, unknown>>(content?.content, {});
  const emergency = Array.isArray(contentPayload.emergency_contacts)
    ? (contentPayload.emergency_contacts as unknown[])
        .map((row) => (row && typeof row === "object" ? (row as EmergencyContact) : ({} as EmergencyContact)))
        .filter((row) => row.label || row.phone)
    : [];

  const title = content && content.title ? String(content.title) : t("site.contact_page.title_fallback");
  const subtitle = content && content.content ? String(content.content) : "";

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{title}</h1>
          {subtitle ? <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{subtitle}</p> : null}
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        {sent ? (
          <div role="status" className="mb-8 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100">
              <svg className="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <div className="text-sm">
              <p className="font-semibold text-emerald-900">Thank You!</p>
              <p className="mt-0.5 leading-relaxed text-emerald-800">Your message has been received. We will get back to you shortly.</p>
            </div>
          </div>
        ) : null}

        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <div className="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
              <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.037 11.037 0 006.105 6.105l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            <h3 className="mt-4 text-sm font-semibold text-slate-900">Phone</h3>
            <p className="mt-1 text-sm text-slate-600">{settings.phone}</p>
          </div>
          <div className="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
              <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 className="mt-4 text-sm font-semibold text-slate-900">Email</h3>
            <p className="mt-1 text-sm text-slate-600">{settings.email}</p>
          </div>
          <div className="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
              <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <h3 className="mt-4 text-sm font-semibold text-slate-900">Address</h3>
            <p className="mt-1 text-sm text-slate-600">{settings.address}</p>
          </div>
          <div className="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 text-center ring-1 ring-blue-100">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white">
              <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 className="mt-4 text-sm font-semibold text-slate-900">Hours</h3>
            <p className="mt-1 text-sm text-slate-600">Sun–Thu, 8 AM – 2 PM</p>
          </div>
        </div>

        <div className="mt-12 grid gap-12 lg:grid-cols-2">
          <div>
            <h2 className="text-2xl font-bold text-slate-900">{t("site.contact_page.form_heading")}</h2>
            <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
            {error ? (
              <p className="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                Please check the form and try again.
              </p>
            ) : null}
            <form action={submitContact} className="mt-8 space-y-5">
              <input type="hidden" name="type" value="contact" />
              <div>
                <label className="block text-sm font-medium text-slate-700">
                  {t("site.contact_page.name")} <span className="text-red-500">*</span>
                </label>
                <input type="text" name="name" required className={inputClass} />
              </div>
              <div className="grid gap-5 sm:grid-cols-2">
                <div>
                  <label className="block text-sm font-medium text-slate-700">
                    {t("site.contact_page.email")} <span className="text-red-500">*</span>
                  </label>
                  <input type="email" name="email" required className={inputClass} />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700">{t("site.contact_page.phone")}</label>
                  <input type="text" name="phone" className={inputClass} />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700">
                  {t("site.contact_page.subject")} <span className="text-red-500">*</span>
                </label>
                <input type="text" name="subject" required className={inputClass} />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700">
                  {t("site.contact_page.message")} <span className="text-red-500">*</span>
                </label>
                <textarea name="message" rows={5} required className={inputClass} />
              </div>
              <button
                type="submit"
                className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700 hover:shadow-xl"
              >
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {t("site.contact_page.send")}
              </button>
            </form>
          </div>

          <div>
            {Object.keys(openingHours).length > 0 ? (
              <div className="rounded-2xl border border-slate-100 bg-slate-50 p-6">
                <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">{t("site.contact_page.location_hours")}</h3>
                <ul className="mt-4 space-y-2 text-sm text-slate-600">
                  {Object.entries(openingHours).map(([day, hours]) => (
                    <li key={day} className="flex justify-between">
                      <span className="font-medium capitalize text-slate-800">{day}</span>
                      {hours?.open && hours.close ? (
                        <span>{hours.open} – {hours.close}</span>
                      ) : (
                        <span className="text-slate-400">{t("site.contact_page.closed")}</span>
                      )}
                    </li>
                  ))}
                </ul>
              </div>
            ) : null}

            {emergency.length > 0 ? (
              <div className="mt-6 rounded-2xl border border-red-100 bg-red-50 p-6">
                <h3 className="flex items-center gap-2 text-sm font-semibold text-red-800">
                  <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  {t("site.contact_page.emergency_contacts")}
                </h3>
                <ul className="mt-3 space-y-2">
                  {emergency.map((row, index) => (
                    <li key={index} className="flex items-center justify-between text-sm">
                      <span className="text-red-700">{row.label ?? ""}</span>
                      <a href={`tel:${String(row.phone ?? "").replace(/\s+/g, "")}`} className="font-semibold text-red-800 hover:underline">
                        {row.phone ?? ""}
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            ) : null}

            <div className="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm">
              <div className="flex h-72 items-center justify-center bg-slate-200 p-6 text-center">
                <a
                  href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(settings.address)}`}
                  className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-blue-700"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {t("site.contact_page.open_in_maps")}
                </a>
              </div>
            </div>
          </div>
        </div>

        <section className="mt-16">
          <h2 className="text-center text-2xl font-bold text-slate-900">Frequently Asked Questions</h2>
          <div className="mx-auto mt-8 max-w-3xl space-y-3">
            <details className="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
              <summary className="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                What are the school hours?
                <svg className="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <p className="mt-3 text-sm leading-relaxed text-slate-600">School operates Sunday through Thursday, 8:00 AM to 2:00 PM. Office hours are 8:00 AM to 4:00 PM.</p>
            </details>
            <details className="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
              <summary className="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                How can I apply for admission?
                <svg className="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <p className="mt-3 text-sm leading-relaxed text-slate-600">You can apply online through our website or visit the school office during working hours for a paper application.</p>
            </details>
            <details className="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
              <summary className="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-900">
                What documents are required for admission?
                <svg className="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
              </summary>
              <p className="mt-3 text-sm leading-relaxed text-slate-600">Birth certificate, previous school transfer certificate, passport-size photographs, and guardian ID card.</p>
            </details>
          </div>
        </section>
      </div>
    </div>
  );
}