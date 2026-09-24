import { prisma } from "@/lib/prisma";

/**
 * Portable backup engine — the cross-variant data format.
 *
 * Every Eskoofy variant writes and consumes the same backup manifest so one
 * variant's backup can be restored by another. See docs/design/DATA-PORTABILITY.md.
 *
 * Zip layout produced by `backup:run` / the backup screen:
 *
 *   MANIFEST.json             {"format":"eskoofy-portable-backup","version":1,…}
 *   database/tables.json      {"tables":[{"table","columns","rows"}, …]}
 *   storage/app/public/…      uploaded files (portable, engine-neutral)
 *
 * `tables.json` is the portable dataset: every table as {columns, rows}, rows in
 * a deterministic order. Restore disables FK constraints, replaces all rows and
 * re-enables constraints, so it works on SQLite, MySQL and MariaDB alike.
 */

export const PORTABLE_BACKUP_FORMAT = "eskoofy-portable-backup";
export const PORTABLE_BACKUP_VERSION = 1;
export const MANIFEST_FILE = "MANIFEST.json";
export const TABLES_FILE = "database/tables.json";
export const STORAGE_PREFIX = "storage/app/public/";

export interface BackedUpTable {
  table: string;
  columns: string[];
  rows: unknown[][];
}

export interface PortableDump {
  tables: BackedUpTable[];
}

export interface BackupManifest {
  format: typeof PORTABLE_BACKUP_FORMAT;
  version: number;
  variant: string;
  createdAt: string;
  engine: string;
  tableCount: number;
  /** bd|int — which Eskoofy profile produced the backup. */
  eskoofyVariant: string | null;
  /**
   * Identity of the source's secret-encryption key. The Laravel variant reports
   * sha256(APP_KEY); the Node variant has no encryption layer and reports null.
   */
  cipherFingerprint: string | null;
  /** Columns whose values the source wrapped with its encryption. */
  sensitiveColumns: Record<string, string[]>;
}

/**
 * Columns that travel encrypted in the Laravel app (its `encrypted` casts).
 * The Node variant stores these plaintext, but lists them so a Laravel→Node or
 * Node→Laravel restore never moves un-decryptable ciphertext (or plaintext into
 * an encrypted column). Mirror of the Laravel PortableBackupService.
 */
export const SENSITIVE_COLUMNS: Record<string, string[]> = {
  payment_gateways: ["api_key", "api_secret", "api_username", "api_password"],
  website_settings: [
    "bkash_merchant_number",
    "bkash_api_key",
    "bkash_api_secret",
    "bkash_username",
    "bkash_password",
    "bkash_app_key",
    "bkash_app_secret",
    "twilio_sid",
    "twilio_auth_token",
    "twilio_from_number",
    "mail_username",
    "mail_password",
  ],
};

/**
 * Fingerprint of the receiving engine's encryption environment. The Node
 * variant has none, so it is always null.
 */
export const TARGET_CIPHER_FINGERPRINT = null;

/**
 * Secrets are only kept when source and target fingerprints match. Both-null
 * matches (Node→Node) keep plaintext; any Laravel↔Node mix or a foreign
 * Laravel key clears the column instead of storing unusable values.
 */
export function secretsCompatible(sourceFingerprint: string | null): boolean {
  return sourceFingerprint === TARGET_CIPHER_FINGERPRINT;
}

/** Narrow DB surface the engine needs — faked in unit tests, real via Prisma. */
export interface BackupDb {
  engine: "mysql" | "sqlite";
  fetchAll(sql: string, params?: unknown[]): Promise<Array<Record<string, unknown>>>;
  run(sql: string, params?: unknown[]): Promise<void>;
  disableForeignKeys(): Promise<void>;
  enableForeignKeys(): Promise<void>;
}

export const SAFE_TABLE_NAME = /^[a-z_][a-z0-9_]*$/i;

export function assertSafeTable(table: string): string {
  if (!SAFE_TABLE_NAME.test(table)) {
    throw new Error(`Unsafe table name in backup: ${table}`);
  }
  return table;
}

// ── Value normalisation ──────────────────────────────────────────────────────

function pad(n: number, len = 2): string {
  return String(n).padStart(len, "0");
}

/**
 * Serialise a DB value to a JSON-safe, engine-neutral primitive.
 *
 * DATETIME columns: the app stores naive local time. Prisma returning a
 * JavaScript Date depends on the driver — for the MySQL driver the Date's local
 * components equal the stored value; for the SQLite driver the stored value is
 * parsed as UTC. Matching the driver's interpretation keeps round-trips exact.
 */
export function serializeValue(value: unknown, engine: "mysql" | "sqlite"): unknown {
  if (value === null || value === undefined) return null;
  if (typeof value === "string" || typeof value === "number") return value;
  if (typeof value === "boolean") return value ? 1 : 0;
  if (typeof value === "bigint") return String(value);

  if (value instanceof Date) {
    const y = engine === "sqlite" ? value.getUTCFullYear() : value.getFullYear();
    const m = engine === "sqlite" ? value.getUTCMonth() + 1 : value.getMonth() + 1;
    const d = engine === "sqlite" ? value.getUTCDate() : value.getDate();
    const h = engine === "sqlite" ? value.getUTCHours() : value.getHours();
    const mi = engine === "sqlite" ? value.getUTCMinutes() : value.getMinutes();
    const s = engine === "sqlite" ? value.getUTCSeconds() : value.getSeconds();
    return `${y}-${pad(m)}-${pad(d)} ${pad(h)}:${pad(mi)}:${pad(s)}`;
  }

  if (Buffer.isBuffer(value)) return value.toString("base64");
  // Prisma returns Decimal as a string; keep it verbatim to preserve precision.
  return String(value);
}

export function formatNow(): string {
  const d = new Date();
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

function buildManifest(variant: string, engine: string, tableCount: number): BackupManifest {
  return {
    format: PORTABLE_BACKUP_FORMAT,
    version: PORTABLE_BACKUP_VERSION,
    variant,
    createdAt: formatNow(),
    engine,
    tableCount,
    eskoofyVariant: process.env.ESKOOFY_VARIANT ?? "int",
    cipherFingerprint: null,
    sensitiveColumns: SENSITIVE_COLUMNS,
  };
}

// ── Dump ─────────────────────────────────────────────────────────────────────

export function tablesJson(dump: PortableDump): string {
  return JSON.stringify(dump);
}

export function parseTablesJson(text: string): PortableDump {
  const parsed = JSON.parse(text) as PortableDump;
  if (!parsed || !Array.isArray(parsed.tables)) {
    throw new Error("Backup tables.json is not a valid portable dump.");
  }
  return parsed;
}

/** Read every listed table into a portable dump (sets the manifest table order). */
export async function buildDump(db: BackupDb, tables: string[]): Promise<{ dump: PortableDump; manifest: BackupManifest; variant: string }> {
  const dump: PortableDump = { tables: [] };
  for (const table of tables.sort()) {
    const safe = assertSafeTable(table);
    const rows = await db.fetchAll(`SELECT * FROM \`${safe}\``);
    const columns = rows.length > 0 ? Object.keys(rows[0]) : [];
    dump.tables.push({
      table: safe,
      columns,
      rows: rows.map((row) => columns.map((column) => serializeValue(row[column], db.engine))),
    });
  }
  const variant = process.env.ESKOOFY_VARIANT ?? "int";
  return { dump, manifest: buildManifest(variant, db.engine, dump.tables.length), variant };
}

// ── Restore ──────────────────────────────────────────────────────────────────

const INSERT_CHUNK = 200;

export interface RestoreOptions {
  /** Columns wrapped by the source's encryption (manifest.sensitiveColumns). */
  sensitiveColumns?: Record<string, string[]>;
  /** Source fingerprint (manifest.cipherFingerprint). */
  sourceFingerprint?: string | null;
  /** Source Eskoofy variant (manifest.eskoofyVariant). */
  eskoofyVariant?: string | null;
}

export interface RestoreResult {
  /** Sensitive columns cleared because the cipher fingerprints differ. */
  cleared: string[];
  /** Human-readable reconciliation notes (cross-variant restores only). */
  reconciled: string[];
}

export async function restoreDump(db: BackupDb, dump: PortableDump, options: RestoreOptions = {}): Promise<RestoreResult> {
  const result: RestoreResult = { cleared: [], reconciled: [] };
  await db.disableForeignKeys();
  try {
    for (const table of dump.tables) {
      const safe = assertSafeTable(table.table);
      const quoted = `\`${safe}\``;
      await db.run(`DELETE FROM ${quoted}`);

      const { columns, rows } = table;
      if (columns.length === 0 || rows.length === 0) continue;

      const placeholders = `(${columns.map(() => "?").join(",")})`;
      for (let i = 0; i < rows.length; i += INSERT_CHUNK) {
        const chunk = rows.slice(i, i + INSERT_CHUNK);
        const flatParams: unknown[] = [];
        const values = chunk.map((row) => {
          columns.forEach((_, index) => flatParams.push(row[index] ?? null));
          return placeholders;
        });
        const sql = `INSERT INTO ${quoted} (${columns.map((c) => `\`${c}\``).join(",")}) VALUES ${values.join(",")}`;
        await db.run(sql, flatParams);
      }
    }
    result.cleared = await clearSensitiveColumns(db, dump.tables, options.sensitiveColumns, options.sourceFingerprint);
    result.reconciled = await reconcileVariant(db, dump.tables, options.eskoofyVariant);
  } finally {
    await db.enableForeignKeys();
  }
  return result;
}

/**
 * Clear every sensitive column the source encrypted unless the cipher
 * fingerprints match. A refused (null) value beats storing ciphertext the
 * target cannot decrypt (or plaintext in an encrypted column).
 */
async function clearSensitiveColumns(
  db: BackupDb,
  tables: BackedUpTable[],
  sensitiveColumns: Record<string, string[]> | undefined,
  sourceFingerprint: string | null | undefined,
): Promise<string[]> {
  if (!sensitiveColumns) return [];
  if (secretsCompatible(sourceFingerprint ?? null)) return [];

  const cleared: string[] = [];
  for (const table of tables) {
    const safe = assertSafeTable(table.table);
    for (const column of sensitiveColumns[safe] ?? []) {
      if (!table.columns.includes(column)) continue;
      await db.run(`UPDATE \`${safe}\` SET \`${column}\` = NULL`);
      cleared.push(`${safe}.${column}`);
    }
  }
  return cleared;
}

/**
 * Re-set variant-owned configuration rows to the receiving profile after a
 * cross-variant restore. Same/unknown-variant restores run verbatim.
 */
export async function reconcileVariant(
  db: BackupDb,
  tables: BackedUpTable[],
  sourceVariant: string | null | undefined,
): Promise<string[]> {
  const target = (process.env.ESKOOFY_VARIANT ?? "int") as "bd" | "int";
  if (sourceVariant == null || sourceVariant === target) return [];

  const { eskoolfy } = await import("@/config/eskoolfy");
  const spec = eskoolfy.restore;
  const eligible = spec.gateways[target];
  const currency = spec.settings.currency[target];
  const notes: string[] = [];

  const gateways = await db.fetchAll("SELECT code FROM `payment_gateways`");
  for (const row of gateways) {
    const code = String(row.code ?? "");
    if (!eligible.includes(code)) {
      await db.run("UPDATE `payment_gateways` SET `is_active` = 0 WHERE `code` = ?", [code]);
      notes.push(`Gateway '${code}' deactivated (not part of the ${target} profile).`);
    }
  }
  for (const code of eligible) {
    const exists = gateways.some((row) => String(row.code ?? "") === code);
    if (exists) {
      await db.run("UPDATE `payment_gateways` SET `is_active` = 1, `currency` = ? WHERE `code` = ?", [currency, code]);
      notes.push(`Gateway '${code}' activated (${currency}).`);
    } else {
      notes.push(`Gateway '${code}' is missing after restore — add it in Settings › Payments.`);
    }
  }

  const settings = spec.settings;
  await db.run("UPDATE `website_settings` SET `currency` = ?, `default_payment_method` = ?, `default_locale` = ?", [
    currency,
    settings.defaultPaymentMethod[target],
    settings.defaultLocale[target],
  ]);
  notes.push(`website_settings reconciled to the ${target} profile (${currency}).`);

  if (target === "int" && spec.stripBanglaForInt) {
    for (const table of tables) {
      if (!["website_settings", "admission_settings", "website_contents"].includes(table.table)) continue;
      for (const column of table.columns) {
        const isBn = column.endsWith("_bn");
        const isPaymentNumber = table.table === "admission_settings" && column === "payment_number";
        if (!isBn && !isPaymentNumber) continue;
        await db.run(`UPDATE \`${assertSafeTable(table.table)}\` SET \`${column}\` = NULL`);
        notes.push(`${table.table}.${column} cleared (Bengali content has no home in the int profile).`);
      }
    }
  }

  return notes;
}

// ── Prisma adapter ───────────────────────────────────────────────────────────

function detectedEngine(): "mysql" | "sqlite" {
  const url = (process.env.DATABASE_URL ?? "").toLowerCase();
  return url.startsWith("sqlite") ? "sqlite" : "mysql";
}

export class PrismaBackupDb implements BackupDb {
  engine: "mysql" | "sqlite";

  constructor(engine: "mysql" | "sqlite" = detectedEngine()) {
    this.engine = engine;
  }

  async fetchAll(sql: string, params: unknown[] = []): Promise<Array<Record<string, unknown>>> {
    return (await prisma.$queryRawUnsafe(sql, ...params)) as Array<Record<string, unknown>>;
  }

  async run(sql: string, params: unknown[] = []): Promise<void> {
    await prisma.$executeRawUnsafe(sql, ...params);
  }

  async disableForeignKeys(): Promise<void> {
    if (this.engine === "sqlite") {
      await prisma.$executeRawUnsafe("PRAGMA foreign_keys = OFF");
    } else {
      await prisma.$executeRawUnsafe("SET FOREIGN_KEY_CHECKS = 0");
    }
  }

  async enableForeignKeys(): Promise<void> {
    if (this.engine === "sqlite") {
      await prisma.$executeRawUnsafe("PRAGMA foreign_keys = ON");
    } else {
      await prisma.$executeRawUnsafe("SET FOREIGN_KEY_CHECKS = 1");
    }
  }
}