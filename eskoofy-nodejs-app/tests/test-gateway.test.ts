import { beforeEach, describe, expect, it, vi, type Mock } from "vitest";

vi.mock("@/lib/prisma", () => {
  const payments = {
    update: vi.fn(async (args: { where: { id: number }; data: Record<string, unknown> }) => ({
      id: args.where.id,
      ...args.data,
    })),
    findFirst: vi.fn(async () => null),
  };
  const payment_gateways = { findUnique: vi.fn(async () => null) };
  const fee_payments = { findUnique: vi.fn(async () => null) };
  return { prisma: { payments, payment_gateways, fee_payments } };
});

import { prisma } from "@/lib/prisma";
import {
  BD_GATEWAY_SEEDS,
  PAYMENT_GATEWAY_SEEDS,
  TEST_GATEWAY_SEED,
} from "@/prisma/gateways";
import {
  TEST_GATEWAY_CODE,
  TEST_GATEWAY_LABEL,
  buildCheckoutUrl,
  checkoutMethod,
  gatewayBaseUrl,
  gatewayConfig,
  isGatewayConfigured,
  parseTestGatewaySimulate,
  planTestGateway,
  resolveGatewayAdapter,
  signatureHeaderFor,
  testSandboxPath,
  type GatewayRow,
} from "@/lib/payments/gateways";
import {
  initializePayment,
  processCallback,
  simulateTestGateway,
  type PaymentRecord,
} from "@/lib/payments/service";

const paymentsUpdate = prisma.payments.update as unknown as Mock;
const paymentsFindFirst = prisma.payments.findFirst as unknown as Mock;
const gatewaysFindUnique = prisma.payment_gateways.findUnique as unknown as Mock;

const testGateway: GatewayRow = {
  code: TEST_GATEWAY_CODE,
  is_active: true,
  is_online: true,
  test_mode: true,
  currency: "BDT",
};

function testPayment(overrides: Partial<PaymentRecord> = {}): PaymentRecord {
  return {
    id: 7,
    invoice_number: "INV202610060001",
    amount: 10,
    total_amount: 10,
    payment_method: TEST_GATEWAY_CODE,
    payment_status: "pending",
    transaction_id: null,
    payment_details: { currency: "BDT", return_url: "/payments/status/7" },
    metadata: {},
    created_by: 1,
    ...overrides,
  };
}

function rawPaymentRow(overrides: Record<string, unknown> = {}): Record<string, unknown> {
  const payment = testPayment();
  return {
    id: payment.id,
    invoice_number: payment.invoice_number,
    amount: payment.amount,
    total_amount: payment.total_amount,
    payment_method: payment.payment_method,
    payment_status: payment.payment_status,
    transaction_id: payment.transaction_id,
    payment_details: JSON.stringify(payment.payment_details),
    metadata: JSON.stringify(payment.metadata),
    created_by: payment.created_by,
    ...overrides,
  };
}

beforeEach(() => {
  paymentsUpdate.mockClear();
  paymentsFindFirst.mockClear();
  gatewaysFindUnique.mockClear();
  paymentsFindFirst.mockImplementation(async () => null);
  gatewaysFindUnique.mockImplementation(async () => null);
});

describe("test gateway contract", () => {
  it("is credential-free and always configured", () => {
    expect(TEST_GATEWAY_SEED.code).toBe("test_gateway");
    expect(TEST_GATEWAY_SEED.code).toBe(TEST_GATEWAY_CODE);
    expect(TEST_GATEWAY_SEED.name).toBe(TEST_GATEWAY_LABEL);
    expect(TEST_GATEWAY_SEED.name).toBe("Test / Sandbox");
    expect(TEST_GATEWAY_SEED.is_active).toBe(true);
    expect(TEST_GATEWAY_SEED.sandbox_url ?? null).toBeNull();
    expect(TEST_GATEWAY_SEED.live_url ?? null).toBeNull();
    expect(TEST_GATEWAY_SEED).not.toHaveProperty("api_key");
    expect(TEST_GATEWAY_SEED).not.toHaveProperty("api_secret");

    expect(isGatewayConfigured({ code: TEST_GATEWAY_CODE, is_online: true })).toBe(true);
    expect(
      isGatewayConfigured({ code: TEST_GATEWAY_CODE, is_online: true, api_key: "", sandbox_url: null, live_url: null }),
    ).toBe(true);
  });

  it("resolves the adapter and the local sandbox path", () => {
    expect(resolveGatewayAdapter({ code: TEST_GATEWAY_CODE, is_online: true })).toBe("TestGatewayAdapter");
    expect(resolveGatewayAdapter({ code: "bkash", is_online: true })).toBe("GenericHostedGatewayAdapter");
    expect(resolveGatewayAdapter({ code: "cash", is_online: false })).toBe("OfflineGateway");
    expect(testSandboxPath(7)).toBe("/payments/sandbox/7");
  });

  it("parses simulate values", () => {
    expect(parseTestGatewaySimulate("success")).toBe("success");
    expect(parseTestGatewaySimulate("FAILURE")).toBe("failure");
    expect(parseTestGatewaySimulate(" Cancel ")).toBe("cancel");
    expect(parseTestGatewaySimulate("nope")).toBeNull();
    expect(parseTestGatewaySimulate(undefined)).toBeNull();
  });
});

describe("planTestGateway", () => {
  const pending = { payment_method: TEST_GATEWAY_CODE, payment_status: "pending", invoice_number: "INV1" };
  const paid = { ...pending, payment_status: "completed" };

  it("completes a success outcome with TEST-<reference>", () => {
    expect(planTestGateway(pending, "success")).toEqual({
      action: "complete",
      transaction_id: "TEST-INV1",
      status: "COMPLETED",
    });
  });

  it("uses the existing status vocabulary for failure and cancel", () => {
    expect(planTestGateway(pending, "failure")).toEqual({
      action: "fail",
      status: "FAILED",
      reason: expect.any(String),
    });
    expect(planTestGateway(pending, "cancel")).toEqual({
      action: "cancel",
      status: "CANCELLED",
      reason: expect.any(String),
    });
  });

  it("never changes a paid payment (verify stays idempotent)", () => {
    expect(planTestGateway(paid, "success").action).toBe("noop");
    expect(planTestGateway(paid, "failure").action).toBe("noop");
    expect(planTestGateway(paid, "cancel").action).toBe("noop");
  });

  it("only simulates test-gateway payments", () => {
    expect(planTestGateway({ ...pending, payment_method: "bkash" }, "success").action).toBe("noop");
    expect(planTestGateway(pending, null).action).toBe("noop");
  });
});

describe("test gateway initialize", () => {
  it("redirects to the local sandbox page and never leaves the host", async () => {
    const result = await initializePayment(testPayment(), testGateway, {
      return_url: "/payments/status/7",
      cancel_url: "/payments/status/7",
    });

    expect(result.success).toBe(true);
    expect(result.redirect_url).toBe("/payments/sandbox/7");
    expect(result.checkout_method).toBe("GET");
    expect(result.redirect_url!.startsWith("/")).toBe(true);
    expect(result.redirect_url).not.toMatch(/^https?:/);

    expect(paymentsUpdate).toHaveBeenCalledWith({
      where: { id: 7 },
      data: {
        payment_details: expect.stringContaining("/payments/sandbox/7"),
        updated_at: expect.any(Date),
      },
    });
  });
});

describe("test gateway simulate (callback path)", () => {
  it("marks a success simulate paid with transaction_id TEST-<reference>", async () => {
    const out = await simulateTestGateway(testPayment(), testGateway, "success");

    expect(out.payment_status).toBe("completed");
    expect(out.transaction_id).toBe("TEST-INV202610060001");
    expect(paymentsUpdate).toHaveBeenCalledTimes(1);
    const args = paymentsUpdate.mock.calls[0]![0] as { data: Record<string, unknown> };
    expect(args.data.payment_status).toBe("completed");
    expect(args.data.transaction_id).toBe("TEST-INV202610060001");
    expect(String(args.data.payment_details)).toContain("TEST-INV202610060001");
  });

  it("records failure and cancel outcomes", async () => {
    const failed = await simulateTestGateway(testPayment(), testGateway, "failure");
    expect(failed.payment_status).toBe("failed");
    const failArgs = paymentsUpdate.mock.calls[0]![0] as { data: Record<string, unknown> };
    expect(String(failArgs.data.payment_details)).toContain('"gateway_status":"FAILED"');

    const cancelled = await simulateTestGateway(testPayment(), testGateway, "cancel");
    expect(cancelled.payment_status).toBe("cancelled");
  });

  it("is idempotent: a paid test payment is returned untouched", async () => {
    const paid = testPayment({ payment_status: "completed", transaction_id: "TEST-INV202610060001" });

    const out = await simulateTestGateway(paid, testGateway, "cancel");

    expect(out.payment_status).toBe("completed");
    expect(out.transaction_id).toBe("TEST-INV202610060001");
    expect(paymentsUpdate).not.toHaveBeenCalled();
  });

  it("dispatches the sandbox callback simulate end to end", async () => {
    paymentsFindFirst.mockImplementation(async () => rawPaymentRow());
    gatewaysFindUnique.mockImplementation(async () => ({ ...testGateway }));

    const out = await processCallback(TEST_GATEWAY_CODE, { payment_id: 7, simulate: "success" });
    expect(out.payment_status).toBe("completed");
    expect(out.transaction_id).toBe("TEST-INV202610060001");
  });

  it("re-verifying a paid callback payment is a no-op", async () => {
    paymentsFindFirst.mockImplementation(async () =>
      rawPaymentRow({ payment_status: "completed", transaction_id: "TEST-INV202610060001" }),
    );
    gatewaysFindUnique.mockImplementation(async () => ({ ...testGateway }));

    const out = await processCallback(TEST_GATEWAY_CODE, { payment_id: 7, simulate: "cancel" });
    expect(out.payment_status).toBe("completed");
    expect(paymentsUpdate).not.toHaveBeenCalled();
  });

  it("refuses to simulate a non-test payment through the test callback", async () => {
    paymentsFindFirst.mockImplementation(async () => rawPaymentRow({ payment_method: "bkash" }));
    gatewaysFindUnique.mockImplementation(async () => ({ ...testGateway }));

    const out = await processCallback(TEST_GATEWAY_CODE, { payment_id: 7, simulate: "success" });
    expect(out.payment_status).toBe("pending");
    expect(paymentsUpdate).not.toHaveBeenCalled();
  });
});

describe("BD hosted gateway seeds", () => {
  const expectedCodes = ["shurjopay", "portwallet", "cellfin", "purse", "cashby", "upay", "mycash", "payer"];

  it("seeds all 8 inactive with test mode, empty credentials and draft sandbox URLs", () => {
    expect(BD_GATEWAY_SEEDS.map((seed) => seed.code)).toEqual(expectedCodes);

    for (const seed of BD_GATEWAY_SEEDS) {
      expect(seed.is_active).toBe(false);
      expect(seed.test_mode).toBe(true);
      expect(seed.is_online).toBe(true);
      expect(seed.has_api).toBe(true);
      expect(seed).not.toHaveProperty("api_key");
      expect(seed).not.toHaveProperty("api_secret");
      expect(seed.sandbox_url).toMatch(/^https:\/\/sandbox\./);
      expect(seed.currency).toBe("BDT");
    }
  });

  it("carries the GenericHosted contract in extra_attributes", () => {
    for (const seed of BD_GATEWAY_SEEDS) {
      const contract = seed.extra_attributes ?? {};
      expect(contract.checkout_method).toBe("GET");
      expect(contract.verify_success_path).toBe("status");
      expect(contract.verify_success_value).toBe("COMPLETED");
      expect(contract.signature_header).toBe("X-Webhook-Signature");
    }
  });

  it("keeps every seeded gateway code unique", () => {
    const codes = PAYMENT_GATEWAY_SEEDS.map((seed) => seed.code);
    expect(new Set(codes).size).toBe(codes.length);
    expect(codes).toContain(TEST_GATEWAY_CODE);
  });
});

describe("hosted checkout request shape (shurjopay, no network)", () => {
  const seed = BD_GATEWAY_SEEDS.find((entry) => entry.code === "shurjopay")!;
  const row: GatewayRow = {
    code: seed.code,
    is_active: seed.is_active,
    is_online: seed.is_online,
    test_mode: seed.test_mode,
    sandbox_url: seed.sandbox_url,
    live_url: seed.live_url,
    api_key: "sp_test_key",
    extra_attributes: JSON.stringify(seed.extra_attributes),
    currency: seed.currency,
  };

  it("needs credentials before it counts as configured", () => {
    expect(isGatewayConfigured({ ...row, api_key: "" })).toBe(false);
    expect(isGatewayConfigured(row)).toBe(true);
  });

  it("picks the sandbox URL in test mode and the live URL otherwise", () => {
    expect(gatewayBaseUrl(row)).toBe("https://sandbox.shurjopay.io");
    expect(gatewayBaseUrl({ ...row, test_mode: false })).toBe("https://payment.shurjohub.com");
  });

  it("builds a GET checkout request against the sandbox URL", () => {
    const config = gatewayConfig(row);
    expect(checkoutMethod(config)).toBe("GET");
    expect(signatureHeaderFor(row.code, config)).toBe("X-Webhook-Signature");

    const url = buildCheckoutUrl(gatewayBaseUrl(row), config, {
      amount: "500.00",
      currency: "BDT",
      reference: "INV1",
      invoice: "INV1",
      callback: "https://school.test/payments/status/9",
      cancel: "https://school.test/payments/status/9",
      api_key: "sp_test_key",
    });

    expect(url.startsWith("https://sandbox.shurjopay.io?")).toBe(true);
    expect(url).toContain("amount=500.00");
    expect(url).toContain("currency=BDT");
    expect(url).toContain("reference=INV1");
    expect(url).toContain("callback_url=https%3A%2F%2Fschool.test%2Fpayments%2Fstatus%2F9");
  });
});
