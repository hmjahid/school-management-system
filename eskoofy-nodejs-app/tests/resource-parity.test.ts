import { describe, it, expect } from "vitest";
import { indexColumns, leadField } from "@/lib/resource-config";
import { displayFields } from "@/lib/schema";

describe("resource-config: tailors ONLY modules verified against real Laravel <th>", () => {
  it("staff index lists the real blade header order (Name|Email|Roles|Teacher profile)", () => {
    const staff = { name: "staff", fields: [
      { name: "name" }, { name: "email" }, { name: "roles" }, { name: "teacher profile" },
    ] } as any;
    expect(indexColumns(staff).map((f) => f.name)).toEqual(["name", "email", "roles", "teacher profile"]);
  });

  it("contact-submissions lists the real blade header order (Date|Type|Name|Email|Message)", () => {
    const cs = { name: "contact-submissions", fields: [
      { name: "date" }, { name: "type" }, { name: "name" }, { name: "email" }, { name: "message" },
    ] } as any;
    expect(indexColumns(cs).map((f) => f.name)).toEqual(["date", "type", "name", "email", "message"]);
  });

  it("untailored models fall back to the generic engine (displayFields)", () => {
    const exam = { name: "exams", fields: [{ name: "title" }, { name: "class_id" }, { name: "status" }] } as any;
    expect(indexColumns(exam).length).toBeGreaterThan(0);
    expect(leadField(exam).name).toBe("title");
  });
});
