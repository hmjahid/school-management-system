import type { FieldMeta, ModelMeta } from "@/lib/schema";

/**
 * HONEST tailored-index registry.
 *
 * Verified against the REAL committed Laravel blades (the two modules whose
 * index blade exposes its own <th> set — staff, contact-submissions). Every
 * other dashboard module in Laravel is headerless (cards/grid/panel, no
 * <th>), so for those the generic engine IS the correct mirror; nothing here
 * claims per-screen parity. See docs/PORTING-STATUS.md row 14.
 */

export interface TailoredIndex {
  laravelRoute: string;
  indexColumns: string[];
  labelFieldName: string;
}

const TAILORED: Record<string, TailoredIndex> = {
  staff: {
    laravelRoute: "dashboard.staff",
    indexColumns: ["name", "email", "roles", "teacher profile"],
    labelFieldName: "name",
  },
  "contact-submissions": {
    laravelRoute: "dashboard.contact-submissions",
    indexColumns: ["date", "type", "name", "email", "message"],
    labelFieldName: "date",
  },
};

/** True iff this module's Laravel index blade exposes its own <th> set. */
export function hasOwnThSet(moduleName: string): boolean {
  return moduleName in TAILORED;
}

/** The tailored screen config for a module, if its blade has its own <th>. */
export function tailorableModule(moduleName: string): TailoredIndex | undefined {
  return TAILORED[moduleName];
}

/** Headerless modules (real Laravel index has no <th>) -> generic engine mirrors them. */
export const HEADERLESS_MODULES: readonly string[] = [
  "settings", "attendance", "classes", "exams", "fees", "parents", "students", "teachers",
];
