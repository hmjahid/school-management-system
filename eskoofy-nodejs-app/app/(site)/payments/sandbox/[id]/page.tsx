import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { t } from "@/lib/i18n";
import {
  TEST_GATEWAY_CODE,
  TEST_GATEWAY_LABEL,
  isTestGatewayCode,
} from "@/lib/payments/gateways";

export const dynamic = "force-dynamic";

type SandboxPayment = {
  id: number;
  invoice_number: string | null;
  payment_method: string | null;
  payment_status: string | null;
  total_amount: unknown;
  payment_details: string | null;
};

const buttonClass =
  "rounded-lg px-4 py-2.5 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-1";

function parseDetails(raw: string | null): Record<string, unknown> {
  if (!raw) return {};
  try {
    const parsed: unknown = JSON.parse(raw);
    return parsed && typeof parsed === "object" && !Array.isArray(parsed) ? (parsed as Record<string, unknown>) : {};
  } catch {
    return {};
  }
}

/**
 * Local test-gateway sandbox page (`/payments/sandbox/{payment}` — the Node
 * mirror of the app's `payments.sandbox` route). It plays the role of the
 * hosted gateway page: it shows the payment, then each button POSTs
 * `simulate=success|failure|cancel` back to the product's callback endpoint.
 * No money moves and no external host is contacted.
 */
export default async function PaymentSandboxPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const payment = await safe(
    () => prisma.payments.findFirst({ where: { id: Number(id) || -1 } }),
    null as SandboxPayment | null,
  );
  if (!payment || !isTestGatewayCode(String(payment.payment_method ?? ""))) notFound();

  const gateway = await safe(
    () => prisma.payment_gateways.findUnique({ where: { code: TEST_GATEWAY_CODE } }),
    null as { name?: string | null; instructions?: string | null } | null,
  );

  const label = String(gateway?.name ?? TEST_GATEWAY_LABEL);
  const details = parseDetails(payment.payment_details);
  const currency = String(details.currency ?? "BDT");
  const reference = String(payment.invoice_number ?? "");
  const status = String(payment.payment_status ?? "pending");
  const callbackAction = `/api/v1/payments/callback/${TEST_GATEWAY_CODE}`;

  const hiddenFields = (
    <>
      <input type="hidden" name="payment_id" value={payment.id} />
      <input type="hidden" name="reference" value={reference} />
    </>
  );

  return (
    <div className="mx-auto max-w-4xl px-4 py-10">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">{label}</h1>
          <p className="mt-1 text-sm text-slate-600">Sandbox checkout · {currency}</p>
        </div>
        <Link href="/payments" className="text-sm font-semibold text-slate-700 hover:text-slate-900">
          {t("site.payment_status.back")}
        </Link>
      </div>

      <section className="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900" role="note">
          <div className="font-semibold">Free test payment — no money moves</div>
          <div className="mt-1">
            {String(gateway?.instructions ?? "Choose an outcome below to simulate the gateway response. Nothing is charged.")}
          </div>
        </div>

        <dl className="mt-5 grid gap-3 text-sm sm:grid-cols-2">
          <dt className="text-slate-500">{t("site.payment_status.gateway")}</dt>
          <dd className="text-slate-900">{label}</dd>
          <dt className="text-slate-500">{t("site.payment_status.amount")}</dt>
          <dd className="font-mono text-slate-900">
            {Number(payment.total_amount).toFixed(2)} {currency}
          </dd>
          <dt className="text-slate-500">{t("site.payment_status.invoice")}</dt>
          <dd className="font-mono text-slate-900">{reference}</dd>
          <dt className="text-slate-500">{t("site.payment_status.status")}</dt>
          <dd className="text-slate-900">{status}</dd>
        </dl>

        <div className="mt-6 flex flex-wrap gap-3">
          <form method="post" action={callbackAction} className="inline">
            {hiddenFields}
            <input type="hidden" name="simulate" value="success" />
            <button className={`${buttonClass} bg-emerald-600 hover:bg-emerald-700`}>Simulate success</button>
          </form>
          <form method="post" action={callbackAction} className="inline">
            {hiddenFields}
            <input type="hidden" name="simulate" value="failure" />
            <button className={`${buttonClass} bg-red-600 hover:bg-red-700`}>Simulate failure</button>
          </form>
          <form method="post" action={callbackAction} className="inline">
            {hiddenFields}
            <input type="hidden" name="simulate" value="cancel" />
            <button className={`${buttonClass} bg-slate-500 hover:bg-slate-600`}>{t("common.cancel")}</button>
          </form>
        </div>

        <p className="mt-4 text-xs text-slate-500">
          After simulating you are redirected to the normal payment status page, exactly like a real gateway return.
        </p>
      </section>
    </div>
  );
}
