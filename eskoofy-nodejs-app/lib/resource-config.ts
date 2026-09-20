import {
  displayFields,
  labelField,
  writableFields,
  type FieldMeta,
  type ModelMeta,
} from "@/lib/schema";

export interface TailoredIndex {
  laravelRoute: string;
  indexColumns: string[];
  labelFieldName?: string;
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

export function indexColumns(model: ModelMeta, generic: FieldMeta[]): FieldMeta[] {
  const cfg = TAILORED[model.name];
  if (!cfg) return generic;
  const byName = new Map(model.fields.map((f) => [f.name, f]));
  const picked = cfg.indexColumns
    .map((n) => byName.get(n))
    .filter((f): f is FieldMeta => Boolean(f) && Boolean(f.display));
  return picked.length > 0 ? picked : generic;
}

export function hasTailoredIndex(model: ModelMeta): boolean {
  return model.name in TAILORED;
}

export function tailoredLabelField(model: ModelMeta): string | undefined {
  return TAILORED[model.name]?.labelFieldName;
}
