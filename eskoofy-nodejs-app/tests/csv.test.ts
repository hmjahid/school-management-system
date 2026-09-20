import { describe, expect, it } from "vitest";
import { formatCsv, mapRow, normalizeHeaders, parseCsv, slugHeader } from "@/lib/csv";

describe("csv", () => {
  it("formats a header row with plain fields", () => {
    expect(formatCsv(["a", "b"], [["1", "2"]])).toBe("a,b\n1,2");
  });

  it("quotes fields containing delimiter, quotes, newlines or whitespace", () => {
    const text = formatCsv(["name", "note"], [["House 1, Dhaka", 'say "hi"']]);
    expect(text).toBe('name,note\n"House 1, Dhaka","say ""hi"""');
  });

  it("parses quoted fields with embedded newlines and escaped quotes", () => {
    const text = 'name,email,note\n"Jane\nDoe",jane@x.com,"""quoted"""';
    expect(parseCsv(text)).toEqual([
      ["name", "email", "note"],
      ["Jane\nDoe", "jane@x.com", '"quoted"'],
    ]);
  });

  it("round-trips format → parse", () => {
    const rows: Array<Array<string | number | null>> = [
      ["Jane Doe", "jane@example.com", "ADM-2024-0001", "House 1, Dhaka", ""],
      ["John Smith", "john@example.com", "EMP-2024-0001", 'said "hi"', null],
    ];
    const text = formatCsv(["name", "email", "id", "address", "note"], rows);
    const parsed = parseCsv(text);
    expect(parsed[0]).toEqual(["name", "email", "id", "address", "note"]);
    expect(parsed.slice(1)).toEqual([
      ["Jane Doe", "jane@example.com", "ADM-2024-0001", "House 1, Dhaka", ""],
      ["John Smith", "john@example.com", "EMP-2024-0001", 'said "hi"', ""],
    ]);
  });

  it("slugifies headers like Laravel Str::slug", () => {
    expect(slugHeader("Admission Number")).toBe("admission_number");
    expect(slugHeader("Date of birth")).toBe("date_of_birth");
    expect(slugHeader("name")).toBe("name");
    expect(slugHeader("  Class_Code  ")).toBe("class_code");
  });

  it("normalizes + maps rows", () => {
    const normalized = normalizeHeaders(["Name", "Email", "Roll Number"]);
    expect(normalized).toEqual(["name", "email", "roll_number"]);
    expect(mapRow(normalized, ["Jane", "jane@x.com", ""])).toEqual({
      name: "Jane",
      email: "jane@x.com",
      roll_number: null,
    });
  });

  it("skips a trailing blank line like fgetcsv", () => {
    expect(parseCsv("a,b\n1,2\n")).toEqual([
      ["a", "b"],
      ["1", "2"],
    ]);
  });
});