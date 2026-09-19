import Link from "next/link";
import { t } from "@/lib/i18n";
import { getEvents, getGallery, getLatestNews, getNotices, getSchoolStats, getTestimonials } from "@/lib/site-data";
import { CardGrid, Empty, Hero, InfoCard, Section, StatBar, formatDate } from "@/components/site/Sections";

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

  return (
    <>
      <Hero
        eyebrow={t("site.home.eyebrow")}
        title={t("site.home.hero_headline")}
        subtitle={t("site.home.hero_subtitle")}
        primary={{ label: t("site.home.hero_cta_primary"), href: "/admissions/apply" }}
        secondary={{ label: t("site.home.hero_cta_secondary"), href: "/about" }}
      />

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
