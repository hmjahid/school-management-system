import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { formatCsv } from "@/lib/csv";

export const dynamic = "force-dynamic";

/**
 * Expenses CSV download — mirrors `DashboardExpenseController@export`
 * (`GET /dashboard/expenses-export`), gated by `manage_expenses`. Uses the
 * shared `formatCsv` contract so the output matches the app's fputcsv writer.
 */
export async function GET() {
  const user = await currentUser();
  if (!user) return new Response("Unauthorized", { status: 401 });
  if (!can(user.role, "manage_expenses")) return new Response("Forbidden", { status: 403 });

  const rows = await prisma.expenses.findMany({ orderBy: [{ date: "desc" }, { id: "desc" }] });

  const csv = formatCsv(
    ["date", "category", "vendor", "amount", "payment_method", "note"],
    rows.map((expense) => [
      expense.date ? new Date(expense.date).toISOString().slice(0, 10) : "",
      expense.category,
      expense.vendor ?? "",
      Number(expense.amount ?? 0).toFixed(2),
      expense.payment_method ?? "",
      expense.note ?? "",
    ]),
  );

  const stamp = new Date().toISOString().replace(/[-:T]/g, "").slice(0, 15);
  return new Response(csv, {
    status: 200,
    headers: {
      "Content-Type": "text/csv; charset=utf-8",
      "Content-Disposition": `attachment; filename="expenses_${stamp}.csv"`,
    },
  });
}
