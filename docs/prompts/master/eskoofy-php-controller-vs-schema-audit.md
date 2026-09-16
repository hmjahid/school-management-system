# Eskoofy PHP — Controller vs Schema Audit & Repair

Master prompt file. Execute this file end-to-end until all verification passes.
Touch **only** `eskoofy-php/`. Read `eskoofy-php/README.md` first if present,
otherwise `WORKPLAN.md` Phase 6.

> Golden rule (AGENTS.md): never hardcode BD/INT branching inside products. Any
> profile change is config/data (`config/`, `build/profiles/profiles.php`).
> This task is purely about runtime correctness — no feature work.

---

## Context

`eskoofy-php` is the raw-PHP port of the Eskoofy school management system. Its
test suite (`composer test`) is green at **232 tests / 411 assertions** and
exercises the `Core`, `Gateways`, and the standalone `LicenseManager` /
`SmsManager` / `ActivityLog` services. The suite **does not exercise the front
controller** — it bypasses `public/index.php` and never routes a request
through `app/Controllers/`.

The dev server (`php -S 127.0.0.1:8031 -t public`) is therefore the first
place the gap between controllers and the real schema becomes visible.
Concretely:

- `app/Core/bootstrap.php` was using a nested `app/app/...` path layout that
  does not exist on disk, so the dev server could not even load
  `Helpers/helpers.php` at line 11. **That part is already fixed** in
  commit `6cf5cbf` — paths are now root-relative via
  `dirname(__DIR__, 2)`. Do not re-fix.
- Once the bootstrap loads, the request reaches
  `App\Controllers\HomeController::index()` and explodes on
  `PDOException: Unknown column 'status' in 'WHERE'` from a query against
  the `news` table. The `news` table actually has `is_published
  TINYINT(1)`, not `status`. The controllers were written against a
  Laravel-style column naming; the schema in
  `database/schema.sql` is the MySQL port of the Laravel migrations and
  uses different column names in several places.

This prompt is the audit + repair of every such mismatch so the dev server
actually serves real pages.

---

## Part A — Discovery (do not skip)

### A1. Read the schema into a machine-greppable inventory

Use a one-off PHP script (or a series of `docker exec ... mariadb -e
"DESCRIBE x"` calls) to capture every column of every table in
`eskoofy_php`. Save the output to `/tmp/eskoofy_php_schema.txt` so the
rest of the audit can grep against it. **Do not commit this file.** Format
example:

```
$table.news
  id BIGINT UNSIGNED
  is_published TINYINT(1)
  is_event TINYINT(1)
  ...
$table.events
  ...
```

### A2. Grep every column reference in the controllers

```bash
rg -n '\bSELECT\b' eskoofy-php/app/Controllers/ | wc -l
```

For every controller, enumerate each `SELECT ... FROM <table>` and the
`WHERE` / `ORDER BY` / `GROUP BY` columns it references. A reasonable
proxy: extract column names from each query with a regex
(`\b(\w+)\s*(?:=|!=|<|>|<=|>=|LIKE|IN|IS)\s*\?` and
`\b\w+\.\w+\b`) and diff against the A1 inventory.

Build a list like:

```
HomeController.php:18  news WHERE is_event = 0 AND status = 'published'   ← news has no `status`
SiteController.php:65  events WHERE status = 'published' AND start_date … ← ok, events.status exists
...
```

### A3. Classify each mismatch

For every mismatch, pick exactly one of:

| Class | Meaning | Action |
|---|---|---|
| **C1** | The query references a column the table has under a different name (e.g. `news.status` → `is_published`). | Fix the query. |
| **C2** | The query references a table that doesn't exist (e.g. `gallery_albums` vs `galleries`). | Fix the query. |
| **C3** | The query references a column the table genuinely lacks (e.g. `students.name` lives on `users`, not `students`). | Fix the query to JOIN `users`, or stop selecting that column. |
| **C4** | The query references a column that exists in the Laravel app's schema but is missing from the raw-PHP port (the port is incomplete). | **STOP** — this is a porting gap. Add a short note to `eskoofy-php/MIGRATION_GAPS.md` (new file, to be committed in Part D) and skip the controller action that depends on it. Document the route as "not navigable until gap is closed." |

### A4. Do not touch these on purpose

- `eskoofy-php/app/Core/bootstrap.php` — already fixed.
- `eskoofy-php/database/schema.sql` — the schema is the source of truth; do
  not "fix" it by adding columns to match bad queries. (Exception: the
  single `refunds.processed_by NOT NULL` vs `ON DELETE SET NULL` bug
  found earlier — patch the schema, see Part B.)
- `eskoofy-php/composer.json` / `phpunit.xml` / `tests/` — the test
  suite is the contract; if a test fails after the audit, the test was
  wrong, not the code.
- `eskoofy-php/config/` — feature flags, no DB.
- `eskoofy-php/app/Gateways/` and the `LicenseManager` / `SmsManager` /
  `ActivityLog` / `I18n` services — already covered by tests.

---

## Part B — Schema patch (one line, do this first)

In `eskoofy-php/database/schema.sql`, the `refunds` table declares
`processed_by BIGINT UNSIGNED NOT NULL` but the foreign key is `ON DELETE
SET NULL`. MySQL 8 / MariaDB 11 reject this with errno 150. Change the
column to `BIGINT UNSIGNED NULL`. Re-import the schema to confirm:

```bash
docker exec -i eskoofy-mariadb \
    mariadb -uesk -peskpw eskoofy_php \
    < eskoofy-php/database/schema.sql
```

(Adjust the container / credentials to whatever dev MariaDB is in use; the
audit should not assume Docker — the user may run a system mariadb
instead. Detect: `mysql -h 127.0.0.1 -P 3306 -uroot -e "SELECT 1"` and
read `DB_*` from `eskoofy-php/.env`.)

If the import reports 95+ tables and zero errors, the schema is healthy.

---

## Part C — Controller repairs (the bulk of the work)

For every entry on the Part A2 list, apply the action from A3.

### C1 / C2 — column / table renames

Direct, mechanical edits in `app/Controllers/`. Common patterns you will
hit repeatedly:

| Wrong | Right | Why |
|---|---|---|
| `WHERE status = 'published'` on `news` | `WHERE is_published = 1` | `news` has `is_published` |
| `FROM gallery_albums` | `FROM galleries` (or whichever exists) | Verify the schema, not your memory |
| `students.name` / `students.photo` | join `users ON s.user_id = u.id` and use `u.name`, `u.photo` | `students` has no name/photo columns |
| `WHERE s.status = 'active'` on `students` | `WHERE u.status = 'active'` (after join) | same reason |
| `WHERE is_event = 0` on `news` | keep — schema has it | sanity check, do not blindly change |

### C3 — JOIN or drop the column

If a controller SELECTs a column that lives on another table, JOIN
properly. If the column isn't really needed by the view, drop it from the
SELECT.

### C4 — porting gap (rare, document it)

If a controller depends on a column the schema is genuinely missing
(e.g. the schema has `students` but the controller wants a `students.cgpa`
field that was added in Laravel and never ported), add a row to
`eskoofy-php/MIGRATION_GAPS.md`:

```markdown
## `students.cgpa`
- Used in: `SiteController.php:511` (toppers query)
- Laravel source: `database/migrations/2024_03_01_000000_add_cgpa_to_students.php`
- Workaround: omit the column from the query for now
- Open: yes
```

Then remove the column from the controller's SELECT (or guard the action
with a 501). Do NOT add the column to `schema.sql` — the migration must
come from the Laravel side.

---

## Part D — Test coverage (so this gap can't reopen)

The whole reason the runtime broke without warning is that the controllers
are not exercised by the test suite. Add a thin integration layer that
exercises the controller routes through the actual front controller and a
fake database.

### D1. Controller smoke harness

Add `eskoofy-php/tests/Integration/FrontControllerTest.php` that:

1. Boots `App\Core\Router` and loads `routes/web.php` exactly the way
   `public/index.php` does (minus the HTTP layer).
2. Provides a fake database via a `DatabaseInterface` implementation
   `tests/Fakes/FakeDatabase.php` (mirroring
   `eskoofy-website/tests/FakeDatabase.php`, but covering the
   `eskoofy_php` schema — at minimum the `news`, `events`, `notices`,
   `customers`, `plans`, `licenses`, `posts` tables used by the
   controllers the audit touched).
3. For each migrated query from Part C, asserts the fake DB returns the
   expected rows (or empty) for the query.

This is a regression test for the column-name corrections: if anyone
re-introduces `WHERE news.status = 'published'`, the fake DB will report
the column as missing and the test fails.

### D2. Public smoke test (optional but valuable)

Add `eskoofy-php/tests/Integration/DevServerSmokeTest.php` that:
1. Spawns `php -S 127.0.0.1:<port> -t public` in a background process.
2. Hits `GET /` and `GET /about` (and 2-3 more representative routes).
3. Asserts the response is HTTP 200 with HTML containing a known
   string from the home / about views.
4. Tears the process down.

This is heavier (skipped in `--testsuite=unit`), but catches exactly the
class of bug the user is hitting.

---

## Part E — Definition of done

1. `cd eskoofy-php && composer test` — still 232+ passing, 0 new failures.
2. `php -l` clean on every modified/added PHP file.
3. `php -S 127.0.0.1:8031 -t public` — `GET /` returns 200 with the
   school home view (not 500, not a stack trace).
4. Smoke through 5-6 representative public routes — all 200:
   `/`, `/about`, `/contact`, `/login`, `/news`, `/events` (or whatever
   the routes file actually exposes — verify by reading
   `routes/web.php`).
5. `docker exec ... mariadb -e "SHOW WARNINGS"` after re-importing the
   schema is empty.
6. `git grep -nE 'WHERE (news|events)\.\w+ = .published.|WHERE (news|events|notices|galleries|gallery_albums)\.\w+ = .active.' eskoofy-php/app/Controllers/` returns
   zero results (the systematic class of bug is gone).
7. `MIGRATION_GAPS.md` (if any C4 rows were found) is committed.
8. A single commit per logical chunk — at minimum one commit per
   controller file, or one commit per ~5 mismatches if a single
   controller is heavily affected. Commit messages follow
   `fix(eskoofy-php): <controller>: <what>`.
9. Update `workplan-implementation-plan.md` Phase 6 to add a row "Audit
   & repair controller queries against schema" marked done, with the
   test baseline updated to whatever the new integration tests bring.

---

## Out of scope

- New features, new routes, new models, new tests for the LicenseManager
  / SmsManager / Gateways (those are already covered).
- eskoofy-website or any other product.
- Schema redesign (e.g. normalizing, renaming columns globally). The
  schema is the contract; queries adapt to it.
- Switching the raw-PHP product to SQLite or any other engine.
- Performance / N+1 fixes — fix correctness first, profile later.
- Fixing Laravel's `eskoofy-app` queries (separate product, separate
  schema, separate task).

If the audit reveals that the right fix is "this controller should be
deleted because its feature was dropped in the port", that is in scope
and should be discussed in the commit message, not silently applied.