import { describe, expect, it } from "vitest";
import {
  ATTENDANCE_HEADERS,
  BulkImportError,
  FEE_HEADERS,
  runBulkImport,
  STUDENT_HEADERS,
  TEACHER_HEADERS,
  buildCsv,
  exportFilename,
  headersFor,
  RowUpserter,
} from "@/lib/bulk";

/**
 * The CSV contract is the cross-variant portability guarantee: these headers
 * must exactly match the Laravel app's `DashboardBulkController`, otherwise a
 * file exported by one variant will not import in another.
 */
const EXPECTED = {
  students: ["name", "email", "admission_number", "admission_date", "class_code", "roll_number", "gender", "date_of_birth", "phone", "present_address", "status"],
  teachers: ["name", "email", "employee_id", "joining_date", "phone", "gender", "date_of_birth", "qualification", "specialization", "status"],
  fees: ["student", "fee", "amount", "due_date", "status", "paid_at"],
  attendances: ["date", "student", "class", "status", "remarks"],
};

describe("bulk CSV contract", () => {
  it("matches the Laravel app's headers exactly (portable format)", () => {
    expect(STUDENT_HEADERS).toEqual(EXPECTED.students);
    expect(TEACHER_HEADERS).toEqual(EXPECTED.teachers);
    expect(FEE_HEADERS).toEqual(EXPECTED.fees);
    expect(ATTENDANCE_HEADERS).toEqual(EXPECTED.attendances);
  });

  it("produces a `students-YYYYMMDD-HHMMSS.csv` filename like the app", () => {
    expect(exportFilename("students")).toMatch(/^students-\d{8}-\d{6}\.csv$/);
  });

  it("backs every export row with empty values for missing fields", () => {
    const text = buildCsv("students", [{ name: "Jane", email: "j@x.com" }]);
    expect(text.split("\n")[1].split(",")).toHaveLength(STUDENT_HEADERS.length);
  });
});

describe("runBulkImport", () => {
  const upsert = async (row: Record<string, string | null>, rowNumber: number): Promise<"created" | "updated" | "skipped"> => {
    if (row.name === "break") throw new Error("boom");
    return "created";
  };

  const csv = [
    STUDENT_HEADERS.join(","),
    "Jane Doe,jane@example.com,ADM-2024-0001,2024-01-15,C1,12,female,2012-05-20,+8801711111111,House 1 Dhaka,active",
    "Jim Doe,jim@example.com,ADM-2024-0002,2024-01-16,C1,,male,,,,",
    "",
  ].join("\n");

  it("counts created/updated/skipped and skips blank lines", async () => {
    const outcome = await runBulkImport(csv, "students", upsert);
    expect(outcome.created).toBe(2);
    expect(outcome.errors).toHaveLength(0);
    expect(outcome.skipped).toBe(0);
  });

  it("collects per-row errors without aborting the batch", async () => {
    const withBadRow = [
      STUDENT_HEADERS.join(","),
      "Jane,jane@x.com,ADM-1,2024-01-15,C1",
      "break,br@x.com,ADM-2,2024-01-15,C1",
      "Anne,anne@x.com,ADM-3,2024-01-15,C1",
    ].join("\n");
    const outcome = await runBulkImport(withBadRow, "students", upsert);
    expect(outcome.created).toBe(2);
    expect(outcome.errors).toEqual(["Row 2: boom"]);
  });

  it("rejects missing required columns with their names", async () => {
    await expect(runBulkImport("name,email\nJane,j@x.com", "students", upsert)).rejects.toBeInstanceOf(
      BulkImportError,
    );
    await expect(runBulkImport("name,email\nJane,j@x.com", "students", upsert)).rejects.toThrow(/admission_number, admission_date, class_code/);
    await expect(runBulkImport(TEACHER_HEADERS.join(",") + "\nJohn,j@x.com,EMP-1,2024-01-10", "teachers", upsert)).resolves.toMatchObject({
      created: 1,
    });
  });

  it("honours dry-run (parse only, no writes)", async () => {
    const outcome = await runBulkImport(csv, "students", upsert, { dryRun: true });
    expect(outcome.dryRun).toBe(true);
    expect(outcome.parsed).toBe(2);
    expect(outcome.created).toBe(0);
  });

  it("accepts headers in any order (mapRow alignment)", async () => {
    const shuffled = "email,name,class_code,admission_date,admission_number\n" + "j@x.com,Jane,C1,2024-01-15,ADM-1";
    let seen: Record<string, string | null> = {};
    const spy: RowUpserter = async (row: Record<string, string | null>) => {
      seen = row;
      return "created";
    };
    await runBulkImport(shuffled, "students", spy);
    expect(seen.name).toBe("Jane");
    expect(seen.email).toBe("j@x.com");
    expect(seen.admission_number).toBe("ADM-1");
  });
});

describe("headersFor", () => {
  it("exposes the shared header list per resource", () => {
    expect(headersFor("teachers")).toEqual(EXPECTED.teachers);
    expect(headersFor("fees")).toEqual(EXPECTED.fees);
    expect(headersFor("attendances")).toEqual(EXPECTED.attendances);
  });
});