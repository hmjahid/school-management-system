import { t } from "@/lib/i18n";
import { getClasses } from "@/lib/site-data";
import { ContentPage } from "@/components/site/ContentPage";
import { CardGrid, Empty, Section } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function AcademicsPage() {
  const classes = await getClasses();

  return (
    <>
      <ContentPage page="academics" title={t("site.nav.academics")} description={t("site.home.features_intro")} />
      <Section title={t("dashboard.classes")} tone="muted">
        {classes.length === 0 ? (
          <Empty>No classes yet.</Empty>
        ) : (
          <CardGrid columns={4}>
            {classes.map((schoolClass) => (
              <div key={String(schoolClass.id)} className="rounded-2xl border border-slate-200 bg-white p-5 text-center">
                <div className="text-lg font-bold text-slate-800">{String(schoolClass.name ?? "")}</div>
                <div className="mt-1 text-xs text-slate-400">
                  {String(schoolClass.shift ?? "")} · {String(schoolClass.grade_level ?? "")}
                </div>
              </div>
            ))}
          </CardGrid>
        )}
      </Section>
    </>
  );
}
