import { prisma } from "@/lib/prisma";

/**
 * Bulk/misc workflow screens — mirror the app's blades:
 * attendance/bulk, payroll/generate, careers/applications,
 * assignments/submissions, staff-attendance/report.
 */

export async function BulkAttendance({ date }: { date?: string }) {
  const today = date ?? new Date().toISOString().slice(0, 10);
  const students = await prisma.students.findMany({
    where: { deleted_at: null },
    orderBy: { roll_number: "asc" },
    take: 100,
  });

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Bulk attendance</h1>
        <p className="mt-1 text-sm text-slate-600">Mark attendance for an entire class or section in one screen.</p>
      </div>

      <form method="get" className="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-4">
        <div>
          <label className="mb-1.5 block text-xs font-semibold text-slate-600">Date</label>
          <input type="date" name="date" defaultValue={today} className="admin-input" />
        </div>
        <div>
          <label className="mb-1.5 block text-xs font-semibold text-slate-600">Section</label>
          <select name="section_id" className="admin-select"><option value="">All sections</option></select>
        </div>
        <div className="flex items-end">
          <button className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Load</button>
        </div>
      </form>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Roll</th>
              <th className="px-4 py-3">Student</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {students.map((s) => (
              <tr key={s.id} className="admin-table-row">
                <td className="px-4 py-3 font-mono text-xs text-slate-600">{String(s.roll_number ?? "")}</td>
                <td className="px-4 py-3 font-medium text-slate-900">{String(s.first_name ?? "")} {String(s.last_name ?? "")}</td>
                <td className="px-4 py-3">
                  <select defaultValue="present" className="admin-select"><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option></select>
                </td>
              </tr>
            ))}
            {students.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No students.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function GeneratePayslips({ month, year }: { month?: string; year?: string }) {
  const m = Number(month) || new Date().getMonth() + 1;
  const y = Number(year) || new Date().getFullYear();
  const payslips = await prisma.payslips.findMany({
    where: { month: m, year: y },
    take: 100,
  });

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Generate payslips</h1>
        <p className="mt-1 text-sm text-slate-600">{m} / {y}</p>
      </div>

      <form method="get" className="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
        <select name="month" className="admin-select">
          {Array.from({ length: 12 }, (_, i) => i + 1).map((mm) => (
            <option key={mm} value={mm} selected={mm === m}>{new Date(2024, mm - 1, 1).toLocaleString("en", { month: "long" })}</option>
          ))}
        </select>
        <input type="number" name="year" min={2020} max={2099} defaultValue={y} className="admin-input" />
        <button className="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Preview</button>
      </form>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Payslip</th>
              <th className="px-4 py-3">Month</th>
              <th className="px-4 py-3 text-right">Net</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {payslips.map((p) => (
              <tr key={p.id} className="admin-table-row">
                <td className="px-4 py-3 font-medium text-slate-900">{`#${p.id}`}</td>
                <td className="px-4 py-3 text-slate-700">{p.month} / {p.year}</td>
                <td className="px-4 py-3 text-right text-slate-900">{Number(p.net_salary ?? 0).toLocaleString()}</td>
              </tr>
            ))}
            {payslips.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No payslips for this period.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function CareersApplications() {
  const applications = await prisma.job_applications.findMany({
    orderBy: { created_at: "desc" },
    take: 100,
  });

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">Career Applications</h1>
        <p className="mt-1 text-sm text-slate-600">Review job applications and manage hiring workflow.</p>
      </div>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Applicant</th>
              <th className="px-4 py-3">Email</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {applications.map((a) => (
              <tr key={a.id} className="admin-table-row">
                <td className="px-4 py-3 font-medium text-slate-900">{String(a.name ?? `#${a.id}`)}</td>
                <td className="px-4 py-3 text-slate-600">{String(a.email ?? "—")}</td>
                <td className="px-4 py-3"><span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold capitalize text-slate-600">{String(a.status ?? "pending")}</span></td>
              </tr>
            ))}
            {applications.length === 0 ? <tr><td colSpan={3} className="px-4 py-8 text-center text-slate-400">No applications yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}
