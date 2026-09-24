import { describe, expect, it } from "vitest";
import {
  buildDump,
  parseTablesJson,
  reconcileVariant,
  restoreDump,
  serializeValue,
  tablesJson,
  SENSITIVE_COLUMNS,
  type BackupDb,
} from "@/lib/portable-backup";

/** In-memory DB used to prove the portable dump + restore cycle without a server. */
class FakeBackupDb implements BackupDb {
  engine: "mysql" | "sqlite";
  tables = new Map<string, Array<Record<string, unknown>>>();
  fkEnabled: boolean | null = null;
  statementLog: string[] = [];

  constructor(engine: "mysql" | "sqlite", tables: Record<string, Array<Record<string, unknown>>>) {
    this.engine = engine;
    this.tables = new Map(Object.entries(tables));
  }

  tableName(sql: string): string {
    const match =
      /FROM `([a-z_0-9]+)`/.exec(sql) ??
      /INTO `([a-z_0-9]+)`/.exec(sql) ??
      /DELETE FROM `([a-z_0-9]+)`/.exec(sql) ??
      /UPDATE `([a-z_0-9]+)`/.exec(sql);
    if (!match) throw new Error(`Cannot parse table from: ${sql}`);
    return match[1];
  }

  columns(sql: string): string[] {
    const match = /\(([^)]+)\)/.exec(sql);
    if (!match) return [];
    return match[1].split(",").map((column) => column.trim().slice(1, -1));
  }

  async fetchAll(sql: string): Promise<Array<Record<string, unknown>>> {
    this.statementLog.push(sql);
    return this.tables.get(this.tableName(sql)) ?? [];
  }

  async run(sql: string, params: unknown[] = []): Promise<void> {
    this.statementLog.push(sql);
    if (/^DELETE FROM/.test(sql)) {
      this.tables.set(this.tableName(sql), []);
      return;
    }
    if (/^UPDATE/.test(sql)) {
      const table = this.tableName(sql);
      const current = this.tables.get(table) ?? [];
      const assignments: Array<[string, unknown]> = [];
      const setRe = /SET\s+(.+?)(?:\sWHERE\s|\s*$)/is.exec(sql);
      if (setRe) {
        for (const part of setRe[1].split(",")) {
          const [, col, raw] = /`?([a-z_0-9]+)`?\s*=\s*([^,]+)/i.exec(part.trim()) ?? [];
          if (!col) continue;
          const token = (raw ?? "").trim();
          let value: unknown;
          if (token === "?") value = params.shift() ?? null;
          else if (token.toUpperCase() === "NULL") value = null;
          else if (/^\d+$/.test(token)) value = Number(token);
          else value = token.replace(/^"|"$/g, "");
          assignments.push([col, value]);
        }
      }
      const whereCol = /WHERE\s+`?([a-z_0-9]+)`?\s*=\s*\?/i.exec(sql)?.[1];
      const whereVal = whereCol ? params.shift() : undefined;
      const updated = current.map((row) => {
        const next = { ...row };
        const matches = whereCol === undefined || String(row[whereCol] ?? "") === String(whereVal);
        if (matches) {
          assignments.forEach(([col, value]) => {
            next[col] = value;
          });
        }
        return next;
      });
      this.tables.set(table, updated);
      return;
    }
    if (/^INSERT INTO/.test(sql)) {
      const table = this.tableName(sql);
      const columns = this.columns(sql);
      const valuesRe = /VALUES\s+(.*)$/s.exec(sql);
      if (!valuesRe) return;
      const tuples = valuesRe[1].split(/\),\(/).map((tuple) => tuple.replace(/^\(/, "").replace(/\)$/, ""));
      let offset = 0;
      for (const tuple of tuples) {
        const count = (tuple.match(/\?/g) ?? []).length;
        const values = params.slice(offset, offset + count);
        offset += count;
        const row: Record<string, unknown> = {};
        columns.forEach((column, index) => {
          row[column] = values[index] ?? null;
        });
        this.tables.set(table, [...(this.tables.get(table) ?? []), row]);
      }
      return;
    }
    if (/^PRAGMA foreign_keys/.test(sql) || /^SET FOREIGN_KEY_CHECKS/.test(sql)) {
      this.fkEnabled = /(ON|1)$/.test(sql);
    }
  }

  async disableForeignKeys(): Promise<void> {
    this.fkEnabled = false;
  }

  async enableForeignKeys(): Promise<void> {
    this.fkEnabled = true;
  }
}

describe("portable backup value serialization", () => {
  it("normalizes dates to the app's naive datetime convention", () => {
    const local = new Date(2024, 8, 30, 10, 20, 30); // 2024-09-30 10:20:30 local
    expect(serializeValue(local, "mysql")).toBe("2024-09-30 10:20:30");
    expect(typeof serializeValue(local, "mysql")).toBe("string");
  });

  it("keeps strings, numbers and null verbatim", () => {
    expect(serializeValue("abc", "mysql")).toBe("abc");
    expect(serializeValue(42, "mysql")).toBe(42);
    expect(serializeValue(null, "mysql")).toBeNull();
    expect(serializeValue(undefined, "mysql")).toBeNull();
  });

  it("stores booleans, bigint and decimals portably", () => {
    expect(serializeValue(true, "mysql")).toBe(1);
    expect(serializeValue(false, "sqlite")).toBe(0);
    expect(serializeValue(12345678901234567890n, "mysql")).toBe("12345678901234567890");
    expect(serializeValue("1250.00", "mysql")).toBe("1250.00");
  });

  it("base64s buffers", () => {
    expect(serializeValue(Buffer.from([1, 2, 3]), "mysql")).toBe("AQID");
  });
});

describe("portable backup dump + restore cycle", () => {
  it("builds a dump and restores it back into an empty DB", async () => {
    const source = new FakeBackupDb("mysql", {
      users: [
        { id: 1, name: "Admin", email: "admin@school.com", password: "x", role: "admin" },
        { id: 2, name: "Teacher A", email: "t@school.com", password: "y", role: "teacher" },
      ],
      school_classes: [{ id: 1, name: "Class 5", code: "C1" }],
      students: [{ id: 1, user_id: 2, class_id: 1, admission_number: "ADM-1", admission_date: new Date(2024, 0, 15), first_name: "Jane", last_name: "Doe" }],
    });

    const { dump } = await buildDump(source, ["students", "users", "school_classes"]);
    const json = tablesJson(dump);
    const parsed = parseTablesJson(json);

    expect(parsed.tables.map((table) => table.table)).toEqual(["school_classes", "students", "users"]); // sorted
    const students = parsed.tables.find((table) => table.table === "students")!;
    expect(students.columns).toContain("admission_number");
    expect(students.rows[0]).toEqual(expect.arrayContaining(["ADM-1", "2024-01-15 00:00:00"]));

    const target = new FakeBackupDb("sqlite", { users: [], school_classes: [], students: [] });
    await restoreDump(target, parsed);

    expect(target.fkEnabled).toBe(true);
    expect(target.tables.get("users")).toHaveLength(2);
    expect(target.tables.get("school_classes")![0]).toEqual({ id: 1, name: "Class 5", code: "C1" });
    const restoredStudent = target.tables.get("students")![0];
    expect(restoredStudent.admission_number).toBe("ADM-1");
    expect(restoredStudent.admission_date).toBe("2024-01-15 00:00:00");
  });

  it("deletes each table before inserting and chunks large inserts", async () => {
    const source = new FakeBackupDb("mysql", {
      users: [{ id: 1, name: "A", email: "a@x", password: "p", role: "student" }],
    });
    const { dump } = await buildDump(source, ["users"]);
    dump.tables[0].rows = Array.from({ length: 450 }, (_, index) => [index + 1, `U${index}`, `u${index}@x`, "p", "student"]);

    const target = new FakeBackupDb("sqlite", { users: [{ id: 999, name: "old" }] });
    await restoreDump(target, dump);

    const inserts = target.statementLog.filter((sql) => sql.startsWith("INSERT INTO"));
    expect(inserts.length).toBe(3); // 200 + 200 + 50
    expect(inserts.every((sql) => /VALUES\s+\(.+\)/.test(sql))).toBe(true);
    expect(target.tables.get("users")![0].id).not.toBe(999);
  });

  it("rejects unsafe table names", async () => {
    const target = new FakeBackupDb("sqlite", {});
    await expect(
      restoreDump(target, { tables: [{ table: "users; DROP TABLE users", columns: ["id"], rows: [[1]] }] }),
    ).rejects.toThrow(/Unsafe table name/);
  });
});

describe("portable backup cross-variant manifest", () => {
  it("tags the backup with its Eskoofy variant, fingerprint and sensitive columns", async () => {
    const source = new FakeBackupDb("mysql", {
      users: [{ id: 1, name: "Admin", email: "admin@school.com", password: "x", role: "admin" }],
    });
    const { manifest } = await buildDump(source, ["users"]);

    expect(manifest.eskoofyVariant).toBe(process.env.ESKOOFY_VARIANT ?? "int");
    expect(manifest.cipherFingerprint).toBeNull();
    expect(manifest.sensitiveColumns).toEqual(SENSITIVE_COLUMNS);
    expect(manifest.sensitiveColumns.payment_gateways).toContain("api_key");
  });

  it("clears encrypted columns restored from a foreign cipher fingerprint", async () => {
    const source = new FakeBackupDb("mysql", {
      payment_gateways: [
        { id: 1, code: "bkash", name: "bKash", is_active: 1, api_key: "ciphertext-from-laravel", currency: "BDT" },
      ],
    });
    const { dump } = await buildDump(source, ["payment_gateways"]);
    const target = new FakeBackupDb("sqlite", { payment_gateways: [] });

    const result = await restoreDump(target, dump, {
      sensitiveColumns: SENSITIVE_COLUMNS,
      sourceFingerprint: "sha256-of-a-laravel-app-key",
    });

    expect(result.cleared).toContain("payment_gateways.api_key");
    expect(target.tables.get("payment_gateways")![0].api_key).toBeNull();
    expect(target.tables.get("payment_gateways")).toHaveLength(1);
  });

  it("keeps plaintext secrets for a Node-to-Node restore", async () => {
    const source = new FakeBackupDb("sqlite", {
      payment_gateways: [{ id: 1, code: "stripe", name: "Stripe", is_active: 1, api_key: "sk_test_abc", currency: "USD" }],
    });
    const { dump } = await buildDump(source, ["payment_gateways"]);
    const target = new FakeBackupDb("sqlite", { payment_gateways: [] });

    const result = await restoreDump(target, dump, {
      sensitiveColumns: SENSITIVE_COLUMNS,
      sourceFingerprint: null,
    });

    expect(result.cleared).toEqual([]);
    expect(target.tables.get("payment_gateways")![0].api_key).toBe("sk_test_abc");
  });

  it("reconciles variant-owned rows when the source variant differs", async () => {
    process.env.ESKOOFY_VARIANT = "int";
    try {
      const tables = [
        {
          table: "payment_gateways",
          columns: ["id", "code", "name", "is_active", "currency"],
          rows: [
            [1, "bkash", "bKash", 1, "BDT"],
            [2, "stripe", "Stripe", 0, "BDT"],
          ],
        },
        {
          table: "website_settings",
          columns: ["id", "currency", "default_payment_method", "default_locale"],
          rows: [[1, "BDT", "bkash", "bn"]],
        },
        {
          table: "admission_settings",
          columns: ["id", "closed_message_bn", "payment_number"],
          rows: [[1, "বন্ধ", "01712345678"]],
        },
      ];
      const db = new FakeBackupDb("sqlite", {
        payment_gateways: [
          { id: 1, code: "bkash", name: "bKash", is_active: 1, currency: "BDT" },
          { id: 2, code: "stripe", name: "Stripe", is_active: 0, currency: "BDT" },
        ],
        website_settings: [{ id: 1, currency: "BDT", default_payment_method: "bkash", default_locale: "bn" }],
        admission_settings: [{ id: 1, closed_message_bn: "বন্ধ", payment_number: "01712345678" }],
      });

      const notes = await reconcileVariant(db, tables, "bd");

      expect(notes.length).toBeGreaterThan(0);
      const gateways = db.tables.get("payment_gateways")!;
      expect(gateways.find((row) => row.code === "bkash")!.is_active).toBe(0);
      expect(gateways.find((row) => row.code === "stripe")!.is_active).toBe(1);
      expect(gateways.find((row) => row.code === "stripe")!.currency).toBe("USD");
      const settings = db.tables.get("website_settings")![0];
      expect(settings.currency).toBe("USD");
      expect(settings.default_payment_method).toBe("stripe");
      const admission = db.tables.get("admission_settings")![0];
      expect(admission.payment_number).toBeNull();
      expect(admission.closed_message_bn).toBeNull();
    } finally {
      delete process.env.ESKOOFY_VARIANT;
    }
  });

  it("does not reconcile when the source variant matches the target", async () => {
    process.env.ESKOOFY_VARIANT = "int";
    try {
      const db = new FakeBackupDb("sqlite", {});
      expect(await reconcileVariant(db, [], "int")).toEqual([]);
      expect(await reconcileVariant(db, [], null)).toEqual([]);
    } finally {
      delete process.env.ESKOOFY_VARIANT;
    }
  });
});