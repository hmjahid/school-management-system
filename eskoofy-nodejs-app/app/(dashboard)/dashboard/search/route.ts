import { NextResponse, type NextRequest } from "next/server";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";

export const dynamic = "force-dynamic";

interface SearchResult {
  id: number | string;
  type: string;
  name: string;
  subtitle: string;
  url: string;
}

/**
 * JSON search-as-you-type — mirrors
 * `DashboardSearchController@search` (`GET /dashboard/search?q=`).
 * Searches students, teachers, classes, notices, fees and payments, then adds
 * quick links for keyword matches. Every lookup degrades gracefully.
 */
export async function GET(request: NextRequest) {
  const user = await currentUser();
  if (!user) return NextResponse.json({ data: [] }, { status: 401 });

  const query = (request.nextUrl.searchParams.get("q") ?? "").trim();
  if (query.length < 2) return NextResponse.json({ data: [] });

  const contains = { contains: query } as const;
  const results: SearchResult[] = [];

  const safe = async <T,>(fn: () => Promise<T>): Promise<T | null> => {
    try {
      return await fn();
    } catch {
      return null;
    }
  };

  const [students, teachers, classes, notices, fees, payments] = await Promise.all([
    safe(() =>
      prisma.students.findMany({
        where: { deleted_at: null, OR: [{ first_name: contains }, { last_name: contains }, { users: { name: contains } }] },
        include: { users: true, school_classes: true },
        take: 5,
      }),
    ),
    safe(() =>
      prisma.teachers.findMany({
        where: { deleted_at: null, OR: [{ employee_id: contains }, { users: { name: contains } }] },
        include: { users: true },
        take: 5,
      }),
    ),
    safe(() => prisma.school_classes.findMany({ where: { name: contains }, take: 5 })),
    safe(() =>
      prisma.notices.findMany({ where: { OR: [{ title: contains }, { title_bn: contains }] }, take: 5 }),
    ),
    safe(() => prisma.fees.findMany({ where: { name: contains, deleted_at: null }, take: 5 })),
    safe(() =>
      prisma.fee_payments.findMany({
        where: { OR: [{ invoice_number: contains }, { transaction_id: contains }] },
        take: 5,
      }),
    ),
  ]);

  for (const student of students ?? []) {
    const name = student.users?.name || `${student.first_name} ${student.last_name}`.trim();
    results.push({
      id: student.id,
      type: "student",
      name: name || "—",
      subtitle: student.school_classes?.name ?? "",
      url: `/dashboard/students/${student.id}`,
    });
  }
  for (const teacher of teachers ?? []) {
    results.push({
      id: teacher.id,
      type: "teacher",
      name: teacher.users?.name || "—",
      subtitle: teacher.qualification ?? "",
      url: `/dashboard/teachers/${teacher.id}`,
    });
  }
  for (const klass of classes ?? []) {
    results.push({ id: klass.id, type: "class", name: klass.name, subtitle: "", url: "/dashboard/classes" });
  }
  for (const notice of notices ?? []) {
    results.push({
      id: notice.id,
      type: "notice",
      name: notice.title,
      subtitle: (notice.content ?? "").replace(/<[^>]+>/g, " ").slice(0, 60).trim(),
      url: `/dashboard/notices/${notice.id}/edit`,
    });
  }
  for (const fee of fees ?? []) {
    results.push({ id: fee.id, type: "fee", name: fee.name, subtitle: "", url: "/dashboard/fees" });
  }
  for (const payment of payments ?? []) {
    results.push({
      id: payment.id,
      type: "payment",
      name: payment.invoice_number,
      subtitle: `${Number(payment.paid_amount ?? 0).toFixed(2)} — ${payment.status ?? ""}`,
      url: "/dashboard/fee-payments",
    });
  }

  const quickLinks: Array<{ pattern: string; name: string; url: string }> = [
    { pattern: "report", name: "Reports", url: "/dashboard/reports" },
    { pattern: "setting", name: "Settings", url: "/dashboard/settings" },
    { pattern: "user", name: "Users", url: "/dashboard/users" },
    { pattern: "role", name: "Roles", url: "/dashboard/roles" },
    { pattern: "permission", name: "Permissions", url: "/dashboard/permissions" },
  ];
  const needle = query.toLowerCase();
  for (const link of quickLinks) {
    if (needle.includes(link.pattern) || link.pattern.includes(needle)) {
      results.push({ id: link.pattern, type: "link", name: link.name, subtitle: "", url: link.url });
    }
  }

  return NextResponse.json({ data: results });
}
