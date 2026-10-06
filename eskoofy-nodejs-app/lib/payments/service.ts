/**
 * Payment service — the Node port of `App\Services\PaymentService` for the
 * config-driven hosted-checkout flow, plus the `PaymentSideEffects` trait and
 * the invoice-number generators from the `Payment` / `FeePayment` models.
 *
 * Gateway webhooks/callbacks are the only unauthenticated entry points; the
 * webhook route verifies the provider signature (fail-closed) before touching
 * any payment state.
 */
import { prisma } from "@/lib/prisma";
import {
  buildCheckoutUrl,
  checkoutMethod,
  classifyStatus,
  gatewayBaseUrl,
  gatewayConfig,
  getPath,
  isTestGatewayCode,
  parseTestGatewaySimulate,
  payloadHash,
  planTestGateway,
  testSandboxPath,
  type GatewayRow,
  type StatusClass,
  type TestGatewaySimulate,
} from "@/lib/payments/gateways";

/* -------------------------------------------------------------------------- */
/* Row helpers                                                                 */
/* -------------------------------------------------------------------------- */

export interface PaymentRecord {
  id: number;
  invoice_number: string;
  amount: number;
  total_amount: number;
  payment_method: string;
  payment_status: string;
  transaction_id: string | null;
  payment_details: Record<string, unknown>;
  metadata: Record<string, unknown>;
  created_by: number | null;
}

function parseObject(raw: unknown): Record<string, unknown> {
  if (raw && typeof raw === "object" && !Array.isArray(raw)) return raw as Record<string, unknown>;
  if (typeof raw !== "string" || raw === "") return {};
  try {
    const parsed: unknown = JSON.parse(raw);
    if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) return parsed as Record<string, unknown>;
  } catch {
    /* malformed → empty */
  }
  return {};
}

function toPaymentRecord(row: Record<string, unknown>): PaymentRecord {
  return {
    id: Number(row.id),
    invoice_number: String(row.invoice_number ?? ""),
    amount: Number(row.amount ?? 0),
    total_amount: Number(row.total_amount ?? 0),
    payment_method: String(row.payment_method ?? ""),
    payment_status: String(row.payment_status ?? "pending"),
    transaction_id: row.transaction_id == null ? null : String(row.transaction_id),
    payment_details: parseObject(row.payment_details),
    metadata: parseObject(row.metadata),
    created_by: row.created_by == null ? null : Number(row.created_by),
  };
}

export function parsePaymentRow(row: Record<string, unknown>): PaymentRecord {
  return toPaymentRecord(row);
}

/* -------------------------------------------------------------------------- */
/* Invoice numbers                                                             */
/* -------------------------------------------------------------------------- */

function ymd(): string {
  const now = new Date();
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
}

/** `INV{YYYYMMDD}{0000}` — mirrors `Payment::generateInvoiceNumber()`. */
export async function generatePaymentInvoiceNumber(): Promise<string> {
  const prefix = `INV${ymd()}`;
  const last = await prisma.payments.findFirst({
    where: { invoice_number: { startsWith: prefix } },
    orderBy: { id: "desc" },
    select: { invoice_number: true },
  });
  const seq = last ? Number(last.invoice_number.slice(prefix.length)) + 1 : 1;
  return `${prefix}${String(Number.isFinite(seq) ? seq : 1).padStart(4, "0")}`;
}

/** `INV-{YYYYMMDD}-{0000}` — mirrors `FeePayment::generateInvoiceNumber()`. */
export async function generateFeePaymentInvoiceNumber(): Promise<string> {
  const prefix = `INV-${ymd()}-`;
  const last = await prisma.fee_payments.findFirst({
    where: { invoice_number: { startsWith: prefix } },
    orderBy: { id: "desc" },
    select: { invoice_number: true },
  });
  const seq = last ? Number(last.invoice_number.slice(prefix.length)) + 1 : 1;
  return `${prefix}${String(Number.isFinite(seq) ? seq : 1).padStart(4, "0")}`;
}

/* -------------------------------------------------------------------------- */
/* Gateway lookup                                                              */
/* -------------------------------------------------------------------------- */

export async function findGateway(code: string): Promise<GatewayRow | null> {
  const row = await prisma.payment_gateways.findUnique({ where: { code } });
  return row ? (row as unknown as GatewayRow) : null;
}

export async function findPayment(idOrInvoice: string | number): Promise<PaymentRecord | null> {
  const numeric = Number(idOrInvoice);
  const row = await prisma.payments.findFirst({
    where: Number.isFinite(numeric) && numeric > 0
      ? { OR: [{ id: numeric }, { invoice_number: String(idOrInvoice) }] }
      : { invoice_number: String(idOrInvoice) },
  });
  return row ? toPaymentRecord(row as unknown as Record<string, unknown>) : null;
}

/* -------------------------------------------------------------------------- */
/* Initialize                                                                  */
/* -------------------------------------------------------------------------- */

export interface InitializeResult {
  success: boolean;
  gateway: string;
  payment_id: number;
  invoice_number: string;
  amount: number;
  currency: string;
  redirect_url: string | null;
  checkout_method?: "GET" | "POST";
  offline_instructions?: string | null;
  payment_details?: Record<string, unknown>;
}

export async function initializePayment(
  payment: PaymentRecord,
  gateway: GatewayRow,
  options: { return_url?: string; cancel_url?: string } = {},
): Promise<InitializeResult> {
  const currency = String(payment.payment_details.currency ?? gateway.currency ?? "BDT");

  const returnUrl = String(
    options.return_url ?? payment.payment_details.return_url ?? gateway.success_url ?? gateway.callback_url ?? "/",
  );
  const cancelUrl = String(options.cancel_url ?? payment.payment_details.cancel_url ?? gateway.cancel_url ?? returnUrl);

  // Test / sandbox gateway: never leaves this host — redirect to our own page.
  if (isTestGatewayCode(gateway.code)) {
    const sandboxPath = testSandboxPath(payment.id);

    await prisma.payments.update({
      where: { id: payment.id },
      data: {
        payment_details: JSON.stringify({
          ...payment.payment_details,
          currency,
          return_url: returnUrl,
          cancel_url: cancelUrl,
          gateway_reference: payment.invoice_number,
          gateway_checkout_url: sandboxPath,
        }),
        updated_at: new Date(),
      },
    });

    return {
      success: true,
      gateway: gateway.code,
      payment_id: payment.id,
      invoice_number: payment.invoice_number,
      amount: payment.total_amount,
      currency,
      checkout_method: "GET",
      redirect_url: sandboxPath,
      payment_details: { payment_url: sandboxPath, reference: payment.invoice_number },
    };
  }

  if (!gateway.is_online) {
    return {
      success: true,
      gateway: gateway.code,
      payment_id: payment.id,
      invoice_number: payment.invoice_number,
      amount: payment.total_amount,
      currency,
      redirect_url: null,
      offline_instructions: gateway.instructions ?? null,
      payment_details: { reference: payment.invoice_number },
    };
  }

  const config = gatewayConfig(gateway);
  const base = gatewayBaseUrl(gateway, config);

  if (base === "" && String(config.checkout_url_template ?? "") === "") {
    throw new Error(`Payment gateway [${gateway.code}] has no checkout URL configured.`);
  }

  const redirectUrl = buildCheckoutUrl(base, config, {
    amount: payment.total_amount.toFixed(2),
    currency,
    reference: payment.invoice_number,
    invoice: payment.invoice_number,
    callback: returnUrl,
    cancel: cancelUrl,
    api_key: String(config.api_key ?? ""),
  });

  await prisma.payments.update({
    where: { id: payment.id },
    data: {
      payment_details: JSON.stringify({
        ...payment.payment_details,
        currency,
        return_url: returnUrl,
        cancel_url: cancelUrl,
        gateway_reference: payment.invoice_number,
        gateway_checkout_url: redirectUrl,
      }),
      updated_at: new Date(),
    },
  });

  return {
    success: true,
    gateway: gateway.code,
    payment_id: payment.id,
    invoice_number: payment.invoice_number,
    amount: payment.total_amount,
    currency,
    checkout_method: checkoutMethod(config),
    redirect_url: redirectUrl,
    payment_details: { payment_url: redirectUrl },
  };
}

/* -------------------------------------------------------------------------- */
/* Side effects + completion                                                   */
/* -------------------------------------------------------------------------- */

/** Port of `PaymentSideEffects::applyPaymentSideEffects()` — mark the fee paid. */
export async function applyPaymentSideEffects(payment: PaymentRecord, context: Record<string, unknown> = {}): Promise<void> {
  const feePaymentId = payment.metadata.fee_payment_id;
  if (!feePaymentId) return;

  const feePayment = await prisma.fee_payments.findUnique({ where: { id: Number(feePaymentId) } });
  if (!feePayment || feePayment.status === "paid") return;

  const metadata = parseObject(feePayment.metadata);
  await prisma.fee_payments.update({
    where: { id: feePayment.id },
    data: {
      status: "paid",
      paid_amount: feePayment.amount,
      balance: 0,
      transaction_id: context.transaction_id ? String(context.transaction_id) : feePayment.transaction_id,
      payment_method: "online_payment",
      metadata: JSON.stringify({
        ...metadata,
        gateway: context.gateway ?? payment.payment_method,
        payment_id: payment.id,
        invoice_number: payment.invoice_number,
      }),
      updated_at: new Date(),
    },
  });
}

async function complete(payment: PaymentRecord, gateway: GatewayRow, data: Record<string, unknown>): Promise<PaymentRecord> {
  const transactionId = String(data.transaction_id ?? data.id ?? payment.transaction_id ?? "");

  const updated = await prisma.payments.update({
    where: { id: payment.id },
    data: {
      payment_status: "completed",
      paid_amount: payment.total_amount,
      due_amount: 0,
      payment_date: new Date(),
      transaction_id: transactionId || null,
      payment_details: JSON.stringify({
        ...payment.payment_details,
        transaction_id: transactionId,
        gateway_status: String(data.status ?? "COMPLETED"),
        gateway_response: data,
        verified_at: new Date().toISOString(),
      }),
      updated_at: new Date(),
    },
  });

  const record = toPaymentRecord(updated as unknown as Record<string, unknown>);
  await applyPaymentSideEffects(record, { gateway: gateway.code, transaction_id: transactionId, raw: data });
  return record;
}

async function fail(payment: PaymentRecord, data: Record<string, unknown>, actual: string): Promise<PaymentRecord> {
  const updated = await prisma.payments.update({
    where: { id: payment.id },
    data: {
      payment_status: "failed",
      payment_details: JSON.stringify({
        ...payment.payment_details,
        gateway_status: actual,
        failure_reason: String(data.message ?? "Payment failed"),
        gateway_response: data,
        verified_at: new Date().toISOString(),
      }),
      updated_at: new Date(),
    },
  });
  return toPaymentRecord(updated as unknown as Record<string, unknown>);
}

/* -------------------------------------------------------------------------- */
/* Verify                                                                      */
/* -------------------------------------------------------------------------- */

export async function verifyPayment(payment: PaymentRecord, gateway: GatewayRow): Promise<PaymentRecord> {
  // The test gateway never calls out: its recorded status is authoritative,
  // so re-verifying a paid payment is a no-op (no side effects, no network).
  if (isTestGatewayCode(gateway.code)) return payment;

  const config = gatewayConfig(gateway);
  const verifyUrl = String(config.verify_url ?? "");
  if (verifyUrl === "") return payment;

  const currency = String(payment.payment_details.currency ?? gateway.currency ?? "BDT");
  let response: Response;
  try {
    response = await fetch(verifyUrl, {
      method: "POST",
      headers: {
        "content-type": "application/json",
        accept: "application/json",
        ...(String(config.api_key ?? "") ? { authorization: `Bearer ${String(config.api_key)}`, "x-api-key": String(config.api_key) } : {}),
      },
      body: JSON.stringify({
        reference: payment.invoice_number,
        amount: payment.total_amount.toFixed(2),
        currency,
        transaction_id: payment.transaction_id ?? "",
      }),
    });
  } catch {
    return payment;
  }

  if (!response.ok) return payment;

  const data = parseObject(await response.json().catch(() => ({})));
  const actual = String(getPath(data, String(config.verify_success_path ?? "status")) ?? "");
  const status: StatusClass = classifyStatus(actual, String(config.verify_success_value ?? "COMPLETED"));

  if (status === "success") return complete(payment, gateway, data);
  if (status === "failure") return fail(payment, data, actual);
  return payment;
}

/* -------------------------------------------------------------------------- */
/* Test / sandbox gateway                                                      */
/* -------------------------------------------------------------------------- */

/**
 * Apply a simulated outcome to a `test_gateway` payment.
 *
 * Success flows through the same paid path as a real gateway (`complete()` →
 * side effects), with `transaction_id = TEST-<reference>`; failure/cancel use
 * the product's existing status vocabulary. A payment that is already `paid`
 * is returned untouched, so re-running the callback stays idempotent.
 */
export async function simulateTestGateway(
  payment: PaymentRecord,
  gateway: GatewayRow,
  simulate: TestGatewaySimulate | null,
): Promise<PaymentRecord> {
  const plan = planTestGateway(payment, simulate);

  if (plan.action === "complete") {
    return complete(payment, gateway, {
      transaction_id: plan.transaction_id,
      status: plan.status,
      simulated: true,
    });
  }

  if (plan.action === "fail" || plan.action === "cancel") {
    // `plan.status` is the gateway-level status (FAILED / CANCELLED), stored in
    // `payment_details.gateway_status`; `payment_status` uses the product's own
    // lowercase vocabulary (`failed` / `cancelled`), same as `fail()` above.
    const paymentStatus = plan.action === "fail" ? "failed" : "cancelled";
    const updated = await prisma.payments.update({
      where: { id: payment.id },
      data: {
        payment_status: paymentStatus,
        payment_details: JSON.stringify({
          ...payment.payment_details,
          gateway_status: plan.status,
          failure_reason: plan.reason,
          gateway_response: { simulate: plan.action, status: plan.status },
          verified_at: new Date().toISOString(),
        }),
        updated_at: new Date(),
      },
    });
    return toPaymentRecord(updated as unknown as Record<string, unknown>);
  }

  return payment;
}

/* -------------------------------------------------------------------------- */
/* Callback                                                                    */
/* -------------------------------------------------------------------------- */

export async function processCallback(gatewayCode: string, data: Record<string, unknown>): Promise<PaymentRecord> {
  const gateway = await findGateway(gatewayCode);
  if (!gateway) throw new Error(`Unknown gateway: ${gatewayCode}`);

  const metadata = parseObject(data.metadata);
  let payment: PaymentRecord | null = null;

  for (const id of [metadata.payment_id, data.payment_id]) {
    if (id) {
      const found = await findPayment(Number(id));
      if (found) {
        payment = found;
        break;
      }
    }
  }

  if (!payment) {
    const reference = data.reference ?? data.invoice_number ?? metadata.invoice_number;
    if (reference) payment = await findPayment(String(reference));
  }

  if (!payment) throw new Error(`Payment not found for ${gatewayCode} callback.`);

  // Sandbox simulate: the local page posts `simulate=success|failure|cancel`.
  if (isTestGatewayCode(gateway.code)) {
    return simulateTestGateway(payment, gateway, parseTestGatewaySimulate(data.simulate));
  }

  return verifyPayment(payment, gateway);
}

/* -------------------------------------------------------------------------- */
/* Webhook                                                                     */
/* -------------------------------------------------------------------------- */

export interface WebhookOutcome {
  duplicate: boolean;
  payment_id?: number;
  status?: string;
}

/** Port of `PaymentController::webhook()` — dedupe by payload hash, then verify + complete. */
export async function handleWebhook(gatewayCode: string, rawBody: string, payload: Record<string, unknown>, headers: Record<string, string>): Promise<WebhookOutcome> {
  const hash = payloadHash(gatewayCode, rawBody);

  const existing = await prisma.payment_webhook_events.findUnique({ where: { payload_hash: hash } });
  if (existing?.processed_at) return { duplicate: true };

  const event = existing ?? (await prisma.payment_webhook_events.create({
    data: {
      gateway: gatewayCode,
      payload_hash: hash,
      headers: JSON.stringify(headers),
      payload: JSON.stringify(payload),
      created_at: new Date(),
      updated_at: new Date(),
    },
  }));

  try {
    const payment = await processCallback(gatewayCode, payload);
    await prisma.payment_webhook_events.update({
      where: { id: event.id },
      data: { processed_at: new Date(), payment_id: payment.id, result_status: payment.payment_status, updated_at: new Date() },
    });
    return { duplicate: false, payment_id: payment.id, status: payment.payment_status };
  } catch (error) {
    await prisma.payment_webhook_events.update({
      where: { id: event.id },
      data: { processed_at: new Date(), result_status: "error", updated_at: new Date() },
    });
    throw error;
  }
}

export { gatewayConfig, gatewayBaseUrl, verifyWebhookSignature, redactPayload, signatureHeaderFor } from "@/lib/payments/gateways";
