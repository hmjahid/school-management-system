import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";
import { formatDate } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function ResultsPdfPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const roll = String(sp.roll ?? "");
  const examId = Number(sp.exam_id) || 0;

  const student = await prisma.students.findFirst({
    where: { OR: [{ roll_number: roll }, { roll_no: roll }], deleted_at: null },
  });

  if (!student) {
    return <div className="p-16 text-center text-slate-500">Student not found — check the roll number.</div>;
  }

  const results = await prisma.exam_results.findMany({
    where: { student_id: student.id, ...(examId ? { exam_id: examId } : {}), is_published: true },
    include: { exams: true },
    take: 100,
  });

  const grouped = new Map<number, { exam: Record<string, unknown>; rows: Record<string, unknown>[] }>();
  for (const row of results) {
    const exam = (row.exams ?? {}) as Record<string, unknown>;
    const key = Number(exam.id ?? 0);
    if (!grouped.has(key)) grouped.set(key, { exam, rows: [] });
    grouped.get(key)!.rows.push(row);
  }

  return (
    <div className="mx-auto max-w-4xl bg-white p-8 text-slate-900">
      <div className="border-b-2 border-blue-700 pb-4 text-center">
        <div className="text-xl font-bold text-blue-700">Eskoofy School</div>
        <div className="text-xs text-slate-500">Marksheet</div>
      </div>
      <div className="mt-4 flex items-start justify-between text-sm">
        <div>
          <div><b>Student:</b> {String(student.first_name ?? "")} {String(student.last_name ?? "")}</div>
          <div><b>Roll:</b> {String(student.roll_number ?? student.roll_no ?? "—")}</div>
        </div>
        <div className="text-right">
          <div><b>Admission No:</b> {String(student.admission_number ?? "—")}</div>
          <div><b>Class:</b> {String(student.class_id ?? "—")}</div>
        </div>
      </div>

      {Array.from(grouped.entries()).map(([examId, { exam, rows }]) => {
        const marks = rows.map((r) => Number(r.obtained_marks ?? 0)).filter((n) => n > 0);
        const total = marks.reduce((a, b) => a + b, 0);
        return (
          <div key={examId} className="mt-6">
            <div className="text-sm font-bold text-blue-900">{String(exam.name ?? "")}</div>
            <table className="mt-2 w-full border-collapse text-sm">
              <thead>
                <tr>
                  <th className="border border-slate-300 bg-blue-50 px-2 py-1 text-left text-xs uppercase">Subject</th>
                  <th className="border border-slate-300 bg-blue-50 px-2 py-1 text-right text-xs uppercase">Marks</th>
                  <th className="border border-slate-300 bg-blue-50 px-2 py-1 text-right text-xs uppercase">Grade</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={String(r.id ?? "")}>
                    <td className="border border-slate-300 px-2 py-1">{String(r.remarks ?? "—")}</td>
                    <td className="border border-slate-300 px-2 py-1 text-right">{String(r.obtained_marks ?? "—")}</td>
                    <td className="border border-slate-300 px-2 py-1 text-right">{String(r.grade ?? "—")}</td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr>
                  <td className="border border-slate-300 px-2 py-1 font-semibold">Total</td>
                  <td className="border border-slate-300 px-2 py-1 text-right font-semibold">{total}</td>
                  <td className="border border-slate-300 px-2 py-1" />
                </tr>
              </tfoot>
            </table>
          </div>
        );
      })}

      <button onClick={() => window.print()} className="mt-8 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
        Print / Save PDF
      </button>
    </div>
  );
}
