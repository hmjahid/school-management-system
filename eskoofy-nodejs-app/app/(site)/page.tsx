import Link from "next/link";
import { t } from "@/lib/i18n";
import { getEvents, getGallery, getLatestNews, getNotices, getSchoolStats, getTestimonials } from "@/lib/site-data";
import { CardGrid, Empty, InfoCard, Section, StatBar, formatDate } from "@/components/site/Sections";
import { getSiteSettings, splitSchoolName } from "@/lib/site-settings";

export const dynamic = "force-dynamic";

export default async function HomePage() {
  const [stats, news, notices, events, gallery, testimonials] = await Promise.all([
    getSchoolStats(),
    getLatestNews(3),
    getNotices(4),
    getEvents(3),
    getGallery(6),
    getTestimonials(3),
  ]);
  const settings = await getSiteSettings();
  const { first, rest } = splitSchoolName(settings.schoolName);

  return (
    <>
      {/* Hero — mirrors Laravel partials/site/hero/design-1.blade.php */}
      <section className="relative flex min-h-[85vh] items-center overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950">
        <div className="absolute inset-x-0 top-0 z-10" aria-hidden="true">
          <div className="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-3xl rounded-2xl border border-white/10 bg-white/[0.06] px-8 py-4 text-center shadow-xl shadow-black/10 backdrop-blur-md">
              <span className="font-black uppercase tracking-widest text-white drop-shadow-lg text-xl sm:text-2xl lg:text-3xl">{first}{rest ? ` ${rest}` : ""}</span>
            </div>
          </div>
        </div>
        <div className="absolute -top-40 -right-40 h-[500px] w-[500px] rounded-full bg-orange-500/10 blur-3xl" aria-hidden="true" />
        <div className="absolute -bottom-40 -left-40 h-[500px] w-[500px] rounded-full bg-indigo-500/10 blur-3xl" aria-hidden="true" />

        <div className="relative z-10 mx-auto w-full max-w-7xl px-4 pb-20 pt-28 sm:px-6 lg:px-8">
          <div className="grid gap-10 lg:grid-cols-12 lg:items-center">
            <div className="lg:col-span-7 xl:col-span-7">
              <h1 className="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-7xl" style={{ textShadow: "0 4px 30px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3)" }}>
                {t("site.home.hero_headline")}
              </h1>
              <p className="mt-6 max-w-2xl text-lg leading-relaxed text-white/90 sm:text-xl">{t("site.home.hero_subtitle")}</p>
              <div className="mt-10 flex flex-wrap items-center gap-4">
                <Link href="/admissions/apply" className="inline-flex items-center gap-2.5 rounded-xl bg-orange-500 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-orange-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-orange-600 hover:shadow-xl">
                  {t("site.home.hero_cta_primary")}
                  <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                </Link>
                <Link href="/about" className="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/5 px-8 py-4 text-base font-semibold text-white backdrop-blur-sm transition-all duration-300 hover:border-white/40 hover:bg-white/10">
                  {t("site.home.hero_cta_secondary")}
                </Link>
              </div>
            </div>

            <div className="lg:col-span-5 xl:col-span-5">
              <div className="rounded-2xl border border-slate-200 bg-white shadow-lg">
                <div className="flex items-center gap-2.5 px-6 pb-4 pt-5 sm:px-7">
                  <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50">
                    <svg className="h-5 w-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clipRule="evenodd" /></svg>
                  </span>
                  <h3 className="text-base font-bold text-slate-900">{t("site.home.latest_notices")}</h3>
                </div>
                <ul className="space-y-3 px-6 pb-6 sm:px-7">
                  {notices.slice(0, 4).map((notice) => (
                    <li key={String(notice.id)} className="rounded-lg border border-slate-100 bg-slate-50 p-3">
                      <p className="text-sm font-medium text-slate-800">{String(notice.title ?? "")}</p>
                      <p className="mt-0.5 text-xs text-slate-400">{formatDate(notice.created_at)}</p>
                    </li>
                  ))}
                  {notices.length === 0 ? <li className="text-sm text-slate-500">No notices yet.</li> : null}
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>

      <Section tone="muted">
        <StatBar
          stats={[
            { label: t("site.home.stats_students"), value: stats.students },
            { label: t("site.home.stats_faculty"), value: stats.teachers },
            { label: t("site.stats.classes", {}) === "site.stats.classes" ? "Classes" : t("site.stats.classes"), value: stats.classes },
            { label: t("site.home.events_title"), value: stats.events },
          ]}
        />
      </Section>

      <Section
        title={t("site.home.news_title")}
        action={{ label: t("site.home.news_view_all"), href: "/news" }}
      >
        {news.length === 0 ? (
          <Empty>{t("site.news.empty", {}) === "site.news.empty" ? "No news published yet." : t("site.news.empty")}</Empty>
        ) : (
          <CardGrid>
            {news.map((item) => (
              <InfoCard
                key={String(item.id)}
                meta={item.category ? String(item.category) : t("site.home.news_badge")}
                title={String(item.title ?? "")}
                subtitle={formatDate(item.published_at)}
                body={item.excerpt ? String(item.excerpt).slice(0, 140) : undefined}
                href={`/news/${item.slug}`}
              />
            ))}
          </CardGrid>
        )}
      </Section>

      <Section title={t("site.home.latest_notices")} action={{ label: t("site.home.view_all_notices"), href: "/notices" }} tone="muted">
        {notices.length === 0 ? (
          <Empty>No notices published yet.</Empty>
        ) : (
          <ul className="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
            {notices.map((notice) => (
              <li key={String(notice.id)} className="flex items-start justify-between gap-4 p-4">
                <span className="text-sm font-medium text-slate-800">{String(notice.title ?? "")}</span>
                <span className="whitespace-nowrap text-xs text-slate-400">{formatDate(notice.created_at)}</span>
              </li>
            ))}
          </ul>
        )}
      </Section>

      <Section title={t("site.home.events_title")} action={{ label: t("site.home.events_view_all"), href: "/events" }}>
        {events.length === 0 ? (
          <Empty>No upcoming events.</Empty>
        ) : (
          <CardGrid>
            {events.map((event) => (
              <InfoCard
                key={String(event.id)}
                meta={formatDate(event.start_date)}
                title={String(event.title ?? "")}
                subtitle={event.location ? String(event.location) : undefined}
                body={event.description ? String(event.description).slice(0, 140) : undefined}
              />
            ))}
          </CardGrid>
        )}
      </Section>

      <Section title={t("site.home.gallery", {}) === "site.home.gallery" ? "Gallery" : t("site.home.gallery")} tone="muted">
        {gallery.length === 0 ? (
          <Empty>No gallery images yet.</Empty>
        ) : (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            {gallery.map((image) => (
              <div key={String(image.id)} className="aspect-square overflow-hidden rounded-xl border border-slate-200 bg-white">
                {image.image_path ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={String(image.image_path)} alt={String(image.title ?? "")} className="h-full w-full object-cover" />
                ) : (
                  <div className="grid h-full place-items-center text-xs text-slate-400">{String(image.title ?? "")}</div>
                )}
              </div>
            ))}
          </div>
        )}
      </Section>

      <Section title={t("site.home.testimonials_title")}>
        {testimonials.length === 0 ? (
          <Empty>No testimonials yet.</Empty>
        ) : (
          <CardGrid>
            {testimonials.map((item) => (
              <InfoCard
                key={String(item.id)}
                title={`“${String(item.content ?? "")}”`}
                subtitle={[item.author_name, item.author_designation].filter(Boolean).join(" · ")}
              />
            ))}
          </CardGrid>
        )}
      </Section>

      <Section tone="dark">
        <div className="text-center">
          <h2 className="text-2xl font-bold">{t("site.home.cta_banner_title")}</h2>
          <p className="mx-auto mt-3 max-w-xl text-slate-300">{t("site.home.cta_banner_intro")}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link href="/admissions/apply" className="rounded-xl bg-blue-600 px-6 py-3 font-semibold hover:bg-blue-500">
              {t("site.home.cta_apply")}
            </Link>
            <Link href="/contact" className="rounded-xl border border-white/30 px-6 py-3 font-semibold hover:border-white">
              {t("site.home.cta_contact")}
            </Link>
          </div>
        </div>
      </Section>
    </>
  );
}
