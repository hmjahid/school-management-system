import { t } from "@/lib/i18n";
import { searchSite } from "@/lib/site-data";
import { CardGrid, Empty, Hero, InfoCard, Section, formatDate } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function SearchPage({ searchParams }: { searchParams: Promise<{ q?: string }> }) {
  const params = await searchParams;
  const term = (params.q ?? "").trim();
  const results = await searchSite(term);
  const total = results.news.length + results.notices.length + results.pages.length;

  return (
    <>
      <Hero eyebrow={t("common.search")} title={t("common.search")} subtitle={term ? `Results for “${term}”` : undefined} />

      <Section>
        <div className="mx-auto max-w-5xl space-y-8">
          <form method="get" className="flex items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5">
            <div className="flex-1">
              <label htmlFor="q" className="mb-1 block text-sm font-semibold">
                {t("common.search")}
              </label>
              <input
                id="q"
                name="q"
                defaultValue={term}
                className="w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              />
            </div>
            <button className="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">Search</button>
          </form>

          {term && total === 0 ? <Empty>No matches found.</Empty> : null}

          {results.news.length > 0 ? (
            <div>
              <h2 className="mb-3 font-bold text-slate-800">{t("site.nav.news")}</h2>
              <CardGrid>
                {results.news.map((item) => (
                  <InfoCard
                    key={String(item.id)}
                    title={String(item.title ?? "")}
                    subtitle={formatDate(item.published_at)}
                    href={`/news/${item.slug}`}
                  />
                ))}
              </CardGrid>
            </div>
          ) : null}

          {results.notices.length > 0 ? (
            <div>
              <h2 className="mb-3 font-bold text-slate-800">{t("site.nav.notices")}</h2>
              <ul className="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                {results.notices.map((notice) => (
                  <li key={String(notice.id)} className="p-4 text-sm text-slate-700">
                    {String(notice.title ?? "")}
                  </li>
                ))}
              </ul>
            </div>
          ) : null}

          {results.pages.length > 0 ? (
            <div>
              <h2 className="mb-3 font-bold text-slate-800">Pages</h2>
              <ul className="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                {results.pages.map((page) => (
                  <li key={String(page.id)} className="p-4 text-sm text-slate-700">
                    <a className="text-blue-600 hover:underline" href={`/${String(page.page ?? "")}`}>
                      {String(page.title ?? page.page ?? "")}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ) : null}
        </div>
      </Section>
    </>
  );
}
