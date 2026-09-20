import { randomBytes } from "node:crypto";
import { existsSync } from "node:fs";
import { mkdir, readdir, readFile, stat, unlink, writeFile } from "node:fs/promises";
import { join, relative } from "node:path";
import { createZip, readZip } from "@/lib/zip";
import {
  buildDump,
  formatNow,
  MANIFEST_FILE,
  parseTablesJson,
  restoreDump,
  STORAGE_PREFIX,
  TABLES_FILE,
  type BackupDb,
  type BackupManifest,
} from "@/lib/portable-backup";

/**
 * File-level backup operations for the Node variant — mirrors the Laravel app's
 * `/dashboard/backup*` controller. Backups live in `storage/backups/*.zip` and
 * use the shared portable format (see docs/design/DATA-PORTABILITY.md), so a
 * backup created here restores in the app and vice-versa.
 */

export interface BackupFileMeta {
  name: string;
  size: number;
  modified: number;
}

export function backupsDirPath(): string {
  return join(process.cwd(), "storage", "backups");
}

export function uploadsDirPath(): string {
  return join(process.cwd(), "storage", "app", "public");
}

export function storagePublicServePath(): string {
  return join(process.cwd(), "public", "storage");
}

async function ensureDir(path: string): Promise<void> {
  await mkdir(path, { recursive: true });
}

export async function listBackups(): Promise<BackupFileMeta[]> {
  const dir = backupsDirPath();
  if (!existsSync(dir)) return [];

  const entries = await readdir(dir);
  const files: BackupFileMeta[] = [];
  for (const name of entries) {
    if (!name.endsWith(".zip")) continue;
    const full = join(dir, name);
    try {
      const s = await stat(full);
      files.push({ name, size: s.size, modified: s.mtimeMs });
    } catch {
      // racy deletion — ignore
    }
  }
  return files.sort((a, b) => b.modified - a.modified);
}

export function backupPathFor(name: string): string {
  if (!/^backup_[0-9]{8}_[0-9]{6}_[A-Za-z0-9]{6}\.zip$/.test(name)) {
    throw new Error(`Invalid backup filename: ${name}`);
  }
  return join(backupsDirPath(), name);
}

/** Recursively collect `storage/app/public/**` files as zip entries. */
async function collectStorageEntries(): Promise<Array<{ name: string; data: Buffer }>> {
  const root = uploadsDirPath();
  if (!existsSync(root)) return [];

  const entries: Array<{ name: string; data: Buffer }> = [];
  const walk = async (dir: string): Promise<void> => {
    const items = await readdir(dir, { withFileTypes: true });
    for (const item of items) {
      const full = join(dir, item.name);
      if (item.isDirectory()) {
        await walk(full);
      } else if (item.isFile()) {
        const rel = relative(root, full).split("\\").join("/");
        entries.push({ name: `${STORAGE_PREFIX}${rel}`, data: await readFile(full) });
      }
    }
  };
  await walk(root);
  return entries;
}

export interface CreatedBackup {
  name: string;
  path: string;
  tableCount: number;
}

/** Create a portable backup zip in `storage/backups`. Mirrors `backup:run`. */
export async function createBackupZip(db: BackupDb, tables: string[]): Promise<CreatedBackup> {
  const { dump, manifest } = await buildDump(db, tables);
  const storageEntries = await collectStorageEntries();

  const zip = createZip([
    { name: MANIFEST_FILE, data: JSON.stringify(manifest, null, 2) },
    { name: TABLES_FILE, data: JSON.stringify(dump) },
    ...storageEntries.map((entry) => ({ name: entry.name, data: entry.data })),
  ]);

  const ts = formatNow().replace(/[-: ]/g, "");
  const name = `backup_${ts}_${randomBytes(3).toString("hex")}.zip`;
  const dir = backupsDirPath();
  await ensureDir(dir);
  const path = join(dir, name);
  await writeFile(path, zip);

  return { name, path, tableCount: manifest.tableCount };
}

export interface RestoredBackup {
  tableCount: number;
  restoredFiles: string[];
  manifest: BackupManifest;
}

/** Restore a portable backup zip: replaces every backed-up table's rows + files. */
export async function restoreBackupZip(file: string, db: BackupDb): Promise<RestoredBackup> {
  const path = backupPathFor(file);
  const buffer = await readFile(path);
  const entries = readZip(buffer);

  const manifestText = entries.get(MANIFEST_FILE);
  const tablesText = entries.get(TABLES_FILE);
  if (!manifestText || !tablesText) {
    throw new Error("Not a portable Eskoofy backup (missing MANIFEST.json / database/tables.json).");
  }

  const manifest = JSON.parse(Buffer.from(manifestText).toString("utf8")) as BackupManifest;
  if (manifest.format !== "eskoofy-portable-backup") {
    throw new Error(`Unsupported backup format: ${manifest.format}`);
  }

  const dump = parseTablesJson(Buffer.from(tablesText).toString("utf8"));
  await restoreDump(db, dump);

  const restoredFiles: string[] = [];
  const serveRoot = storagePublicServePath();
  await ensureDir(serveRoot);
  for (const [entryName, data] of entries) {
    if (!entryName.startsWith(STORAGE_PREFIX)) continue;
    const rel = entryName.slice(STORAGE_PREFIX.length);
    if (rel === "" || rel.includes("..")) continue;
    const target = join(serveRoot, rel);
    await ensureDir(join(target, ".."));
    await writeFile(target, data);
    restoredFiles.push(rel);
  }

  return { tableCount: manifest.tableCount, restoredFiles, manifest };
}

/** Delete a backup zip. */
export async function deleteBackup(file: string): Promise<void> {
  const path = backupPathFor(file);
  await unlink(path);
}