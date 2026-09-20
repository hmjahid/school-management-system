import { t } from "@/lib/i18n";
import { getClasses, getRoutinesForClass } from "@/lib/site-data";
import { Empty, Section, PageHero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

const inputClass =
  "rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

const DAYS = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];

export default async function RoutinePage({ searchParams }: { searchParams: Promise<{ class?: string }> }) {
  const params = await searchParams;
  const classes = await getClasses();
  const classId = Number(params.class ?? 0) || Number(classes[0]?.id ?? 0);
  const rows = classId ? await getRoutinesForClass(classId) : [];

  const byDay = DAYS.map((day) => ({
    day,
    periods: rows
      .filter((row) => String(row.day_of_week ?? "").toLowerCase() === day)
      .sort((a, b) => String(a.start_time ?? "").localeCompare(String(b.start_time ?? ""))),
  }));

  return (
    <>
      <PageHero title={t("site.nav.routine")} />

      <Section>
        <div className="mx-auto max-w-5xl space-y-6">
          <form method="get" className="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5">
            <div>
              <label htmlFor="class" className="mb-1 block text-sm font-semibold">
                {t("dashboard.classes")}
              </label>
              <select id="class" name="class" defaultValue={classId ? String(classId) : ""} className={inputClass}>
                {classes.map((schoolClass) => (
                  <option key={String(schoolClass.id)} value={String(schoolClass.id)}>
                    {String(schoolClass.name ?? schoolClass.id)}
                  </option>
                ))}
              </select>
            </div>
            <button className="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">Show</button>
          </form>

          {classes.length === 0 ? (
            <Empty>No classes configured yet.</Empty>
          ) : rows.length === 0 ? (
            <Empty>No routine published for this class yet.</Empty>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {byDay
                .filter((entry) => entry.periods.length > 0)
                .map((entry) => (
                  <div key={entry.day} className="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 className="font-bold capitalize text-slate-800">{entry.day}</h2>
                    <ul className="mt-3 space-y-2 text-sm text-slate-600">
                      {entry.periods.map((period, index) => (
                        <li key={index} className="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 last:border-0">
                          <span>
                            {String(period.start_time ?? "")} – {String(period.end_time ?? "")}
                          </span>
                          <span className="text-xs text-slate-400">
                            {String(period.room_number ?? "")}
                          </span>
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}
            </div>
          )}
        </div>
      </Section>
    </>
  );
}
