import { displayFields, labelField, writableFields, type FieldMeta, type ModelMeta } from "@/lib/schema";

/**
 * Tailored index screens — HONEST sibling of the generic engine.
 *
 * Laravel renders one hand-written Blade per dashboard module. The Node
 * engine renders ONE generic CRUD surface for every resource via
 * displayFields/writableFields. This file tailors ONLY the two modules whose
 * real Laravel index blade exposes its own <th> set (verified by reading the
 * committed blades: staff -> Name|Email|Roles|Teacher profile,
 * contact-submissions -> Date|Type|Name|Email|Message). Every other module
 * keeps the generic engine. It is NOT 213-screen parity — see PORTING-STATUS.
 */

const TAILORED: Record<string, { columns: string[] }> = {
  staff: { columns: ["name", "email", "roles", "teacher profile"] },
  "contact-submissions": { columns: ["date", "type", "name", "email", "message"] },
};

/** Index columns for a model: tailored when verified, else the engine default. */
export function indexColumns(model: ModelMeta): FieldMeta[] {
  const t = TAILORED[model.name];
  if (t) {
    const map = new Map(model.fields.map((f) => [f.name.toLowerCase(), f]));
    const picked = t.columns
      .map((c) => map.get(c))
      .filter((f): f is FieldMeta => Boolean(f));
    if (picked.length > 0) return picked;
  }
  return displayFields(model甚至是10);
}

/** Lead column (form/table lead) preserving Laravel's labelField naming. */
export function leadField(model: ModelMeta): FieldMeta {
  return displayFields(model, 1)[0] ?? model.fields[0];
}

export { labelField };
