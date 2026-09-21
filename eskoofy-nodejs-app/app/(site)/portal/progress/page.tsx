import Link from "next/link";
import { notFound, redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { currentUser } from "@/lib/auth";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const EXAM_TYPES = ["quiz", "mid_term", "final", "assignment", "project", "practical", "oral", "other"];

const inputClass =
  "mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500";

type Row = Record<string, unknown>;

export default async function PortalProgressPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/portal/progress");

  const isStudent = user.role === "student";
  const isParent = user.role === "parent";

  let studentIds: number[] = [];
  if (isStudent) {
    const id = await safe(
      () => prisma.students.findFirst({ where: { user_id: user.id, deleted_at: null }, select: { id: true } }),
      null,
    );
    studentIds = id ? [id.id] : [];
  } else if (isParent) {
    const guardian = await safe(
      () => prisma.guardians.findFirst({ where: { user_id: user.id, deleted_at: null } }),
      null,
    );
    if (guardian) {
      const links = await safe(
        () => prisma.guardian_student.findMany({ where: { guardian_id: guardian.id }, select: { student_id: true } }),
        [],
      );
      studentIds = links.map((l) => l.student_id);
    }
  }

  if (studentIds.length === 0) notFound();

  const requestedStudent = Number(sp.student_id) || studentIds[0];
  if (!studentIds.includes(requestedStudent)) notFound();
  const selectedStudentId = requestedStudent;

  const requestedSession = Number(sp.academic_session_id) || null;
  const selectedExamType = typeof sp.exam_type === "string" ? sp.exam_type : null;

  const [sessions, student, results] = await Promise.all([
    safe(() => prisma.academic_sessions.findMany({ orderBy: { start_date: "desc" }, take: 10 }), []),
    safe(
      () =>
        prisma.students.findUnique({
          where: { id: selectedStudentId },
          include: { school_classes: true, sections: true, batches: true, users: true },
        }),
      null,
    ),
    safe(
      () =>
        prisma.exam_results.findMany({
          where: {
            student_id: selectedStudentId,
            is_published: true,
            ...(requestedSession ? { exams: { academic_session_id: requestedSession } } : {}),
            ...(selectedExamType ? { exams: { type: selectedExamType } } : {}),
          },
          include: { exams: true },
          orderBy: { id: "desc" },
          take: 200,
        }),
      [],
    ),
  ]);

  const byExam = new Map<number, { exam: Row; rows: Row[] }>();
  for (const row of results as unknown as Row[]) {
    const exam = (row.exams ?? {}) as Row;
    if (!exam.id) continue;
    const key = Number(exam.id);
    if (!byExam.has(key)) byExam.set(key, { exam, rows: [] });
    byExam.get(key)!.rows.push(row);
  }

  const summaries = Array.from(byExam.values())
    .map((group) => {
      const gps = group.rows.map((r) => Number(r.grade_point ?? NaN)).filter((v) => Number.isFinite(v));
      const marks = group.rows.map((r) => Number(r.obtained_marks ?? NaN)).filter((v) => Number.isFinite(v));
      const avgGp = gps.length ? gps.reduce((a, b) => a + b, 0) / gps.length : null;
      const avgMarks = marks.length ? marks.reduce((a, b) => a + b, 0) / marks.length : null;
      return { ...group, avgGp, avgMarks };
    })
    .sort((a, b) => {
      const da = new Date(String(a.exam.start_date ?? 0)).getTime();
      const db = new Date(String(b.exam.start_date ?? 0)).getTime();
      return db - da;
    });

  const statusBadge = (status: unknown) => {
    const s = String(status ?? "").toLowerCase();
    if (s === "published") return "bg-emerald-100 text-emerald-800";
    if (s === "passed") return "bg-green-100 text-green-800";
    if (s === "failed") return "bg-red-100 text-red-800";
    return "bg-slate-100 text-slate-700";
  };

  const selectedStudentName = `${String((student as unknown as Row | null)?.first_name ?? "")} ${String((student as unknown as Row | null)?.last_name ?? "")}`.trim();

  return (
    <div className="mx-auto max-w-6xl px-4 py-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t("site.portal_progress.heading")}</h1>
          <p className="mt-1 text-sm text-gray-600">{t("site.portal_progress.intro")}</p>
        </div>
        <Link href="/portal" className="text-sm font-semibold text-gray-700 hover:text-gray-900">
          {t("site.portal_progress.back")}
        </Link>
      </div>

      <form method="get" className="mt-8 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-3">
        <div>
          <label className="block text-sm font-medium text-gray-700">{t("site.portal_progress.student")}</label>
          <select name="student_id" className={inputClass} defaultValue={selectedStudentId}>
            {studentIds.map((sid) => (
              <option key={sid} value={sid}>
                {t("site.portal_progress.student_num", { id: String(sid) })}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">{t("site.portal_progress.academic_session")}</label>
          <select name="academic_session_id" className={inputClass} defaultValue={requestedSession ?? ""}>
            <option value="">{t("site.portal_progress.all")}</option>
            {sessions.map((s) => (
              <option key={s.id} value={s.id}>
                {String(s.name ?? `#${s.id}`)}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700">{t("site.portal_progress.exam_type")}</label>
          <select name="exam_type" className={inputClass} defaultValue={selectedExamType ?? ""}>
            <option value="">{t("site.portal_progress.all")}</option>
            {EXAM_TYPES.map((examType) => (
              <option key={examType} value={examType}>
                {examType.replace(/_/g, " ").replace(/^\w/, (c) => c.toUpperCase())}
              </option>
            ))}
          </select>
        </div>

        <div className="flex items-center justify-between gap-3 sm:col-span-3">
          <button className="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">
            {t("site.portal_progress.apply_filters")}
          </button>
          <Link href="/portal/progress" className="text-sm font-medium text-gray-600 hover:text-gray-900">
            {t("site.portal_progress.reset")}
          </Link>
        </div>
      </form>

      <section className="mt-8">
        {summaries.length === 0 ? (
          <div className="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-700 shadow-sm">{t("site.portal_progress.no_results")}</div>
        ) : (
          <div className="space-y-6">
            {summaries.map((summary) => {
              const exam = summary.exam;
              return (
                <div key={Number(exam.id)} className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h2 className="text-lg font-semibold text-gray-900">{String(exam.name ?? "") || t("site.portal_progress.exam")}</h2>
                      <div className="mt-1 text-sm text-gray-600">
                        {t("site.portal_progress.type_colon", { t: String(exam.type ?? "—").replace(/_/g, " ") })}
                        {" · "}
                        {t("site.portal_progress.results_colon", { n: String(summary.rows.length) })}
                      </div>
                    </div>
                    <div className="text-sm text-gray-700">
                      <div>
                        {t("site.portal_progress.avg_marks", { m: summary.avgMarks !== null ? summary.avgMarks.toFixed(2) : "—" })}
                      </div>
                      <div>
                        {t("site.portal_progress.avg_gp", { g: summary.avgGp !== null ? summary.avgGp.toFixed(2) : "—" })}
                      </div>
                    </div>
                  </div>

                  <div className="mt-4 overflow-x-auto rounded-lg border border-gray-100">
                    <table className="min-w-full divide-y divide-gray-100 text-sm">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-4 py-3 text-left font-semibold text-gray-700">{t("site.portal_progress.marks")}</th>
                          <th className="px-4 py-3 text-left font-semibold text-gray-700">{t("site.portal_progress.grade")}</th>
                          <th className="px-4 py-3 text-left font-semibold text-gray-700">{t("site.portal_progress.grade_point")}</th>
                          <th className="px-4 py-3 text-left font-semibold text-gray-700">{t("site.portal_progress.status")}</th>
                          <th className="px-4 py-3 text-left font-semibold text-gray-700">{t("site.portal_progress.remarks")}</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {summary.rows.map((r) => (
                          <tr key={String(r.id ?? "")}>
                            <td className="px-4 py-3 text-gray-900">{String(r.obtained_marks ?? "—")}</td>
                            <td className="px-4 py-3 text-gray-900">{String(r.grade ?? "—")}</td>
                            <td className="px-4 py-3 text-gray-900">{String(r.grade_point ?? "—")}</td>
                            <td className="px-4 py-3">
                              <span className={`rounded-full px-2 py-1 text-xs font-semibold ${statusBadge(r.status)}`}>
                                {String(r.status ?? "—").replace(/^\w/, (c) => c.toUpperCase())}
                              </span>
                            </td>
                            <td className="px-4 py-3 text-gray-700">{String(r.remarks ?? "—")}</td>
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

      {selectedStudentName ? (
        <p className="mt-6 text-sm text-gray-500">
          {t("site.portal_progress.student")}: {selectedStudentName}
        </p>
      ) : null}
    </div>
  );
}