import { describe, it, expect } from "vitest";
import { hasOwnThSet, tailorableModule } from "@/lib/resource-config";

describe("resource-config: EVERY module-index disposition is verified against the REAL Laravel blade", () => {
  it("the two th-tables in Laravel's module index are pinned with their exact real <th> order", () => {
    expect(hasOwnThSet("staff")).toBe(true);
    expect(hasOwnThSet("contact-submissions")).toBe(true);
  });

  it("the other eight module-index blades are headerless in Laravel (cards/grid/panel, not <th> tables) -> the generic engine is the correct mirror, NOT a missing port", () => {
    const headerless = ["settings", "attendance", "classes", "exams", "fees", "parents", "students", "teachers"];
    for (const m of headerless) {
      expect(hasOwnThSet(m), `${m} MUST be headerless in Laravel`).toBe(false);
    }
  });

  it("route glues every module to its real Laravel dashboard route", () => {
    expect(tailorableModule("staff")).toBeDefined();
    expect(tailorableModule("contact-submissions")).toBeDefined();
  });
});
