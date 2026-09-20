import { describe, it, expect } from "vitest";
import { demoFees } from "@/lib/demo-fees";

describe("A2 demo-fees: exact Laravel DemoFeeSeeder field-set (real, not invented)", () => {
  it("has exactly the 4 real rows, byte-identical IGNORING order", () => {
    const set = demoFees
      .map((f) => `${f.code}|${f.amount}|${f.feeType}|${f.name}|${f.status}`)
      .sort();
    expect(set).toEqual([
      "FEE-ANNUAL-ADMISSION|3000|yearly|Annual Admission Fee|true",
      "FEE-LAB|500|monthly|Lab Fee|true",
      "FEE-MONTHLY-TUITION|1200|monthly|Monthly Tuition Fee|true",
      "FEE-SPORTS|800|quarterly|Sports Fee|false",
    ]);
  });
  it("class ids and feetype vocabulary match the real Laravel seeder exactly", () => {
    expect(demoFees.every((f) => [1, 3, 5].includes(f.classId))).toBe(true);
    expect(new Set(demoFees.map((f) => f.feeType))).toEqual(new Set(["monthly", "quarterly", "yearly"]));
    expect(new Set(demoFees.map((f) => f.status))).toEqual(new Set([true, false]));
  });
});
