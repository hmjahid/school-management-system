import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { prisma } from "@/lib/prisma";
import { formatCsv } from "@/lib/csv";

export const dynamic = "force-dynamic";

const TYPES = ["fees", "attendance", "students"] as const;
type ExportType = (typeof TYPES)[number];

const PRESENT_STATUSES = ["present", "late", "half_day"];

function dayKey(date: Date | string | null): string {
  if (!date) return "";
  const d = new Date(date);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function monthKey(date: Date | string | null): string {
  if (!date) return "";
  const d = new Date(date);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
}

function csvResponse(type: ExportType, csv: string): Response {
  const stamp = new Date();
  const pad = (n: number) => String(n).padStart(2, "0");
  const filename = `report-${type}-${stamp.getFullYear()}${pad(stamp.getMonth() + 1)}${pad(stamp.getDate())}-${pad(stamp.getHours())}${pad(stamp.getMinutes())}${pad(stamp.getSeconds())}.csv`;
  return new Response(csv, {
    headers: {
      "Content-Type": "text/csv; charset=UTF-8",
      "Content-Disposition": `attachment; filename="${filename}"`,
    },
  });
}

async function feesCsv(): Promise<string> {
  const payments = await prisma.payments
    .findMany({ select: { payment_date: true, paid_amount: true }, orderBy: { payment_date: "asc" } })
    .catch(() => []);

  const byMonth = new Map<string, { total: number; count: number }>();
  for (const payment of payments) {
    const key = monthKey(payment.payment_date);
    if (!key) continue;
    const current = byMonth.get(key) ?? { total: 0, count: 0 };
    current.total += Number(payment.paid_amount ?? 0);
    current.count += 1;
    byMonth.set(key, current);
  }

  const rows = [...byMonth.entries()].sort(([a], [b]) => a.localeCompare(b)).map(([month, v]) => [month, v.total, v.count]);
  return formatCsv(["month", "total", "count"], rows);
}

async function attendanceCsv(): Promise<string> {
  const rows = await prisma.attendances
    .findMany({
      where: { deleted_at: null },
      select: { date: true, status: true, school_classes: { select: { name: true } } },
      orderBy: { date: "asc" },
    })
    .catch(() => []);

  const grouped = new Map<string, { day: string; className: string; present: number; total: number }>();
  for (const row of rows) {
    const day = dayKey(row.date);
    const className = row.school_classes?.name ?? "";
    const key = `${day}|${className}`;
    const current = grouped.get(key) ?? { day, className, present: 0, total: 0 };
    current.total += 1;
    if (row.status && PRESENT_STATUSES.includes(row.status)) current.present += 1;
    grouped.set(key, current);
  }

  const out = [...grouped.values()].sort((a, b) => a.day.localeCompare(b.day) || a.className.localeCompare(b.className));
  return formatCsv(["date", "class", "present", "total"], out.map((r) => [r.day, r.className, r.present, r.total]));
}

async function studentsCsv(): Promise<string> {
  const students = await prisma.students
    .findMany({ where: { deleted_at: null }, select: { school_classes: { select: { name: true } } } })
    .catch(() => []);

  const counts = new Map<string, number>();
  for (const student of students) {
    const name = student.school_classes?.name ?? "";
    counts.set(name, (counts.get(name) ?? 0) + 1);
  }

  const rows = [...counts.entries()].sort(([a], [b]) => a.localeCompare(b)).map(([name, total]) => [name, total]);
  return formatCsv(["class", "count"], rows);
}

/**
 * CSV export stream — mirrors `DashboardReportController@export`
 * (`/dashboard/reports/export/{type}`, types fees | attendance | students).
 */
export async function GET(_request: Request, ctx: { params: Promise<{ type: string }> }): Promise<Response> {
  const { type } = await ctx.params;
  if (!TYPES.includes(type as ExportType)) {
    return new Response("Not Found", { status: 404 });
  }

  const user = await currentUser();
  if (!can(user?.role, "view_reports")) {
    return new Response("Forbidden", { status: 403 });
  }

  const exportType = type as ExportType;
  const csv = exportType === "fees" ? await feesCsv() : exportType === "attendance" ? await attendanceCsv() : await studentsCsv();
  return csvResponse(exportType, csv);
}