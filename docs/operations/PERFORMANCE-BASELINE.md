# Performance Baseline

Target: **p95 < 2s** on the public result lookup, admission apply, and the admin
dashboard home under expected load.

## Baseline procedure

Run against a production-like environment (Docker stack or a VPS), **not** `php artisan serve`
(single-threaded). Use a release build with caches:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### 1. Public result lookup (`GET /api/v1/academics/results/lookup`)

```bash
ab -n 500 -c 20 -H "Accept: application/json" \
   "https://your-host/api/v1/academics/results/lookup?exam_id=1&roll=123&session_id=1"
```

### 2. Admission apply (`POST /api/v1/admissions`)

```bash
ab -n 200 -c 10 -p admission-payload.json -T application/json \
   "https://your-host/api/v1/admissions"
```

### 3. Admin dashboard home (`GET /dashboard`)

Login first, then load-test with the session cookie:

```bash
ab -n 200 -c 10 -C "session=<cookie>" "https://your-host/dashboard"
```

## Success criteria

| flow | target |
|---|---|
| result lookup | p95 < 2s · 0 errors |
| admission apply | p95 < 2s · 0 errors |
| dashboard home | p95 < 2s · 0 errors |

## Recording results

Keep the output CSV in this file's folder (or `storage/performance/`):

```bash
ab -n 500 -c 20 ... | tee storage/performance/result-lookup-<date>.txt
```

Update the numbers in this doc for each release.

## Related levers

- Queries are preloaded (`with`, `withCount`) in the result/admission/dashboard code paths.
- Public endpoints are cached where applicable.
- If p95 degrades: enable query logging (`DB::listen`), add an index, or cache the hot path.