import { formatCsv, mapRow, normalizeHeaders, parseCsv } from "@/lib/csv";

/**
 * Bulk import/export contract — the CSV headers below are IDENTICAL in every
 * Eskoofy variant (see docs/design/DATA-PORTABILITY.md). A `students.csv`
 * exported by the Laravel app imports here unchanged, and vice-versa.
 *
 * Mirrors `eskoofy-laravel-app/App/Http/Controllers/Web/DashboardBulkController`.
 */

export const BULK_RESOURCES = {
  students: "Students",
  teachers: "Teachers",
  fees: "Fees",
  attendances: "Attendances",
} as const;

export type BulkResource = keyof typeof BULK_RESOURCES;

export const STUDENT_HEADERS = [
  "name",
  "email",
  "admission_number",
  "admission_date",
  "class_code",
  "roll_number",
  "gender",
  "date_of_birth",
  "phone",
  "present_address",
  "status",
];

export const TEACHER_HEADERS = [
  "name",
  "email",
  "employee_id",
  "joining_date",
  "phone",
  "gender",
  "date_of_birth",
  "qualification",
  "specialization",
  "status",
];

export const FEE_HEADERS = ["student", "fee", "amount", "due_date", "status", "paid_at"];

export const ATTENDANCE_HEADERS = ["date", "student", "class", "status", "remarks"];

const HEADERS: Record<BulkResource, string[]> = {
  students: STUDENT_HEADERS,
  teachers: TEACHER_HEADERS,
  fees: FEE_HEADERS,
  attendances: ATTENDANCE_HEADERS,
};

const SAMPLES: Record<BulkResource, string[][]> = {
  students: [["Jane Doe", "jane@example.com", "ADM-2024-0001", "2024-01-15", "C1", "12", "female", "2012-05-20", "+8801711111111", "House 1, Dhaka", "active"]],
  teachers: [["John Smith", "john@example.com", "EMP-2024-0001", "2024-01-10", "+8801711111112", "male", "1990-07-20", "MSc Mathematics", "Math", "active"]],
  fees: [["Jane Doe", "Tuition Fee", "1000", "2024-02-01", "paid", "2024-01-15"]],
  attendances: [["2024-09-30", "Jane Doe", "Class 5", "present", ""]],
};

const REQUIRED: Record<BulkResource, string[]> = {
  students: ["name", "email", "admission_number", "admission_date", "class_code"],
  teachers: ["name", "email", "employee_id", "joining_date"],
  fees: [],
  attendances: [],
};

export function headersFor(resource: BulkResource): string[] {
  return HEADERS[resource];
}

export function sampleRowFor(resource: BulkResource): string[][] {
  return SAMPLES[resource];
}

export function requiredColumnsFor(resource: BulkResource): string[] {
  return REQUIRED[resource];
}

export function isImportable(resource: BulkResource): boolean {
  return resource === "students" || resource === "teachers";
}

export function labelFor(resource: BulkResource): string {
  return BULK_RESOURCES[resource];
}

function pad(n: number, len = 2): string {
  return String(n).padStart(len, "0");
}

/** `students-20240930-153000.csv` (mirrors the app's `Ymd-His` naming). */
export function exportFilename(resource: BulkResource): string {
  const d = new Date();
  const stamp = `${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}-${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`;
  return `${resource}-${stamp}.csv`;
}

/** Format a date-like value as `YYYY-MM-DD` (the app's `->format('Y-m-d')`). */
export function toYmd(value: unknown, engine: "mysql" | "sqlite" = "mysql"): string {
  if (value === null || value === undefined) return "";
  if (value instanceof Date) {
    const y = engine === "sqlite" ? value.getUTCFullYear() : value.getFullYear();
    const m = engine === "sqlite" ? value.getUTCMonth() + 1 : value.getMonth() + 1;
    const d = engine === "sqlite" ? value.getUTCDate() : value.getDate();
    return `${y}-${pad(m)}-${pad(d)}`;
  }
  const s = String(value);
  return s.slice(0, 10);
}

export function buildCsv(resource: BulkResource, dataRows: Array<Record<string, unknown>>): string {
  const headers = headersFor(resource);
  const rows = dataRows.map((row) => headers.map((header) => (row[header] ?? "") as string | number | null));
  return formatCsv(headers, rows);
}

// ── Import handling ──────────────────────────────────────────────────────────

export interface BulkImportOutcome {
  created: number;
  updated: number;
  skipped: number;
  errors: string[];
  /** Rows that parsed cleanly in a dry run (no writes). */
  parsed: number;
  dryRun: boolean;
}

export interface RowUpserter {
  (row: Record<string, string | null>, rowNumber: number): Promise<"created" | "updated" | "skipped">;
}

export class BulkImportError extends Error {
  missing: string[];
  constructor(missing: string[]) {
    super(`Missing columns: ${missing.join(", ")}`);
    this.missing = missing;
  }
}

/**
 * Parse + validate a CSV and run one row at a time through `upsert`.
 * Mirrors the app's `importStore` (required columns, blank-row skip, per-row
 * error collection, created/updated/skipped accounting).
 */
export async function runBulkImport(
  text: string,
  resource: BulkResource,
  upsert: RowUpserter,
  opts: { dryRun?: boolean } = {},
): Promise<BulkImportOutcome> {
  const rows = parseCsv(text);
  if (rows.length === 0) throw new Error("Empty file.");

  const headers = normalizeHeaders(rows[0]);
  const required = requiredColumnsFor(resource);
  const missing = required.filter((column) => !headers.includes(column));
  if (missing.length > 0) throw new BulkImportError(missing);

  const outcome: BulkImportOutcome = { created: 0, updated: 0, skipped: 0, errors: [], parsed: 0, dryRun: opts.dryRun ?? false };

  for (let i = 1; i < rows.length; i++) {
    const raw = rows[i];
    if (raw.length === 1 && raw[0].trim() === "") continue;
    const row = mapRow(headers, raw);

    if (outcome.dryRun) {
      outcome.parsed++;
      continue;
    }

    try {
      const result = await upsert(row, i);
      if (result === "created") outcome.created++;
      else if (result === "updated") outcome.updated++;
      else outcome.skipped++;
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      outcome.errors.push(`Row ${i}: ${message}`);
    }
  }

  return outcome;
}

export function outcomeMessage(outcome: BulkImportOutcome): string {
  const base = `Imported: ${outcome.created} created, ${outcome.updated} updated, ${outcome.skipped} skipped.`;
  return outcome.errors.length > 0 ? `${base} Errors: ${outcome.errors.length}` : base;
}