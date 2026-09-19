import { displayFields, writableFields, type ModelMeta } from "@/lib/schema";
import { t } from "@/lib/i18n";
import { Badge, StatusBadge } from "@/components/ui/Badge";
import { Card } from "@/components/ui/Card";

function format(value: unknown): string {
  if (value === null || value === undefined || value === "") return "—";
  if (value instanceof Date) return value.toISOString().replace("T", " ").slice(0, 19);
  if (typeof value === "boolean") return value ? "yes" : "no";
  return String(value);
}

/** Generic "show" screen — a definition list over the model's columns. */
export function ResourceDetail({ model, row }: { model: ModelMeta; row: Record<string, unknown> }) {
  const fields = Array.from(new Set([...displayFields(model, 12), ...writableFields(model).slice(0, 12)]));

  return (
    <Card>
      <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
        {fields.map((field) => (
          <div key={field.name}>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-400">{field.name.replace(/_/g, " ")}</dt>
            <dd className="mt-1 text-sm text-slate-800">
              {field.name === "status" ? <StatusBadge value={row[field.name]} /> : format(row[field.name])}
            </dd>
          </div>
        ))}
        <div>
          <dt className="text-xs font-semibold uppercase tracking-wide text-slate-400">{t("dashboard.dashboard")}</dt>
          <dd className="mt-1">
            <Badge variant="brand">{model.table}</Badge>
          </dd>
        </div>
      </dl>
    </Card>
  );
}
