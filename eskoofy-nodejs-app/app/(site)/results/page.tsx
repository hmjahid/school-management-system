import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";
import { Empty, Section, formatDate, PageHero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

const inputClass =
  "w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

interface ResultRow {
  id: unknown;
  obtained_marks: unknown;
  grade: unknown;
  remarks: unknown;
  examTitle: unknown;
  totalMarks: unknown;
  examDate: unknown;
}

async function lookupResults(roll: string, examId: number): Promise<{ student: string; rows: ResultRow[] } | null> {
  try {
    const student = (await prisma.students.findFirst({
      where: { OR: [{ roll_number: roll }, { roll_no: roll }], deleted_at: null },
    })) as Record<string, unknown> | null;
    if (!student) return null;

    const results = (await prisma.exam_results.findMany({
      where: { student_id: Number(student.id), ...(examId ? { exam_id: examId } : {}) },
      include: { exams: true },
      take: 50,
    })) as unknown as Array<Record<string, unknown>>;

    return {
      student: `${String(student.first_name ?? "")} ${String(student.last_name ?? "")}`.trim() || roll,
      rows: results.map((row) => {
        const exam = (row.exams ?? {}) as Record<string, unknown>;
        return {
          id: row.id,
          obtained_marks: row.obtained_marks,
          grade: row.grade,
          remarks: row.remarks,
          examTitle: exam.name ?? exam.title ?? `Exam #${String(row.exam_id ?? "")}`,
          totalMarks: exam.total_marks,
          examDate: exam.start_date ?? exam.created_at,
        };
      }),
    };
  } catch {
    return null;
  }
}

export default async function ResultsPage({
  searchParams,
}: {
  searchParams: Promise<{ roll?: string; exam?: string }>;
}) {
  const params = await searchParams;
  const roll = (params.roll ?? "").trim();
  const examId = Number(params.exam ?? 0);
  const outcome = roll ? await lookupResults(roll, examId) : null;

  return (
    <>
      <PageHero title={t("site.nav.results")} />

      <Section>
        <div className="mx-auto max-w-3xl space-y-6">
          <form method="get" className="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5">
            <div className="flex-1">
              <label htmlFor="roll" className="mb-1 block text-sm font-semibold">
                Roll number
              </label>
              <input id="roll" name="roll" defaultValue={roll} required className={inputClass} />
            </div>
            <div className="w-40">
              <label htmlFor="exam" className="mb-1 block text-sm font-semibold">
                Exam id (optional)
              </label>
              <input id="exam" name="exam" defaultValue={examId || ""} inputMode="numeric" className={inputClass} />
            </div>
            <button className="rounded-xl bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">Look up</button>
          </form>

          {roll ? (
            outcome && outcome.rows.length > 0 ? (
              <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div className="border-b border-slate-100 px-5 py-4">
                  <h2 className="font-bold text-slate-900">{outcome.student}</h2>
                  <p className="text-xs text-slate-400">Roll {roll}</p>
                </div>
                <table className="w-full text-sm">
                  <thead className="bg-slate-50 text-left text-xs uppercase text-slate-400">
                    <tr>
                      <th className="px-5 py-3">Exam</th>
                      <th className="px-5 py-3">Date</th>
                      <th className="px-5 py-3 text-right">Obtained</th>
                      <th className="px-5 py-3 text-right">Total</th>
                      <th className="px-5 py-3">Grade</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {outcome.rows.map((row, index) => (
                      <tr key={index}>
                        <td className="px-5 py-3 font-medium text-slate-800">{String(row.examTitle ?? "")}</td>
                        <td className="px-5 py-3 text-slate-500">{formatDate(row.examDate)}</td>
                        <td className="px-5 py-3 text-right">{String(row.obtained_marks ?? "")}</td>
                        <td className="px-5 py-3 text-right text-slate-400">{String(row.totalMarks ?? "")}</td>
                        <td className="px-5 py-3">{String(row.grade ?? "—")}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <Empty>No published results found for that roll number.</Empty>
            )
          ) : null}
        </div>
      </Section>
    </>
  );
}
