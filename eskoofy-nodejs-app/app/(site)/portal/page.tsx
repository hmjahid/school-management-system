import { redirect } from "next/navigation";
import { t } from "@/lib/i18n";
import { currentUser } from "@/lib/auth";
import { loadPortalData, portalFormat } from "@/lib/portal-data";
import PortalTabs from "@/components/site/PortalTabs";

export const dynamic = "force-dynamic";

const STAFF_ROLES = ["admin", "teacher", "accountant", "staff", "librarian"];

export default async function PortalPage({
  searchParams,
}: {
  searchParams: Promise<{ sent?: string; error?: string }>;
}) {
  const params = await searchParams;
  const sent = Boolean(params.sent);
  const error = Boolean(params.error);

  const user = await currentUser();
  if (!user) redirect("/login?redirect=/portal");
  if (STAFF_ROLES.includes(user.role)) redirect("/dashboard");

  const data = await loadPortalData(user.id, user.role, user.email, user.name);
  const { fmtDate, fmtDay, fmtMonth } = portalFormat;

  const upcomingCount = data.upcomingEvents.length;
  const presentCount = data.recentAttendance.filter((a) => String(a.status ?? "") === "present").length;
  const attendanceTotal = data.recentAttendance.length;
  const className = data.isStudent ? String((data.student?.school_classes as Record<string, unknown> | undefined)?.name ?? "") : "";

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col items-center gap-4 md:flex-row md:justify-between">
            <div>
              <h1 className="text-4xl font-bold md:text-5xl">{t("site.portal.hero_title")}</h1>
              <p className="mt-2 text-lg text-blue-100">
                {t("site.portal.hero_subtitle_prefix")} {user.name}
              </p>
            </div>
            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-white/10 text-2xl font-bold text-white ring-2 ring-white/30">
              {user.name.slice(0, 1)}
            </div>
          </div>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="grid gap-6 lg:grid-cols-4 reveal">
          <div className="rounded-2xl border border-slate-100 bg-gradient-to-br from-blue-50 to-indigo-50 p-6 shadow-sm lg:col-span-2">
            <div className="flex items-center gap-4">
              <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xl font-bold text-white shadow-lg">
                {user.name.slice(0, 1)}
              </div>
              <div>
                <h2 className="text-xl font-bold text-slate-900">Welcome back, {user.name}</h2>
                <p className="text-sm text-slate-500">
                  {data.isStudent && data.student
                    ? `${className} · Roll: ${String(data.student.roll_number ?? data.student.roll_no ?? "—")}`
                    : data.isParent
                      ? "Parent Account"
                      : user.email}
                </p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 text-green-600">
                <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p className="text-2xl font-bold text-slate-900">
                  {presentCount}/{attendanceTotal || "—"}
                </p>
                <p className="text-xs text-slate-500">Attendance</p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            <div className="flex items-center gap-3">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
                <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div>
                <p className="text-2xl font-bold text-slate-900">{upcomingCount}</p>
                <p className="text-xs text-slate-500">Upcoming Exams</p>
              </div>
            </div>
          </div>
        </div>

        <PortalTabs
          isStudent={data.isStudent}
          isParent={data.isParent}
          student={data.student}
          linkedChildren={data.children}
          recentAttendance={data.recentAttendance}
          examResults={data.examResults}
          feePayments={data.feePayments}
          routine={Array.from(data.routine.entries())}
          teachers={data.teachers}
          attendanceCalendar={Array.from(data.attendanceCalendar.entries())}
          duesTimeline={data.duesTimeline}
          sent={sent}
          error={error}
        />

        {data.isStudent && data.assignments.length > 0 ? (
          <section className="mt-10 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm reveal">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_homework")}</h2>
            <div className="mt-4 divide-y divide-slate-100">
              {data.assignments.map((a, i) => (
                <div key={i} className="flex items-center justify-between gap-4 py-3">
                  <div>
                    <p className="text-sm font-medium text-slate-900">{String(a.title ?? "")}</p>
                    {a.due_date ? <p className="text-xs text-slate-500">{t("site.portal.due_label")}: {fmtDate(a.due_date)}</p> : null}
                  </div>
                  {a.due_date ? (
                    <span className={`shrink-0 text-xs font-semibold ${new Date(String(a.due_date)).getTime() < Date.now() ? "text-red-600" : "text-blue-600"}`}>
                      {new Date(String(a.due_date)).getTime() < Date.now() ? "Overdue" : fmtDate(a.due_date)}
                    </span>
                  ) : null}
                </div>
              ))}
            </div>
          </section>
        ) : null}

        <section className="mt-10 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm reveal">
          <h2 className="text-lg font-semibold text-slate-900">{t("site.portal.section_communication")}</h2>
          <p className="mt-1 text-sm text-slate-500">{t("site.portal.communication_intro")}</p>
          {data.announcements.length === 0 ? (
            <p className="mt-4 text-sm text-slate-600">{t("site.portal.no_announcements")}</p>
          ) : (
            <div className="mt-4 space-y-3">
              {data.announcements.map((a, i) => (
                <div key={i} className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                  <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <div className="font-semibold text-slate-900">{String(a.title ?? "")}</div>
                    <div className="text-xs text-slate-500">{fmtDate(a.starts_at ?? a.created_at)}</div>
                  </div>
                  {a.body ? <div className="mt-2 whitespace-pre-line text-sm text-slate-700">{String(a.body)}</div> : null}
                </div>
              ))}
            </div>
          )}
        </section>

        {data.upcomingEvents.length > 0 ? (
          <section className="mt-10 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm reveal">
            <h2 className="text-lg font-semibold text-slate-900">Upcoming events</h2>
            <div className="mt-4 space-y-3">
              {data.upcomingEvents.map((ev, i) => (
                <div key={i} className="flex items-center gap-4 rounded-xl border border-slate-100 bg-slate-50 p-4">
                  <div className="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-gradient-to-b from-orange-400 to-orange-600 text-white">
                    <span className="text-lg font-bold">{fmtDay(ev.start_date)}</span>
                    <span className="text-[10px] font-semibold uppercase">{fmtMonth(ev.start_date)}</span>
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="font-semibold text-slate-900">{String(ev.title ?? "")}</p>
                    <p className="text-sm text-slate-500">{fmtDate(ev.start_date)}{ev.location ? ` · ${String(ev.location)}` : ""}</p>
                  </div>
                </div>
              ))}
            </div>
          </section>
        ) : null}
      </div>
    </div>
  );
}