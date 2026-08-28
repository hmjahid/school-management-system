# Operations Runbooks

## 1. Deployment

Backend: Laravel 12 (Blade) · Frontend: Vite · Scheduler: `schedule:run`

```bash
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci && npm run build

php artisan down --retry=20
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Then reload workers:

```bash
php artisan queue:restart
```

Verify: `/up` health endpoint returns `ok`, `php artisan about` reports production,
`php artisan schedule:list` shows the backup + queue monitor entries.

### Pre-deploy checklist
Run `docs/PRODUCTION-CHECKLIST.md` items 1–6, 14 (proxies), 18–19 (secrets).

## 2. Rollback

Keep the previous release as a git tag or branch and its asset build.

```bash
php artisan down
git checkout <previous-release-tag>
composer install --no-dev --prefer-dist
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
# If the rollback includes code that touches schema, run rollback migrations ONLY if documented:
php artisan migrate:rollback --step=1   # verify with migrate:status first
php artisan queue:restart
php artisan up
```

Rules:
- Never delete data in rollback unless a restore drill (docs/BACKUP-RESTORE.md) proves the backup.
- `migrate:rollback` should only be used when the forward migration removed/renamed data; otherwise forward-fix instead.

## 3. Gateway credential rotation (bKash / Nagad / Rocket / offline)

Runtime credentials live in the `payment_gateways` table (`App\Models\PaymentGateway`) or `.env`
fallbacks in `config/payment.php`.

1. Open admin → Payments → Gateways and edit the gateway.
2. Enter the **new** API key / secret, keep sandbox/live mode consistent with your PSP account.
3. Save, then sanity-check with a small sandbox payment + refund round-trip.
4. Update `.env` fallbacks only if the runtime table is not used for that gateway.
5. Update the shared secrets vault (never commit to git).
6. Record rotation in the audit log (`spatie/laravel-activitylog`) — it records automatically.

If a live test fails after rotation:
- Confirm the gateway contract (base URL, token grant, request signature).
- Check `logs/laravel-*.log` for outgoing request errors (gateway credentials are never logged).
- Revert the table to the previous values and re-issue new credentials.

## 4. Incident response

### Severity table
| Sev | Definition | Response time |
|-----|------------|---------------|
| P1  | Payments/refunds down, data loss, security breach | Immediate |
| P2  | Core workflow broken (admission, results, fees) | < 1 business day |
| P3  | Cosmetic / single page issue | Next sprint |

### P1 playbook
1. `php artisan down --retry=30` — stop the bleeding; users see a maintenance page.
2. Pull diagnostics: `php artisan about`, `php artisan queue:failed` count, `storage/logs/laravel-*.log`,
   web server error logs, newrelic/sentry/slack alerts.
3. If it is a security incident: revoke the affected API keys immediately, then rotate
   (runbook §3). Preserve logs before cleanup.
4. Restore from the latest verified backup if data corruption is suspected — ONLY after a
   restore drill is green (docs/BACKUP-RESTORE.md).
5. Apply hotfix, deploy per runbook §1, then `php artisan up`.
6. Postmortem: root cause, timeline, blast radius, prevention.

### P2 playbook
1. Reproduce in staging with the same commit.
2. Fix forward (do not roll back unless the runbook §2 conditions hold).
3. Deploy without downtime if possible (`php artisan route:cache`/`view:cache` are atomic).
4. Add a regression test, run `composer test`.

## 5. Scheduler & queue health

- Cron entry (recommended): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
- `queue:monitor-failed` runs every 5 min (routes/console.php) and logs failures.
- If `failed_jobs` grows: `php artisan queue:retry all` after fixing the root cause.
- `backup:database` daily 02:00 — validate the file size grows.

## 6. Public DNS / TLS / proxies

- `TRUSTED_PROXIES` must list the load balancer CIDRs (never `*` in production).
- All traffic should 301 to HTTPS; HSTS header applies via `SecurityHeaders`.
- Certificate rotation: apply new cert on the LB, `curl -sI https://host/` shows new expiry.

## 7. Version & contacts

| Area | Owner | Escalation |
|------|-------|-----------|
| Payments & refunds | Finance/Backend on-call | CTO |
| Admissions | Backend on-call | CTO |
| Public site/CMS | Web team | CTO |
| Infrastructure (LBs, DB) | DevOps | CTO |