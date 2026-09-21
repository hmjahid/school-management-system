import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { currentUser } from "@/lib/auth";
import { t } from "@/lib/i18n";
import { Hero } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

type FeeRow = { id: number; name: string; amount: unknown; fee_type: string; description: string | null };
type GatewayRow = { id: number; name: string; code: string; description: string | null };

const inputClass =
  "mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default async function PaymentsPage() {
  const user = await currentUser();
  const [feeRows, gateways] = await Promise.all([
    safe(() => prisma.fees.findMany({ where: { status: "active", deleted_at: null }, orderBy: { name: "asc" }, take: 40 }), [] as FeeRow[]),
    safe(() => prisma.payment_gateways.findMany({ where: { is_active: true }, orderBy: [{ sort_order: "asc" }, { name: "asc" }] }), [] as GatewayRow[]),
  ]);

  let students: Array<{ id: number; first_name: string; last_name: string }> = [];
  let feePayments: Array<{ id: number; invoice_number: string | null; paid_amount: number; status: string; payment_date: Date | null }> = [];

  const isLinkedRole = user && ["student", "parent"].includes(user.role);
  if (user && isLinkedRole) {
    const studentIds = await safe(async () => {
      if (user.role === "student") {
        const row = await prisma.students.findFirst({ where: { user_id: user.id, deleted_at: null }, select: { id: true } });
        return row ? [row.id] : [];
      }
      const rows = await prisma.guardian_student.findMany({
        where: { guardians: { user_id: user.id } },
        select: { student_id: true },
      });
      return rows.map((r) => r.student_id);
    }, [] as number[]);

    if (studentIds.length > 0) {
      [students, feePayments] = await Promise.all([
        safe(
          () =>
            prisma.students.findMany({
              where: { id: { in: studentIds }, deleted_at: null },
              orderBy: [{ first_name: "asc" }, { last_name: "asc" }],
              select: { id: true, first_name: true, last_name: true },
            }),
          [] as Array<{ id: number; first_name: string; last_name: string }>,
        ),
        safe(
          () =>
            prisma.fee_payments
              .findMany({
                where: { student_id: { in: studentIds }, deleted_at: null },
                orderBy: [{ payment_date: "desc" }, { id: "desc" }],
                take: 25,
                select: { id: true, invoice_number: true, paid_amount: true, status: true, payment_date: true },
              })
              .then((rows) =>
                rows.map((r) => ({
                  id: r.id,
                  invoice_number: r.invoice_number,
                  paid_amount: Number(r.paid_amount),
                  status: r.status,
                  payment_date: r.payment_date,
                })),
              ),
          [] as Array<{ id: number; invoice_number: string | null; paid_amount: number; status: string; payment_date: Date | null }>,
        ),
      ]);
    }
  }

  return (
    <>
      <Hero eyebrow={t("site.nav.payments")} title={t("site.payments_page.hero_fallback")} subtitle={t("site.payments.intro")} />
      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        {user && isLinkedRole && students.length > 0 ? (
          <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-900">{t("site.payments.pay_section_title")}</h2>
            <p className="mt-2 text-sm text-slate-600">{t("site.payments.pay_section_intro")}</p>
            <form action="/payments/initiate" method="post" className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label className="block text-sm font-medium text-slate-700">{t("site.payments.label_student")}</label>
                <select name="student_id" className={inputClass} required defaultValue="">
                  <option value="">{t("site.admissions_apply.select")}</option>
                  {students.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.first_name} {s.last_name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700">{t("site.payments.label_fee_item")}</label>
                <select name="fee_id" className={inputClass} required defaultValue="">
                  <option value="">{t("site.admissions_apply.select")}</option>
                  {feeRows.map((fee) => (
                    <option key={fee.id} value={fee.id}>{String(fee.name ?? "")}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700">{t("site.payments.label_gateway")}</label>
                <select name="gateway" className={inputClass} required defaultValue="">
                  <option value="">{t("site.admissions_apply.select")}</option>
                  {gateways.map((g) => (
                    <option key={g.id} value={String(g.code ?? "")}>{String(g.name ?? "")}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700">{t("site.payments.label_amount")}</label>
                <input name="amount" type="number" min="1" step="0.01" className={inputClass} required />
              </div>
              <div className="sm:col-span-2 lg:col-span-4">
                <button className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                  {t("site.payments.proceed")}
                </button>
              </div>
            </form>
          </section>
        ) : null}

        <section className="mt-10">
          <h2 className="text-lg font-semibold text-slate-900">{t("site.payments.fee_table_title")}</h2>
          {feeRows.length === 0 ? (
            <p className="mt-4 text-sm text-slate-600">{t("site.payments.fee_table_empty")}</p>
          ) : (
            <div className="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
              <table className="min-w-full divide-y divide-slate-200 text-sm">
                <thead className="bg-slate-50">
                  <tr>
                    <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_name")}</th>
                    <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_type")}</th>
                    <th className="px-4 py-3 text-right font-semibold text-slate-700">{t("site.payments.col_amount")}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {feeRows.map((fee) => (
                    <tr key={fee.id}>
                      <td className="px-4 py-3 text-slate-900">{String(fee.name ?? "")}</td>
                      <td className="px-4 py-3 text-slate-600">{String(fee.fee_type ?? "—")}</td>
                      <td className="px-4 py-3 text-right text-slate-900">{Number(fee.amount).toFixed(2)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>

        <section className="mt-10">
          <h2 className="text-lg font-semibold text-slate-900">{t("site.payments.gateways_title")}</h2>
          <p className="mt-2 text-sm text-slate-600">{t("site.payments.gateways_intro")}</p>
          {gateways.length === 0 ? (
            <p className="mt-4 text-sm text-slate-500">{t("site.payments.gateways_none")}</p>
          ) : (
            <ul className="mt-4 grid gap-3 sm:grid-cols-2">
              {gateways.map((g) => (
                <li key={g.id} className="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                  <span className="font-medium text-slate-900">{String(g.name ?? "")}</span>
                  {g.description ? <p className="mt-1 text-xs text-slate-600">{String(g.description).slice(0, 120)}</p> : null}
                </li>
              ))}
            </ul>
          )}
        </section>

        {user && isLinkedRole ? (
          feePayments.length > 0 ? (
            <section className="mt-10">
              <h2 className="text-lg font-semibold text-slate-900">{t("site.payments.history_title")}</h2>
              <div className="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                  <thead className="bg-slate-50">
                    <tr>
                      <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_invoice")}</th>
                      <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_date")}</th>
                      <th className="px-4 py-3 text-right font-semibold text-slate-700">{t("site.payments.col_paid")}</th>
                      <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_status")}</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {feePayments.map((p) => (
                      <tr key={p.id}>
                        <td className="px-4 py-3 font-mono text-xs text-slate-800">{String(p.invoice_number ?? "")}</td>
                        <td className="px-4 py-3 text-slate-600">
                          {p.payment_date
                            ? new Date(p.payment_date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })
                            : "—"}
                        </td>
                        <td className="px-4 py-3 text-right text-slate-900">{Number(p.paid_amount).toFixed(2)}</td>
                        <td className="px-4 py-3 text-slate-600">
                          <div className="flex items-center justify-between gap-3">
                            <span>{String(p.status ?? "")}</span>
                            <Link href={`/payments/receipts/${p.id}`} className="text-xs font-medium text-blue-600 hover:underline">
                              {t("site.payments.receipt")}
                            </Link>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <p className="mt-3 text-xs text-slate-500">{t("site.payments.history_footer")}</p>
            </section>
          ) : (
            <p className="mt-8 text-sm text-slate-600">{t("site.payments.no_payments_linked")}</p>
          )
        ) : (
          <p className="mt-8 text-sm text-slate-600">{t("site.payments.login_prompt")}</p>
        )}
      </div>
    </>
  );
}