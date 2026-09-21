import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { t } from "@/lib/i18n";
import { updateProfile } from "@/app/(dashboard)/dashboard/actions";

/**
 * Misc bespoke dashboard screens — mirror the app's blades:
 *   - students/promote.blade.php
 *   - exams/my-results.blade.php
 *   - onboarding.blade.php
 */

export async function PromoteStudents({ fromClassId }: { fromClassId?: number }) {
  const [classes, students] = await Promise.all([
    prisma.school_classes.findMany({ orderBy: { name: "asc" } }),
    prisma.students.findMany({
      where: { deleted_at: null, ...(fromClassId ? { class_id: fromClassId } : {}) },
      orderBy: { roll_number: "asc" },
      take: 200,
    }),
  ]);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Promote students</h1>
          <p className="mt-1 text-sm text-slate-600">Move students to the next class / batch in one step.</p>
        </div>
        <Link href="/dashboard/students" className="text-sm font-medium text-brand-600 hover:text-brand-800">Back to list</Link>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 text-lg font-semibold text-slate-800">1. Select source students</h2>
          <form method="get" className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-slate-700">From class *</label>
              <select name="from_class_id" defaultValue={fromClassId ?? ""} className="admin-select mt-1 w-full">
                <option value="">Select class</option>
                {classes.map((c) => <option key={c.id} value={c.id}>{String(c.name)}</option>)}
              </select>
            </div>
            <button className="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Load students</button>
          </form>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 text-lg font-semibold text-slate-800">2. Students found</h2>
          {students.length === 0 ? (
            <p className="text-sm text-slate-400">Pick a class above to load students.</p>
          ) : (
            <ul className="max-h-80 space-y-1 overflow-y-auto">
              {students.map((s) => (
                <li key={s.id} className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm">
                  <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-brand-600" />
                  <span className="font-medium text-slate-800">{String(s.first_name ?? "")} {String(s.last_name ?? "")}</span>
                  <span className="ml-auto font-mono text-xs text-slate-500">{String(s.roll_number ?? "")}</span>
                </li>
              ))}
            </ul>
          )}
          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-medium text-slate-700">To class</label>
              <select className="admin-select mt-1 w-full">{classes.map((c) => <option key={c.id} value={c.id}>{String(c.name)}</option>)}</select>
            </div>
            <div className="flex items-end">
              <button className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Promote selected</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export async function MyResults() {
  const user = await currentUser();
  const email = user?.email ?? "";
  const student = email
    ? await prisma.students.findFirst({ where: { email } })
    : null;

  const results = student
    ? await prisma.exam_results.findMany({
        where: { student_id: student.id, is_published: true },
        include: { exams: true },
        take: 100,
      })
    : [];

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">{t("dashboard.my_results")}</h1>
          <p className="mt-1 text-sm text-slate-600">Your published exam results and marksheets.</p>
        </div>
        <Link href="/dashboard/exams" className="text-sm font-medium text-brand-600 hover:text-brand-800">All exams →</Link>
      </div>

      <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
              <th className="px-4 py-3">Exam</th>
              <th className="px-4 py-3">Marks</th>
              <th className="px-4 py-3">Grade</th>
              <th className="px-4 py-3">Grade point</th>
              <th className="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {results.map((r) => {
              const exam = (r.exams ?? {}) as Record<string, unknown>;
              return (
                <tr key={r.id} className="admin-table-row">
                  <td className="px-4 py-3 font-medium text-slate-900">{String(exam.name ?? "Exam")}</td>
                  <td className="px-4 py-3 text-slate-700">{String(r.obtained_marks ?? "—")}</td>
                  <td className="px-4 py-3 text-slate-700">{String(r.grade ?? "—")}</td>
                  <td className="px-4 py-3 text-slate-700">{String(r.grade_point ?? "—")}</td>
                  <td className="px-4 py-3"><span className="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Published</span></td>
                </tr>
              );
            })}
            {results.length === 0 ? <tr><td colSpan={5} className="px-4 py-8 text-center text-slate-400">No published results yet.</td></tr> : null}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export async function Onboarding() {
  const steps = [
    { title: "Add your school info", done: true },
    { title: "Create classes & sections", done: false },
    { title: "Add teachers & staff", done: false },
    { title: "Enrol students", done: false },
    { title: "Set up fees", done: false },
    { title: "Publish your website", done: false },
  ];
  const done = steps.filter((s) => s.done).length;

  return (
    <div className="mx-auto max-w-2xl py-10">
      <h1 className="text-2xl font-bold text-slate-900">Welcome to {t("brand.name")}!</h1>
      <p className="mt-1 text-sm text-slate-600">Complete these steps to get your school up and running.</p>

      <div className="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="flex items-center justify-between text-sm">
          <span className="font-medium text-slate-700">Setup progress</span>
          <span className="font-semibold text-brand-600">{done}/{steps.length}</span>
        </div>
        <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
          <div className="h-full rounded-full bg-brand-600" style={{ width: `${(done / steps.length) * 100}%` }} />
        </div>
        <ul className="mt-6 space-y-3">
          {steps.map((step) => (
            <li key={step.title} className={`flex items-center gap-3 rounded-lg px-4 py-3 ${step.done ? "bg-emerald-50 text-emerald-800" : "bg-slate-50 text-slate-700"}`}>
              <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold ${step.done ? "bg-emerald-500 text-white" : "bg-slate-300 text-white"}`}>
                {step.done ? "✓" : String(steps.indexOf(step) + 1)}
              </span>
              <span className="text-sm font-medium">{step.title}</span>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}

const inputClass =
  "mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20";

/** dashboard/profile — mirrors the app's dashboard/profile/edit.blade.php. */
export async function ProfileScreen({
  searchParams,
}: {
  searchParams: Promise<{ sent?: string; error?: string }>;
}) {
  const sp = await searchParams;
  const sent = Boolean(sp.sent);
  const error = Boolean(sp.error);

  const user = await currentUser();
  const row = user ? await prisma.users.findUnique({ where: { id: user.id } }) : null;

  return (
    <div className="mx-auto max-w-3xl">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">{t("dashboard.my_profile")}</h1>
        <p className="mt-1 text-sm text-slate-500">{t("dashboard.profile_description")}</p>
      </div>

      {sent ? (
        <p className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
          Profile updated successfully.
        </p>
      ) : null}
      {error ? (
        <p className="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
          Please check the form and try again.
        </p>
      ) : null}

      <form action={updateProfile} className="space-y-6">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="flex items-center gap-6">
            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-indigo-600 text-2xl font-bold text-white ring-4 ring-slate-100">
              {(row?.name ?? "?").slice(0, 1).toUpperCase()}
            </div>
            <div className="flex-1">
              <h2 className="text-lg font-semibold text-slate-900">{row?.name ?? ""}</h2>
              <p className="text-sm text-slate-500">{row?.email ?? ""}</p>
              <p className="mt-1 text-xs capitalize text-slate-400">{row?.role ?? ""}</p>
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 className="mb-4 text-lg font-semibold text-slate-900">{t("dashboard.personal_information")}</h3>
          <div className="grid gap-5 sm:grid-cols-2">
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-slate-700">{t("dashboard.full_name")}</label>
              <input id="name" name="name" type="text" defaultValue={row?.name ?? ""} required className={inputClass} />
            </div>
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-slate-700">{t("dashboard.email")}</label>
              <input id="email" name="email" type="email" defaultValue={row?.email ?? ""} required className={inputClass} />
            </div>
            <div>
              <label htmlFor="phone" className="block text-sm font-medium text-slate-700">{t("dashboard.phone")}</label>
              <input id="phone" name="phone" type="text" defaultValue={row?.phone ?? ""} className={inputClass} />
            </div>
            <div>
              <label htmlFor="gender" className="block text-sm font-medium text-slate-700">{t("dashboard.gender")}</label>
              <select id="gender" name="gender" defaultValue={row?.gender ?? ""} className={inputClass}>
                <option value="">Select</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
            <div>
              <label htmlFor="date_of_birth" className="block text-sm font-medium text-slate-700">{t("dashboard.date_of_birth")}</label>
              <input id="date_of_birth" name="date_of_birth" type="date" defaultValue={row?.date_of_birth ? new Date(String(row.date_of_birth)).toISOString().slice(0, 10) : ""} className={inputClass} />
            </div>
            <div className="sm:col-span-2">
              <label htmlFor="address" className="block text-sm font-medium text-slate-700">{t("dashboard.address")}</label>
              <textarea id="address" name="address" rows={2} defaultValue={row?.address ?? ""} className={inputClass} />
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 className="mb-1 text-lg font-semibold text-slate-900">{t("dashboard.change_password")}</h3>
          <p className="mb-4 text-sm text-slate-500">{t("dashboard.leave_blank_password")}</p>
          <div className="grid gap-5 sm:grid-cols-2">
            <div>
              <label htmlFor="password" className="block text-sm font-medium text-slate-700">{t("dashboard.new_password")}</label>
              <input id="password" name="password" type="password" className={inputClass} />
            </div>
            <div>
              <label htmlFor="password_confirmation" className="block text-sm font-medium text-slate-700">{t("dashboard.confirm_password")}</label>
              <input id="password_confirmation" name="password_confirmation" type="password" className={inputClass} />
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button type="submit" className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50">
            {t("dashboard.save_changes")}
          </button>
        </div>
      </form>
    </div>
  );
}
