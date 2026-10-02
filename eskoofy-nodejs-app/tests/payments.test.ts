import { describe, expect, it } from "vitest";
import {
  buildCheckoutUrl,
  classifyStatus,
  computeHmac,
  gatewayBaseUrl,
  gatewayConfig,
  isGatewayConfigured,
  payloadHash,
  redactPayload,
  signatureHeaderFor,
  supportedCurrencies,
  verifyWebhookSignature,
} from "@/lib/payments/gateways";

const headerGetter = (headers: Record<string, string>) => (name: string) => headers[name] ?? null;

describe("gatewayConfig", () => {
  it("merges extra_attributes over the row defaults", () => {
    const config = gatewayConfig({
      code: "custom",
      currency: "USD",
      api_key: "k",
      extra_attributes: JSON.stringify({ checkout_method: "POST", verify_url: "https://v.test" }),
    });
    expect(config.currency).toBe("USD");
    expect(config.api_key).toBe("k");
    expect(config.checkout_method).toBe("POST");
    expect(config.verify_url).toBe("https://v.test");
  });

  it("tolerates malformed extra_attributes JSON", () => {
    expect(gatewayConfig({ code: "x", extra_attributes: "{not json" }).api_key).toBe("");
  });
});

describe("isGatewayConfigured", () => {
  it("treats offline gateways as always configured", () => {
    expect(isGatewayConfigured({ code: "cash", is_online: false })).toBe(true);
  });

  it("needs key + URL for config-driven gateways", () => {
    expect(isGatewayConfigured({ code: "skrill", is_online: true, api_key: "k" })).toBe(false);
    expect(isGatewayConfigured({ code: "skrill", is_online: true, api_key: "k", sandbox_url: "https://s" })).toBe(true);
  });

  it("needs key + secret + callback for stripe", () => {
    expect(isGatewayConfigured({ code: "stripe", is_online: true, api_key: "k", api_secret: "s" })).toBe(false);
    expect(isGatewayConfigured({ code: "stripe", is_online: true, api_key: "k", api_secret: "s", callback_url: "https://c" })).toBe(true);
  });
});

describe("gatewayBaseUrl", () => {
  it("picks sandbox in test mode and live otherwise", () => {
    const row = { code: "x", test_mode: true, sandbox_url: "https://sandbox.test/", live_url: "https://live.test/" };
    expect(gatewayBaseUrl(row)).toBe("https://sandbox.test");
    expect(gatewayBaseUrl({ ...row, test_mode: false })).toBe("https://live.test");
  });
});

describe("buildCheckoutUrl", () => {
  const params = { amount: "10.00", currency: "BDT", reference: "INV1", invoice: "INV1", callback: "https://cb", cancel: "https://cx", api_key: "k" };

  it("appends a query string by default", () => {
    const url = buildCheckoutUrl("https://gw.test/pay", gatewayConfig({ code: "x" }), params);
    expect(url).toContain("https://gw.test/pay?");
    expect(url).toContain("amount=10.00");
    expect(url).toContain("reference=INV1");
  });

  it("uses & when the base already has a query", () => {
    const url = buildCheckoutUrl("https://gw.test/pay?mode=test", gatewayConfig({ code: "x" }), params);
    expect(url).toContain("?mode=test&amount=10.00");
  });

  it("expands a checkout_url_template with encoded placeholders", () => {
    const config = gatewayConfig({ code: "x", extra_attributes: JSON.stringify({ checkout_url_template: "https://gw.test/{reference}?a={amount}" }) });
    expect(buildCheckoutUrl("", config, params)).toBe("https://gw.test/INV1?a=10.00");
  });
});

describe("signatureHeaderFor", () => {
  it("uses per-gateway defaults", () => {
    expect(signatureHeaderFor("bkash", gatewayConfig({ code: "bkash" }))).toBe("X-Bkash-Signature");
    expect(signatureHeaderFor("unknown", gatewayConfig({ code: "unknown" }))).toBe("X-Webhook-Signature");
  });

  it("honours a configured header", () => {
    const config = gatewayConfig({ code: "x", extra_attributes: JSON.stringify({ signature_header: "X-Sig" }) });
    expect(signatureHeaderFor("x", config)).toBe("X-Sig");
  });
});

describe("verifyWebhookSignature (fail-closed)", () => {
  const body = '{"status":"COMPLETED"}';

  it("rejects when no secret or key is configured", () => {
    expect(verifyWebhookSignature({ code: "x" }, body, headerGetter({}))).toBe(false);
  });

  it("accepts a correct HMAC-SHA256 signature", () => {
    const row = { code: "x", api_secret: "shh" };
    const sig = computeHmac("shh", body);
    expect(verifyWebhookSignature(row, body, headerGetter({ "X-Webhook-Signature": sig }))).toBe(true);
  });

  it("rejects a wrong signature", () => {
    expect(verifyWebhookSignature({ code: "x", api_secret: "shh" }, body, headerGetter({ "X-Webhook-Signature": "nope" }))).toBe(false);
  });

  it("rejects a missing signature header", () => {
    expect(verifyWebhookSignature({ code: "x", api_secret: "shh" }, body, headerGetter({}))).toBe(false);
  });

  it("falls back to API-key equality when only a key is set", () => {
    expect(verifyWebhookSignature({ code: "x", api_key: "key" }, body, headerGetter({ "X-API-Key": "key" }))).toBe(true);
    expect(verifyWebhookSignature({ code: "x", api_key: "key" }, body, headerGetter({ "X-API-Key": "other" }))).toBe(false);
  });
});

describe("classifyStatus", () => {
  it("classifies success, failure and pending values", () => {
    expect(classifyStatus("COMPLETED")).toBe("success");
    expect(classifyStatus("paid")).toBe("success");
    expect(classifyStatus("CANCELLED")).toBe("failure");
    expect(classifyStatus("PROCESSING")).toBe("pending");
    expect(classifyStatus(undefined)).toBe("pending");
  });

  it("treats the configured expected value as success", () => {
    expect(classifyStatus("APPROVED", "APPROVED")).toBe("success");
  });
});

describe("payloadHash / redactPayload / supportedCurrencies", () => {
  it("hashes gateway + raw body deterministically", () => {
    expect(payloadHash("bkash", "{}")).toBe(payloadHash("bkash", "{}"));
    expect(payloadHash("bkash", "{}")).not.toBe(payloadHash("nagad", "{}"));
  });

  it("redacts sensitive keys recursively", () => {
    expect(redactPayload({ card_number: "1", nested: { api_secret: "s", keep: "ok" } })).toEqual({
      card_number: "[REDACTED]",
      nested: { api_secret: "[REDACTED]", keep: "ok" },
    });
  });

  it("parses supported currencies or falls back to the gateway currency", () => {
    expect(supportedCurrencies({ code: "x", supported_currencies: JSON.stringify(["BDT", "USD"]) })).toEqual(["BDT", "USD"]);
    expect(supportedCurrencies({ code: "x", currency: "USD", supported_currencies: "not-json" })).toEqual(["USD"]);
  });
});
