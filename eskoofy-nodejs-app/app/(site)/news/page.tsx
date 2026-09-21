import { t } from "@/lib/i18n";
import { getLatestNews, getNewsEvents, getUpcomingEvents, getPastEvents, getPageContent } from "@/lib/site-data";
import { NewsLoadMore } from "@/components/site/NewsLoadMore";
import { CMSContentSections } from "@/components/site/CMSContentSections";

export const dynamic = "force-dynamic";

const fmtShortDate = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
};

const fmtDay = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  return Number.isNaN(d.getTime()) ? "" : String(d.getDate()).padStart(2, "0");
};

const fmtMonth = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  return Number.isNaN(d.getTime()) ? "" : d.toLocaleDateString("en-US", { month: "short" });
};

const fmtTime = (value: unknown) => {
  if (!value) return "";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
};

export default async function NewsIndexPage() {
  const [all, newsEvents, upcomingEvents, pastEvents, content] = await Promise.all([
    getLatestNews(12),
    getNewsEvents(8),
    getUpcomingEvents(8),
    getPastEvents(8),
    getPageContent("news"),
  ]);
  const [featured, ...rest] = all;
  const restCount = rest.length;

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{String(content?.title ?? t("site.nav.news"))}</h1>
          {content?.meta_description ? (
            <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{String(content.meta_description)}</p>
          ) : null}
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <CMSContentSections page="news" />

        {featured ? (
          <section className="mb-12 reveal">
            <a href={`/news/${String(featured.slug ?? "")}`} className="group block overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl">
              <div className="grid md:grid-cols-2">
                <div className="h-64 overflow-hidden bg-slate-200 md:h-full">
                  {featured.image_url ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={String(featured.image_url)} alt="" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="eager" />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-100">
                      <svg className="h-16 w-16 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
                      </svg>
                    </div>
                  )}
                </div>
                <div className="flex flex-col justify-center p-8 lg:p-12">
                  {featured.published_at ? (
                    <div className="mb-3 flex items-center gap-3 text-sm text-slate-500">
                      <time>{fmtShortDate(featured.published_at)}</time>
                      <span className="h-1 w-1 rounded-full bg-slate-300" />
                      <span className="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">{featured.category ? String(featured.category) : "Featured"}</span>
                    </div>
                  ) : null}
                  <h2 className="text-2xl font-bold text-slate-900 transition-colors group-hover:text-blue-600 lg:text-3xl">{String(featured.title ?? "")}</h2>
                  <p className="mt-4 leading-relaxed text-slate-600">
                    {String(featured.content ?? "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim().slice(0, 200)}
                  </p>
                  <span className="mt-6 inline-flex items-center gap-1 font-medium text-blue-600 group-hover:text-blue-800">
                    {t("site.home.read_more")}
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                  </span>
                </div>
              </div>
            </a>
          </section>
        ) : null}

        {restCount > 0 ? (
          <NewsLoadMore items={rest} />
        ) : !featured ? (
          <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
            <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            <p className="mt-4 text-sm text-slate-500">{t("site.news.empty_news")}</p>
          </div>
        ) : null}

        <div className="mt-16 grid gap-8 lg:grid-cols-3">
          <div className="lg:col-span-2">
            <h2 className="text-xl font-bold text-slate-900">{t("site.news.events_heading")}</h2>
            <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-orange-400 to-orange-600" />
            {upcomingEvents.length === 0 && newsEvents.length === 0 ? (
              <p className="mt-6 text-sm text-slate-500">{t("site.news.empty_events")}</p>
            ) : (
              <div className="mt-6 space-y-3">
                {upcomingEvents.map((ev) => (
                  <div key={String(ev.id ?? "")} className="flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:shadow-md">
                    <div className="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gradient-to-b from-orange-400 to-orange-600 text-white">
                      <span className="text-lg font-bold">{fmtDay(ev.start_date)}</span>
                      <span className="text-[10px] font-semibold uppercase">{fmtMonth(ev.start_date)}</span>
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="font-semibold text-slate-900">{String(ev.title ?? "")}</p>
                      <p className="text-sm text-slate-500">
                        {ev.start_date ? fmtTime(ev.start_date) : ""}
                        {ev.location ? ` · ${String(ev.location)}` : ""}
                      </p>
                    </div>
                  </div>
                ))}
                {newsEvents.map((ev) => (
                  <div key={String(ev.id ?? "")} className="flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:shadow-md">
                    <div className="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gradient-to-b from-blue-500 to-indigo-600 text-white">
                      <span className="text-lg font-bold">{fmtDay(ev.event_date)}</span>
                      <span className="text-[10px] font-semibold uppercase">{fmtMonth(ev.event_date)}</span>
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="font-semibold text-slate-900">{String(ev.title ?? "")}</p>
                      {ev.event_location ? <p className="text-sm text-slate-500">{String(ev.event_location)}</p> : null}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="space-y-8">
            <div className="rounded-2xl border border-slate-100 bg-slate-50 p-6">
              <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Categories</h3>
              <ul className="mt-4 space-y-2">
                <li>
                  <a href="#" className="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                    <span>All News</span>
                    <span className="rounded-full bg-slate-200 px-2 py-0.5 text-xs">{all.length}</span>
                  </a>
                </li>
                <li>
                  <a href="#" className="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                    <span>Academic</span>
                    <span className="rounded-full bg-slate-200 px-2 py-0.5 text-xs">4</span>
                  </a>
                </li>
                <li>
                  <a href="#" className="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                    <span>Events</span>
                    <span className="rounded-full bg-slate-200 px-2 py-0.5 text-xs">6</span>
                  </a>
                </li>
                <li>
                  <a href="#" className="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                    <span>Sports</span>
                    <span className="rounded-full bg-slate-200 px-2 py-0.5 text-xs">2</span>
                  </a>
                </li>
              </ul>
            </div>

            <div className="rounded-2xl border border-slate-100 bg-slate-50 p-6">
              <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Recent Posts</h3>
              <ul className="mt-4 space-y-3">
                {all.slice(0, 4).map((item) => (
                  <li key={String(item.id ?? "")}>
                    <a href={`/news/${String(item.slug ?? "")}`} className="group flex gap-3">
                      <div className="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-200">
                        {item.image_url ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img src={String(item.image_url)} alt="" className="h-full w-full object-cover" />
                        ) : null}
                      </div>
                      <div className="min-w-0">
                        <p className="truncate text-sm font-medium text-slate-900 transition-colors group-hover:text-blue-600">{String(item.title ?? "")}</p>
                        {item.published_at ? <p className="text-xs text-slate-500">{fmtShortDate(item.published_at)}</p> : null}
                      </div>
                    </a>
                  </li>
                ))}
              </ul>
            </div>

            <div className="rounded-2xl border border-slate-100 bg-slate-50 p-6">
              <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Archives</h3>
              <ul className="mt-4 space-y-2">
                {[0, -1, -2].map((offset) => {
                  const d = new Date();
                  d.setMonth(d.getMonth() + offset);
                  return (
                    <li key={offset}>
                      <a href="#" className="block rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-blue-50 hover:text-blue-700">
                        {d.toLocaleDateString("en-US", { month: "long", year: "numeric" })}
                      </a>
                    </li>
                  );
                })}
              </ul>
            </div>
          </div>
        </div>

        {pastEvents.length > 0 ? (
          <section className="mt-16 reveal">
            <h2 className="text-xl font-bold text-slate-900">{t("site.news.past_events_heading")}</h2>
            <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-teal-400 to-teal-600" />
            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {pastEvents.map((ev) => (
                <div key={String(ev.id ?? "")} className="group rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                  <h3 className="font-semibold text-slate-900 transition-colors group-hover:text-teal-600">{String(ev.title ?? "")}</h3>
                  <div className="mt-3 flex items-center gap-2 text-sm text-slate-500">
                    <svg className="h-4 w-4 shrink-0 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <time>{fmtShortDate(ev.start_date)}</time>
                  </div>
                  {ev.location ? (
                    <div className="mt-1.5 flex items-center gap-2 text-sm text-slate-500">
                      <svg className="h-4 w-4 shrink-0 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span>{String(ev.location)}</span>
                    </div>
                  ) : null}
                </div>
              ))}
            </div>
          </section>
        ) : null}
      </div>
    </div>
  );
}