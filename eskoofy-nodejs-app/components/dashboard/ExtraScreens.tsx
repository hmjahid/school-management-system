import Link from "next/link";
import { prisma } from "@/lib/prisma";

/**
 * Remaining bespoke dashboard screens — mirror the app's settings tabs,
 * reports/attendance, students/results, staff-attendance/report,
 * assignments/submissions, batch generation and payslip-show/structures.
 */

export async function SettingsTab({ tab }: { tab: "about" | "cms" | "global-labels" }) {
  const title = tab === "about" ? "About" : tab === "cms" ? "CMS settings" : "Global labels";
  const fields = tab === "global-labels"
    ? ["school_name", "tagline", "hero_headline", "hero_subtitle", "cta_apply", "cta_contact", "footer_about"]
    : ["title", "content", "meta_description"];

  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/settings" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Settings</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">{title}</h1>
      </div>
      <div className="mb-6 flex gap-2">
        {["general", "about", "cms", "global-labels"].map((t) => (
          <Link key={t} href={`/dashboard/settings/${t === "general" ? "" : t}`} className={`rounded-lg px-3 py-2 text-sm font-medium ${tab === t ? "bg-brand-50 text-brand-700" : "text-slate-600 hover:bg-slate-50"}`}>
            {t === "global-labels" ? "Global labels" : t === "cms" ? "CMS" : t.charAt(0).toUpperCase() + t.slice(1)}
          </Link>
        ))}
      </div>
      <form className="max-w-2xl space-y-4">
        {fields.map((f) => (
          <div key={f}>
            <label className="mb-1 block text-sm font-medium capitalize text-slate-700">{f.replace(/_/g, " ")}</label>
            <input name={f} className="admin-input w-full" />
          </div>
        ))}
        <button className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save</button>
      </form>
    </div>
  );
}

export async function AttendanceReport({ from, to }: { from?: string; to?: string }) {
  const f = from ?? new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10);
  const tt = to ?? new Date().toISOString().slice(0, 10);
  const rows = await prisma.attendances.groupBy({ by: ["status", "date"], _count: { _all: true }, where: { date: { gte: new Date(f), lte: new Date(tt) } }, orderBy: { date: "desc" } });

  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/reports" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Reports</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">Attendance report</h1>
      </div>
      <div className="mb-4 flex items-end gap-2">
        <input type="date" name="from" defaultValue={f} className="admin-input" />
        <input type="date" name="to" defaultValue={tt} className="admin-input" />
        <button className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Apply</button>
      </div>
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th className="px-4 py-3">Date</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Count</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((r, i) => (
              <tr key={i} className="admin-table-row">
                <td className="px-4 py-3 text-slate-700">{r.date ? new Date(String(r.date)).toISOString().slice(0, 10) : "—"}</td>
                <td className="px-4 py-3 capitalize text-slate-900">{String(r.status)}</td>
                <td className="px-4 py-3 text-right text-slate-900">{r._count._all}</td>
              </tr>
            ))}
            {rows.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No attendance in this period.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function StudentResults({ studentId }: { studentId?: number }) {
  const results = studentId
    ? await prisma.exam_results.findMany({ where: { student_id: studentId }, include: { exams: true }, take: 100 })
    : [];

  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/students" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Students</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">Student results</h1>
      </div>
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th className="px-4 py-3">Exam</th><th className="px-4 py-3 text-right">Marks</th><th className="px-4 py-3">Grade</th><th className="px-4 py-3">Status</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {results.map((r) => {
              const exam = (r.exams ?? {}) as Record<string, unknown>;
              return (
                <tr key={r.id} className="admin-table-row">
                  <td className="px-4 py-3 font-medium text-slate-900">{String(exam.name ?? "Exam")}</td>
                  <td className="px-4 py-3 text-right text-slate-900">{String(r.obtained_marks ?? "—")}</td>
                  <td className="px-4 py-3 text-slate-700">{String(r.grade ?? "—")}</td>
                  <td className="px-4 py-3 capitalize text-slate-600">{String(r.status ?? "—")}</td>
                </tr>
              );
            })}
            {results.length === 0 ? <tr><td colSpan={4} className="px-4 py-8 text-center text-slate-400">No results for this student.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function StaffAttendanceReport({ from, to }: { from?: string; to?: string }) {
  const f = from ?? new Date(Date.now() - 30 * 86400000).toISOString().slice(0, 10);
  const tt = to ?? new Date().toISOString().slice(0, 10);
  const rows = await prisma.staff_attendances.groupBy({ by: ["status", "date"], _count: { _all: true }, where: { date: { gte: new Date(f), lte: new Date(tt) } }, orderBy: { date: "desc" } });

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Staff attendance report</h1>
      </div>
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th className="px-4 py-3">Date</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Count</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((r, i) => (
              <tr key={i} className="admin-table-row">
                <td className="px-4 py-3 text-slate-700">{r.date ? new Date(String(r.date)).toISOString().slice(0, 10) : "—"}</td>
                <td className="px-4 py-3 capitalize text-slate-900">{String(r.status)}</td>
                <td className="px-4 py-3 text-right text-slate-900">{r._count._all}</td>
              </tr>
            ))}
            {rows.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No staff attendance in this period.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function AssignmentsSubmissions() {
  const submissions = await prisma.assignment_submissions.findMany({ orderBy: { created_at: "desc" }, take: 100 }).catch(() => []);
  const rows = submissions as unknown as Array<Record<string, unknown>>;

  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/assignments" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Assignments</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">Submissions</h1>
      </div>
      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr><th className="px-4 py-3">ID</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Submitted</th></tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((s) => (
              <tr key={String(s.id ?? "")} className="admin-table-row">
                <td className="px-4 py-3 font-medium text-slate-900">#{String(s.id ?? "")}</td>
                <td className="px-4 py-3 capitalize text-slate-700">{String(s.status ?? "submitted")}</td>
                <td className="px-4 py-3 text-slate-600">{s.created_at ? new Date(String(s.created_at)).toISOString().slice(0, 10) : "—"}</td>
              </tr>
            ))}
            {rows.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No submissions yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function BatchGenerate({ kind }: { kind: "admit-cards" | "student-id-cards" }) {
  const students = await prisma.students.findMany({ where: { deleted_at: null }, take: 100 });
  const title = kind === "admit-cards" ? "Batch admit cards" : "Batch student ID cards";

  return (
    <div>
      <div className="mb-6">
        <Link href={`/dashboard/${kind}`} className="text-sm font-medium text-brand-600 hover:text-brand-800">← {kind.replace(/-/g, " ")}</Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900">{title}</h1>
      </div>
      <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 className="mb-4 text-base font-semibold text-slate-900">Select students</h2>
        <ul className="max-h-96 space-y-1 overflow-y-auto">
          {students.map((s) => (
            <li key={s.id} className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm">
              <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-brand-600" />
              <span className="font-medium text-slate-800">{String(s.first_name ?? "")} {String(s.last_name ?? "")}</span>
              <span className="ml-auto font-mono text-xs text-slate-500">{String(s.roll_number ?? "")}</span>
            </li>
          ))}
        </ul>
        <button className="mt-4 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Generate {kind.replace(/-/g, " ")}</button>
      </div>
    </div>
  );
}
