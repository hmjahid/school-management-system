import { describe, expect, it } from "vitest";
import { resolveModel, slugToTable } from "@/lib/resources";
import { MODELS, MODEL_BY_TABLE, TABLE_NAMES, displayFields, writableFields } from "@/lib/schema";

describe("schema metadata (ported from the app)", () => {
  it("ports the app's full table set", () => {
    expect(MODELS.length).toBeGreaterThanOrEqual(100);
    for (const table of ["users", "students", "teachers", "school_classes", "classes", "exams", "exam_results", "fees"]) {
      expect(TABLE_NAMES).toContain(table);
    }
  });

  it("keeps the legacy classes / school_classes split", () => {
    expect(MODEL_BY_TABLE.has("classes")).toBe(true);
    expect(MODEL_BY_TABLE.has("school_classes")).toBe(true);
  });

  it("keeps exam_results.obtained_marks (not marks_obtained)", () => {
    const examResults = MODEL_BY_TABLE.get("exam_results");
    const names = examResults?.fields.map((field) => field.name) ?? [];
    expect(names).toContain("obtained_marks");
    expect(names).not.toContain("marks_obtained");
  });

  it("exposes display + writable fields without secrets", () => {
    const users = MODEL_BY_TABLE.get("users")!;
    expect(displayFields(users).map((f) => f.name)).not.toContain("password");
    expect(writableFields(users).map((f) => f.name)).not.toContain("password");
    expect(writableFields(users).map((f) => f.name)).not.toContain("id");
  });
});

describe("path → model resolution", () => {
  it("slugifies path segments", () => {
    expect(slugToTable("fee-payments")).toBe("fee_payments");
  });

  it("resolves plain resources by table name", () => {
    expect(resolveModel(["students"])?.table).toBe("students");
    expect(resolveModel(["teachers"])?.table).toBe("teachers");
    expect(resolveModel(["exams"])?.table).toBe("exams");
  });

  it("resolves overridden paths to the app's real tables", () => {
    expect(resolveModel(["classes"])?.table).toBe("school_classes");
    expect(resolveModel(["fee-payments"])?.table).toBe("fee_payments");
    expect(resolveModel(["staff-attendance"])?.table).toBe("staff_attendances");
  });

  it("ignores numeric ids when resolving", () => {
    expect(resolveModel(["students", "42", "edit"])?.table).toBe("students");
  });

  it("returns undefined for unknown resources", () => {
    expect(resolveModel(["not-a-real-resource"])).toBeUndefined();
  });
});
