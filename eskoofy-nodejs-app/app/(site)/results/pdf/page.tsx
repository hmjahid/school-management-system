import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { getSiteSettings } from "@/lib/site-settings";

export const dynamic = "force-dynamic";

type ExamRow = Record<string, unknown>;

type StudentRow = {
  id: number;
  batch_id: number | null;
  roll_number: string | null;
  roll_no: string | null;
  admission_number: string | null;
  users: { name: string } | null;
  school_classes: { name: string } | null;
  sections: { name: string } | null;
};

export default async function ResultsPdfPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const classId = Number(sp.class_id) || 0;
  const sessionId = Number(sp.academic_session_id) || 0;
  const roll = String(sp.roll ?? "");
  const examId = Number(sp.exam_id) || 0;

  const settings = await getSiteSettings();

  const student = await safe(
    () =>
      prisma.students.findFirst({
        where: {
          ...(classId ? { class_id: classId } : {}),
          OR: [{ roll_number: roll }, { roll_no: roll }],
          deleted_at: null,
        },
        include: {
          users: { select: { name: true } },
          school_classes: { select: { name: true } },
          sections: { select: { name: true } },
        },
      }),
    null as StudentRow | null,
  );

  if (!student) {
    return <div className="p-16 text-center text-slate-500">Student not found — check the roll number.</div>;
  }

  const results = await safe(
    () =>
      prisma.exam_results.findMany({
        where: {
          student_id: student.id,
          ...(examId ? { exam_id: examId } : {}),
          is_published: true,
          exams: {
            is_published_to_public: true,
            ...(sessionId ? { academic_session_id: sessionId } : {}),
            batch_id: student.batch_id,
          },
        },
        include: { exams: { include: { subjects: true } } },
        take: 200,
      }),
    [] as ExamRow[],
  );

  const grouped = new Map<string, ExamRow[]>();
  for (const row of results) {
    const name = String(((row.exams as Record<string, unknown> | null)?.name as string | undefined) ?? "Exam");
    const list = grouped.get(name) ?? [];
    list.push(row);
    grouped.set(name, list);
  }

  const totalObtained = results.reduce((sum, r) => sum + Number(r.obtained_marks ?? 0), 0);
  const totalMax = results.reduce((sum, r) => sum + Number((r.exams as Record<string, unknown> | null)?.total_marks ?? 0), 0);
  const percentage = totalMax > 0 ? Math.round((totalObtained / totalMax) * 1000) / 10 : 0;
  const grade =
    percentage >= 80 ? "A+" : percentage >= 70 ? "A" : percentage >= 60 ? "A-" : percentage >= 50 ? "B" : percentage >= 40 ? "C" : "F";

  return (
    <div className="mx-auto max-w-3xl bg-white p-6 text-slate-800" style={{ fontFamily: "DejaVu Sans, sans-serif" }}>
      <div className="border-b-2 border-blue-700 pb-3 text-center">
        <div className="text-xl font-bold text-blue-700">{settings.schoolName}</div>
        {settings.tagline ? <div className="text-xs text-slate-500">{settings.tagline}</div> : null}
        {settings.address ? <div className="text-xs text-slate-500">{settings.address}</div> : null}
      </div>

      <div className="mt-5 flex items-start justify-between text-[13px]" style={{ marginBottom: 16 }}>
        <div>
          <div><b>Student:</b> {String(student.users?.name ?? "")}</div>
          <div><b>Class:</b> {String(student.school_classes?.name ?? "—")}</div>
          <div><b>Section:</b> {String(student.sections?.name ?? "—")}</div>
        </div>
        <div className="text-right">
          <div><b>Roll:</b> {String(student.roll_number ?? student.roll_no ?? "—")}</div>
          <div><b>Admission No:</b> {String(student.admission_number ?? "—")}</div>
        </div>
      </div>

      {Array.from(grouped.entries()).map(([examName, rows]) => (
        <div key={examName} className="mt-5">
          <div className="text-sm font-bold text-blue-900" style={{ margin: "18px 0 6px", color: "#1e3a8a" }}>{examName}</div>
          <table className="w-full border-collapse text-xs">
            <thead>
              <tr>
                <th className="border border-slate-300 bg-blue-50 px-2 py-1.5 text-left text-[10px] uppercase tracking-wide">Subject</th>
                <th className="border border-slate-300 bg-blue-50 px-2 py-1.5 text-right text-[10px] uppercase tracking-wide">Marks</th>
                <th className="border border-slate-300 bg-blue-50 px-2 py-1.5 text-right text-[10px] uppercase tracking-wide">Grade</th>
                <th className="border border-slate-300 bg-blue-50 px-2 py-1.5 text-left text-[10px] uppercase tracking-wide">Remarks</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r) => {
                const exam = (r.exams ?? {}) as Record<string, unknown>;
                const subject = (exam.subjects ?? null) as Record<string, unknown> | null;
                return (
                  <tr key={String(r.id ?? "")}>
                    <td className="border border-slate-300 px-2 py-1.5">{String(subject?.name ?? "—")}</td>
                    <td className="border border-slate-300 px-2 py-1.5 text-right">
                      {String(r.obtained_marks ?? "—")} / {String(exam.total_marks ?? "—")}
                    </td>
                    <td className="border border-slate-300 px-2 py-1.5 text-right">{String(r.grade ?? "—")}</td>
                    <td className="border border-slate-300 px-2 py-1.5">{String(r.remarks ?? "")}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      ))}

      <div className="mt-6 text-[13px]">
        <span className="mr-6"><b>Total:</b> {totalObtained} / {totalMax}</span>
        <span className="mr-6"><b>Percentage:</b> {percentage}%</span>
        <span className="mr-6"><b>Grade:</b> {grade}</span>
      </div>

      <div className="mt-8 text-center text-[11px] text-slate-400">
        This is a computer-generated marksheet and does not require a signature.
      </div>

      <div className="mt-8 flex justify-center">
        <button onClick={() => window.print()} className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
          Print / Save PDF
        </button>
      </div>
    </div>
  );
}