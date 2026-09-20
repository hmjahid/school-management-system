import { describe, it, expect } from "vitest";
import { demoEvents } from "@/lib/demo-events";
import { demoGallery } from "@/lib/demo-gallery";
import { demoLeaveTypes } from "@/lib/demo-leaves";
import { demoVehicles } from "@/lib/demo-transport";
import { demoLedgerRows } from "@/lib/demo-ledger";
import { demoUsers } from "@/lib/demo-users";
import { demoExams, DEMO_EXAM_NAMES } from "@/lib/demo-exams";
import { demoAttendanceRows } from "@/lib/demo-attendance";

describe("G5 demo-contents: mirrored from real Laravel seeders (no invented rows)", () => {
  it("events match DemoEventSeeder exactly", () => {
    expect(demoEvents.map((e) => e.title)).toEqual([
      "Annual Sports Day",
      "Cultural Program 2025",
      "Parent-Teacher Meeting",
      "Science Fair",
      "Independence Day Celebration",
      "Victory Day Program",
      "Educational Tour",
    ]);
    expect(demoEvents[0].location).toBe("School Playground");
  });

  it("gallery matches DemoGallerySeeder", () => {
    expect(demoGallery.length).toBe(7);
    expect(demoGallery[0].category).toBe("sports");
    expect(demoGallery.every((g) => g.is_published)).toBe(true);
  });

  it("leave types match DemoLeaveSeeder (days/year)", () => {
    expect(demoLeaveTypes.map((l) => l.daysPerYear)).toEqual([14, 10, 30, 120, 7]);
    expect(demoLeaveTypes[0].name).toBe("Sick Leave");
  });

  it("vehicles match DemoTransportSeeder plates", () => {
    expect(demoVehicles.map((v) => v.number)).toContain("DHAKA-METRO-B-12-3456");
    expect(demoVehicles.every((v) => v.capacity > 0)).toBe(true);
  });

  it("ledger opening balances match DemoLedgerSeeder", () => {
    expect(demoLedgerRows).toEqual([
      { note: "Opening balance - Cash on Hand", debit: 150000, credit: 0 },
      { note: "Opening balance - Bank Account", debit: 500000, credit: 0 },
    ]);
  });

  it("users mirror DemoUsersSeeder shape (roleIds present)", () => {
    expect(demoUsers.every((u) => u.roleId > 0)).toBe(true);
    expect(demoUsers[0].name).toBe("Admin User");
  });

  it("exam generator produces the real exam vocabulary", () => {
    expect(DEMO_EXAM_NAMES).toEqual(["First Term Examination", "Second Term Examination", "Final Examination"]);
    expect(demoExams(2, 3)).toHaveLength(3);
  });

  it("attendance generator fills a present/absent window", () => {
    const rows = demoAttendanceRows([1, 2], "2026-09-01");
    expect(rows.length).toBe(2 * 30);
    expect(rows[0].status).toBe("absent"); // day 0 is the weekly absent slot
    expect(rows.some((r) => r.status === "present")).toBe(true);
  });
});
