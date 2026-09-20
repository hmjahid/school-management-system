import Link from "next/link";
import { t } from "@/lib/i18n";
import { getSiteSettings, splitSchoolName } from "@/lib/site-settings";

export const dynamic = "force-dynamic";

/**
 * Site header — mirrors the Laravel app's `partials/site/nav.blade.php`:
 * blue-900 utility bar (phone/email/address, locale, socials), optional
 * admissions CTA bar, and a white sticky header with dropdown nav groups
 * (About / Academics / Contact / News) and an auth button.
 */
export async function SiteHeader() {
  const settings = await getSiteSettings();
  const { first, rest } = splitSchoolName(settings.schoolName);

  return (
    <>
      {/* Top utility bar: blue-900, contact + locale */}
      <div className="hidden bg-blue-900 text-sm text-white sm:block">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-2 lg:flex-row">
          <div className="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 lg:justify-start">
            <span className="inline-flex items-center gap-1.5">
              <svg className="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
              <a href={`tel:${settings.phone.replace(/\s+/g, "")}`} className="whitespace-nowrap font-medium hover:text-blue-100">{settings.phone}</a>
            </span>
            <span className="inline-flex items-center gap-1.5">
              <svg className="h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
              <a href={`mailto:${settings.email}`} className="max-w-[16rem] truncate font-medium hover:text-blue-100 lg:max-w-none">{settings.email}</a>
            </span>
            <span className="hidden items-start gap-1.5 xl:inline-flex">
              <svg className="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd"/></svg>
              <span className="text-blue-100">{settings.address.length > 80 ? `${settings.address.slice(0, 80)}…` : settings.address}</span>
            </span>
          </div>
          <div className="flex flex-wrap items-center justify-center gap-3 lg:justify-end">
            <div className="flex items-center gap-1">
              <span className="inline-flex min-w-[1.75rem] items-center justify-center rounded border border-white bg-white/15 px-2 py-0.5 text-[0.7rem] font-bold uppercase tracking-wide text-white">EN</span>
              <span className="inline-flex min-w-[1.75rem] items-center justify-center rounded border border-blue-400/60 px-2 py-0.5 text-[0.7rem] font-bold uppercase tracking-wide text-blue-200">বাংলা</span>
            </div>
            <span className="hidden h-4 w-px bg-blue-600 sm:block" aria-hidden="true" />
            <div className="flex items-center gap-2 text-blue-200">
              {settings.facebookUrl ? <a href={settings.facebookUrl} target="_blank" rel="noopener noreferrer" aria-label="Facebook" className="text-blue-200 hover:text-white"><svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0022 12z"/></svg></a> : null}
              {settings.instagramUrl ? <a href={settings.instagramUrl} target="_blank" rel="noopener noreferrer" aria-label="Instagram" className="text-blue-200 hover:text-white"><svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2-.1-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4 1.3-.1 1.7-.1 4.9-.1zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.2.8-.4.4-.6.7-.8 1.2-.2.4-.3 1-.4 2.1-.1 1.2-.1 1.6-.1 4.7s0 3.5.1 4.7c.1 1.1.2 1.7.4 2.1.2.5.4.9.8 1.2.4.4.7.6 1.2.8.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.2-.8.4-.4.6-.7.8-1.2.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.4-.9-.8-1.2-.4-.4-.7-.6-1.2-.8-.4-.2-1-.3-2.1-.4-1.2-.1-1.6-.1-4.7-.1zm0 3.1a4.9 4.9 0 110 9.8 4.9 4.9 0 010-9.8zm0 1.8a3.1 3.1 0 100 6.2 3.1 3.1 0 000-6.2zm5.1-3a1.1 1.1 0 110 2.2 1.1 1.1 0 010-2.2z"/></svg></a> : null}
              {settings.youtubeUrl ? <a href={settings.youtubeUrl} target="_blank" rel="noopener noreferrer" aria-label="YouTube" className="text-blue-200 hover:text-white"><svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 00.5 6.2 31.3 31.3 0 000 12a31.3 31.3 0 00.5 5.8 3 3 0 002.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 002.1-2.1A31.3 31.3 0 0024 12a31.3 31.3 0 00-.5-5.8zM9.6 15.6V8.4L15.8 12l-6.2 3.6z"/></svg></a> : null}
            </div>
          </div>
        </div>
      </div>

      {/* Main white sticky header with dropdown nav groups */}
      <header className="site-header sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-3 sm:py-4">
          <Link href="/" className="flex min-w-0 items-center gap-2 no-underline sm:gap-3">
            <span className="truncate text-lg font-bold leading-tight text-blue-700 sm:text-2xl md:text-3xl">
              {first}
              {rest ? <span className="text-orange-500"> {rest}</span> : null}
            </span>
          </Link>

          <nav className="hidden items-center gap-1 lg:flex" aria-label="Menu">
            <Link href="/" className="rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">Home</Link>

            <div className="group relative">
              <button type="button" className="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                About
                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div className="invisible absolute right-0 top-full z-50 mt-1 min-w-[14rem] rounded-lg border border-slate-100 bg-white p-2 opacity-0 shadow-lg ring-1 ring-black/5 transition-all group-hover:visible group-hover:opacity-100">
                <ul className="space-y-0.5">
                  <li><Link href="/about" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">About the school</Link></li>
                  <li><Link href="/faculty" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Faculty</Link></li>
                  <li><Link href="/committee" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Committee</Link></li>
                  <li><Link href="/students" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Students</Link></li>
                </ul>
              </div>
            </div>

            <div className="group relative">
              <button type="button" className="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                Academics
                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div className="invisible absolute right-0 top-full z-50 mt-1 min-w-[14rem] rounded-lg border border-slate-100 bg-white p-2 opacity-0 shadow-lg ring-1 ring-black/5 transition-all group-hover:visible group-hover:opacity-100">
                <ul className="space-y-0.5">
                  <li><Link href="/academics" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Academics</Link></li>
                  <li><Link href="/routine" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Routine</Link></li>
                  <li><Link href="/admissions" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Admissions</Link></li>
                  <li><Link href="/gallery" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Gallery</Link></li>
                  <li><Link href="/results" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Results</Link></li>
                </ul>
              </div>
            </div>

            <div className="group relative">
              <button type="button" className="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                Contact
                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div className="invisible absolute right-0 top-full z-50 mt-1 min-w-[14rem] rounded-lg border border-slate-100 bg-white p-2 opacity-0 shadow-lg ring-1 ring-black/5 transition-all group-hover:visible group-hover:opacity-100">
                <ul className="space-y-0.5">
                  <li><Link href="/contact" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Contact</Link></li>
                  <li><Link href="/payments" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Payments</Link></li>
                </ul>
              </div>
            </div>

            <div className="group relative">
              <button type="button" className="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                News
                <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div className="invisible absolute right-0 top-full z-50 mt-1 min-w-[14rem] rounded-lg border border-slate-100 bg-white p-2 opacity-0 shadow-lg ring-1 ring-black/5 transition-all group-hover:visible group-hover:opacity-100">
                <ul className="space-y-0.5">
                  <li><Link href="/news" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">News</Link></li>
                  <li><Link href="/notices" className="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700">Notices</Link></li>
                </ul>
              </div>
            </div>

            <Link href="/login" className="ml-1 inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
              {t("site.nav.login")}
            </Link>
          </nav>
        </div>
      </header>
    </>
  );
}
