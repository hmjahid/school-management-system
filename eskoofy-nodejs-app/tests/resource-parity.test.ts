import { describe, it, expect } from "vitest";
import { indexColumns, formColumns, TAILORED } from "@/lib/resource-config";

const verifiedColumnCount = 2;

describe("resource-config: honest mirror of ONLY the Laravel <th> sets that were actually read", () => {
  it(`tailors exactly the ${verifiedColumnCount} modules whose real index blades expose their own <th> set`, () => {
    expect(Object.keys(TAILORED).sort()).toEqual(["contact-submissions", "staff"].sort());
  });

  it("staff index columns === real blade <th> order (Name|Email|Roles|Teacher profile)", () => {
    const staff = TAILORED["staff"];
    expect(staff.indexColumns).toEqual(["name", "email", "roles", "teacher profile"]);
  });

  it("contact-submissions index columns === real blade <th> order (Date|Type|Name|Email|Message)", () => {
    const c = TAILORED["contact-submissions"];
    expect(c.indexColumns).toEqual(["date", "type", "name", "email", "message"]);
  });
});
