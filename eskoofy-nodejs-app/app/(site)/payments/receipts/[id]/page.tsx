import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

type FeePaymentRow = {
  id: number;
  invoice_number: string | null;
  payment_date: Date | null;
  payment_method: string | null;
  amount: unknown;
  balance: unknown;
  students: { first_name: string | null; last_name: string | null } | null;
  fees: { name: string | null } | null;
};

export default async function FeeReceiptPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const fp = await safe(
    () =>
      prisma.fee_payments.findFirst({
        where: { id: Number(id) || -1 },
        include: { students: true, fees: true },
      }),
    null as FeePaymentRow | null,
  );
  if (!fp) notFound();
  const student = (fp.students ?? {}) as Record<string, unknown>;
  const fee = (fp.fees ?? {}) as Record<string, unknown>;

  return (
    <div className="mx-auto max-w-2xl px-4 py-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">{t("site.fee_receipt.heading")}</h1>
          <p className="mt-1 text-sm text-slate-600">{t("site.fee_receipt.invoice")}: <span className="font-mono">{String(fp.invoice_number ?? "")}</span></p>
        </div>
        <Link href="/payments" className="text-sm font-semibold text-slate-700 hover:text-slate-900">{t("site.fee_receipt.back")}</Link>
      </div>

      <section className="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="border-b border-slate-100 pb-4">
          <div className="text-sm text-slate-500">{t("site.fee_receipt.billed_to")}</div>
          <div className="mt-1 font-semibold text-slate-900">{String(student.first_name ?? "")} {String(student.last_name ?? "")}</div>
        </div>
        <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
          <dt className="text-slate-500">{t("site.fee_receipt.fee")}</dt>
          <dd className="text-slate-900">{String(fee.name ?? "")}</dd>
          <dt className="text-slate-500">{t("site.fee_receipt.date")}</dt>
          <dd className="text-slate-900">{fp.payment_date ? new Date(fp.payment_date).toISOString().slice(0, 10) : "—"}</dd>
          <dt className="text-slate-500">{t("site.fee_receipt.amount")}</dt>
          <dd className="text-slate-900">{Number(fp.amount).toFixed(2)}</dd>
          <dt className="text-slate-500">{t("site.fee_receipt.balance")}</dt>
          <dd className="text-slate-900">{Number(fp.balance).toFixed(2)}</dd>
          <dt className="text-slate-500">{t("site.fee_receipt.method")}</dt>
          <dd className="text-slate-900">{String(fp.payment_method ?? "")}</dd>
        </dl>
        <button onClick={() => window.print()} className="mt-6 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
          Print
        </button>
      </section>
    </div>
  );
}
