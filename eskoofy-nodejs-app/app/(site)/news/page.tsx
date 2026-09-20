import Link from "next/link";
import { t } from "@/lib/i18n";
import { getLatestNews } from "@/lib/site-data";
import { formatDate } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

/**
 * News index — mirrors the real Laravel `site/news.blade.php`: hero gradient
 * banner, a featured magazine article, then a 3-col grid of the rest.
 */
export default async function NewsIndexPage() {
  const all = await getLatestNews(24);
  const [featured, ...rest] = all;

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.nav.news")}</h1>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        {featured ? (
          <section className="mb-12 reveal">
            <Link href={`/news/${featured.slug}`} className="group block overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl">
              <div className="grid md:grid-cols-2">
                <div className="h-64 overflow-hidden bg-slate-200 md:h-full">
                  {featured.image_url ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={String(featured.image_url)} alt="" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="eager" />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-100">
                      <svg className="h-16 w-16 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" /></svg>
                    </div>
                  )}
                </div>
                <div className="flex flex-col justify-center p-8 lg:p-12">
                  {featured.published_at ? (
                    <div className="mb-3 flex items-center gap-3 text-sm text-slate-500">
                      <time>{formatDate(featured.published_at)}</time>
                      <span className="h-1 w-1 rounded-full bg-slate-300" />
                      <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{featured.category ? String(featured.category) : t("site.home.news_badge")}</span>
                    </div>
                  ) : null}
                  <h2 className="text-2xl font-bold text-slate-900 transition-colors group-hover:text-blue-600 lg:text-3xl">{String(featured.title ?? "")}</h2>
                  <p className="mt-4 leading-relaxed text-slate-600">{String(featured.excerpt ?? featured.content ?? "").slice(0, 200)}</p>
                  <span className="mt-6 inline-flex items-center gap-1 font-medium text-blue-600 group-hover:text-blue-800">
                    {t("site.home.read_more")}
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                  </span>
                </div>
              </div>
            </Link>
          </section>
        ) : null}

        {rest.length > 0 ? (
          <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            {rest.map((item) => (
              <Link key={String(item.id)} href={`/news/${item.slug}`} className="group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl reveal">
                <div className="relative h-48 overflow-hidden bg-slate-200">
                  {item.image_url ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={String(item.image_url)} alt="" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-50">
                      <svg className="h-10 w-10 text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" /></svg>
                    </div>
                  )}
                  {item.category ? <span className="absolute left-3 top-3 rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">{String(item.category)}</span> : null}
                </div>
                <div className="p-5">
                  {item.published_at ? <time className="text-xs text-slate-500">{formatDate(item.published_at)}</time> : null}
                  <h3 className="mt-2 text-lg font-semibold text-slate-900 transition-colors group-hover:text-blue-600">{String(item.title ?? "")}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-600">{String(item.excerpt ?? item.content ?? "").slice(0, 120)}</p>
                  <span className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
                    {t("site.home.read_more")}
                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                  </span>
                </div>
              </Link>
            ))}
          </div>
        ) : (
          <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
            <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
            <p className="mt-4 text-sm text-slate-500">{t("site.news.empty", {}) === "site.news.empty" ? "No news published yet." : t("site.news.empty")}</p>
          </div>
        )}
      </div>
    </div>
  );
}
