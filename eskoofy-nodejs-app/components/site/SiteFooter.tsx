import Link from "next/link";
import { t } from "@/lib/i18n";
import { eskoolfy } from "@/config/eskoolfy";

const QUICK_LINKS = [
  { href: "/about", key: "site.footer.link_about_school" },
  { href: "/academics", key: "site.footer.link_academics" },
  { href: "/admissions", key: "site.footer.link_admissions" },
  { href: "/news", key: "site.footer.link_news" },
  { href: "/gallery", key: "site.footer.link_gallery" },
  { href: "/contact", key: "site.footer.link_contact" },
];

const LEGAL_LINKS = [
  { href: "/terms", key: "site.footer.link_terms" },
  { href: "/privacy", key: "site.footer.link_privacy" },
];

export function SiteFooter() {
  return (
    <footer className="border-t border-slate-800 bg-slate-900 text-slate-400">
      <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="grid h-8 w-8 place-items-center rounded-lg bg-blue-600 text-sm font-bold text-white">E</span>
            <span className="font-bold text-white">{t("brand.name")}</span>
          </div>
          <p className="mt-3 text-sm text-slate-500">{t("site.footer.about_fallback")}</p>
        </div>

        <div>
          <div className="mb-3 text-xs font-semibold uppercase tracking-widest text-white">
            {t("site.footer.quick_links_title")}
          </div>
          <ul className="space-y-2 text-sm">
            {QUICK_LINKS.map((link) => (
              <li key={link.href}>
                <Link href={link.href} className="hover:text-white">
                  {t(link.key)}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <div className="mb-3 text-xs font-semibold uppercase tracking-widest text-white">
            {t("site.footer.legal_title")}
          </div>
          <ul className="space-y-2 text-sm">
            {LEGAL_LINKS.map((link) => (
              <li key={link.href}>
                <Link href={link.href} className="hover:text-white">
                  {t(link.key)}
                </Link>
              </li>
            ))}
            <li>
              <Link href="/routine" className="hover:text-white">
                {t("site.footer.link_routine")}
              </Link>
            </li>
            <li>
              <Link href="/certificates" className="hover:text-white">
                {t("site.footer.link_certificates")}
              </Link>
            </li>
          </ul>
        </div>

        <div>
          <div className="mb-3 text-xs font-semibold uppercase tracking-widest text-white">
            {t("site.footer.contact_title")}
          </div>
          <p className="text-sm text-slate-500">
            {eskoolfy.profile.label} · {eskoolfy.currency} · {eskoolfy.timezone}
          </p>
        </div>
      </div>

      <div className="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
        © {new Date().getFullYear()} {t("brand.name")}. {t("site.footer.copyright_suffix")}
      </div>
    </footer>
  );
}
