/**
 * Config-driven hosted-checkout gateway support — the Node mirror of the app's
 * `App\Services\Payment\GenericHostedGatewayAdapter` plus the
 * `VerifiesWebhookSignature` trait and the `PaymentGateway` accessors.
 *
 * Every gateway (the built-in BD/INT presets and any row an admin adds from the
 * dashboard) is driven entirely by its `payment_gateways` row:
 *   sandbox_url / live_url : hosted checkout endpoint (picked via test_mode)
 *   api_key / api_secret   : credentials, also used for webhook verification
 *   extra_attributes       : the optional tuning contract (see below)
 *
 * extra_attributes keys:
 *   checkout_method        GET (default) | POST
 *   checkout_url_template  URL with {amount} {currency} {reference} {invoice}
 *                          {callback} {cancel} {api_key} placeholders
 *   verify_url             server-side verification endpoint
 *   verify_success_path    JSON path in the verify response (default: status)
 *   verify_success_value   expected value at that path (default: COMPLETED)
 *   signature_header       webhook signature header (default: X-Webhook-Signature)
 */
import { createHash, createHmac, timingSafeEqual } from "node:crypto";

export interface GatewayRow {
  code: string;
  is_active?: boolean | null;
  is_online?: boolean | null;
  test_mode?: boolean | null;
  sandbox_url?: string | null;
  live_url?: string | null;
  api_key?: string | null;
  api_secret?: string | null;
  api_username?: string | null;
  api_password?: string | null;
  callback_url?: string | null;
  webhook_url?: string | null;
  success_url?: string | null;
  cancel_url?: string | null;
  ipn_url?: string | null;
  currency?: string | null;
  instructions?: string | null;
  supported_currencies?: string | null;
  extra_attributes?: string | null;
}

export type GatewayConfig = Record<string, unknown> & {
  test_mode: boolean;
  api_key: string;
  api_secret: string;
  api_username: string;
  api_password: string;
  callback_url: string;
  webhook_url: string;
  success_url: string;
  cancel_url: string;
  ipn_url: string;
  currency: string;
};

function parseJsonObject(raw: unknown): Record<string, unknown> {
  if (raw && typeof raw === "object" && !Array.isArray(raw)) return raw as Record<string, unknown>;
  if (typeof raw !== "string" || raw === "") return {};
  try {
    const parsed: unknown = JSON.parse(raw);
    if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) return parsed as Record<string, unknown>;
  } catch {
    /* malformed JSON → empty */
  }
  return {};
}

function text(value: unknown): string {
  return value === null || value === undefined ? "" : String(value);
}

/** Port of `PaymentGateway::getApiConfig()` — row fields merged under extra_attributes. */
export function gatewayConfig(row: GatewayRow): GatewayConfig {
  const base = {
    test_mode: row.test_mode !== false,
    api_key: text(row.api_key),
    api_secret: text(row.api_secret),
    api_username: text(row.api_username),
    api_password: text(row.api_password),
    callback_url: text(row.callback_url),
    webhook_url: text(row.webhook_url),
    success_url: text(row.success_url),
    cancel_url: text(row.cancel_url),
    ipn_url: text(row.ipn_url),
    currency: text(row.currency) || "BDT",
  };

  return { ...base, ...parseJsonObject(row.extra_attributes) };
}

/* -------------------------------------------------------------------------- */
/* Test / sandbox gateway (zero credentials, local simulated results)          */
/* -------------------------------------------------------------------------- */

/** Credential-free test gateway code (same code the app's refunds short-circuit on). */
export const TEST_GATEWAY_CODE = "test_gateway";
export const TEST_GATEWAY_LABEL = "Test / Sandbox";

export function isTestGatewayCode(code: string): boolean {
  return code === TEST_GATEWAY_CODE;
}

/** Adapter resolved for a gateway row — the admin diagnostics "adapter" column. */
export type GatewayAdapterName = "TestGatewayAdapter" | "GenericHostedGatewayAdapter" | "OfflineGateway";

export function resolveGatewayAdapter(row: Pick<GatewayRow, "code" | "is_online">): GatewayAdapterName {
  if (isTestGatewayCode(row.code)) return "TestGatewayAdapter";
  if (!row.is_online) return "OfflineGateway";
  return "GenericHostedGatewayAdapter";
}

/** Local sandbox page for a payment — Laravel's `payments.sandbox` URL shape. */
export function testSandboxPath(paymentId: number | string): string {
  return `/payments/sandbox/${paymentId}`;
}

export type TestGatewaySimulate = "success" | "failure" | "cancel";

/** `simulate` form value → outcome (case-insensitive), or null when unusable. */
export function parseTestGatewaySimulate(value: unknown): TestGatewaySimulate | null {
  const normalized = text(value).trim().toLowerCase();
  if (normalized === "success" || normalized === "failure" || normalized === "cancel") return normalized;
  return null;
}

export interface TestGatewayPaymentState {
  payment_method: string;
  payment_status: string;
  invoice_number: string;
}

export type TestGatewayPlan =
  | { action: "noop"; reason: string }
  | { action: "complete"; transaction_id: string; status: string }
  | { action: "fail"; status: string; reason: string }
  | { action: "cancel"; status: string; reason: string };

/**
 * Pure simulate decision for the test gateway:
 *   - only payments taken with `test_gateway` can be simulated,
 *   - a paid payment is never changed (verify stays idempotent),
 *   - success completes with `TEST-<reference>`, failure/cancel reuse the
 *     product's existing status vocabulary (`failed` / `cancelled`).
 */
export function planTestGateway(payment: TestGatewayPaymentState, simulate: TestGatewaySimulate | null): TestGatewayPlan {
  if (payment.payment_method !== TEST_GATEWAY_CODE) return { action: "noop", reason: "not a test payment" };
  if (payment.payment_status === "completed") return { action: "noop", reason: "already paid" };
  if (simulate === "success") return { action: "complete", transaction_id: `TEST-${payment.invoice_number}`, status: "COMPLETED" };
  if (simulate === "failure") return { action: "fail", status: "FAILED", reason: "Simulated payment failure" };
  if (simulate === "cancel") return { action: "cancel", status: "CANCELLED", reason: "Payment cancelled in the sandbox" };
  return { action: "noop", reason: "no outcome simulated" };
}

/** Port of `PaymentGateway::getIsConfiguredAttribute()`. */
export function isGatewayConfigured(row: GatewayRow): boolean {
  // The test gateway never has (nor needs) credentials.
  if (isTestGatewayCode(row.code)) return true;

  if (!row.is_online) return true;

  const code = row.code;
  const apiKey = text(row.api_key);
  const apiSecret = text(row.api_secret);
  const callbackUrl = text(row.callback_url);

  if (["bkash", "nagad", "rocket"].includes(code)) return apiKey !== "" && apiSecret !== "";
  if (code === "uddoktapay") return apiKey !== "";
  if (["stripe", "paypal", "sslcommerz", "paystack", "razorpay", "square", "paddle"].includes(code)) {
    return apiKey !== "" && apiSecret !== "" && callbackUrl !== "";
  }

  return apiKey !== "" && (text(row.live_url) !== "" || text(row.sandbox_url) !== "");
}

/** Hosted checkout base URL for the gateway's active mode. */
export function gatewayBaseUrl(row: GatewayRow, config: GatewayConfig = gatewayConfig(row)): string {
  const testMode = config.test_mode ?? true;
  const base = testMode ? text(row.sandbox_url) : text(row.live_url);
  return base.replace(/\/+$/, "");
}

export function checkoutMethod(config: GatewayConfig): "GET" | "POST" {
  return String(config.checkout_method ?? "GET").toUpperCase() === "POST" ? "POST" : "GET";
}

export interface CheckoutParams {
  amount: string;
  currency: string;
  reference: string;
  invoice: string;
  callback: string;
  cancel: string;
  api_key: string;
}

/** Port of `GenericHostedGatewayAdapter::buildCheckoutUrl()`. */
export function buildCheckoutUrl(base: string, config: GatewayConfig, params: CheckoutParams): string {
  const template = text(config.checkout_url_template);

  if (template !== "") {
    let url = template;
    for (const [key, value] of Object.entries(params)) {
      url = url.replaceAll(`{${key}}`, encodeURIComponent(value));
    }
    return url;
  }

  const query = new URLSearchParams({
    amount: params.amount,
    currency: params.currency,
    reference: params.reference,
    callback_url: params.callback,
  });

  return `${base}${base.includes("?") ? "&" : "?"}${query.toString()}`;
}

/** Per-gateway default signature header, matching the reference adapters. */
export function signatureHeaderFor(code: string, config: GatewayConfig): string {
  const configured = text(config.signature_header);
  if (configured !== "") return configured;

  const defaults: Record<string, string> = {
    bkash: "X-Bkash-Signature",
    nagad: "X-Nagad-Signature",
    rocket: "X-Rocket-Signature",
    stripe: "Stripe-Signature",
    paypal: "PAYPAL-TRANSMISSION-SIG",
    paddle: "Paddle-Signature",
  };

  return defaults[code] ?? "X-Webhook-Signature";
}

export function computeHmac(secret: string, body: string): string {
  return createHmac("sha256", secret).update(body).digest("hex");
}

export function payloadHash(gateway: string, rawBody: string): string {
  return createHash("sha256").update(`${gateway}|${rawBody}`).digest("hex");
}

function constantTimeEqual(a: string, b: string): boolean {
  const left = Buffer.from(a);
  const right = Buffer.from(b);
  if (left.length !== right.length || left.length === 0) return false;
  return timingSafeEqual(left, right);
}

/**
 * Fail-closed webhook verification. Returns false when no secret/key is
 * configured, when the signature header is missing, or when it does not match.
 * There is intentionally no bypass path.
 */
export function verifyWebhookSignature(
  row: GatewayRow,
  rawBody: string,
  getHeader: (name: string) => string | null,
  config: GatewayConfig = gatewayConfig(row),
): boolean {
  const header = signatureHeaderFor(row.code, config);
  const secret = text(config.api_secret);

  if (secret !== "") {
    const provided = getHeader(header) ?? getHeader("signature") ?? getHeader("X-Webhook-Signature") ?? "";
    if (provided === "") return false;
    return constantTimeEqual(computeHmac(secret, rawBody), provided);
  }

  const apiKey = text(config.api_key);
  if (apiKey === "") return false;
  const provided = getHeader(header) ?? getHeader("X-API-Key") ?? "";
  return provided !== "" && constantTimeEqual(apiKey, provided);
}

export type StatusClass = "success" | "failure" | "pending";

const SUCCESS_VALUES = new Set(["COMPLETED", "SUCCESS", "SUCCEEDED", "PAID", "CAPTURED", "SETTLED", "OK"]);
const FAILURE_VALUES = new Set(["FAILED", "CANCELLED", "CANCELED", "EXPIRED", "ERROR", "DECLINED"]);

/** Classify a gateway-reported status into success / failure / pending. */
export function classifyStatus(value: unknown, expected = "COMPLETED"): StatusClass {
  const actual = String(value ?? "").toUpperCase();
  if (actual === String(expected).toUpperCase() || SUCCESS_VALUES.has(actual)) return "success";
  if (FAILURE_VALUES.has(actual)) return "failure";
  return "pending";
}

/** Look up a dotted path in a decoded JSON object (Laravel `data_get`). */
export function getPath(source: unknown, path: string): unknown {
  let cursor: unknown = source;
  for (const segment of path.split(".")) {
    if (cursor === null || typeof cursor !== "object" || Array.isArray(cursor)) return undefined;
    cursor = (cursor as Record<string, unknown>)[segment];
  }
  return cursor;
}

const SENSITIVE = ["card_number", "pan", "cvv", "cvc", "security_code", "token", "secret", "signature", "password", "pin", "account_number"];

/** Recursively mask sensitive gateway fields before logging (port of `redactPayload`). */
export function redactPayload(payload: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = {};

  for (const [key, value] of Object.entries(payload)) {
    const lower = key.toLowerCase();
    if (SENSITIVE.some((needle) => lower.includes(needle))) {
      out[key] = "[REDACTED]";
    } else if (value && typeof value === "object" && !Array.isArray(value)) {
      out[key] = redactPayload(value as Record<string, unknown>);
    } else {
      out[key] = value;
    }
  }

  return out;
}

/** Currencies the gateway accepts (JSON column or the gateway currency). */
export function supportedCurrencies(row: GatewayRow): string[] {
  const raw = row.supported_currencies;
  if (typeof raw === "string" && raw !== "") {
    try {
      const parsed: unknown = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        const list = parsed.filter((c): c is string => typeof c === "string");
        if (list.length > 0) return list;
      }
    } catch {
      /* not JSON — fall through */
    }
  }
  return [text(row.currency) || "BDT"];
}
