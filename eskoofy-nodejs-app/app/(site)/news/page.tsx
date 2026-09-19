import { t } from "@/lib/i18n";
import { getLatestNews } from "@/lib/site-data";
import { CardGrid, Empty, Hero, InfoCard, Section, formatDate } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function NewsIndexPage() {
  const news = await getLatestNews(24);

  return (
    <>
      <Hero eyebrow={t("site.nav.news")} title={t("site.home.news_title")} />
      <Section>
        {news.length === 0 ? (
          <Empty>No news published yet.</Empty>
        ) : (
          <CardGrid>
            {news.map((item) => (
              <InfoCard
                key={String(item.id)}
                meta={item.category ? String(item.category) : t("site.home.news_badge")}
                title={String(item.title ?? "")}
                subtitle={formatDate(item.published_at)}
                body={item.excerpt ? String(item.excerpt).slice(0, 160) : undefined}
                href={`/news/${item.slug}`}
              />
            ))}
          </CardGrid>
        )}
      </Section>
    </>
  );
}
