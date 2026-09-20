import {
  displayFields,
  labelField,
  type FieldMeta,
  type ModelMeta,
} from "@/lib/schema";

/**
 * Tailored per-resource dashboard screens (HONEST scope).
 *
 * Laravel renders each dashboard module from its own hand-written Blade
 * (`dashboard/modules/{students,fees,admissions,staff}.blade.php`). The Node
 * engine ships ONE generic CRUD surface for all 34 resources. This module
 * tailors the four highest-traffic *index* screens to the exact columns that
 * the real Laravel blades list — and nothing else. Every other resource keeps
 * the generic engine. It is NOT 213-screen parity (see docs/PORTING-STATUS.md).
 */

export interface TailoredIndex {
  laravelRoute: string;
  indexColumns: string[];
  descriptionKey: string;
  labelFieldName?: string;
}

const TAILORED: Record<string, TailoredIndex> = {};

/** Columns for a resource index: tailored when configured, else the generic engine. */
export function indexColumns(model: ModelMeta, generic: FieldMeta[]): FieldMeta[] {
  const cfg = TAILORED[model.name];
  if (!cfg) return generic;
  const byName = new Map(model.fields.map((f) => [f.name, f]));
  const picked = cfg.indexColumns
    .map((name) => byName.get(name))
    .filter((f): f is FieldMeta => Boolean(f));
  return picked.length > 0 ? picked : generic;
}

/** Form field order: tailored when configured, else the engine's writable set. */
export function formColumns(model: ModelMeta, generic: FieldMeta[]): FieldMeta[] {
  const cfg = TAILORED[model.name];
  if (!cfg) return generic;
  const byName = new Map(model.fields.map((f) => [f.name, f]));
  const picked = cfg.indexColumns
    .slice(0, 7)
    .map((name) => byName.get(name))
    .filter((f): f is FieldMeta => Boolean(f));
  return picked.length > 0 ? picked : generic;
}

export function tailoredLabelField(model: ModelMeta): string | undefined {
  const cfg = TAILORED[model.name];
  return cfg?.labelFieldName;
}

export function tailoredDescriptionKey(model: ModelMeta): string | undefined {
  return TAILORED[model.name]?.descriptionKey;
}

export function tailoredRoute(model: ModelMeta): string | undefined {
  return TAILORED[model.name]?.laravelRoute;
}
