/**
 * Fixed-window rate limiter — parity with the Laravel app's RateLimiter facade
 * and its `DashboardWriteThrottle` middleware (120 writes / 60s per user+ip),
 * and with the `RateLimiter` service in `eskoofy-php-app`.
 *
 * Deliberately dependency-free and runtime-agnostic (no node:fs, no Prisma) so
 * it can be imported from `middleware.ts`, which runs on the edge, as well as
 * from route handlers and server actions on the Node runtime.
 *
 * The API mirrors Laravel exactly — check with `tooManyWriteAttempts()`, then
 * record with `hitWriteLimit()` — so the two ports can be diffed line by line.
 * Note Laravel's `tooManyAttempts()` compares with `>`, so a limit of 120
 * admits 121 requests before blocking the 122nd; that off-by-one is preserved
 * here on purpose rather than silently "fixed".
 *
 * State is a module-level Map. That matches Laravel's default `array` cache
 * driver — per-process, not shared across instances — which is the right scope
 * for the job: this limiter absorbs write bursts, it does not enforce a global
 * quota.
 */

export const DASHBOARD_WRITE_LIMIT = 120;
export const DASHBOARD_WRITE_WINDOW_SECONDS = 60;

const WRITE_METHODS = new Set(["POST", "PUT", "PATCH", "DELETE"]);

interface Window {
  hits: number;
  startedAt: number;
}

const windows = new Map<string, Window>();

function nowSeconds(): number {
  return Math.floor(Date.now() / 1000);
}

/** Test seam — drop all buckets. */
export function resetRateLimiter(): void {
  windows.clear();
}

/** Test seam — inspect the live buckets. */
export function rateLimiterSnapshot(): Record<string, Window> {
  return Object.fromEntries(windows.entries());
}

/** Only POST/PUT/PATCH/DELETE count as writes; browsing must never be throttled. */
export function isWriteMethod(method: string | null | undefined): boolean {
  return WRITE_METHODS.has((method ?? "GET").toUpperCase());
}

/**
 * Bucket identity: user id (or guest) + client ip, matching the app's key.
 */
export function writeRateLimitKey(userId: number | string | null | undefined, ip: string | null | undefined): string {
  return `dashboard_write:${userId ?? "guest"}:${ip || "unknown"}`;
}

/** Read a bucket, treating an expired window as absent. */
function read(key: string, windowSeconds: number): Window | null {
  const state = windows.get(key);
  if (!state) return null;

  if (nowSeconds() - state.startedAt >= windowSeconds) {
    windows.delete(key);
    return null;
  }

  return state;
}

/**
 * Whether the bucket is already over its allowance. Check this BEFORE recording
 * an attempt, exactly like the app's middleware does.
 */
export function tooManyWriteAttempts(
  key: string,
  limit = DASHBOARD_WRITE_LIMIT,
  windowSeconds = DASHBOARD_WRITE_WINDOW_SECONDS,
): boolean {
  const state = read(key, windowSeconds);
  if (!state) return false;

  return state.hits > limit;
}

/**
 * Record an attempt and return the running count for this window.
 */
export function hitWriteLimit(key: string, windowSeconds = DASHBOARD_WRITE_WINDOW_SECONDS): number {
  const now = nowSeconds();
  const state = read(key, windowSeconds) ?? { hits: 0, startedAt: now };

  state.hits += 1;
  windows.set(key, state);

  return state.hits;
}

/** Attempts left without recording a new attempt. */
export function remainingWriteLimit(
  key: string,
  limit = DASHBOARD_WRITE_LIMIT,
  windowSeconds = DASHBOARD_WRITE_WINDOW_SECONDS,
): number {
  const state = read(key, windowSeconds);
  if (!state) return limit;

  return Math.max(0, limit - state.hits);
}

/** Seconds until the current window expires (0 when no window is open). */
export function writeLimitRetryAfter(key: string, windowSeconds = DASHBOARD_WRITE_WINDOW_SECONDS): number {
  const state = read(key, windowSeconds);
  if (!state) return 0;

  return Math.max(0, state.startedAt + windowSeconds - nowSeconds());
}

/**
 * Drop a bucket — called after a successful authentication.
 */
export function clearWriteLimit(key: string): void {
  windows.delete(key);
}