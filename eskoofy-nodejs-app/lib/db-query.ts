import { prisma } from "@/lib/prisma";
import { MODEL_BY_NAME, labelField, type FieldMeta, type ModelMeta } from "@/lib/schema";

/**
 * Generic data access over any Prisma model, used by the dashboard catch-all
 * and the `/api/v1` catch-all so all tables get list/detail/write behaviour
 * without a hand-written controller each.
 */

interface Delegate {
  findMany: (args?: unknown) => Promise<Record<string, unknown>[]>;
  count: (args?: unknown) => Promise<number>;
  findUnique: (args: unknown) => Promise<Record<string, unknown> | null>;
  create: (args: unknown) => Promise<Record<string, unknown>>;
  update: (args: unknown) => Promise<Record<string, unknown>>;
  delete: (args: unknown) => Promise<Record<string, unknown>>;
}

function delegateFor(model: ModelMeta): Delegate | undefined {
  const registry = prisma as unknown as Record<string, Delegate | undefined>;
  return registry[model.name];
}

function idField(model: ModelMeta): FieldMeta | undefined {
  return model.fields.find((field) => field.isId) ?? model.scalars[0];
}

function searchableFields(model: ModelMeta): FieldMeta[] {
  return model.scalars.filter(
    (field) => !field.isList && (field.type === "String" || field.type === "Int" || field.type === "BigInt"),
  );
}

function buildWhere(model: ModelMeta, search?: string): Record<string, unknown> {
  const where: Record<string, unknown> = {};
  const softDelete = model.fields.find((field) => field.name === "deleted_at");
  if (softDelete) where.deleted_at = null;

  const term = search?.trim();
  if (term) {
    const or = searchableFields(model).map((field) => ({ [field.name]: { contains: term } }));
    if (or.length > 0) where.OR = or;
  }

  return where;
}

export interface ListResult {
  rows: Record<string, unknown>[];
  total: number;
}

export async function listRows(model: ModelMeta, opts: { take?: number; skip?: number; search?: string } = {}): Promise<ListResult> {
  const delegate = delegateFor(model);
  if (!delegate) return { rows: [], total: 0 };

  const take = Math.min(Math.max(opts.take ?? 25, 1), 100);
  const where = buildWhere(model, opts.search);
  const orderField = idField(model)?.name;

  try {
    const [rows, total] = await Promise.all([
      delegate.findMany({
        where,
        take,
        skip: opts.skip ?? 0,
        ...(orderField ? { orderBy: { [orderField]: "desc" } } : {}),
      }),
      delegate.count({ where }),
    ]);
    return { rows, total };
  } catch {
    return { rows: [], total: 0 };
  }
}

export interface Option {
  value: string;
  label: string;
}

/** Distinct options for a lookup select (id + a human label). */
export async function loadOptions(modelName: string, limit = 200): Promise<Option[]> {
  const model = MODEL_BY_NAME.get(modelName);
  const delegate = model ? delegateFor(model) : undefined;
  if (!model || !delegate) return [];

  const label = labelField(model);
  try {
    const rows = await delegate.findMany({ take: limit, orderBy: { id: "asc" } });
    return rows.map((row) => ({
      value: String(row.id ?? ""),
      label: String(row[label] ?? row.id ?? ""),
    }));
  } catch {
    return [];
  }
}

export async function findRow(model: ModelMeta, id: string | number): Promise<Record<string, unknown> | null> {
  const delegate = delegateFor(model);
  const idMeta = idField(model);
  if (!delegate || !idMeta) return null;

  const value = idMeta.type === "Int" || idMeta.type === "BigInt" ? Number(id) : id;
  try {
    return await delegate.findUnique({ where: { [idMeta.name]: value } });
  } catch {
    return null;
  }
}

/** Coerce a raw string form value to the model field's scalar type. */
function coerce(field: FieldMeta, raw: unknown): unknown {
  if (raw === "" || raw === undefined) return null;
  if (raw === null) return null;

  switch (field.type) {
    case "Int":
    case "BigInt":
      return Number(raw);
    case "Float":
    case "Decimal":
      return Number(raw);
    case "Boolean":
      return raw === true || raw === "1" || raw === "true" || raw === "on";
    case "DateTime":
      return new Date(String(raw));
    default:
      return String(raw);
  }
}

export async function createRow(model: ModelMeta, payload: Record<string, unknown>): Promise<{ ok: boolean; data?: Record<string, unknown>; error?: string }> {
  const delegate = delegateFor(model);
  if (!delegate) return { ok: false, error: "Unknown resource." };

  const writable = model.scalars.filter((field) => !field.isId && !field.isUpdatedAt && !field.isList);
  const data: Record<string, unknown> = {};
  for (const field of writable) {
    if (field.name in payload) {
      const value = coerce(field, payload[field.name]);
      if (value !== null || field.isRequired === false) data[field.name] = value;
    } else if (field.hasDefaultValue || field.isRequired === false) {
      // let the database default fill it
      continue;
    }
  }

  try {
    const created = await delegate.create({ data });
    return { ok: true, data: created };
  } catch (error) {
    return { ok: false, error: error instanceof Error ? error.message : "Create failed." };
  }
}

export async function updateRow(model: ModelMeta, id: string | number, payload: Record<string, unknown>): Promise<{ ok: boolean; data?: Record<string, unknown>; error?: string }> {
  const delegate = delegateFor(model);
  const idMeta = idField(model);
  if (!delegate || !idMeta) return { ok: false, error: "Unknown resource." };

  const data: Record<string, unknown> = {};
  for (const field of model.scalars) {
    if (field.isId || field.isList || field.isUpdatedAt) continue;
    if (field.name in payload) data[field.name] = coerce(field, payload[field.name]);
  }

  const value = idMeta.type === "Int" || idMeta.type === "BigInt" ? Number(id) : id;
  try {
    const updated = await delegate.update({ where: { [idMeta.name]: value }, data });
    return { ok: true, data: updated };
  } catch (error) {
    return { ok: false, error: error instanceof Error ? error.message : "Update failed." };
  }
}

export async function deleteRow(model: ModelMeta, id: string | number): Promise<{ ok: boolean; error?: string }> {
  const delegate = delegateFor(model);
  const idMeta = idField(model);
  if (!delegate || !idMeta) return { ok: false, error: "Unknown resource." };

  const value = idMeta.type === "Int" || idMeta.type === "BigInt" ? Number(id) : id;
  try {
    await delegate.delete({ where: { [idMeta.name]: value } });
    return { ok: true };
  } catch (error) {
    return { ok: false, error: error instanceof Error ? error.message : "Delete failed." };
  }
}
