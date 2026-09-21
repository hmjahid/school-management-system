import Link from "next/link";
import type { ReactNode } from "react";
import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

type ClassRow = { id: number; name: string };
type SessionRow = { id: number; name: string };

const selectClass =
  "w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

type ExamRow = Record<string, unknown>;

interface Portfolio {
  student: Record<string, unknown>;
  rows: ExamRow[];
}

async function lookup(classId: number, sessionId: number, roll: string): Promise<Portfolio | null> {
  try {
    const student = await prisma.students.findFirst({
      where: { class_id: classId, deleted_at: null, OR: [{ roll_number: roll }, { roll_no: roll }] },
      include: { users: { select: { name: true } }, school_classes: { select: { name: true } } },
    });
    if (!student) return null;

    const rows = await prisma.exam_results.findMany({
      where: {
        student_id: student.id,
        is_published: true,
        exams: { is_published_to_public: true, academic_session_id: sessionId, batch_id: student.batch_id },
      },
      include: { exams: { include: { subjects: true } } },
    });

    return { student: student as unknown as Record<string, unknown>, rows: rows as unknown[] as ExamRow[] };
  } catch {
    return null;
  }
}

function gradePill(grade: string, negative?: boolean): ReactNode {
  if (negative) {
    const isFail = grade === "F";
    return (
      <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${isFail ? "bg-red-100 text-red-800" : "bg-slate-100 text-slate-700"}`}>
        {grade}
      </span>
    );
  }
  const tone =
    grade === "A+" || grade === "A" ? "bg-green-100 text-green-800" : grade === "F" ? "bg-red-100 text-red-800" : "bg-yellow-100 text-yellow-800";
  return (
    <span className={`inline-flex items-center gap-1 rounded-full px-4 py-1.5 text-sm font-bold ${tone}`}>
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
      </svg>
      Grade: {grade}
    </span>
  );
}

export default async function ResultsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const classId = Number(sp.class_id) || 0;
  const sessionId = Number(sp.academic_session_id) || 0;
  const roll = String(sp.roll ?? "");

  const [classes, sessions, portfolio] = await Promise.all([
    safe(() => prisma.school_classes.findMany({ orderBy: { name: "asc" }, select: { id: true, name: true } }), [] as ClassRow[]),
    safe(() => prisma.academic_sessions.findMany({ orderBy: { name: "desc" }, select: { id: true, name: true } }), [] as SessionRow[]),
    classId && sessionId && roll ? lookup(classId, sessionId, roll) : Promise.resolve(null),
  ]);

  const filled = Boolean(classId && sessionId && roll);
  const student = portfolio?.student ?? null;
  const grouped = new Map<string, ExamRow[]>();
  if (portfolio) {
    for (const row of portfolio.rows) {
      const exam = (row.exams ?? {}) as Record<string, unknown>;
      const name = String(exam.name ?? "Exam");
      const list = grouped.get(name) ?? [];
      list.push(row);
      grouped.set(name, list);
    }
  }
  const totalObtained = (portfolio?.rows ?? []).reduce((sum, r) => sum + Number(r.obtained_marks ?? 0), 0);
  const totalMax = (portfolio?.rows ?? []).reduce((sum, r) => sum + Number((r.exams as Record<string, unknown> | null)?.total_marks ?? 0), 0);
  const percentage = totalMax > 0 ? Math.round((totalObtained / totalMax) * 1000) / 10 : 0;
  const grade = percentage >= 80 ? "A+" : percentage >= 70 ? "A" : percentage >= 60 ? "A-" : percentage >= 50 ? "B" : percentage >= 40 ? "C" : "F";

  const studentName =
    String((student?.users as Record<string, unknown> | null)?.name ?? "").trim() || `${String(student?.first_name ?? "")} ${String(student?.last_name ?? "")}`.trim();

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.nav.results")}</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">Search published exam results by class, year, and roll number.</p>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="mx-auto max-w-3xl">
          <form method="get" className="rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
            <div className="grid gap-5 sm:grid-cols-3">
              <div>
                <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">Class</label>
                <select name="class_id" required defaultValue={classId || ""} className={selectClass}>
                  <option value="">—</option>
                  {classes.map((c) => (
                    <option key={c.id} value={c.id}>{String(c.name)}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">Session</label>
                <select name="academic_session_id" required defaultValue={sessionId || ""} className={selectClass}>
                  <option value="">—</option>
                  {sessions.map((s) => (
                    <option key={s.id} value={s.id}>{String(s.name)}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">Roll Number</label>
                <input
                  type="text"
                  name="roll"
                  required
                  defaultValue={roll}
                  placeholder="e.g. 101"
                  className={selectClass}
                />
              </div>
            </div>
            <div className="mt-6 text-center">
              <button
                type="submit"
                className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-10 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700 hover:shadow-xl"
              >
                <svg className="h-5 w-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Search Results
              </button>
            </div>
          </form>
        </div>

        {filled ? (
          <div className="mt-10">
            {portfolio && student ? (
              <>
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                  <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                      <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600">
                        {studentName.slice(0, 1) || "S"}
                      </div>
                      <div>
                        <h2 className="text-xl font-bold text-slate-900">{studentName || "Student"}</h2>
                        <p className="text-sm text-slate-500">
                          {String((student.school_classes as Record<string, unknown> | null)?.name ?? "")} · Roll: {String(student.roll_number ?? student.roll_no ?? "—")}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-4">
                      <div className="relative h-20 w-20">
                        <svg className="h-20 w-20 -rotate-90" viewBox="0 0 36 36">
                          <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" strokeWidth="3" />
                          <circle
                            cx="18"
                            cy="18"
                            r="15.5"
                            fill="none"
                            stroke={percentage >= 60 ? "#22c55e" : percentage >= 40 ? "#eab308" : "#ef4444"}
                            strokeWidth="3"
                            strokeDasharray={`${percentage * 0.865} 100`}
                            strokeLinecap="round"
                          />
                        </svg>
                        <span className="absolute inset-0 flex items-center justify-center text-lg font-bold text-slate-900">{percentage}%</span>
                      </div>
                      <div className="text-center">{gradePill(grade)}</div>
                    </div>
                  </div>
                </div>

                {Array.from(grouped.entries()).map(([examName, rows]) => (
                  <div key={examName} className="mt-8">
                    <h3 className="mb-3 text-lg font-semibold text-slate-800">{examName}</h3>
                    <div className="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                      <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                          <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <tr>
                              <th className="px-5 py-3.5">Subject</th>
                              <th className="px-5 py-3.5 text-right">Marks</th>
                              <th className="px-5 py-3.5 text-center">Grade</th>
                              <th className="px-5 py-3.5">Remarks</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-slate-100 bg-white">
                            {rows.map((r) => {
                              const exam = (r.exams ?? {}) as Record<string, unknown>;
                              const subject = (exam.subjects ?? null) as Record<string, unknown> | null;
                              const marks = Number(r.obtained_marks ?? 0);
                              const total = Number(exam.total_marks ?? 100);
                              const low = marks < total * 0.4;
                              return (
                                <tr key={String(r.id ?? "")} className="transition-colors hover:bg-slate-50">
                                  <td className="px-5 py-3 font-medium text-slate-900">{String(subject?.name ?? "—")}</td>
                                  <td className="px-5 py-3 text-right font-mono">
                                    <span className={low ? "font-semibold text-red-600" : "text-slate-900"}>{String(r.obtained_marks ?? "—")}</span>
                                    <span className="text-slate-400"> / {String(total)}</span>
                                  </td>
                                  <td className="px-5 py-3 text-center">{gradePill(String(r.grade ?? "—"), true)}</td>
                                  <td className="px-5 py-3 text-slate-500">{String(r.remarks ?? "")}</td>
                                </tr>
                              );
                            })}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                ))}

                <div className="mt-10 flex flex-wrap gap-3">
                  <button
                    onClick={() => window.print()}
                    className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:shadow-md"
                  >
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Result
                  </button>
                  <Link
                    href={`/results/pdf?class_id=${classId}&academic_session_id=${sessionId}&roll=${encodeURIComponent(roll)}`}
                    className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                  >
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                  </Link>
                </div>
              </>
            ) : (
              <div className="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center">
                <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p className="mt-4 text-sm text-slate-500">No results found for the given roll number.</p>
              </div>
            )}
          </div>
        ) : null}
      </div>
    </div>
  );
}