import { Prisma } from "@prisma/client";

/**
 * Runtime schema metadata, read from Prisma's DMMF (generated from
 * `prisma/schema.prisma`, which is itself the app's migrated schema).
 *
 * The generic resource engine (`lib/resources.ts` + the dashboard/API
 * catch-alls) uses this to render list/detail views and accept writes for
 * every table without hand-writing 100 model-specific screens.
 */

export interface FieldMeta {
  name: string;
  type: string;
  kind: "scalar" | "enum" | "object";
  isList: boolean;
  isRequired: boolean;
  hasDefaultValue: boolean;
  isId: boolean;
  isUpdatedAt: boolean;
  relationName?: string;
}

export interface RelationMeta {
  /** Field name on this model (the relation field, e.g. `students`). */
  name: string;
  /** Target model name. */
  target: string;
  relationName?: string;
  /** Scalar FK columns backing this relation (e.g. `["class_id"]`). */
  fromFields: string[];
}

export interface ModelMeta {
  /** Prisma model name (also the client accessor, e.g. `prisma.students`). */
  name: string;
  /** Real table name in the database (identical to the Laravel app). */
  table: string;
  fields: FieldMeta[];
  /** Scalar/enum fields only — the ones a generic form or table can show. */
  scalars: FieldMeta[];
  relations: RelationMeta[];
}

type DmmfField = {
  name: string;
  type: string;
  kind: string;
  isList: boolean;
  isRequired: boolean;
  hasDefaultValue: boolean;
  isId: boolean;
  isUpdatedAt: boolean;
  relationName?: string;
  relationFromFields?: string[];
};

type DmmfModel = {
  name: string;
  dbName?: string | null;
  fields: DmmfField[];
  primaryKey?: { fields: string[] } | null;
};

const dmmfModels = (Prisma.dmmf?.datamodel?.models ?? []) as unknown as DmmfModel[];

function toFieldMeta(field: DmmfModel["fields"][number]): FieldMeta {
  return {
    name: field.name,
    type: field.type,
    kind: field.kind as FieldMeta["kind"],
    isList: field.isList,
    isRequired: field.isRequired,
    hasDefaultValue: field.hasDefaultValue,
    isId: field.isId,
    isUpdatedAt: field.isUpdatedAt,
    relationName: field.relationName,
  };
}

export const MODELS: ModelMeta[] = dmmfModels.map((model) => {
  const fields = model.fields.map(toFieldMeta);
  const relations: RelationMeta[] = model.fields
    .filter((field) => field.kind === "object")
    .map((field) => ({
      name: field.name,
      target: field.type,
      relationName: field.relationName,
      fromFields: field.relationFromFields ?? [],
    }));

  return {
    name: model.name,
    table: model.dbName ?? model.name,
    fields,
    scalars: fields.filter((field) => field.kind === "scalar" || field.kind === "enum"),
    relations,
  };
});

/** Enum name → allowed values (from the Prisma schema). */
type DmmfEnum = { name: string; values: Array<{ name: string }> };
const dmmfEnums = (Prisma.dmmf?.datamodel?.enums ?? []) as unknown as DmmfEnum[];
export const ENUMS: Record<string, string[]> = Object.fromEntries(
  dmmfEnums.map((entry) => [entry.name, entry.values.map((value) => value.name)]),
);

/** The scalar FK column → relation target, so forms can render lookup selects. */
export function foreignKeyTargets(model: ModelMeta): Map<string, RelationMeta> {
  const map = new Map<string, RelationMeta>();
  for (const relation of model.relations) {
    for (const from of relation.fromFields) {
      if (!map.has(from)) map.set(from, relation);
    }
  }
  return map;
}

export const MODEL_BY_NAME = new Map(MODELS.map((model) => [model.name, model]));
export const MODEL_BY_TABLE = new Map(MODELS.map((model) => [model.table, model]));
export const TABLE_NAMES = MODELS.map((model) => model.table).sort();

/** Fields safe to show in a generic table/form (hide secrets + huge blobs). */
const HIDDEN_FIELDS = new Set(["password", "remember_token", "two_factor_secret", "metadata", "raw"]);

export function displayFields(model: ModelMeta, limit = 8): FieldMeta[] {
  return model.scalars
    .filter((field) => !HIDDEN_FIELDS.has(field.name) && !field.isList)
    .slice(0, limit);
}

const LABEL_CANDIDATES = [
  "name",
  "title",
  "label",
  "full_name",
  "first_name",
  "code",
  "key",
  "email",
  "admission_number",
  "employee_id",
  "license_key",
];

/** Best human label field for a model (for lookup selects). */
export function labelField(model: ModelMeta): string {
  for (const candidate of LABEL_CANDIDATES) {
    if (model.scalars.some((field) => field.name === candidate)) return candidate;
  }
  return model.fields.find((field) => field.isId)?.name ?? "id";
}

export function writableFields(model: ModelMeta): FieldMeta[] {
  return model.scalars.filter(
    (field) => !field.isId && !field.isUpdatedAt && !field.isList && !HIDDEN_FIELDS.has(field.name),
  );
}
