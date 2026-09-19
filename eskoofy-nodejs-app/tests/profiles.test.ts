import { describe, expect, it } from "vitest";
import { PROFILES } from "@/config/eskoolfy";

describe("variant profiles (BD/INT as data, never branching)", () => {
  it("bd ships bilingual + local gateways", () => {
    expect(PROFILES.bd.locales).toEqual(["en", "bn"]);
    expect(PROFILES.bd.currency).toBe("BDT");
    expect(PROFILES.bd.gateways).toEqual(["bkash", "rocket", "nagad"]);
    expect(PROFILES.bd.features.ministryLinks).toBe(true);
  });

  it("int ships English-only + international gateways", () => {
    expect(PROFILES.int.locales).toEqual(["en"]);
    expect(PROFILES.int.currency).toBe("USD");
    expect(PROFILES.int.gateways).toEqual(["stripe", "paypal", "paddle"]);
    expect(PROFILES.int.features.ministryLinks).toBe(false);
  });

  it("keeps the two profiles disjoint on gates and currency", () => {
    const overlap = PROFILES.bd.gateways.filter((gateway) => PROFILES.int.gateways.includes(gateway));
    expect(overlap).toEqual([]);
    expect(PROFILES.bd.currency).not.toBe(PROFILES.int.currency);
  });
});
