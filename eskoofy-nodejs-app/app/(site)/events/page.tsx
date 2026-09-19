import { t } from "@/lib/i18n";
import { getEvents } from "@/lib/site-data";
import { CardGrid, Empty, Hero, InfoCard, Section, formatDate } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function EventsPage() {
  const events = await getEvents(48);

  return (
    <>
      <Hero eyebrow={t("site.nav.news")} title={t("site.home.events_title")} />
      <Section>
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
                body={event.description ? String(event.description) : undefined}
              />
            ))}
          </CardGrid>
        )}
      </Section>
    </>
  );
}
