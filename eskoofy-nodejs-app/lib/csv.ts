/**
 * CSV helpers mirroring PHP's `fputcsv` / `fgetcsv` defaults (comma delimiter,
 * double-quote enclosure, UTF-8). This is the portable CSV contract shared by
 * every Eskoofy variant — see docs/design/DATA-PORTABILITY.md.
 *
 *  - `formatCsv` writes rows exactly like `fputcsv` (field quoted when it
 *    contains a delimiter, quote, newline or leading/trailing whitespace).
 *  - `parseCsv` reads rows exactly like `fgetcsv` (RFC 4180 + quoted fields
 *    that may span newlines).
 */

const DELIMITER = ",";
const ENCLOSURE = '"';

export type CsvCell = string | number | boolean | null | undefined;

function needsQuoting(value: string): boolean {
  return (
    value.includes(DELIMITER) ||
    value.includes(ENCLOSURE) ||
    value.includes("\n") ||
    value.includes("\r") ||
    value !== value.trim() ||
    value === ""
  );
}

function escapeField(value: CsvCell): string {
  const s = value === null || value === undefined ? "" : String(value);
  if (needsQuoting(s)) {
    return `${ENCLOSURE}${s.replaceAll(ENCLOSURE, `${ENCLOSURE}${ENCLOSURE}`)}${ENCLOSURE}`;
  }
  return s;
}

/** Serialize rows to CSV text (no trailing newline, like the app's export). */
export function formatCsv(headers: string[], rows: CsvCell[][]): string {
  const lines = [headers.map(escapeField).join(DELIMITER)];
  for (const row of rows) {
    lines.push(row.map(escapeField).join(DELIMITER));
  }
  return lines.join("\n");
}

/**
 * Parse CSV text into rows of unescaped strings. Handles quoted fields with
 * embedded line breaks and `""` escapes (mirrors PHP's fgetcsv behaviour).
 */
export function parseCsv(text: string): string[][] {
  const rows: string[][] = [];
  let row: string[] = [];
  let field = "";
  let inQuotes = false;

  const pushField = () => {
    row.push(field);
    field = "";
  };
  const pushRow = () => {
    pushField();
    rows.push(row);
    row = [];
  };

  let i = 0;
  const len = text.length;
  while (i < len) {
    const ch = text[i];
    if (inQuotes) {
      if (ch === ENCLOSURE) {
        if (text[i + 1] === ENCLOSURE) {
          field += ENCLOSURE;
          i += 2;
          continue;
        }
        inQuotes = false;
        i++;
        continue;
      }
      field += ch;
      i++;
      continue;
    }

    if (ch === ENCLOSURE && field === "") {
      inQuotes = true;
      i++;
      continue;
    }
    if (ch === DELIMITER) {
      pushField();
      i++;
      continue;
    }
    if (ch === "\r") {
      if (text[i + 1] === "\n") i++;
      pushRow();
      i++;
      continue;
    }
    if (ch === "\n") {
      pushRow();
      i++;
      continue;
    }
    field += ch;
    i++;
  }

  if (field !== "" || row.length > 0) pushRow();

  // Drop trailing empty lines (fgetcsv skips a trailing blank line).
  while (rows.length > 0 && rows[rows.length - 1].length === 1 && rows[rows.length - 1][0] === "") {
    rows.pop();
  }
  return rows;
}

/** Slug a CSV header like Laravel's `Str::slug($h, '_')`. */
export function slugHeader(header: string): string {
  const lower = header
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "");
  return lower;
}

/** Trim + slug every header row value and skip empty trailer rows. */
export function normalizeHeaders(headers: string[]): string[] {
  return headers.map((h) => slugHeader(h.trim()));
}

/** Map a raw CSV row onto (slugged) headers, padding short rows with null. */
export function mapRow(headers: string[], row: string[]): Record<string, string | null> {
  const out: Record<string, string | null> = {};
  headers.forEach((header, index) => {
    const value = row[index];
    out[header] = value === undefined || value.trim() === "" ? null : value.trim();
  });
  return out;
}