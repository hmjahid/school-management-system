import Link from "next/link";
import { prisma } from "@/lib/prisma";

/**
 * Exam results + payroll — mirror the app's
 * dashboard/exams/results.blade.php, exams/marksheet-pdf.blade.php and
 * dashboard/payroll/{payslips,payslip-show,structures}.blade.php.
 */

export async function ExamResults({ examId }: { examId?: number }) {
  const [exams, results] = await Promise.all([
    prisma.exams.findMany({ orderBy: { name: "asc" } }),
    examId
      ? prisma.exam_results.findMany({ where: { exam_id: examId }, include: { exams: true, students: true }, take: 200 })
      : Promise.resolve([]),
  ]);
  const exam = examId ? exams.find((e) => e.id === examId) : undefined;

  return (
    <div>
      <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <Link href="/dashboard/exams" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Exams</Link>
          <h1 className="mt-1 text-2xl font-bold text-slate-900">{exam ? String(exam.name) : "Results"}</h1>
        </div>
        <form method="get" className="flex items-end gap-2">
          <select name="exam_id" defaultValue={examId ?? ""} className="admin-select">
            <option value="">Select exam</option>
            {exams.map((e) => <option key={e.id} value={e.id}>{String(e.name)}</option>)}
          </select>
          <button className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Load</button>
        </form>
      </div>

      {!examId ? (
        <div className="rounded-xl border border-dashed border-slate-200 p-12 text-center text-sm text-slate-400">Pick an exam to see its results.</div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
          <table className="min-w-full divide-y divide-slate-200 text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
              <tr>
                <th className="px-4 py-3">Student</th>
                <th className="px-4 py-3 text-right">Marks</th>
                <th className="px-4 py-3">Grade</th>
                <th className="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {results.map((r) => {
                const student = (r.students ?? {}) as Record<string, unknown>;
                return (
                  <tr key={r.id} className="admin-table-row">
                    <td className="px-4 py-3 font-medium text-slate-900">{String(student.first_name ?? "")} {String(student.last_name ?? "")}</td>
                    <td className="px-4 py-3 text-right text-slate-900">{String(r.obtained_marks ?? "—")}</td>
                    <td className="px-4 py-3 text-slate-700">{String(r.grade ?? "—")}</td>
                    <td className="px-4 py-3"><span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">{String(r.status ?? "pending")}</span></td>
                  </tr>
                );
              })}
              {results.length === 0 ? <tr><td colSpan={4} className="px-4 py-8 text-center text-slate-400">No results for this exam.</td></tr> : null}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

export async function Payslips() {
  const payslips = await prisma.payslips.findMany({ orderBy: [{ year: "desc" }, { month: "desc" }], take: 100 });

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Payslips</h1>
        <p className="mt-1 text-sm text-slate-600">All generated payslips.</p>
      </div>
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Payslip</th>
              <th className="px-4 py-3">Period</th>
              <th className="px-4 py-3 text-right">Basic</th>
              <th className="px-4 py-3 text-right">Allowances</th>
              <th className="px-4 py-3 text-right">Deductions</th>
              <th className="px-4 py-3 text-right">Net</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {payslips.map((p) => (
              <tr key={p.id} className="admin-table-row">
                <td className="px-4 py-3 font-medium text-slate-900">#{p.id}</td>
                <td className="px-4 py-3 text-slate-700">{p.month} / {p.year}</td>
                <td className="px-4 py-3 text-right text-slate-700">{Number(p.basic).toLocaleString()}</td>
                <td className="px-4 py-3 text-right text-slate-700">{Number(p.total_allowances).toLocaleString()}</td>
                <td className="px-4 py-3 text-right text-slate-700">{Number(p.total_deductions).toLocaleString()}</td>
                <td className="px-4 py-3 text-right font-semibold text-slate-900">{Number(p.net_salary).toLocaleString()}</td>
                <td className="px-4 py-3"><span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-slate-600">{String(p.status)}</span></td>
              </tr>
            ))}
            {payslips.length === 0 ? <tr><td colSpan={7} className="px-4 py-8 text-center text-slate-400">No payslips yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}
