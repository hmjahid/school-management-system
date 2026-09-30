import { describe, expect, it } from "vitest";
import { PROFILES, eskoolfy } from "@/config/eskoolfy";

// Optional international gateways shipped in BOTH variants (disabled until an
// admin enables them in Dashboard > Payment Gateways).
const INTERNATIONAL_GATEWAYS = [
  "gpay",
  "applepay",
  "razorpay",
  "paystack",
  "flutterwave",
  "sslcommerz",
  "square",
  "mollie",
  "authorize_net",
  "xendit",
  "adyen",
  "skrill",
];

describe("variant profiles (BD/INT as data, never branching)", () => {
  it("bd ships bilingual + local gateways plus the optional international set", () => {
    expect(PROFILES.bd.locales).toEqual(["en", "bn"]);
    expect(PROFILES.bd.currency).toBe("BDT");
    expect(PROFILES.bd.gateways).toEqual(["bkash", "rocket", "nagad", "uddoktapay", ...INTERNATIONAL_GATEWAYS]);
    expect(PROFILES.bd.features.ministryLinks).toBe(true);
  });

  it("int ships English-only + international gateways (existing + optional)", () => {
    expect(PROFILES.int.locales).toEqual(["en"]);
    expect(PROFILES.int.currency).toBe("USD");
    expect(PROFILES.int.gateways).toEqual(["stripe", "paypal", "paddle", ...INTERNATIONAL_GATEWAYS]);
    expect(PROFILES.int.features.ministryLinks).toBe(false);
  });

  it("shares the optional international gateways across both variants", () => {
    for (const code of INTERNATIONAL_GATEWAYS) {
      expect(PROFILES.bd.gateways).toContain(code);
      expect(PROFILES.int.gateways).toContain(code);
    }

    // The variant-owned gateways stay exclusive.
    expect(PROFILES.bd.gateways).not.toContain("stripe");
    expect(PROFILES.int.gateways).not.toContain("bkash");
    expect(PROFILES.bd.currency).not.toBe(PROFILES.int.currency);
  });

  it("keeps the optional international gateways out of the restore activation set", () => {
    for (const variant of ["bd", "int"] as const) {
      for (const code of INTERNATIONAL_GATEWAYS) {
        expect(eskoolfy.restore.gateways[variant]).not.toContain(code);
      }
      // The variant-owned + offline gateways are still reconciled.
      expect(eskoolfy.restore.gateways[variant]).toContain("cash");
      expect(eskoolfy.restore.gateways[variant]).toContain(variant === "bd" ? "bkash" : "stripe");
    }
  });
});
