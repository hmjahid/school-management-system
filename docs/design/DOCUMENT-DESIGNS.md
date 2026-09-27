# Configurable document designs & watermarks

**Status:** Implemented across all four products (Laravel app, raw-PHP port, WordPress theme,
Node.js variant) + marketed on the branding website.

Every exportable document type — certificate, testimonial, marksheet, admit card, student
ID card — can carry a branded design (colours, fonts, borders, layout template) and an
optional watermark (school name, logo or an uploaded image). The design is stored as a
per-type theme block plus a watermark block, both normalised by the shared service so what
the admin saves is exactly what the printers read back.

## The contract (one source of truth)

| Product | Table | Normaliser | Print CSS |
|---|---|---|---|
| Laravel app | `document_designs` (migration `2026_09_27_000003`) | `App\Services\DocumentDesignService` | `partials/dashboard/document-style.blade.php` + `document-watermark.blade.php` (byte-identical in raw-PHP) |
| Raw-PHP port | `document_designs` (`database/schema.sql`) | `App\Services\DocumentDesignService` (same namespace) | same Blade partials |
| WP theme | `esk_document_designs` | `inc/document-designs.php` (`esk_document_*`) | `esk_document_style()` / `esk_document_watermark_html()` |
| Node variant | `document_designs` (Prisma) | `lib/document-designs.ts` | `documentStyle()` / `watermarkMarkup()` |

## Layering (lowest priority first)

1. `config/eskoolfy.php` → `documents.defaults` / `documents.watermark` (the shipped
   `bd`-profile look; `DOCUMENT_WATERMARK_*` env vars).
2. The install's active `document_designs` row for that type (`settings` + `watermark` +
   sanitised `custom_css`), where `is_default = 1 AND is_active = 1`.

Identical input config ⇒ identical normalised output, which is what keeps the four
implementations auditable against each other.

## Document types & templates

- Types: `certificate`, `testimonial`, `marksheet`, `admit_card`, `id_card`.
- Templates: `classic`, `modern`, `minimal`, `bordered`.

## Watermark semantics

- Two independent switches, not one chain: `enabled` (global, or the design row's explicit
  override) is the master switch, and per-type `documents.*` then selects which types it
  applies to. A per-type entry can never re-enable a watermark the install switched off
  globally.
- Types: `text` (falls back to the school name), `image` (uploaded path), `logo` (falls
  back to the site's own `logo_path`).
- Clamped: opacity `0.05–1.0`, rotation `-180..180`, font size `8..120`, colour must be a
  hex value.

## Dompdf-safe CSS (why the values are literal)

The certificate, testimonial, marksheet, admit-card and ID-card **PDFs are rendered with
dompdf**, which supports none of `var()`, `calc()`, `color-mix()` or `:not()`. So the CSS
block every printer injects uses **literal values** for colours, sizes and tints, while a
`:root` custom-property block is still emitted for the browser preview. `custom_css` is
sanitised (no tags, no `@import`, no remote `url()`, no unbalanced braces) and injected last
so it can override anything.

## Admin surfaces

- **Laravel app**: `dashboard/document-designs` — per-type cards, one default per type,
  live preview endpoint (GET+POST `/preview`) that renders unsaved form values.
- **Raw-PHP port**: `/dashboard/document-designs` (byte-identical Blade).
- **WP theme**: `views/admin/document-designs.php` at `/dashboard/document-designs`.
- **Node variant**: `/dashboard/document-designs` (Next.js page + server actions).

## Integrity rules

- One default per type: marking a design default demotes the others **inside the same
  transaction** as the save, so a failure cannot leave a type with no default.
- The live preview folds the top-level `template` field into the theme (it lives in its own
  column, not inside `settings`).
- `?document_type=` on the per-type "Add" link opens the form on the right type.

## Permissions

`manage_document_designs` for every action (app seeder `RolePermissionSeeder`, raw-PHP
`config/access.php` + `seed_demo.php`, WP `esk_can`, Node `lib/permissions.ts`).

## References

- Implementation prompt: `docs/prompts/cloud-backup-watermark-document-designs-prompt.md`
- Printed-document templates: `docs/operations/BACKUP-RESTORE.md` (prints share the backup
  storage surface)