import { describe, expect, it } from "vitest";
import { can, canAny, PERMISSIONS, rolePermissions } from "@/lib/permissions";

describe("permissions (spatie-equivalent)", () => {
  it("gives admins every permission", () => {
    for (const permission of PERMISSIONS) {
      expect(can("admin", permission)).toBe(true);
    }
  });

  it("scopes teachers to academic permissions", () => {
    expect(can("teacher", "manage_attendance")).toBe(true);
    expect(can("teacher", "manage_payroll")).toBe(false);
    expect(can("teacher", "manage_users")).toBe(false);
  });

  it("scopes accountants to finance permissions", () => {
    expect(can("accountant", "manage_fees")).toBe(true);
    expect(can("accountant", "manage_expenses")).toBe(true);
    expect(can("accountant", "manage_students")).toBe(false);
  });

  it("denies unknown and anonymous roles", () => {
    expect(can("parent", "manage_students")).toBe(false);
    expect(can(null, "manage_students")).toBe(false);
    expect(can(undefined, "manage_students")).toBe(false);
  });

  it("supports any-of checks", () => {
    expect(canAny("librarian", ["manage_library", "manage_fees"])).toBe(true);
    expect(canAny("librarian", ["manage_fees", "manage_payroll"])).toBe(false);
  });

  it("returns no permissions for unknown roles", () => {
    expect(rolePermissions("wizard")).toEqual([]);
  });
});
