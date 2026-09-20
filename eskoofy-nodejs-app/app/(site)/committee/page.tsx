import { t } from "@/lib/i18n";
import { getCommittee } from "@/lib/site-data";
import { CardGrid, Empty, InfoCard, Section, PageHero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function CommitteePage() {
  const members = await getCommittee();

  return (
    <>
      <PageHero title={t("site.home.committee_title")} subtitle={t("site.home.committee_intro")} />
      <Section>
        {members.length === 0 ? (
          <Empty>{t("site.home.committee_empty")}</Empty>
        ) : (
          <CardGrid>
            {members.map((member) => (
              <InfoCard
                key={String(member.id)}
                title={String(member.name ?? "")}
                subtitle={String(member.designation ?? "")}
                meta={String(member.phone ?? member.email ?? "")}
              />
            ))}
          </CardGrid>
        )}
      </Section>
    </>
  );
}
