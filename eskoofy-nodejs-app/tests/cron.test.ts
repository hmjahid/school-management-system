import { describe, expect, it } from "vitest";
import { nextOccurrence, resolveRecipientIds } from "@/lib/scheduler";

describe("resolveRecipientIds", () => {
  it("accepts numeric and numeric-string recipients", () => {
    expect(resolveRecipientIds([1, "2", 3])).toEqual([1, 2, 3]);
  });

  it("reads {id} and {user_id} shapes", () => {
    expect(resolveRecipientIds([{ id: 4 }, { user_id: 5 }])).toEqual([4, 5]);
  });

  it("deduplicates and ignores invalid entries", () => {
    expect(resolveRecipientIds([1, 1, "x", null, { id: "7" }])).toEqual([1, 7]);
  });

  it("handles a single scalar recipient", () => {
    expect(resolveRecipientIds(9)).toEqual([9]);
  });
});

describe("nextOccurrence", () => {
  const from = new Date("2026-01-01T00:00:00Z");

  it("advances daily / weekly / monthly", () => {
    expect(nextOccurrence({ type: "daily" }, from).toISOString()).toBe("2026-01-02T00:00:00.000Z");
    expect(nextOccurrence({ type: "weekly" }, from).toISOString()).toBe("2026-01-08T00:00:00.000Z");
    expect(nextOccurrence({ type: "monthly" }, from).toISOString()).toBe("2026-02-01T00:00:00.000Z");
  });

  it("honours a custom interval and unit", () => {
    expect(nextOccurrence({ type: "custom", interval: 2, unit: "hour" }, from).toISOString()).toBe("2026-01-01T02:00:00.000Z");
    expect(nextOccurrence({ type: "custom", interval: 3, unit: "day" }, from).toISOString()).toBe("2026-01-04T00:00:00.000Z");
  });

  it("leaves 'once' at the same instant", () => {
    expect(nextOccurrence({ type: "once" }, from).toISOString()).toBe(from.toISOString());
  });
});
