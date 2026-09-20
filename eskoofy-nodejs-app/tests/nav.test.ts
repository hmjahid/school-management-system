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

  it("uses unique paths for implemented items (intentional cross-group repeats allowed: staff, users, settings)", () => {
    const paths = implementedPaths();
    const dupes = paths.filter((p, i) => paths.indexOf(p) !== i);
    expect(dupes).toEqual(["/dashboard/staff", "/dashboard/users", "/dashboard/settings"]);
  });

  it("every nav label key has an English translation", () => {
    const missing = navLabelKeys().filter((key) => !(key in en));
    expect(missing).toEqual([]);
  });

  it("marks the implemented modules: every 'done' path is served by the engine (resolves to a Prisma model or a real page)", async () => {
    const { resolveModel } = await import("@/lib/resources");
    const done = implementedPaths();
    expect(done.length).toBeGreaterThan(30);
    const unresolvable = done.filter((path) => {
      const segs = path.replace(/^\/dashboard\//, "").split("/").filter(Boolean);
      return !resolveModel(segs);
    });
    // only the genuinely bespoke non-CRUD pages may not resolve to a table
    const bespokeOK = ["/dashboard", "/dashboard/backup", "/dashboard/bulk"];
    expect(unresolvable.filter((p) => !bespokeOK.includes(p))).toEqual([]);
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
