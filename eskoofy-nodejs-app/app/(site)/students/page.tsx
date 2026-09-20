import { t } from "@/lib/i18n";
import { getStudentsNotable } from "@/lib/site-data";
import { CardGrid, Empty, InfoCard, Section, PageHero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function StudentsPage() {
  const notable = await getStudentsNotable(24);

  return (
    <>
      <PageHero title={t("site.home.remarkable_students_title")} subtitle={t("site.home.remarkable_students_intro")} />
      <Section>
        {notable.length === 0 ? (
          <Empty>No notable students to display yet.</Empty>
        ) : (
          <CardGrid>
            {notable.map((student) => (
              <InfoCard
                key={String(student.id)}
                title={`${String(student.first_name ?? "")} ${String(student.last_name ?? "")}`.trim()}
                subtitle={student.roll_number ? `Roll ${String(student.roll_number)}` : undefined}
                body={student.achievement ? String(student.achievement) : undefined}
              />
            ))}
          </CardGrid>
        )}
      </Section>
    </>
  );
}
