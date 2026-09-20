import Link from "next/link";
import { t } from "@/lib/i18n";
import { getSiteSettings, splitSchoolName, ministryLinks } from "@/lib/site-settings";

export const dynamic = "force-dynamic";

/**
 * Site footer — mirrors the Laravel app's `partials/site/footer.blade.php`:
 * slate-900, 4 columns (school info + social, quick links, ministry links,
 * contact + newsletter), copyright bar.
 */
export async function SiteFooter() {
  const settings = await getSiteSettings();
  const { first } = splitSchoolName(settings.schoolName);
  const year = new Date().getFullYear();

  return (
    <footer className="no-print border-t border-slate-200 bg-slate-900 text-slate-300">
      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
          {/* Column 1: school info + social */}
          <div>
            <div className="flex items-center gap-3">
              <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-lg font-bold text-white">{first.slice(0, 1)}</span>
              <div>
                <p className="text-base font-bold text-white">{settings.schoolName}</p>
                <p className="text-xs text-slate-400">{settings.tagline}</p>
              </div>
            </div>
            <p className="mt-4 text-sm leading-relaxed text-slate-400">{t("site.footer.about_fallback")}</p>
            <div className="mt-4 flex items-center gap-2">
              {settings.facebookUrl ? <a href={settings.facebookUrl} target="_blank" rel="noopener noreferrer" aria-label="Facebook" className="rounded-full bg-white/5 p-2 text-slate-400 transition hover:bg-white/10 hover:text-white"><svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0022 12z"/></svg></a> : null}
              {settings.instagramUrl ? <a href={settings.instagramUrl} target="_blank" rel="noopener noreferrer" aria-label="Instagram" className="rounded-full bg-white/5 p-2 text-slate-400 transition hover:bg-white/10 hover:text-white"><svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2-.1-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4 1.3-.1 1.7-.1 4.9-.1zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.2.8-.4.4-.6.7-.8 1.2-.2.4-.3 1-.4 2.1-.1 1.2-.1 1.6-.1 4.7s0 3.5.1 4.7c.1 1.1.2 1.7.4 2.1.2.5.4.9.8 1.2.4.4.7.6 1.2.8.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.2-.8.4-.4.6-.7.8-1.2.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.4-.9-.8-1.2-.4-.4-.7-.6-1.2-.8-.4-.2-1-.3-2.1-.4-1.2-.1-1.6-.1-4.7-.1zm0 3.1a4.9 4.9 0 110 9.8 4.9 4.9 0 010-9.8zm0 1.8a3.1 3.1 0 100 6.2 3.1 3.1 0 000-6.2zm5.1-3a1.1 1.1 0 110 2.2 1.1 1.1 0 010-2.2z"/></svg></a> : null}
              {settings.youtubeUrl ? <a href={settings.youtubeUrl} target="_blank" rel="noopener noreferrer" aria-label="YouTube" className="rounded-full bg-white/5 p-2 text-slate-400 transition hover:bg-white/10 hover:text-white"><svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 00-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 00.5 6.2 31.3 31.3 0 000 12a31.3 31.3 0 00.5 5.8 3 3 0 002.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 002.1-2.1A31.3 31.3 0 0024 12a31.3 31.3 0 00-.5-5.8zM9.6 15.6V8.4L15.8 12l-6.2 3.6z"/></svg></a> : null}
            </div>
          </div>

          {/* Column 2: Quick links */}
          <div>
            <h3 className="text-sm font-semibold uppercase tracking-wider text-white">{t("site.footer.quick_links_title")}</h3>
            <ul className="mt-4 space-y-2.5">
              {[
                ["/about", "site.footer.link_about_school"],
                ["/academics", "site.footer.link_academics"],
                ["/admissions", "site.footer.link_admissions"],
                ["/faculty", "site.footer.link_about_school"],
                ["/committee", "site.footer.link_about_school"],
                ["/news", "site.footer.link_news"],
                ["/gallery", "site.footer.link_gallery"],
              ].map(([href, key]) => (
                <li key={href}>
                  <Link href={href} className="text-sm text-slate-400 transition-colors hover:text-white">{t(key)}</Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Column 3: Ministry links (bd profile) */}
          <div>
            <h3 className="text-sm font-semibold uppercase tracking-wider text-white">{t("site.footer.important_title")}</h3>
            <ul className="mt-4 space-y-2.5">
              {ministryLinks().map((link) => (
                <li key={link.key}>
                  <a href={link.url} target="_blank" rel="noopener noreferrer" className="inline-flex items-start gap-2 text-sm text-slate-400 transition-colors hover:text-white">
                    <svg className="mt-0.5 h-4 w-4 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.828 10.172a4 4 0 010 5.656l-4 4a4 4 0 01-5.656-5.656l1.5-1.5"/><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.172 13.828a4 4 0 010-5.656l4-4a4 4 0 015.656 5.656l-1.5 1.5"/></svg>
                    {t(link.labelKey)}
                  </a>
                </li>
              ))}
              <li className="pt-2">
                <Link href="/transport" className="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition-colors hover:text-white">{t("site.nav.transport")}</Link>
              </li>
            </ul>
          </div>

          {/* Column 4: Contact */}
          <div>
            <h3 className="text-sm font-semibold uppercase tracking-wider text-white">{t("site.footer.contact_title")}</h3>
            <ul className="mt-4 space-y-3">
              <li className="flex items-start gap-2">
                <svg className="mt-0.5 h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span className="text-sm text-slate-400">{settings.address}</span>
              </li>
              <li className="flex items-center gap-2">
                <svg className="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <a href={`mailto:${settings.email}`} className="text-sm text-slate-400 transition-colors hover:text-white">{settings.email}</a>
              </li>
              <li className="flex items-center gap-2">
                <svg className="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <a href={`tel:${settings.phone.replace(/\s+/g, "")}`} className="text-sm text-slate-400 transition-colors hover:text-white">{settings.phone}</a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div className="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
        © {year} {settings.schoolName}. {t("site.footer.copyright_suffix")}
      </div>
    </footer>
  );
}
