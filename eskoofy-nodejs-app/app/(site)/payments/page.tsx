import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";
import { Hero, Section } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

const inputClass =
  "w-full rounded-lg border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20";

export default async function PaymentsPage() {
  const [feeRows, payments] = await Promise.all([
    prisma.fees.findMany({ orderBy: { name: "asc" } }),
    prisma.payments.findMany({ orderBy: { created_at: "desc" }, take: 10 }),
  ]);

  return (
    <>
      <Hero eyebrow={t("site.nav.payments")} title={t("site.pages.payments.title_fallback_bn")} subtitle={t("site.payments.intro")} />
      <Section>
        <div className="mx-auto max-w-7xl">
          <form action="/payments/initiate" method="post" className="grid gap-4 rounded-2xl border border-slate-200 bg-white p-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label className="mb-1 block text-sm font-semibold">{t("site.payments.label_student")}</label>
              <select name="student_id" className={inputClass} required>
                <option value="">{t("site.admissions_apply.select")}</option>
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold">{t("site.payments.label_fee_item")}</label>
              <select name="fee_id" className={inputClass} required>
                {feeRows.map((fee) => (
                  <option key={fee.id} value={fee.id}>{String(fee.name ?? "")}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold">{t("site.payments.label_gateway")}</label>
              <select name="gateway" className={inputClass} required>
                <option value="bkash">bKash</option>
                <option value="nagad">Nagad</option>
                <option value="rocket">Rocket</option>
              </select>
            </div>
            <div>
              <label className="mb-1 block text-sm font-semibold">{t("site.payments.label_amount")}</label>
              <input name="amount" type="number" min="1" step="0.01" required className={inputClass} />
            </div>
            <div className="sm:col-span-2 lg:col-span-4">
              <button className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                {t("site.payments.proceed")}
              </button>
            </div>
          </form>

          <div className="mt-12 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
              <thead className="bg-slate-50">
                <tr>
                  <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_invoice")}</th>
                  <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_type")}</th>
                  <th className="px-4 py-3 text-left font-semibold text-slate-700">{t("site.payments.col_date")}</th>
                  <th className="px-4 py-3 text-right font-semibold text-slate-700">{t("site.payments.col_amount")}</th>
                  <th className="px-4 py-3 text-right font-semibold text-slate-700">{t("site.payments.col_status")}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {payments.map((p) => (
                  <tr key={p.id}>
                    <td className="px-4 py-3 font-mono text-slate-900">
                      <Link href={`/payments/status/${p.id}`} className="text-blue-600 hover:underline">
                        {String(p.invoice_number ?? "")}
                      </Link>
                    </td>
                    <td className="px-4 py-3 text-slate-600">{String(p.payment_method ?? "")}</td>
                    <td className="px-4 py-3 text-slate-600">{p.payment_date ? new Date(p.payment_date).toISOString().slice(0, 10) : "—"}</td>
                    <td className="px-4 py-3 text-right text-slate-900">{Number(p.total_amount).toFixed(2)}</td>
                    <td className="px-4 py-3 text-right capitalize text-slate-600">{String(p.payment_status ?? "")}</td>
                  </tr>
                ))}
                {payments.length === 0 ? (
                  <tr><td colSpan={5} className="px-4 py-8 text-center text-slate-400">{t("site.payments.fee_table_empty")}</td></tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </div>
      </Section>
    </>
  );
}
