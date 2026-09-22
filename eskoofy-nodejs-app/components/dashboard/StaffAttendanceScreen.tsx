import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { PageHeader } from "@/components/ui/PageHeader";
import { saveStaffAttendance } from "@/app/(dashboard)/dashboard/staff-attendance-actions";

const STATUSES: Array<{ value: string; label: string }> = [
  { value: "present", label: "Present" },
  { value: "absent", label: "Absent" },
  { value: "late", label: "Late" },
  { value: "leave", label: "On leave" },
];

function todayString(offset = 0): string {
  const d = new Date(Date.now() + offset * 86400000);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/**
 * Staff attendance sheet — mirrors `dashboard/staff-attendance/index.blade.php`:
 * a per-teacher status + note grid saved as one batch.
 */
export async function StaffAttendanceScreen({
  filters,
}: {
  filters?: { date?: string; saved?: string; error?: string };
}) {
  const q = filters ?? {};
  const dateRaw = /^\d{4}-\d{2}-\d{2}$/.test(q.date ?? "") ? q.date! : todayString();
  const date = new Date(`${dateRaw}T00:00:00`);

  const user = await currentUser();
  if (!can(user?.role, "manage_attendance")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — Staff attendance
      </p>
    );
  }

  const [teachers, existing] = await Promise.all([
    prisma.teachers.findMany({ where: { status: "active" }, include: { users: true }, orderBy: { id: "asc" } }).catch(() => []),
    prisma.staff_attendances
      .findMany({ where: { date: { gte: date, lt: new Date(date.getTime() + 86400000) } } })
      .catch(() => []),
  ]);

  const existingByTeacher = new Map(existing.map((e) => [e.teacher_id, e]));

  return (
    <div>
      <PageHeader
        title="Staff attendance"
        description="Mark teacher / staff attendance for the day."
        actions={
          <form method="get" className="flex flex-wrap items-center gap-2">
            <input type="date" name="date" defaultValue={dateRaw} className="admin-input" />
            <button type="submit" className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
              Change date
            </button>
            <Link href="/dashboard/staff-attendance/report" className="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
              Monthly report
            </Link>
          </form>
        }
      />

      {q.saved === "1" && (
        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">Attendance saved.</div>
      )}
      {q.error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Failed to save attendance.</div>
      )}

      <form action={saveStaffAttendance}>
        <input type="hidden" name="date" value={dateRaw} />

        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                  <th className="px-4 py-3">Staff</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Note</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {teachers.map((teacher) => {
                  const rec = existingByTeacher.get(teacher.id);
                  const name = teacher.users?.name ?? teacher.employee_id ?? `Teacher #${teacher.id}`;
                  return (
                    <tr key={teacher.id} className="admin-table-row">
                      <td className="px-4 py-3">
                        <div className="font-medium text-slate-900">{name}</div>
                        <div className="text-xs text-slate-500">{teacher.users?.email ?? ""}</div>
                      </td>
                      <td className="px-4 py-3">
                        <input type="hidden" name="teacher_id" value={teacher.id} />
                        <select name="status" defaultValue={rec?.status ?? "present"} className="admin-select">
                          {STATUSES.map((s) => (
                            <option key={s.value} value={s.value}>
                              {s.label}
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="px-4 py-3">
                        <input type="text" name="note" defaultValue={rec?.note ?? ""} maxLength={500} placeholder="—" className="admin-input" />
                      </td>
                    </tr>
                  );
                })}
                {teachers.length === 0 ? (
                  <tr>
                    <td colSpan={3} className="px-4 py-6 text-center text-sm text-slate-500">
                      No staff members yet.
                    </td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
          {teachers.length > 0 ? (
            <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
              <button type="submit" className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Save attendance
              </button>
            </div>
          ) : null}
        </div>
      </form>
    </div>
  );
}