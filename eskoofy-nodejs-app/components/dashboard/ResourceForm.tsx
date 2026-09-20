import Link from "next/link";
import { ENUMS, foreignKeyTargets, writableFields, type FieldMeta, type ModelMeta } from "@/lib/schema";
import { loadOptions } from "@/lib/db-query";
import { t } from "@/lib/i18n";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { saveResource } from "@/app/(dashboard)/dashboard/actions";

/** Columns the generic form never renders (server-managed). */
const EXCLUDED = new Set(["id", "created_at", "updated_at", "deleted_at", "remember_token", "password"]);

const TEXTAREA_HINTS = ["notes", "description", "address", "message", "content", "remarks", "body", "details", "reason"];

function inputType(field: FieldMeta): string {
  if (field.type === "DateTime") return "date";
  if (["Int", "BigInt", "Float", "Decimal"].includes(field.type)) return "number";
  if (field.type === "String" && field.name.includes("email")) return "email";
  if (field.type === "String" && field.name.includes("phone")) return "tel";
  return "text";
}

function isTextarea(field: FieldMeta): boolean {
  return TEXTAREA_HINTS.some((hint) => field.name.includes(hint));
}

function initialValue(value: unknown): string {
  if (value === null || value === undefined) return "";
  if (value instanceof Date) return value.toISOString().slice(0, 10);
  if (typeof value === "boolean") return value ? "1" : "0";
  return String(value);
}

export async function ResourceForm({
  model,
  basePath,
  mode,
  row,
}: {
  model: ModelMeta;
  basePath: string;
  mode: "create" | "edit";
  row?: Record<string, unknown> | null;
}) {
  const fkTargets = foreignKeyTargets(model);
  const fields = writableFields(model).filter((field) => !EXCLUDED.has(field.name));

  // Pre-load lookup options for FK columns so the form stays a server component.
  const optionsByField = new Map<string, { value: string; label: string }[]>();
  for (const field of fields) {
    const relation = fkTargets.get(field.name);
    if (relation) optionsByField.set(field.name, await loadOptions(relation.target));
  }

  const idField = model.fields.find((field) => field.isId)?.name ?? "id";
  const idValue = row ? initialValue(row[idField]) : "";

  const inputClass =
    "admin-input";

  const resourceKey = `dashboard.${(basePath.split("/").pop() ?? model.table).replace(/-/g, "_")}`;
  const formTitle = t(resourceKey) === resourceKey ? model.table : t(resourceKey);

  return (
    <form action={saveResource} className="space-y-6">
      <input type="hidden" name="__table" value={model.name} />
      <input type="hidden" name="__base" value={basePath} />
      {idValue ? <input type="hidden" name="__id" value={idValue} /> : null}

      <Card title={formTitle}>
        <div className="grid gap-4 sm:grid-cols-2">
          {fields.map((field) => {
            const label = field.name.replace(/_/g, " ");
            const value = row ? initialValue(row[field.name]) : "";
            const options = optionsByField.get(field.name);
            const enumValues = field.kind === "enum" ? ENUMS[field.type] : undefined;

            return (
              <div key={field.name} className={isTextarea(field) ? "sm:col-span-2" : ""}>
                <label htmlFor={field.name} className="mb-1 block text-sm font-semibold capitalize text-slate-700">
                  {label}
                  {field.isRequired ? <span className="ml-0.5 text-red-500">*</span> : null}
                </label>

                {enumValues ? (
                  <select id={field.name} name={field.name} defaultValue={value} className={inputClass}>
                    <option value="">—</option>
                    {enumValues.map((option) => (
                      <option key={option} value={option}>
                        {option}
                      </option>
                    ))}
                  </select>
                ) : options ? (
                  <select id={field.name} name={field.name} defaultValue={value} className={inputClass}>
                    <option value="">—</option>
                    {options.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                ) : field.type === "Boolean" ? (
                  <label className="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id={field.name} name={field.name} defaultChecked={value === "1" || value === "true"} className="rounded border-slate-300" />
                    {label}
                  </label>
                ) : isTextarea(field) ? (
                  <textarea id={field.name} name={field.name} rows={4} defaultValue={value} className={inputClass} />
                ) : (
                  <input
                    id={field.name}
                    name={field.name}
                    type={inputType(field)}
                    defaultValue={value}
                    required={field.isRequired && !field.hasDefaultValue}
                    className={inputClass}
                  />
                )}
              </div>
            );
          })}
        </div>
      </Card>

      <div className="flex items-center gap-3">
        <Button type="submit">{mode === "create" ? t("common.create") : t("common.save")}</Button>
        <Link href={basePath} className="text-sm font-medium text-slate-500 hover:text-slate-700">
          {t("common.cancel")}
        </Link>
      </div>
    </form>
  );
}
