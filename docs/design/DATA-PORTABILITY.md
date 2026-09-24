# Data portability — portable backup & CSV contracts

**Status:** Implemented (Laravel + Node variants, in lockstep; php-app + WP theme follow the
same cross-variant restore semantics with their own formats).

Covers the portable backup archive (zip) shared by `eskoofy-laravel-app` and
`eskoofy-nodejs-app`, the bulk CSV import/export contract, and the cross-variant restore
rules (encrypted secrets + variant-owned config). The goal: a backup or export made in one
variant — `bd` or `int` — restores/imports cleanly in the other, with no cross-engine SQL in
between and no BD-only residue leaking into an int install (and vice-versa).

Reference implementation: the Laravel app's `BackupRunCommand` / `BackupRestoreCommand` and
`PortableBackupService`; the Node variant's `lib/portable-backup.ts` + `lib/backup.ts`.

## 1. Portable backup archive

Created by `backup:run` (and `backup:restore`) in Laravel, `lib/backup.ts`
`createBackupZip` in Node, via a `dashboard/backup` controller/actions pair. Layout:

```
MANIFEST.json                 format + version + variant + engine + dates + table count +
                              eskoofyVariant + cipherFingerprint + sensitiveColumns
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

### 1a. Manifest fields (portable v1, additive)

| field | meaning |
|---|---|
| `format` / `version` | `eskoofy-portable-backup` / `1` |
| `variant` | creator engine label (`laravel` for the app; the Node variant keeps its historical bd/int value here) |
| `eskoofyVariant` | `"bd"` or `"int"` — which Eskoofy profile produced the backup |
| `cipherFingerprint` | identity of the creator's secret-encryption key: `sha256(APP_KEY)` (Laravel) or `null` (Node — no encryption layer) |
| `sensitiveColumns` | `{ table: [column, …] }` — columns the creator wrapped with its encryption (derived from the Laravel models' `encrypted` casts) |

## 2. Cross-variant restore rules

### 2a. Encrypted credentials (fingerprint check)

Laravel stores payment-gateway and SMTP secrets AES-encrypted with `APP_KEY`
(`payment_gateways.api_key/api_secret/api_username/api_password`;
`website_settings.bkash_*`, `twilio_*`, `mail_username/password`). Restoring that
ciphertext under a different key would crash every payment page with a
`DecryptException`. Restore therefore:

- **keeps** a sensitive column's value only when source and target fingerprints are equal
  (same app, or Node→Node plaintext with both `null`);
- **clears** it (sets `NULL`) otherwise — a refused (null) value is safer than unusable
  ciphertext or plaintext stuffed into an encrypted column. The operator is warned per
  column, and re-enters credentials after restore.

| source → target | fingerprint match? | secret column |
|---|---|---|
| Laravel → same Laravel | yes | kept |
| Laravel → different Laravel | no | cleared |
| Laravel → Node | no | cleared |
| Node → Node | both null (equal) | kept |
| Node → Laravel | no | cleared |
| pre-v2 backup (no fingerprint) | n/a | cleared |

### 2b. Variant-owned config reconciliation

Business data (students, staff, fees, results, content) restores **verbatim**. But
variant-owned configuration rows are re-set to the **receiving** variant's profile when
`eskoofyVariant` differs from the receiving `eskoolfy.variant` (same-variant and unknown
sources restore verbatim):

- `payment_gateways`: the receiving profile's gateway set is activated (and `currency`
  set to the profile currency); any gateway outside that set is deactivated (never
  deleted, so custom rows survive).
- `website_settings`: `currency`, `default_payment_method`, `default_locale` are set to the
  receiving profile's values (bd → BDT/bkash/en; int → USD/stripe/en).
- **int restores** also clear Bengali-only UI content (`*_bn` columns across
  `website_settings`, `admission_settings`, `website_contents`) and the BD admission
  `payment_number`, so no remnant of the bd profile surfaces in an int install.

The reconcile spec lives in `config/eskoolfy.php` (`eskoolfy.restore`, mirrored by
`eskoofy-nodejs-app/config/eskoolfy.ts` and `eskoofy-php-app/config/eskoolfy.php`) —
variant differences are data, never `if (variant === 'bd')` branching.

## 3. Bulk CSV import/export

Single source of truth for headers is the Laravel `DashboardBulkController`; the Node
variant's `lib/bulk.ts` reproduces the identical header lists and CSV quote rules
(fputcsv-compatible). Exports:
`students`, `teachers`, `fees`, `attendances` (Node also `fees`/`attendances`).

The import contract does not carry `nationality`/`country`, so those fall back to the
**receiving** variant's `eskoolfy.import.student_defaults` — bd stamps
`Bangladeshi`/`Bangladesh` (today's behavior), int stores `NULL` (the columns are nullable
in the app + Node schema). This keeps exported bd lists from baking BD defaults into an int
install.

## 4. php-app & WP theme

- **php-app** uses raw SQL backups; `BackupController::create` writes a
  `-- eskoofy-variant:` header and `restore` reconciles variant-owned rows when the source
  variant differs (`app/Services/VariantRestoreReconciler.php`). Payment CSV exports take
  their `Currency` from the gateway row (fallback `config('payment.currency')`).
- **WP theme** backups are JSON of `esk_*` tables; it has no separate variant system
  (variants are build-time translations/branding), so restores stay verbatim. Refund rows
  now take their `currency` from the linked payment's gateway instead of a hardcoded `BDT`
  (`esk_refund_currency()` in `inc/helpers.php`).

## 5. Contract tests

- Laravel: `tests/Feature/PortableBackupTest.php` (round-trip restore, layout match,
  fingerprint keep/clear, cross-variant reconcile), `tests/Feature/DashboardBulkImportTest.php`
  (bd/int import defaults).
- Node: `tests/portable-backup.test.ts`, `tests/backup.test.ts`, `tests/zip.test.ts`,
  `tests/bulk.test.ts`.
- php-app: `tests/Unit/Services/VariantRestoreReconcilerTest.php`.