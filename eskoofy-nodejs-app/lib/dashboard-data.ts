import { prisma } from "@/lib/prisma";

/** All dashboard queries degrade to defaults when the DB is unreachable. */
async function safe<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export interface DashboardStats {
  totalStudents: number;
  totalTeachers: number;
  totalParents: number;
  totalRevenue: number;
  attendanceRate: number;
  pendingAdmissions: number;
  pendingDues: number;
}

export interface AttendanceStats {
  presentToday: number;
  absentToday: number;
  lateToday: number;
  leaveToday: number;
  todayRate: number;
  trend: { date: string; rate: number }[];
}

export interface RevenueExpenseTrend {
  months: string[];
  revenue: number[];
  expenses: number[];
}

export interface SetupItem {
  key: string;
  label: string;
  description: string;
  url: string;
  done: boolean;
}

export interface WorkbenchItem {
  label: string;
  value: number;
  url: string;
}

/** Mirrors DashboardController@stats(). */
export async function getDashboardStats(): Promise<DashboardStats> {
  const defaults: DashboardStats = {
    totalStudents: 0,
    totalTeachers: 0,
    totalParents: 0,
    totalRevenue: 0,
    attendanceRate: 0,
    pendingAdmissions: 0,
    pendingDues: 0,
  };

  const weekStart = new Date(Date.now() - 7 * 86_400_000);

  const [students, teachers, parents, revenue, attendance, admissions, dues] = await Promise.all([
    safe(() => prisma.students.count({ where: { deleted_at: null } }), 0),
    safe(() => prisma.users.count({ where: { role: "teacher", deleted_at: null } }), 0),
    safe(() => prisma.users.count({ where: { role: "parent", deleted_at: null } }), 0),
    safe(
      () =>
        prisma.payments
          .aggregate({ where: { payment_status: "completed" }, _sum: { paid_amount: true } })
          .then((r) => Number(r._sum.paid_amount ?? 0)),
      0,
    ),
    safe(
      async () => {
        const total = await prisma.attendances.count({ where: { date: { gte: weekStart }, deleted_at: null } });
        if (total === 0) return 0;
        const present = await prisma.attendances.count({
          where: { date: { gte: weekStart }, deleted_at: null, status: { in: ["present", "late", "half_day"] } },
        });
        return Math.round((100 * present) / total);
      },
      0,
    ),
    safe(() => prisma.admissions.count({ where: { status: "submitted", deleted_at: null } }), 0),
    safe(
      () =>
        prisma.fee_payments
          .aggregate({ where: { status: { in: ["pending", "partial"] } }, _sum: { balance: true } })
          .then((r) => Number(r._sum.balance ?? 0)),
      0,
    ),
  ]);

  return { ...defaults, totalStudents: students, totalTeachers: teachers, totalParents: parents, totalRevenue: revenue, attendanceRate: attendance, pendingAdmissions: admissions, pendingDues: dues };
}

/** Mirrors DashboardService@attendanceStats(). */
export async function getAttendanceStats(): Promise<AttendanceStats> {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const weekStart = new Date(today.getTime() - 6 * 86_400_000);

  const [todayCounts, trendRows] = await Promise.all([
    safe(
      () =>
        prisma.attendances.groupBy({
          by: ["status"],
          where: { date: { gte: today }, deleted_at: null },
          _count: { _all: true },
        }),
      [],
    ),
    safe(
      () =>
        prisma.attendances.groupBy({
          by: ["date", "status"],
          where: { date: { gte: weekStart, lte: today }, deleted_at: null },
          _count: { _all: true },
        }),
      [],
    ),
  ]);

  const byStatus = new Map<string, number>();
  for (const row of todayCounts as unknown as { status: string | null; _count: { _all: number } }[]) {
    byStatus.set(String(row.status ?? ""), Number(row._count._all));
  }
  const total = [...byStatus.values()].reduce((a, b) => a + b, 0);
  const present = (byStatus.get("present") ?? 0) + (byStatus.get("late") ?? 0) + (byStatus.get("half_day") ?? 0);
  const rate = total > 0 ? Math.round((100 * present) / total) / 10 : 0;

  const trendByDate = new Map<string, { present: number; total: number }>();
  for (const row of trendRows as unknown as { date: Date; status: string | null; _count: { _all: number } }[]) {
    const key = row.date.toISOString().slice(0, 10);
    const cur = trendByDate.get(key) ?? { present: 0, total: 0 };
    cur.total += Number(row._count._all);
    if (row.status === "present") cur.present += Number(row._count._all);
    trendByDate.set(key, cur);
  }
  const trend = Array.from(trendByDate.entries())
    .sort((a, b) => a[0].localeCompare(b[0]))
    .map(([date, v]) => ({ date, rate: v.total > 0 ? Math.round((100 * v.present) / v.total) / 10 : 0 }));

  return {
    presentToday: byStatus.get("present") ?? 0,
    absentToday: byStatus.get("absent") ?? 0,
    lateToday: byStatus.get("late") ?? 0,
    leaveToday: byStatus.get("on_leave") ?? 0,
    todayRate: rate,
    trend,
  };
}

/** Mirrors DashboardController@revenueExpenseTrend(). */
export async function getRevenueExpenseTrend(): Promise<RevenueExpenseTrend> {
  const months: string[] = [];
  const startDates: Date[] = [];
  for (let i = 11; i >= 0; i--) {
    const d = new Date();
    d.setMonth(d.getMonth() - i);
    months.push(d.toLocaleDateString("en-US", { month: "short" }));
    startDates.push(new Date(d.getFullYear(), d.getMonth(), 1));
  }
  const revenue = Array(12).fill(0) as number[];
  const expenses = Array(12).fill(0) as number[];
  const monthStart = startDates[0];

  const [payments, expenseRows] = await Promise.all([
    safe(
      () =>
        prisma.payments.findMany({
          where: { payment_status: "completed", payment_date: { gte: monthStart } },
          select: { paid_amount: true, payment_date: true },
        }),
      [],
    ),
    safe(
      () =>
        prisma.expenses.findMany({
          where: { date: { gte: monthStart } },
          select: { amount: true, date: true },
        }),
      [],
    ),
  ]);

  for (const row of payments as unknown as { paid_amount: unknown; payment_date: Date | null }[]) {
    if (!row.payment_date) continue;
    const idx = startDates.findIndex((d) => d.getFullYear() === row.payment_date!.getFullYear() && d.getMonth() === row.payment_date!.getMonth());
    if (idx >= 0) revenue[idx] += Number(row.paid_amount ?? 0);
  }
  for (const row of expenseRows as unknown as { amount: unknown; date: Date }[]) {
    const idx = startDates.findIndex((d) => d.getFullYear() === row.date.getFullYear() && d.getMonth() === row.date.getMonth());
    if (idx >= 0) expenses[idx] += Number(row.amount ?? 0);
  }

  return { months, revenue, expenses };
}

/** Mirrors SetupChecklistService@items() + completionPercent(). */
export async function getSetupChecklist(): Promise<{ items: SetupItem[]; percent: number; complete: boolean }> {
  const items = await safe(async (): Promise<SetupItem[]> => {
    const [settings, sessionCount, classCount, teacherCount, gatewayCount] = await Promise.all([
      safe(() => prisma.website_settings.findFirst(), null),
      safe(() => prisma.academic_sessions.count(), 0),
      safe(() => prisma.school_classes.count(), 0),
      safe(() => prisma.users.count({ where: { role: "teacher", deleted_at: null } }), 0),
      safe(() => prisma.payment_gateways.count({ where: { is_active: true } }), 0),
    ]);
    const schoolName = settings?.school_name ? String(settings.school_name) : "";
    const timezone = settings?.timezone ? String(settings.timezone) : "";

    const list: SetupItem[] = [
      { key: "school_info", label: "school_info", description: "school_info_desc", url: "/dashboard/settings/general", done: schoolName.length > 0 },
      { key: "timezone", label: "timezone", description: "timezone_desc", url: "/dashboard/settings/general", done: timezone.length > 0 },
      { key: "academic_session", label: "academic_session", description: "academic_session_desc", url: "/dashboard/classes", done: sessionCount > 0 },
      { key: "classes", label: "classes", description: "classes_desc", url: "/dashboard/classes", done: classCount > 0 },
      { key: "teachers", label: "teachers", description: "teachers_desc", url: "/dashboard/teachers", done: teacherCount > 0 },
      { key: "payment", label: "payment", description: "payment_desc", url: "/dashboard/settings", done: gatewayCount > 0 },
    ];
    return list;
  }, [] as SetupItem[]);

  const done = items.filter((i) => i.done).length;
  const percent = items.length > 0 ? Math.round((100 * done) / items.length) : 100;
  const complete = items.every((i) => i.done);

  return { items, percent, complete };
}

/** Mirrors DashboardController@workbench() — role-focused quick stats. */
export async function getWorkbench(role: string | undefined, userId: number): Promise<{ title: string; items: WorkbenchItem[] } | null> {
  if (role === "teacher") {
    const [classes, unreadMessages, upcomingExams] = await Promise.all([
      safe(
        () =>
          prisma.teachers
            .findFirst({ where: { user_id: userId, deleted_at: null }, include: { _count: { select: { class_teacher: true } } } })
            .then((t) => t?._count.class_teacher ?? 0),
        0,
      ),
      safe(() => prisma.messages.count({ where: { receiver_id: userId, read_at: null } }), 0),
      safe(() => prisma.exams.count({ where: { is_published: true, start_date: { gte: new Date() } } }), 0),
    ]);
    return {
      title: "My teaching",
      items: [
        { label: "My classes", value: classes, url: "/dashboard/teachers" },
        { label: "Unread messages", value: unreadMessages, url: "/dashboard/communications" },
        { label: "Upcoming exams", value: upcomingExams, url: "/dashboard/exams" },
      ],
    };
  }

  if (role === "accountant") {
    const [pendingApprovals, monthRevenue, pendingDues] = await Promise.all([
      safe(() => prisma.fee_payments.count({ where: { status: "pending" } }), 0),
      safe(
        () =>
          prisma.payments
            .aggregate({
              where: { payment_status: "completed", created_at: { gte: new Date(new Date().getFullYear(), new Date().getMonth(), 1) } },
              _sum: { paid_amount: true },
            })
            .then((r) => Number(r._sum.paid_amount ?? 0)),
        0,
      ),
      safe(
        () =>
          prisma.fee_payments
            .aggregate({ where: { status: { in: ["pending", "partial"] } }, _sum: { balance: true } })
            .then((r) => Number(r._sum.balance ?? 0)),
        0,
      ),
    ]);
    return {
      title: "Finance overview",
      items: [
        { label: "Pending approvals", value: pendingApprovals, url: "/dashboard/fee-payments" },
        { label: "Collected this month", value: monthRevenue, url: "/dashboard/fee-payments" },
        { label: "Outstanding dues", value: pendingDues, url: "/dashboard/fees" },
      ],
    };
  }

  if (role === "librarian") {
    const [books, issuedBooks] = await Promise.all([
      safe(() => prisma.books.count(), 0),
      safe(() => prisma.book_issues.count({ where: { status: { not: "returned" } } }), 0),
    ]);
    return {
      title: "Library overview",
      items: [
        { label: "Books in catalogue", value: books, url: "/dashboard/library/books" },
        { label: "Checked out", value: issuedBooks, url: "/dashboard/library/issues" },
      ],
    };
  }

  return null;
}