import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

type PaymentRow = {
  id: number;
  invoice_number: string | null;
  payment_method: string | null;
  payment_status: string | null;
  total_amount: unknown;
  reference_number: string | null;
};

export default async function PaymentStatusPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const payment = await safe(
    () => prisma.payments.findFirst({ where: { id: Number(id) || -1 } }),
    null as PaymentRow | null,
  );
  if (!payment) notFound();

  const ok = String(payment.payment_status ?? "").toLowerCase() === "completed";
  const failed = ["failed", "cancelled", "expired"].includes(String(payment.payment_status ?? "").toLowerCase());

  return (
    <div className="mx-auto max-w-4xl px-4 py-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">{t("site.payment_status.page_title")}</h1>
          <p className="mt-1 text-sm text-slate-600">{t("site.payment_status.intro")}</p>
        </div>
        <Link href="/payments" className="text-sm font-semibold text-slate-700 hover:text-slate-900">{t("site.payment_status.back")}</Link>
      </div>

      <section className="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className={`mb-5 rounded-lg border p-4 text-sm ${ok ? "border-emerald-200 bg-emerald-50 text-emerald-900" : failed ? "border-red-200 bg-red-50 text-red-900" : "border-amber-200 bg-amber-50 text-amber-900"}`} role="status" aria-live="polite">
          <div className="font-semibold">{ok ? t("site.payment_status.success_title") : failed ? t("site.payment_status.failed_title") : t("site.payment_status.pending_title")}</div>
          <div className="mt-1">{ok ? t("site.payment_status.success_body") : failed ? t("site.payment_status.failed_body") : t("site.payment_status.pending_body")}</div>
        </div>

        <dl className="grid gap-3 text-sm sm:grid-cols-2">
          <dt className="text-slate-500">{t("site.payment_status.invoice")}</dt>
          <dd className="font-mono text-slate-900">{String(payment.invoice_number ?? "")}</dd>
          <dt className="text-slate-500">{t("site.payment_status.gateway")}</dt>
          <dd className="text-slate-900">{String(payment.payment_method ?? "")}</dd>
          <dt className="text-slate-500">{t("site.payment_status.status")}</dt>
          <dd className="text-slate-900">{String(payment.payment_status ?? "")}</dd>
          <dt className="text-slate-500">{t("site.payment_status.amount")}</dt>
          <dd className="text-slate-900">{Number(payment.total_amount).toFixed(2)}</dd>
          <dt className="text-slate-500">{t("site.payment_status.transaction")}</dt>
          <dd className="font-mono text-slate-900">{payment.reference_number ? String(payment.reference_number) : "—"}</dd>
        </dl>

        <p className="mt-4 text-xs text-slate-500">{t("site.payment_status.tip_refresh")}</p>
      </section>
    </div>
  );
}
