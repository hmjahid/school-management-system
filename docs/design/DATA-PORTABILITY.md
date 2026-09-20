# Data portability — portable backup & CSV contracts

**Status:** Implemented (Laravel + Node variants, in lockstep).

Covers the portable backup archive (zip) shared by `eskoofy-laravel-app` and
`eskoofy-nodejs-app`, and the bulk CSV import/export contract. The goal: a backup or
export made in one variant restores/imports verbatim in the other, with no cross-engine
SQL in between.

Reference implementation: the Laravel app's `BackupRunCommand` / `BackupRestoreCommand`
and `PortableBackupService`; the Node variant's `lib/portable-backup.ts` + `lib/backup.ts`.

## 1. Portable backup archive

Created by `backup:run` (and `backup:restore`) in Laravel, `lib/backup.ts`
`createBackupZip` in Node, via a `dashboard/backup` controller/actions pair. Layout:

```
MANIFEST.json                 format + version + variant + engine + dates + table count
database/tables.json          { "tables": [ { "table", "columns[]," rows[][] }, ... ] }
database/sqlite.sqlite        legacy convenience copy — Laravel only, optional
storage/app/public/**         user uploads (documents, news images, gallery, etc.)
```

- Values are serialized portably (dates → `Y-m-d H:i:s`, bools → 0/1, decimals/strings
  kept, blobs → base64), so rows survive engine swaps (sqlite ↔ mysql ↔ pgsql).
- Table rows are exported as arrays aligned to `columns[]` — no SQL text; only
  table names (validated against `^[A-Za-z_][A-Za-z0-9_]*$`) reach the DB.
- Restore turns FK checks/triggers off, `DELETE`s each table, then `INSERT`s in chunks
  of 200, with a single transaction.

## 2. Bulk CSV import/export

Single source of truth for headers is the Laravel `DashboardBulkController`; the Node
variant's `lib/bulk.ts` reproduces the identical header lists and CSV quote rules
(fputcsv-compatible). Exports:
`students`, `teachers`, `fees`, `attendances` (Node also `fees`/`attendances`).

`MANIFEST.json` sample (ported identically both ways):

```json
{ "format": "eskoofy-portable-backup", "version": 1, "variant": "eskoofy:laravel" }
```

## 3. Contract tests

- Laravel: `tests/Feature/PortableBackupTest.php` (round-trip restore, layout match).
- Node: `tests/portable-backup.test.ts`, `tests/backup.test.ts`, `tests/zip.test.ts`,
  `tests/bulk.test.ts`.
