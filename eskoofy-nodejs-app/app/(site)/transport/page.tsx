import { t } from "@/lib/i18n";
import { getTransportRoutes } from "@/lib/site-data";
import { CardGrid, Empty, Hero, InfoCard, Section } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function TransportPage() {
  const routes = await getTransportRoutes();

  return (
    <>
      <Hero eyebrow={t("dashboard.transport")} title={t("dashboard.transport")} />
      <Section>
        {routes.length === 0 ? (
          <Empty>No transport routes published yet.</Empty>
        ) : (
          <CardGrid>
            {routes.map((route) => (
              <InfoCard
                key={String(route.id)}
                title={String(route.name ?? "")}
                subtitle={route.code ? String(route.code) : undefined}
                body={route.fare !== undefined ? `Fare: ${String(route.fare)}` : undefined}
              />
            ))}
          </CardGrid>
        )}
      </Section>
    </>
  );
}
