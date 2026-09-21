import Link from "next/link";
import { currentUser } from "@/lib/auth";
import { t, locale } from "@/lib/i18n";
import { DASHBOARD_SETUP_DEFAULTS } from "@/lib/site-cms-defaults";
import {
  getAttendanceStats,
  getDashboardStats,
  getRevenueExpenseTrend,
  getSetupChecklist,
  getWorkbench,
} from "@/lib/dashboard-data";

export const dynamic = "force-dynamic";

const nf = (value: number) => value.toLocaleString("en-US");
const money = (value: number) => value.toFixed(2);

export default async function DashboardHomePage() {
  const user = await currentUser();
  const n = locale();
  const setupStrings = DASHBOARD_SETUP_DEFAULTS[n] ?? DASHBOARD_SETUP_DEFAULTS.en;

  const [stats, attendance, trend, checklist, workbench] = await Promise.all([
    getDashboardStats(),
    getAttendanceStats(),
    getRevenueExpenseTrend(),
    getSetupChecklist(),
    getWorkbench(user?.role, user?.id ?? 0),
  ]);

  const trendBars = (attendance.trend.length > 0 ? attendance.trend : [{ date: "", rate: 0 }]).map((d) => Math.max(8, Math.min(100, d.rate)));

  const maxTrend = Math.max(...trend.revenue, ...trend.expenses, 1);
  const revenuePath = trend.revenue
    .map((v, i) => `${i === 0 ? "M" : "L"}${(i + 0.5) * (600 / 12)} ${200 - (v / maxTrend) * 200}`)
    .join(" ");
  const expensePath = trend.expenses
    .map((v, i) => `${i === 0 ? "M" : "L"}${(i + 0.5) * (600 / 12)} ${200 - (v / maxTrend) * 200}`)
    .join(" ");

  const remaining = checklist.items.filter((i) => !i.done).length;

  const quickActions = [
    { label: "Add Student", href: "/dashboard/students/create", icon: "student" },
    { label: "Add Teacher", href: "/dashboard/teachers/create", icon: "teacher" },
    { label: "Mark Attendance", href: "/dashboard/attendance/bulk", icon: "attendance" },
    { label: "Collect Fees", href: "/dashboard/fees", icon: "fees" },
    { label: "Manage Exams", href: "/dashboard/exams", icon: "exams" },
  ];

  const statCards = [
    {
      label: t("dashboard.students"),
      value: nf(stats.totalStudents),
      sub: "Active enrollment",
      href: "/dashboard/students",
      icon: "student",
      tone: "bg-brand-50 text-brand-600",
      badge: stats.pendingAdmissions > 0 ? { text: `${nf(stats.pendingAdmissions)} pending admissions`, href: "/dashboard/admissions" } : null,
    },
    {
      label: t("dashboard.teachers"),
      value: nf(stats.totalTeachers),
      sub: "Full-time staff",
      href: "/dashboard/teachers",
      icon: "teacher",
      tone: "bg-emerald-50 text-emerald-600",
      badge: null,
    },
    {
      label: t("dashboard.parents"),
      value: nf(stats.totalParents),
      sub: "Registered guardians",
      href: "/dashboard/parents",
      icon: "parent",
      tone: "bg-amber-50 text-amber-600",
      badge: null,
    },
    {
      label: t("dashboard.attendance"),
      value: `${stats.attendanceRate}%`,
      sub: "Last 7 days",
      href: "/dashboard/attendance",
      icon: "attendance",
      tone: "bg-sky-50 text-sky-600",
      badge: null,
    },
    {
      label: "Revenue",
      value: money(stats.totalRevenue),
      sub: "Total collected",
      href: "/dashboard/fee-payments",
      icon: "revenue",
      tone: "bg-violet-50 text-violet-600",
      badge: stats.pendingDues > 0 ? { text: `${money(stats.pendingDues)} pending dues`, href: "/dashboard/fees" } : null,
    },
  ];

  const icons: Record<string, React.ReactNode> = {
    student: (
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z" />
      </svg>
    ),
    teacher: (
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
      </svg>
    ),
    parent: (
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
      </svg>
    ),
    attendance: (
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path fillRule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clipRule="evenodd" />
      </svg>
    ),
    fees: (
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clipRule="evenodd" />
      </svg>
    ),
    exams: (
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path fillRule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clipRule="evenodd" />
      </svg>
    ),
    revenue: (
      <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582z" />
      </svg>
    ),
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">{t("dashboard.dashboard")}</h1>
          <p className="mt-1 text-sm text-slate-500">
            Welcome back, {user?.name}!
          </p>
        </div>
        <div className="flex gap-2">
          <Link href="/dashboard/bulk" className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Import
          </Link>
          <Link href="/dashboard/reports" className="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">
            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            {t("dashboard.reports")}
          </Link>
        </div>
      </div>

      {!checklist.complete ? (
        <div className="mb-6 overflow-hidden rounded-xl border border-sky-200/70 bg-gradient-to-r from-sky-50 via-white to-white shadow-sm">
          <div className="flex flex-wrap items-center gap-4 p-5">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
              <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </span>
            <div className="min-w-0 flex-1">
              <p className="text-sm font-semibold text-slate-900">
                {setupStrings.remaining.replace(":n", String(remaining)).replace(":total", String(checklist.items.length))}
              </p>
              <div className="mt-2 h-1.5 w-full max-w-md overflow-hidden rounded-full bg-slate-100">
                <div className="h-full rounded-full bg-gradient-to-r from-sky-400 to-brand-500 transition-all" style={{ width: `${checklist.percent}%` }} />
              </div>
            </div>
            <Link href="/dashboard/onboarding" className="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-sky-700">
              <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              {setupStrings.start_now}
            </Link>
          </div>
        </div>
      ) : null}

      <div className="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        {statCards.map((card) => (
          <a key={card.label} href={card.href} className="block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
            <div className="flex items-center justify-between">
              <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{card.label}</p>
              <span className={`rounded-full p-1.5 ${card.tone}`}>{icons[card.icon]}</span>
            </div>
            <p className="mt-2 text-3xl font-bold tracking-tight text-slate-900">{card.value}</p>
            <p className="mt-1 text-xs text-slate-400">{card.sub}</p>
            {card.badge ? (
              <a href={card.badge.href} className="mt-3 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 hover:bg-amber-100">
                {card.badge.text}
              </a>
            ) : null}
          </a>
        ))}
      </div>

      <div className="mb-8 grid gap-6 lg:grid-cols-2">
        <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="flex items-center justify-between border-b border-slate-100 p-5">
            <h2 className="text-base font-semibold text-slate-900">Revenue vs Expenses</h2>
            <span className="text-xs text-slate-400">Last 12 months</span>
          </div>
          <div className="p-5">
            <div className="mb-4 flex items-center gap-4 text-xs text-slate-500">
              <span className="flex items-center gap-1">
                <span className="inline-block h-2 w-4 rounded bg-emerald-500" /> Revenue
              </span>
              <span className="flex items-center gap-1">
                <span className="inline-block h-2 w-4 rounded bg-red-400" /> Expenses
              </span>
            </div>
            <div className="relative h-48">
              <svg viewBox="0 0 600 200" className="h-full w-full" preserveAspectRatio="none">
                {[0, 50, 100, 150, 200].map((y) => (
                  <line key={y} x1="0" y1={y} x2="600" y2={y} stroke="rgba(15,23,42,0.1)" strokeWidth="0.5" />
                ))}
                <path d={`${revenuePath} L600 200 L0 200 Z`} fill="rgba(16,185,129,0.15)" stroke="rgb(16,185,129)" strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
                <path d={`${expensePath} L600 200 L0 200 Z`} fill="rgba(248,113,113,0.15)" stroke="rgb(248,113,113)" strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
              </svg>
              <div className="mt-1 flex justify-between text-[0.6rem] text-slate-400">
                {trend.months.map((m, i) => (
                  <span key={i}>{m}</span>
                ))}
              </div>
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="flex items-center justify-between border-b border-slate-100 p-5">
            <h2 className="text-base font-semibold text-slate-900">Today&apos;s Attendance</h2>
            <span className="text-xs text-slate-400">Real-time</span>
          </div>
          <div className="p-5">
            <div className="grid grid-cols-2 gap-3">
              <div className="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                <p className="text-xs uppercase tracking-wide text-emerald-700">Present</p>
                <p className="mt-1 text-3xl font-bold text-emerald-700">{attendance.presentToday}</p>
              </div>
              <div className="rounded-lg border border-red-100 bg-red-50 p-4">
                <p className="text-xs uppercase tracking-wide text-red-700">Absent</p>
                <p className="mt-1 text-3xl font-bold text-red-700">{attendance.absentToday}</p>
              </div>
              <div className="rounded-lg border border-amber-100 bg-amber-50 p-4">
                <p className="text-xs uppercase tracking-wide text-amber-700">Late</p>
                <p className="mt-1 text-3xl font-bold text-amber-700">{attendance.lateToday}</p>
              </div>
              <div className="rounded-lg border border-sky-100 bg-sky-50 p-4">
                <p className="text-xs uppercase tracking-wide text-sky-700">On leave</p>
                <p className="mt-1 text-3xl font-bold text-sky-700">{attendance.leaveToday}</p>
              </div>
            </div>
            <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
              <span className="text-sm text-slate-600">Today&apos;s rate</span>
              <span className="text-2xl font-bold text-brand-600">{attendance.todayRate}%</span>
            </div>
          </div>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
          <div className="flex items-center justify-between border-b border-slate-100 p-5">
            <h2 className="text-base font-semibold text-slate-900">Attendance Trend</h2>
            <span className="text-xs text-slate-400">Last 7 days</span>
          </div>
          <div className="p-5">
            <div className="flex h-44 items-end justify-between gap-2 rounded-lg bg-slate-50 p-4">
              {trendBars.map((h, i) => (
                <div key={i} className="group relative w-full">
                  <div
                    className="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition-all duration-300 hover:from-brand-500 hover:to-brand-300"
                    style={{ height: `${h}%` }}
                  />
                  <div className="absolute -top-8 left-1/2 -translate-x-1/2 rounded bg-slate-800 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100">
                    {Math.round(h)}%
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="border-b border-slate-100 p-5">
            <h2 className="text-base font-semibold text-slate-900">Quick Actions</h2>
          </div>
          <div className="space-y-2 p-5">
            {quickActions.map((action) => (
              <a
                key={action.label}
                href={action.href}
                className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
              >
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">{icons[action.icon]}</span>
                {action.label}
              </a>
            ))}
          </div>
        </div>
      </div>

      {workbench ? (
        <div className="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="border-b border-slate-100 p-5">
            <h2 className="text-base font-semibold text-slate-900">{workbench.title}</h2>
          </div>
          <div className="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
            {workbench.items.map((item) => (
              <a key={item.label} href={item.url} className="group rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-300 hover:bg-brand-50/50">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{item.label}</p>
                <p className="mt-2 text-2xl font-bold text-slate-900">{nf(item.value)}</p>
              </a>
            ))}
          </div>
        </div>
      ) : null}
    </div>
  );
}