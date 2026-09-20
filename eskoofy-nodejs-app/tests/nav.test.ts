import { describe, expect, it } from "vitest";
import { NAV_GROUPS, flattenNav, implementedPaths, navLabelKeys } from "@/lib/nav";
import en from "@/lang/en";

describe("dashboard nav (parity contract)", () => {
  it("has the top-level groups in the app's order", () => {
    expect(NAV_GROUPS.map((group) => group.key)).toEqual([
      "main",
      "academic",
      "finance",
      "hr",
      "documents",
      "library",
      "operations",
      "system",
      "website",
      "administration",
      "configuration",
      "help_group",
    ]);
  });

  it("uses unique paths for implemented items", () => {
    const paths = implementedPaths();
    expect(new Set(paths).size).toBe(paths.length);
  });

  it("every nav label key has an English translation", () => {
    const missing = navLabelKeys().filter((key) => !(key in en));
    expect(missing).toEqual([]);
  });

  it("marks the implemented modules", () => {
    expect(implementedPaths().sort()).toEqual(
      [
        "/dashboard",
        "/dashboard/backup",
        "/dashboard/bulk",
        "/dashboard/students",
        "/dashboard/teachers",
        "/dashboard/classes",
        "/dashboard/attendance",
        "/dashboard/fees",
        "/dashboard/exams",
      ].sort(),
    );
  });

  it("keeps every admin-only group gated", () => {
    const adminGroups = NAV_GROUPS.filter((group) => group.adminOnly).map((group) => group.key);
    expect(adminGroups).toEqual(["system", "website", "administration", "configuration"]);
  });

  it("flattens group trees deterministically", () => {
    const flat = flattenNav();
    expect(flat.length).toBeGreaterThan(0);
    expect(flat.every((item) => item.path.startsWith("/dashboard"))).toBe(true);
  });
});
