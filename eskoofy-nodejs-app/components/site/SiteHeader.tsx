import Link from "next/link";
import { t } from "@/lib/i18n";

export function SiteHeader() {
  return (
    <header className="sticky top-0 z-40 bg-slate-900 text-white shadow-lg shadow-slate-900/20">
      <nav className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
        <Link href="/" className="flex items-center gap-2.5">
          <span className="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 font-bold">E</span>
          <span className="text-xl font-bold tracking-tight">{t("brand.name")}</span>
        </Link>

        <div className="hidden items-center gap-1 text-sm font-medium text-slate-200 md:flex">
          <Link href="/" className="rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white">
            {t("site.nav.home")}
          </Link>
          <Link href="/about" className="rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white">
            {t("site.nav.about")}
          </Link>
          <Link href="/academics" className="rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white">
            {t("site.nav.academics")}
          </Link>
          <Link href="/contact" className="rounded-lg px-3 py-2 hover:bg-slate-800 hover:text-white">
            {t("site.nav.contact")}
          </Link>
        </div>

        <div className="flex items-center gap-2 text-sm">
          <Link href="/login" className="rounded-lg px-3 py-2 font-medium text-slate-200 hover:text-white">
            {t("site.nav.login")}
          </Link>
          <Link href="/register" className="rounded-lg bg-blue-600 px-4 py-2 font-semibold hover:bg-blue-500">
            {t("site.nav.register")}
          </Link>
        </div>
      </nav>
    </header>
  );
}
