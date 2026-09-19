import { describe, expect, it } from "vitest";
import { resolveResource } from "@/lib/resource-route";
import { tOr, has } from "@/lib/i18n";

describe("resource route classification (CRUD screens)", () => {
  it("classifies an index route", () => {
    const resolved = resolveResource(["students"]);
    expect(resolved.mode).toBe("index");
    expect(resolved.basePath).toBe("/dashboard/students");
    expect(resolved.model?.table).toBe("students");
  });

  it("classifies create", () => {
    const resolved = resolveResource(["students", "create"]);
    expect(resolved.mode).toBe("create");
    expect(resolved.basePath).toBe("/dashboard/students");
  });

  it("classifies show", () => {
    const resolved = resolveResource(["students", "12"]);
    expect(resolved.mode).toBe("show");
    expect(resolved.id).toBe("12");
    expect(resolved.basePath).toBe("/dashboard/students");
  });

  it("classifies edit", () => {
    const resolved = resolveResource(["students", "12", "edit"]);
    expect(resolved.mode).toBe("edit");
    expect(resolved.id).toBe("12");
    expect(resolved.basePath).toBe("/dashboard/students");
  });

  it("handles kebab-case resources and nested paths", () => {
    const resolved = resolveResource(["fee-payments", "7"]);
    expect(resolved.mode).toBe("show");
    expect(resolved.model?.table).toBe("fee_payments");
    expect(resolved.basePath).toBe("/dashboard/fee-payments");
  });

  it("still resolves a model for overridden paths", () => {
    expect(resolveResource(["classes"]).model?.table).toBe("school_classes");
  });
});

describe("i18n fallbacks", () => {
  it("returns the fallback for a missing key", () => {
    expect(has("definitely.not.a.key")).toBe(false);
    expect(tOr("definitely.not.a.key", "Fallback")).toBe("Fallback");
  });

  it("returns the translation when the key exists", () => {
    expect(has("dashboard.students")).toBe(true);
    expect(tOr("dashboard.students", "Fallback", {}, "en")).toBe("Students");
  });
});
