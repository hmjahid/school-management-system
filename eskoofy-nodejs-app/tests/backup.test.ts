import { afterAll, beforeAll, describe, expect, it } from "vitest";
import { mkdirSync, rmSync, writeFileSync } from "node:fs";
import { join } from "node:path";
import {
  backupsDirPath,
  deleteBackup,
  listBackups,
  restoreBackupZip,
  storagePublicServePath,
} from "@/lib/backup";
import { MANIFEST_FILE, TABLES_FILE, type BackupDb } from "@/lib/portable-backup";
import { createZip } from "@/lib/zip";

class FakeDb implements BackupDb {
  engine: "mysql" | "sqlite" = "mysql";
  rows: Record<string, unknown>[][] = [];
  async fetchAll(): Promise<Array<Record<string, unknown>>> {
    return [] as Array<Record<string, unknown>>;
  }
  async run(sql: string): Promise<void> {
    this.rows.push([{ sql }]);
  }
  async disableForeignKeys(): Promise<void> {}
  async enableForeignKeys(): Promise<void> {}
}

const FIXTURE = "backup_20240101_101010_abcdef.zip";

beforeAll(() => {
  mkdirSync(backupsDirPath(), { recursive: true });
});

afterAll(() => {
  rmSync(backupsDirPath(), { recursive: true, force: true });
  rmSync(storagePublicServePath(), { recursive: true, force: true });
});

const manifest = {
  format: "eskoofy-portable-backup",
  version: 1,
  variant: "int",
  createdAt: "2024-01-01 10:10:10",
  engine: "mysql",
  tableCount: 1,
};

describe("backup files", () => {
  it("restores the portable format and extracts storage files", async () => {
    const zip = createZip([
      { name: MANIFEST_FILE, data: JSON.stringify(manifest), method: 0 },
      { name: TABLES_FILE, data: JSON.stringify({ tables: [{ table: "users", columns: ["id", "name"], rows: [[1, "Jane"]] }] }), method: 8 },
      { name: "storage/app/public/uploads/a.txt", data: "file-content", method: 8 },
    ]);
    writeFileSync(join(backupsDirPath(), FIXTURE), zip);

    const db = new FakeDb();
    const result = await restoreBackupZip(FIXTURE, db);

    expect(result.tableCount).toBe(1);
    expect(result.restoredFiles).toEqual(["uploads/a.txt"]);
    const restored = join(storagePublicServePath(), "uploads", "a.txt");
    expect(require("node:fs").readFileSync(restored, "utf8")).toBe("file-content");
    // restore ran DELETE then INSERT
    expect(db.rows.map((entry) => entry[0].sql)).toEqual(["DELETE FROM `users`", "INSERT INTO `users` (`id`,`name`) VALUES (?,?)"]);
  });

  it("rejects a zip that is not a portable backup", async () => {
    const zip = createZip([{ name: "hello.txt", data: "nope", method: 0 }]);
    writeFileSync(join(backupsDirPath(), FIXTURE), zip);
    await expect(restoreBackupZip(FIXTURE, new FakeDb())).rejects.toThrow(/Not a portable Eskoofy backup/);
  });

  it("rejects non-backup filenames", async () => {
    writeFileSync(join(backupsDirPath(), "oops.txt"), "x");
    await expect(restoreBackupZip("oops.txt", new FakeDb())).rejects.toThrow(/Invalid backup filename/);
  });

  it("lists and deletes backup archives", async () => {
    writeFileSync(join(backupsDirPath(), FIXTURE), "zip-data");
    const files = await listBackups();
    expect(files.some((file) => file.name === FIXTURE)).toBe(true);
    await deleteBackup(FIXTURE);
    const after = await listBackups();
    expect(after.some((file) => file.name === FIXTURE)).toBe(false);
  });
});