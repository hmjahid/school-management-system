import { t } from "@/lib/i18n";
import { prisma } from "@/lib/prisma";
import { PageHero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

const DAY_NAMES: Record<number, string> = {
  1: "Sunday",
  2: "Monday",
  3: "Tuesday",
  4: "Wednesday",
  5: "Thursday",
  6: "Friday",
  7: "Saturday",
};

const inputClass =
  "rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default async function RoutinePage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const classId = Number(Array.isArray(sp.class_id) ? sp.class_id[0] : sp.class_id) || 0;
  const sectionId = Number(Array.isArray(sp.section_id) ? sp.section_id[0] : sp.section_id) || 0;

  const [classes, sections, rows] = await Promise.all([
    prisma.school_classes.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true } }),
    prisma.sections.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true } }),
    prisma.routines.findMany({
      where: {
        is_active: true,
        ...(classId ? { school_class_id: classId } : {}),
        ...(sectionId ? { section_id: sectionId } : {}),
      },
      orderBy: [{ day_of_week: "asc" }, { start_time: "asc" }],
      include: { subjects: { select: { id: true, name: true } }, teachers: { include: { users: { select: { name: true } } } } },
    }),
  ]);

  const byDay: Record<number, typeof rows> = {};
  for (const row of rows) {
    const day = Number(row.day_of_week);
    (byDay[day] ??= []).push(row);
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.nav.routine")}</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">View the weekly class schedule</p>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <form method="get" className="mb-8 flex flex-wrap gap-4">
          <select name="class_id" className={inputClass} defaultValue={classId || ""}>
            <option value="">Select class</option>
            {classes.map((c) => (
              <option key={c.id} value={c.id}>{String(c.name)}</option>
            ))}
          </select>
          <select name="section_id" className={inputClass} defaultValue={sectionId || ""}>
            <option value="">All sections</option>
            {sections.map((s) => (
              <option key={s.id} value={s.id}>{String(s.name)}</option>
            ))}
          </select>
          <button type="submit" className="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            View routine
          </button>
        </form>

        {rows.length === 0 ? (
          <div className="rounded-xl border-2 border-dashed border-gray-300 p-12 text-center">
            <p className="text-gray-500">Select a class to view the routine.</p>
          </div>
        ) : (
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {[1, 2, 3, 4, 5, 6, 7].map((day) => {
              const periods = byDay[day];
              if (!periods || periods.length === 0) return null;
              return (
                <div key={day} className="rounded-xl bg-white p-6 shadow-md">
                  <h3 className="mb-4 text-lg font-bold text-blue-800">{DAY_NAMES[day] ?? `Day ${day}`}</h3>
                  <div className="space-y-3">
                    {periods.map((period) => (
                      <div key={period.id} className="rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
                        <p className="font-semibold text-gray-900">{String(period.subjects?.name ?? "—")}</p>
                        <p className="text-xs text-gray-500">
                          {String(period.start_time ?? "").slice(0, 5)} - {String(period.end_time ?? "").slice(0, 5)}
                        </p>
                        <p className="text-xs text-gray-400">
                          {String(period.teachers?.users?.name ?? "")}
                          {period.room_number ? <> | Room {String(period.room_number)}</> : null}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}