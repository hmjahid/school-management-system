import { t } from "@/lib/i18n";
import { getTeachers } from "@/lib/site-data";
import { CardGrid, Empty, InfoCard, Section, PageHero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function FacultyPage() {
  const teachers = await getTeachers(60);

  return (
    <>
      <PageHero title={t("site.home.teachers_title")} subtitle={t("site.home.teachers_intro")} />
      <Section>
        {teachers.length === 0 ? (
          <Empty>No teachers to display yet.</Empty>
        ) : (
          <CardGrid>
            {teachers.map((teacher) => (
              <InfoCard
                key={String(teacher.id)}
                title={String(teacher.name ?? "")}
                subtitle={String(teacher.email ?? "")}
                meta={t("site.home.teacher_fallback")}
              />
            ))}
          </CardGrid>
        )}
      </Section>
    </>
  );
}
