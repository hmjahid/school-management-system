import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { getSiteSettings } from "@/lib/site-settings";

/**
 * Final bespoke screens — marksheet-pdf (print), payslip-show, payroll
 * structures, sms preview, software/about, testimonials print, careers form.
 */

export async function MarksheetPdf({ studentId, examId }: { studentId?: number; examId?: number }) {
  const settings = await getSiteSettings();
  const student = studentId ? await prisma.students.findFirst({ where: { id: studentId } }) : null;
  const results = studentId && examId
    ? await prisma.exam_results.findMany({ where: { student_id: studentId, exam_id: examId, is_published: true }, include: { exams: true } })
    : [];
  const exam = results[0]?.exams as Record<string, unknown> | undefined;

  return (
    <div className="p-5">
      <div className="mx-auto max-w-3xl bg-white p-8">
        <div className="border-b-2 border-blue-700 pb-4 text-center">
          <div className="text-xl font-bold text-blue-700">{settings.schoolName}</div>
          <div className="text-xs text-slate-500">Marksheet</div>
        </div>
        <div className="mt-4 grid grid-cols-2 gap-2 text-sm">
          <div><b>Student:</b> {student ? `${String(student.first_name ?? "")} ${String(student.last_name ?? "")}` : "—"}</div>
          <div className="text-right"><b>Roll:</b> {student ? String(student.roll_number ?? student.roll_no ?? "—") : "—"}</div>
          <div><b>Exam:</b> {exam ? String(exam.name ?? "") : "—"}</div>
        </div>
        <table className="mt-4 w-full border-collapse text-sm">
          <thead>
            <tr>
              <th className="border border-slate-300 bg-blue-50 px-2 py-1 text-left text-xs uppercase">Subject</th>
              <th className="border border-slate-300 bg-blue-50 px-2 py-1 text-right text-xs uppercase">Marks</th>
              <th className="border border-slate-300 bg-blue-50 px-2 py-1 text-right text-xs uppercase">Grade</th>
            </tr>
          </thead>
          <tbody>
            {results.map((r) => (
              <tr key={r.id}>
                <td className="border border-slate-300 px-2 py-1">{String(r.remarks ?? "—")}</td>
                <td className="border border-slate-300 px-2 py-1 text-right">{String(r.obtained_marks ?? "—")}</td>
                <td className="border border-slate-300 px-2 py-1 text-right">{String(r.grade ?? "—")}</td>
              </tr>
            ))}
            {results.length === 0 ? <tr><td colSpan={3} className="border border-slate-300 px-2 py-6 text-center text-slate-400">No published results.</td></tr> : null}
          </tbody>
        </table>
        <div className="mt-6 text-center text-xs text-slate-500">
          <p>Authorized signature: ___________________</p>
        </div>
      </div>
      <div className="mt-6 text-center">
        <button onClick={() => window.print()} className="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Print / Save PDF</button>
      </div>
    </div>
  );
}

export async function PayslipShow({ id }: { id?: number }) {
  const settings = await getSiteSettings();
  const slip = id ? await prisma.payslips.findFirst({ where: { id }, include: { teachers: true } }) : null;
  if (!slip) return <p className="p-6 text-slate-500">Payslip not found.</p>;
  const teacher = (slip.teachers ?? {}) as Record<string, unknown>;

  return (
    <div className="p-5">
      <div className="mx-auto max-w-2xl rounded-2xl border-2 border-slate-300 bg-white p-8">
        <div className="border-b-2 border-slate-300 pb-4 text-center">
          <div className="text-xl font-bold text-slate-900">{settings.schoolName}</div>
          <div className="text-xs text-slate-500">Payslip #{slip.id} · {slip.month} / {slip.year}</div>
        </div>
        <div className="mt-4 text-sm"><b>Teacher:</b> {String(teacher.name ?? `#${slip.teacher_id}`)}</div>
        <table className="mt-4 w-full text-sm">
          <tbody>
            <tr className="border-b border-slate-100"><td className="py-1.5 text-slate-600">Basic</td><td className="py-1.5 text-right font-mono">{Number(slip.basic).toLocaleString()}</td></tr>
            <tr className="border-b border-slate-100"><td className="py-1.5 text-slate-600">Allowances</td><td className="py-1.5 text-right font-mono">{Number(slip.total_allowances).toLocaleString()}</td></tr>
            <tr className="border-b border-slate-100"><td className="py-1.5 text-slate-600">Deductions</td><td className="py-1.5 text-right font-mono">{Number(slip.total_deductions).toLocaleString()}</td></tr>
            <tr className="font-semibold"><td className="py-2 text-slate-900">Net pay</td><td className="py-2 text-right font-mono text-slate-900">{Number(slip.net_salary).toLocaleString()}</td></tr>
          </tbody>
        </table>
        <div className="mt-6 text-center text-xs text-slate-500">Authorized signature: ___________________</div>
      </div>
      <div className="mt-6 text-center">
        <button onClick={() => window.print()} className="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Print / Save PDF</button>
      </div>
    </div>
  );
}

export async function PayrollStructures() {
  const structures = await prisma.salary_structures.findMany({ orderBy: { effective_from: "desc" } }).catch(() => []);
  const rows = structures as unknown as Array<Record<string, unknown>>;

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Payroll structures</h1>
        <p className="mt-1 text-sm text-slate-600">Salary structures with basic and allowance components.</p>
      </div>
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th className="px-4 py-3">Teacher</th><th className="px-4 py-3 text-right">Basic</th><th className="px-4 py-3">Active</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((s) => (
              <tr key={String(s.id ?? "")} className="admin-table-row">
                <td className="px-4 py-3 font-medium text-slate-900">#{String(s.teacher_id ?? "—")}</td>
                <td className="px-4 py-3 text-right text-slate-700">{Number(s.basic ?? 0).toLocaleString()}</td>
                <td className="px-4 py-3">{s.is_active ? <span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Active</span> : <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Inactive</span>}</td>
              </tr>
            ))}
            {rows.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No structures yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function SmsPreview() {
  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/sms" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Bulk SMS</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">SMS preview</h1>
      </div>
      <div className="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
          Dear parent, your child {`{{student_name}}`} ({`{{roll}}`}) has an outstanding fee balance. Please clear it at your earliest convenience. Thank you.
        </div>
        <div className="mt-3 text-xs text-slate-400">160 characters · 1 SMS per recipient</div>
        <button className="mt-4 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Send</button>
      </div>
    </div>
  );
}

export async function SoftwareAbout() {
  const settings = await getSiteSettings();
  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">About the software</h1>
      </div>
      <div className="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p className="text-sm text-slate-600">Eskoofy is a complete school management system. This is the Node.js variant, a full-featured mirror of the Laravel application.</p>
        <dl className="mt-4 space-y-2 text-sm">
          <div className="flex justify-between"><dt className="text-slate-500">School</dt><dd className="text-slate-900">{settings.schoolName}</dd></div>
          <div className="flex justify-between"><dt className="text-slate-500">Variant</dt><dd className="text-slate-900">Node.js (Next.js App Router)</dd></div>
          <div className="flex justify-between"><dt className="text-slate-500">Version</dt><dd className="text-slate-900">1.0.0</dd></div>
        </dl>
      </div>
    </div>
  );
}

export async function TestimonialsPrint() {
  const testimonials = await prisma.testimonials.findMany({ take: 50 });
  return (
    <div className="p-5">
      <div className="mx-auto max-w-2xl">
        {testimonials.map((item) => (
          <blockquote key={item.id} className="mb-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p className="text-sm text-slate-700">&ldquo;{String(item.content ?? "")}&rdquo;</p>
            <footer className="mt-3 text-sm font-medium text-slate-900">— {String(item.author_name ?? item.name ?? "Anonymous")}</footer>
          </blockquote>
        ))}
      </div>
      <div className="mt-6 text-center">
        <button onClick={() => window.print()} className="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Print / Save PDF</button>
      </div>
    </div>
  );
}

export async function CareersForm() {
  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/careers" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Careers</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">Post a job opening</h1>
      </div>
      <form className="max-w-2xl space-y-4">
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Job title</label>
          <input name="title" className="admin-input w-full" />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-slate-700">Description</label>
          <textarea name="description" rows={5} className="admin-input w-full" />
        </div>
        <button className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Post job</button>
      </form>
    </div>
  );
}
