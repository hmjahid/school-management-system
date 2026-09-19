import Link from "next/link";
import { displayFields, type FieldMeta, type ModelMeta } from "@/lib/schema";
import { listRows } from "@/lib/db-query";
import { t } from "@/lib/i18n";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { StatusBadge } from "@/components/ui/Badge";
import { deleteResource } from "@/app/(dashboard)/dashboard/actions";

const STATUS_FIELDS = new Set(["status", "state", "type", "frequency", "period", "shift", "grade"]);

function display(value: unknown): string {
  if (value === null || value === undefined || value === "") return "—";
  if (value instanceof Date) return value.toISOString().slice(0, 10);
  if (typeof value === "boolean") return value ? "yes" : "no";
  const text = String(value);
  return text.length > 48 ? `${text.slice(0, 45)}…` : text;
}

export async function ResourceTable({
  model,
  basePath,
  search,
  page = 1,
  pageSize = 25,
}: {
  model: ModelMeta;
  basePath: string;
  search?: string;
  page?: number;
  pageSize?: number;
}) {
  const { rows, total } = await listRows(model, { search, take: pageSize, skip: (page - 1) * pageSize });
  const dataFields = displayFields(model, 6);
  const idField = model.fields.find((field) => field.isId)?.name ?? "id";

  const columns: Column[] = [
    ...dataFields.map((field) => ({ key: field.name, label: field.name.replace(/_/g, " ") })),
    { key: "__actions", label: t("common.actions"), align: "right" },
  ];

  const body = rows.map((row) => {
    const id = String(row[idField] ?? "");
    const cells = dataFields.map((field: FieldMeta) =>
      STATUS_FIELDS.has(field.name) ? (
        <StatusBadge key={field.name} value={row[field.name]} />
      ) : (
        <span key={field.name}>{display(row[field.name])}</span>
      ),
    );

    cells.push(
      <span key="__actions" className="flex items-center justify-end gap-2">
        <Link href={`${basePath}/${id}`} className="text-xs font-semibold text-blue-600 hover:underline">
          {t("common.view")}
        </Link>
        <Link href={`${basePath}/${id}/edit`} className="text-xs font-semibold text-slate-600 hover:underline">
          {t("common.save")}
        </Link>
        <form action={deleteResource}>
          <input type="hidden" name="__table" value={model.name} />
          <input type="hidden" name="__base" value={basePath} />
          <input type="hidden" name="__id" value={id} />
          <button type="submit" className="text-xs font-semibold text-red-600 hover:underline">
            ✕
          </button>
        </form>
      </span>,
    );

    return cells;
  });

  return (
    <DataTable
      columns={columns}
      rows={body}
      emptyTitle={t("common.empty")}
      emptyMessage={`${model.table} · 0 ${t("common.rows")}`}
      pagination={{
        page,
        lastPage: Math.max(1, Math.ceil(total / pageSize)),
        basePath,
        query: search ? { q: search } : undefined,
      }}
    />
  );
}
