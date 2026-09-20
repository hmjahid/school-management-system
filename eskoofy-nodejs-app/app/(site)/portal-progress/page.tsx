import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const inputClass =
  "w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default async function PortalProgressPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const studentId = Number(sp.student_id) || 0;
  const sessionId = Number(sp.academic_session_id) || 0;
  const examType = String(sp.exam_type ?? "");

  const [studentIds, sessions, students, examResults] = await Promise.all([
    prisma.students.findMany({ select: { id: true }, take: 200 }),
    prisma.academic_sessions.findMany({ orderBy: { name: "asc" } }),
    prisma.students.findMany({ where: { id: studentId || undefined }, select: { id: true, first_name: true, last_name: true } }),
    prisma.exam_results.findMany({
      where: { ...(studentId ? { student_id: studentId } : {}), is_published: true },
      include: { exams: true },
      take: 200,
    }),
  ]);

  const grouped = new Map<number, { exam: Record<string, unknown>; rows: Record<string, unknown>[] }>();
  for (const row of examResults) {
    const exam = (row.exams ?? {}) as Record<string, unknown>;
    if (examType && String(exam.type ?? "") !== examType) continue;
    const key = Number(exam.id ?? 0);
    if (!grouped.has(key)) grouped.set(key, { exam, rows: [] });
    grouped.get(key)!.rows.push(row);
  }

  const student = students[0] as Record<string, unknown> | undefined;

  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">{t("site.portal_progress.heading")}</h1>
          <p className="mt-1 text-sm text-slate-600">{t("site.portal_progress.intro")}</p>
        </div>
        <Link href="/portal" className="text-sm font-semibold text-slate-700 hover:text-slate-900">{t("site.portal_progress.back")}</Link>
      </div>

      <form method="get" className="mt-8 grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-3">
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">{t("site.portal_progress.student")}</label>
          <select name="student_id" className={inputClass} defaultValue={studentId || ""}>
            <option value="">{t("site.portal_progress.all")}</option>
            {studentIds.map((s) => (
              <option key={s.id} value={s.id}>{String(s.id)}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">{t("site.portal_progress.academic_session")}</label>
          <select name="academic_session_id" className={inputClass} defaultValue={sessionId || ""}>
            <option value="">{t("site.portal_progress.all")}</option>
            {sessions.map((s) => (
              <option key={s.id} value={s.id}>{String(s.name ?? `#${s.id}`)}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">{t("site.portal_progress.exam_type")}</label>
          <input name="exam_type" className={inputClass} placeholder="exam_type" defaultValue={examType} />
        </div>
        <div className="flex items-center justify-between gap-3 sm:col-span-3">
          <button className="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
            {t("site.portal_progress.apply_filters")}
          </button>
          <Link href="/portal-progress" className="text-sm font-medium text-slate-600 hover:text-slate-900">{t("site.portal_progress.reset")}</Link>
        </div>
      </form>

      <section className="mt-8">
        {grouped.size === 0 ? (
          <div className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-700 shadow-sm">{t("site.portal_progress.no_results")}</div>
        ) : (
          <div className="space-y-6">
            {Array.from(grouped.entries()).map(([examId, { exam, rows }]) => {
              const marks = rows.map((r) => Number(r.obtained_marks ?? 0)).filter((n) => n > 0);
              const avgMarks = marks.length ? (marks.reduce((a, b) => a + b, 0) / marks.length).toFixed(2) : "—";
              return (
                <div key={examId} className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h2 className="text-lg font-semibold text-slate-900">{String(exam.name ?? "") || t("site.portal_progress.exam")}</h2>
                      <div className="mt-1 text-sm text-slate-600">
                        {t("site.portal_progress.type_colon", { t: String(exam.type ?? "—") }).replace(":t", String(exam.type ?? "—"))} · {t("site.portal_progress.results_colon", { n: String(rows.length) }).replace(":n", String(rows.length))}
                      </div>
                    </div>
                    <div className="text-sm text-slate-700">
                      <div>{t("site.portal_progress.avg_marks").replace(":m", avgMarks)}</div>
                    </div>
                  </div>
                  <div className="mt-4 overflow-x-auto rounded-lg border border-slate-100">
                    <table className="min-w-full divide-y divide-slate-100 text-sm">
                      <thead className="bg-slate-50">
                        <tr>
                          <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.portal_progress.marks")}</th>
                          <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.portal_progress.grade")}</th>
                          <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.portal_progress.grade_point")}</th>
                          <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.portal_progress.status")}</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {rows.map((r) => (
                          <tr key={String(r.id ?? "")}>
                            <td className="px-4 py-3 text-slate-900">{String(r.obtained_marks ?? "—")}</td>
                            <td className="px-4 py-3 text-slate-600">{String(r.grade ?? "—")}</td>
                            <td className="px-4 py-3 text-slate-600">{String(r.grade_point ?? "—")}</td>
                            <td className="px-4 py-3 capitalize text-slate-600">{String(r.status ?? "—")}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </section>
      {student ? (
        <p className="mt-6 text-sm text-slate-500">
          {t("site.portal_progress.student")}: {String(student.first_name ?? "")} {String(student.last_name ?? "")}
        </p>
      ) : null}
    </div>
  );
}
