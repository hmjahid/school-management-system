# Master prompt — Cloud backup (Google Drive / Dropbox / 4shared), auto-backup, document watermarks, and fully customizable document designs

> Status: **EXECUTING** — this prompt is the single source of truth for the work below.
> Execute every task in order. Do not stop until all acceptance criteria pass.
>
> Origin: `docs/notes/self-notes-products.md:382` (raw request) + the institution-watermark
> and design-customisation requirements. This file is the normalised, executable version.

---

## Context

Eskoofy is a 4-product monorepo plus a branding/licensing website:

| Folder | Product | Stack |
|---|---|---|
| `eskoofy-laravel-app/` | School management app (**reference product**) | Laravel 12 |
| `eskoofy-php-app/` | Raw PHP rewrite (shared hosting) | Raw PHP 8.2 |
| `eskoofy-wp-theme/` | WordPress theme (plugin-hybrid) | WP theme |
| `eskoofy-nodejs-app/` | Node.js clone of the app | Next.js + Prisma |
| `eskoofy-branding-website/` | Marketing site + license server (NOT a product) | Raw PHP |

BD/INT are build-time profiles produced by `build/export.sh` + `build/profiles/*` — never forks.
The Laravel app is the reference; the other products follow it (`docs/design/FEATURE-PROPAGATION.md`).

### What already exists (do not rebuild)

- Portable backup zip format — `docs/design/DATA-PORTABILITY.md`
  (`MANIFEST.json` + `database/tables.json` + `storage/app/public/**`), implemented in all 4 products.
- `backup:database` / `backup:run` / `backup:restore` (Laravel), `lib/backup.ts` (Node),
  `BackupController` (php), `views/admin/backup.php` (theme), `BackupService` (website).
- Document print/PDF surfaces: certificates, testimonials, marksheets, admit cards, student ID cards.
- **No cloud provider integration exists** (only archived placeholders in
  `eskoofy-laravel-app/archive/frontend/.../BackupSettings.jsx`).
- **No watermark exists** in any product.
- **No design customisation exists** — print templates hard-code colours/sizes.

---

## Task 1 — Cloud backup providers (Google Drive, Dropbox, 4shared) in all 4 products

Ship a **pluggable cloud-backup subsystem** in every product, built on the existing portable
zip so a cloud copy restores everywhere.

### 1.1 Shared contract (must be identical in shape across all products)

**Provider keys** (never rename — they are the wire contract):

| key | label | credential model |
|---|---|---|
| `local` | This server | none (no-op mirror, always available) |
| `google_drive` | Google Drive | `client_id`, `client_secret`, `refresh_token` **or** `service_account_email` + `service_account_private_key` |
| `dropbox` | Dropbox | `app_key`, `app_secret`, `refresh_token` (or `access_token`) |
| `4shared` | 4shared | `api_key`, `username`, `password` (folder optional) |
| `s3` | Amazon S3 / compatible | `endpoint`, `bucket`, `key`, `secret`, `region` (bonus, config-driven) |

**Driver interface** (same four verbs everywhere):

```
key(): string
label(): string
test(): array{ok: bool, message: string}      // credential check, never throws
upload(localPath, fileName): array{id, name, size, path}   // returns remote descriptor
download(remoteId, localPath): string          // local path
delete(remoteId): bool
list(folder?): array<int, array{id,name,size,modified}>     // newest first
```

**HTTP transport must be injectable** so tests never touch the network:
Laravel → `App\Services\CloudBackup\Contracts\CloudHttpClient` (interface) +
`CurlCloudHttpClient` (production) + `FakeCloudHttpClient` (tests).
php/theme → same idea behind a small client class with a static/injectable handler.
Node → `CloudHttpClient` interface + `fetchCloudHttpClient` + a fake in `tests/`.

**Storage** — one settings row per install + a run log:

| table | columns |
|---|---|
| `cloud_backup_settings` | `id`, `provider`, `credentials` (encrypted json), `folder`, `is_enabled`, `auto_enabled`, `interval_minutes`, `keep`, `last_run_at`, `last_status`, `last_error`, timestamps |
| `cloud_backup_runs` | `id`, `provider`, `file_name`, `remote_id`, `size`, `status` (`success`/`failed`/`skipped`), `message`, `created_at` |

Theme equivalents: `esk_cloud_backup_settings` + `esk_cloud_backup_runs` in `inc/database.php`.
Node: Prisma models `@@map("cloud_backup_settings")` / `@@map("cloud_backup_runs")`.
php: same tables in `database/schema.sql`.

**Security rules (all products)**

- Credentials are stored encrypted at rest (Laravel `encrypted` cast / php `openssl` with
  `APP_KEY` / theme `AUTH_KEY`+`SECURE_AUTH_KEY` AES-256-GCM / Node AES-256-GCM with `APP_KEY`).
- Never echo a secret back in HTML, JSON, logs, or the run log. The settings page shows
  only `configured: true/false` per credential field.
- Uploads use HTTPS only; TLS verification on; a request timeout (default 60s) is mandatory.
- Remote file names are sanitised to `backup_<variant>_<timestamp>.zip`; the local path is
  never taken from user input (`basename()` + allow-list regex on every entry point).
- Restore from the cloud is a privileged action: `restore_database` (products) / admin (website).

### 1.2 Per-product deliverables

**`eskoofy-laravel-app` (reference)**

- `config/backup.php` — providers, defaults (`keep`, `interval_minutes`, `timeout`, `retention`),
  env keys, `auto` toggle.
- `app/Services/CloudBackup/Contracts/CloudBackupDriver.php`, `CloudHttpClient.php`
- `app/Services/CloudBackup/Http/CurlCloudHttpClient.php`
- `app/Services/CloudBackup/Drivers/{LocalCloudDriver,GoogleDriveDriver,DropboxDriver,FourSharedDriver,S3CloudDriver}.php`
- `app/Services/CloudBackup/CloudBackupManager.php` — provider resolution + upload/download/list/delete
- `app/Services/CloudBackup/CloudBackupService.php` — settings get/set, `runNow()`, `isDue()`, `prune()`, run logging
- `app/Console/Commands/CloudBackupRunCommand.php` (`backup:cloud {--provider=} {--force}`)
- `app/Console/Commands/CloudBackupDispatchCommand.php` (`backup:cloud:dispatch`)
- `app/Console/Commands/CloudBackupRestoreCommand.php` (`backup:cloud:restore {file?} {--latest}`)
- `app/Http/Controllers/Web/DashboardCloudBackupController.php` (index/save/test/upload-now/list/restore/delete/prune)
- `resources/views/dashboard/cloud-backup/index.blade.php`
- routes `dashboard.cloud-backup.*` in `routes/dashboard.php`; sidebar link (System group)
- permissions: `manage_cloud_backup` (new) in `RolePermissionSeeder`; `backup_database` /
  `restore_database` reuse existing gates
- tests: `tests/Unit/Services/CloudBackup/DriverTest.php`,
  `tests/Unit/Services/CloudBackup/ServiceTest.php`, `tests/Feature/CloudBackupPageTest.php`

**`eskoofy-php-app`** — same structure under `app/Services/CloudBackup/**`,
`app/Controllers/Dashboard/CloudBackupController.php`, `scripts/backup-cloud.php` (CLI for cron),
`routes/web.php` + `config/routes.php` entries, `resources/views/dashboard/cloud-backup/index.blade.php`
(byte-identical copy from the app), `config/backup.php`, `config/eskoolfy.php` cloud block,
schema tables, `tests/Unit/Services/CloudBackup/*`.

**`eskoofy-wp-theme`** — `inc/cloud-backup.php` (drivers over `wp_remote_request`,
`wp_schedule_event` for the auto interval), `views/admin/cloud-backup.php`,
`inc/front-dashboard.php` route `esk-cloud-backup` + required cap,
`inc/admin-shell.php` (group/title/icon/link), `inc/database.php` tables, print/pot strings.

**`eskoofy-nodejs-app`** — `lib/cloud-backup/{types,http,drivers,manager,service}.ts`,
`app/(dashboard)/dashboard/cloud-backup/{page.tsx,actions.ts}`,
`prisma/schema.prisma` models, `lib/nav.ts` item (`cloud_backup`), `lang/{en,bn}.ts` keys,
`tests/cloud-backup.test.ts`.

**`eskoofy-branding-website`** — `app/Services/CloudBackup/**` (same 4 verbs, `local` + the three
providers), `app/Controllers/Admin/CloudBackupController.php`, `views/admin/cloud-backup.php`,
`routes/web.php`, settings in the existing `settings` table (key `cloud_backup`),
`database/schema.sql` run log, sales copy on `/products/*` + `/features`, tests.

### 1.3 Acceptance criteria (Task 1)

1. Every product has a working "cloud backup" admin surface: pick provider → save credentials →
   **Test connection** → **Back up now** → list remote files → **Restore** → **Delete**.
2. Credentials are stored encrypted and never rendered back.
3. `backup:cloud` produces a portable zip identical in contract to `backup:run` and uploads it.
4. Restoring a cloud copy restores tables + `storage/app/public/**` exactly like a local restore.
5. Unit tests cover all 4 provider drivers' request shape (URL, method, headers, body) with a fake
   HTTP client; no test performs network I/O.

---

## Task 2 — Automatic backup after a specific interval (all 4 products)

- Settings: `auto_enabled` (bool) + `interval_minutes` (int, 5…10080, default 60) + `keep`
  (retention, 1…365, default 7) + optional `run_at` (HH:MM) for a daily window.
- **Dispatch, don't statically schedule.** A single dispatcher runs on a fixed, cheap cadence
  (Laravel: `Schedule::command('backup:cloud:dispatch')->everyFiveMinutes()->withoutOverlapping()`)
  and decides from `isDue()` whether the configured interval elapsed. This keeps the interval
  user-configurable without redeploying/re-scheduling cron.
  - Laravel + Node + theme: dispatcher as above.
  - php + branding website: documented cron entry (`* * * * * php /path/scripts/backup-cloud.php --dispatch`)
    plus a `backup-cloud.php`/`public/cron/backup-cloud.php` CLI that is idempotent.
- Locking: `Cache::lock('eskoofy-cloud-backup', 600)` (Laravel) / a lock file (php, theme, website) /
  an in-flight flag row (Node) so a slow upload never overlaps the next tick.
- Retention: after a successful upload, prune the remote folder to the newest `keep` files
  (driver `list()` + `delete()`), and prune the local `backups/` dir to `keep` as well.
- Every run appends to the run log with `success` / `failed` / `skipped` (skipped = not due,
  disabled, or no credentials) and updates `last_run_at` / `last_status` / `last_error`.
- Failures never throw into the scheduler; they are logged + recorded.
- Docs: `docs/operations/BACKUP-RESTORE.md` gets a "Cloud & automatic backups" section with the
  cron lines per product; each product's `docs/USER-MANUAL.md` + `docs/SETUP-GUIDE.md` too.

### Acceptance criteria (Task 2)

1. With `auto_enabled = 1` and `interval_minutes = 15`, running the dispatcher four times in a row
   uploads exactly once (covered by a test with a frozen clock).
2. `interval_minutes = 0`/disabled ⇒ `skipped`, no upload, no error.
3. Retention keeps exactly `keep` remote files.
4. The dispatcher never runs twice concurrently (lock asserted in a test).

---

## Task 3 — Institution watermark on every exportable document (all 4 products)

Applies to **testimonial, certificate, marksheet (dashboard + public results PDF), admit card,
student ID card** — print views, preview modals, batch print sheets and generated PDFs.

### 3.1 Watermark configuration

Stored as data, never hardcoded (`config/eskoolfy.php` → `documents.watermark` defaults, overridable
per install by the active design row, see Task 4):

| key | type | default | notes |
|---|---|---|---|
| `enabled` | bool | `false` | master switch |
| `type` | `text` \| `image` \| `logo` | `text` | `logo` reuses the institution logo |
| `text` | string | school name | text watermark |
| `image_path` | string\|null | null | image watermark (upload) |
| `opacity` | float 0.05–1 | `0.18` | |
| `rotation` | int -180…180 | `45` | negative = counter-clockwise |
| `position` | `center` \| `diagonal` \| `tile` \| `top` \| `bottom` | `diagonal` | |
| `font_size` | int 8–120 | `48` | text only |
| `color` | hex | `#0f172a` | text only |
| `font_family` | string | inherit | text only |
| `documents` | map | all five `true` | per-document-type override |

Rules:

- **Never hardcode a variant branch** — the watermark is config + data (`build/profiles/*` may
  override the default text/opacity per variant).
- The same normalised array shape is produced by every product's resolver so the four
  implementations are auditable against each other.
- Rendering is pure CSS: an absolutely positioned, `pointer-events:none` layer inside the document
  root, `z-index:0` with the content at `z-index:1`, `transform: rotate()` for `diagonal`,
  repeated tiles for `tile`. Must work in a browser print dialog **and** in dompdf.
- Sanitised: `text` is escaped, `color` must match `^#[0-9a-fA-F]{3,8}$`, `image_path` must be a
  relative path under the uploads dir (no `..`, no protocol), numeric ranges are clamped.

### 3.2 Per-product deliverables

- A **shared partial** per Blade product:
  `resources/views/partials/dashboard/document-watermark.blade.php` (app) — copied **byte-identical**
  to `eskoofy-php-app/resources/views/partials/dashboard/document-watermark.blade.php`.
  It must only use Blade directives the raw-PHP compiler supports
  (`@if/@foreach/@php/@json/@class/…` — see `eskoofy-php-app/app/Core/Blade.php`).
- Included by all 5 print/PDF templates in both Blade products, plus
  `resources/views/site/results-pdf.blade.php` (public marksheet PDF).
- Theme: `esk_document_watermark_html( $type )` helper + `esc_document_watermark_css( $type )`
  emitted by the print templates in `views/admin/{certificates,id-cards,admit-cards}.php`
  and the results view.
- Node: `<DocumentWatermark design={…} />` component + `documentStyleCss()` helper used by the
  print screens in `components/dashboard/`.
- Laravel/PHP/Node: `DocumentDesignService::watermark(string $type): array` normalises
  config + design row; theme: `esk_document_watermark( $type ): array`.

### Acceptance criteria (Task 3)

1. With the watermark enabled, every one of the 5 document types renders the watermark layer
   (asserted in tests on the rendered HTML for app + php + node).
2. Disabling it removes the layer entirely (no empty container left behind).
3. A per-document-type override (`documents.certificate = false`) only affects certificates.
4. Clamping works: `opacity = 9` → `1.0`, `rotation = 900` → `180`, `color = "red; background:url(x)"` → dropped.
5. The Blade partial is byte-identical between `eskoofy-laravel-app` and `eskoofy-php-app`
   (verified with `cmp`/`diff`).

---

## Task 4 — Fully customizable designs for all 5 document types (all 4 products)

### 4.1 Data model

`document_designs` table (theme: `esk_document_designs`):

| column | notes |
|---|---|
| `id` | |
| `document_type` | `certificate` \| `testimonial` \| `marksheet` \| `admit_card` \| `id_card` |
| `name` | human label |
| `template` | `classic` \| `modern` \| `minimal` \| `bordered` (4 built-ins) |
| `is_default` | the active design for its type (single per type) |
| `settings` | json: theme + section toggles + header/footer + signatory |
| `watermark` | json: per-design watermark override (Task 3 keys) |
| `custom_css` | text: sanitised extra CSS appended last |
| `is_active` | soft enable |
| timestamps | |

`settings` keys (all optional, all clamped):

```
primary_color, secondary_color, text_color, muted_color, background_color,
font_family, base_font_size, title_font_size, border_style (none|solid|double|dashed|dotted),
border_width, border_radius, border_color, orientation (portrait|landscape),
page_size (a4|letter|legal), padding, logo (show|hide), logo_position (top-left|top-center),
header_text, footer_text, show_header, show_footer, show_signature, signature_label,
show_number, show_issue_date, show_notes, show_qr, accent_bar (none|top|bottom|left|right)
```

### 4.2 Built-in templates

Four templates × 5 document types = 20 combinations, all reachable from the editor, each
producing a visibly different layout (borders, accent bars, typography, spacing). The template
name changes the emitted CSS class + rules — no forked Blade file per template.

### 4.3 Editing surface

- Route group `dashboard.document-designs.*` (index/create/store/show/edit/update/destroy/activate/
  duplicate/preview) with permission `manage_document_designs` (new) — or reuse
  `manage_certificates` where the product has no permission system (php/theme/website).
- **Index** — one row per document type showing the active design + a "design settings" link.
- **Editor** — a single form page: template picker, colour pickers, font/size selects, border
  controls, orientation/page size, section toggles, header/footer/signatory text, the full
  watermark block (Task 3), an advanced "custom CSS" textarea, and a **live preview** panel that
  renders a sample of the selected document type with the chosen design (server-rendered, no JS
  framework needed).
- **Preview/activation** — activating a design immediately changes print output; the previous
  design becomes non-default (never deleted).
- `custom_css` is sanitised: strip `<`/`>`, `javascript:`/`expression(`/`@import`, `url(` with a
  protocol, and cap the length (16 KB).

### 4.4 Rendering pipeline (all products)

```
config/eskoolfy.php documents.watermark defaults
        ↓ merged with
active document_designs row (settings + watermark)      ← DocumentDesignService::for($type)
        ↓
normalised theme array + sanitised custom CSS            ← DocumentDesignService::css($type)
        ↓
injected into every print/PDF template                   ← <partials/dashboard/document-style.blade.php>
        ↓
CSS custom properties (--doc-primary, --doc-font-size, …) + template rules + custom CSS
```

- Every print template must consume the variables instead of hardcoded colours/sizes, so changing
  a colour in the editor really changes the output. The `bd` profile's **default** output must stay
  visually identical when no design row exists (config defaults mirror today's CSS values).
- Watermark layer is rendered by the shared partial (Task 3), styled by the same variable block.

### Acceptance criteria (Task 4)

1. For each of the 5 document types: creating + activating a design changes the rendered print
   output (asserted in tests: primary colour, font size, template class, watermark text).
2. Four built-in templates render four distinct class/rule sets for the same type.
3. `custom_css` is sanitised (`<script>`, `javascript:`, `@import`, `url(http…)` removed) and the
   length cap is enforced.
4. Deleting the active design falls back to the config default (no fatal, no watermark leftover).
5. Editor form validates and round-trips every `settings` key (test posts a full payload and
   asserts persistence).
6. php app's `resources/views/**` stays byte-identical to the app's.

---

## Task 5 — Cross-product consistency, docs, and feature tracking

1. **Parity gates green** (see Verification).
2. **New design docs**: `docs/design/CLOUD-BACKUP.md` (provider contract, credential setup per
   provider, security model, cron recipes) and `docs/design/DOCUMENT-DESIGNS.md` (data model,
   template list, settings/watermark reference, extension guide).
3. **Ops docs**: `docs/operations/BACKUP-RESTORE.md` gains cloud + automatic sections;
   `docs/guides/DEVELOPMENT.md` mentions the new commands/pages.
4. **Per-product docs**: each product's `docs/USER-MANUAL.md` (how to use) and
   `docs/SETUP-GUIDE.md` (env/credentials/cron) updated; `eskoofy-nodejs-app/docs/PORTING-STATUS.md`
   + `NOT-IMPLEMENTED.md` updated.
5. **AGENTS.md command tables**: add the new commands/pages to the root `AGENTS.md` and each
   product's `AGENTS.md` (the repo's agents read these first).
6. **Feature tracking (REQUIRED — do not skip)**:
   - `docs/feature-tracking/build-product-matrix.py` — add rows under `Documents` (watermark,
     design builder per document type) and `Cross-cutting` (cloud backup providers, automatic
     backup, retention/pruning, cloud restore) with per-product Implemented/Working status derived
     from the actual code written; regenerate `products-feature-matrix.xlsx`
     (`python3 docs/feature-tracking/build-product-matrix.py`).
   - `docs/feature-tracking/build-branding-matrix.py` — add the website's cloud-backup rows;
     regenerate `branding-website-feature-matrix.xlsx`.
   - `workplan-implementation-plan.md` — add a task block with `✅ done` + verification evidence.
   - `docs/features/FEATURE-IMPROVEMENTS-IMPLEMENTATION.md` — link the delivered items.
   - Marketing copy parity in `eskoofy-branding-website` (`/products/*`, `/features`).
7. `.po`/`.pot` for the theme: add the English source strings (`__()`/`esc_html_e()`); regenerate
   `languages/eskoofy.pot` if `wp i18n` tooling is available, otherwise document the follow-up.

---

## Verification (all must pass before "DONE")

| Gate | Command |
|---|---|
| App suite | `cd eskoofy-laravel-app && composer test` |
| App style | `cd eskoofy-laravel-app && ./vendor/bin/pint --test` |
| Raw-PHP suite | `cd eskoofy-php-app && composer test` |
| Raw-PHP view parity | `diff -r eskoofy-laravel-app/resources/views eskoofy-php-app/resources/views` (only pre-existing diffs) |
| Theme syntax | `php -l` on every touched `inc/` + `views/admin/` file |
| Theme lint | `cd eskoofy-wp-theme && composer run lint` (non-gating, match house style) |
| Node tests | `cd eskoofy-nodejs-app && npm test` |
| Node types | `cd eskoofy-nodejs-app && npm run typecheck` |
| Node lint | `cd eskoofy-nodejs-app && npm run lint` |
| Node parity | `cd eskoofy-nodejs-app && npm run route:parity` (regenerate `lib/routes.generated.ts` from the app's `route:list --json`) |
| Website suite | `cd eskoofy-branding-website && composer test` |
| Trackers | both XLSX load via openpyxl with the new rows |

## Out of scope

- No changes to `eskoofy-laravel-app/archive/**`.
- No new Composer/npm runtime dependencies (cURL + `ZipArchive` only).
- No live provider calls in CI or tests.
- No commits unless the user explicitly asks.

---

## Propagation

| Product | Apply? | Areas |
|---------|--------|-------|
| eskoofy-laravel-app | ✅ | config, migrations, models, services, commands, controllers, views, routes, scheduler, permissions, sidebar, tests |
| eskoofy-php-app | ✅ | services, controllers, views (byte-identical), routes/config, schema, cron script, tests |
| eskoofy-wp-theme | ✅ | inc modules, admin views, routes, sidebar, database tables, print templates, wp-cron |
| eskoofy-nodejs-app | ✅ | lib, prisma, dashboard pages/actions, nav, lang, tests |
| eskoofy-branding-website | ✅ | services, controller, admin view, routes, schema, marketing copy, tests |

Confirmation: [x] granted (user request: "all 4 products and the branding website")

---

## Status: COMPLETE (all 5 products)
