/**
 * @fileoverview Unit tests for the Node rate limiter (fixed-window). Mirrors the
 * php-app's `RateLimiterTest`, including Laravel's deliberate `>` comparison in
 * `tooManyAttempts` — a limit of 120 therefore admits 121 requests and blocks
 * the 122nd.
 */

import { afterEach, describe, expect, it, vi } from "vitest";
import {
  clearWriteLimit,
  DASHBOARD_WRITE_LIMIT,
  DASHBOARD_WRITE_WINDOW_SECONDS,
  hitWriteLimit,
  isWriteMethod,
  rateLimiterSnapshot,
  remainingWriteLimit,
  resetRateLimiter,
  tooManyWriteAttempts,
  writeLimitRetryAfter,
  writeRateLimitKey,
} from "@/lib/rate-limit";

describe("rate-limit", () => {
  afterEach(() => {
    resetRateLimiter();
    vi.useRealTimers();
  });

  it("isWriteMethod flags only mutating verbs", () => {
    for (const method of ["POST", "PUT", "PATCH", "DELETE", "post", "put", "patch", "delete"]) {
      expect(isWriteMethod(method)).toBe(true);
    }
    for (const method of ["GET", "HEAD", "OPTIONS", "get", "head", "options", null, undefined]) {
      expect(isWriteMethod(method)).toBe(false);
    }
  });

  it("writeRateLimitKey includes user and ip, falling back to guest/unknown", () => {
    expect(writeRateLimitKey(42, "1.2.3.4")).toBe("dashboard_write:42:1.2.3.4");
    expect(writeRateLimitKey("7", "1.2.3.4")).toBe("dashboard_write:7:1.2.3.4");
    expect(writeRateLimitKey(null, "1.2.3.4")).toBe("dashboard_write:guest:1.2.3.4");
    expect(writeRateLimitKey(undefined, "1.2.3.4")).toBe("dashboard_write:guest:1.2.3.4");
    expect(writeRateLimitKey(1, "")).toBe("dashboard_write:1:unknown");
    expect(writeRateLimitKey(null, null)).toBe("dashboard_write:guest:unknown");
  });

  it("an unseen bucket is never blocked", () => {
    expect(tooManyWriteAttempts(writeRateLimitKey(1, "10.0.0.1"))).toBe(false);
    expect(remainingWriteLimit(writeRateLimitKey(1, "10.0.0.1"))).toBe(DASHBOARD_WRITE_LIMIT);
    expect(writeLimitRetryAfter(writeRateLimitKey(1, "10.0.0.1"))).toBe(0);
  });

  it("matches the app: check-then-record blocks the 122nd write at limit 120", () => {
    const key = writeRateLimitKey(1, "10.0.0.1");

    // Laravel compares with `>`, so hits 0..120 all pass.
    for (let hits = 0; hits <= DASHBOARD_WRITE_LIMIT; hits++) {
      expect(tooManyWriteAttempts(key)).toBe(false);
      expect(hitWriteLimit(key)).toBe(hits + 1);
    }

    expect(hitWriteLimit(key)).toBe(DASHBOARD_WRITE_LIMIT + 2);
    expect(tooManyWriteAttempts(key)).toBe(true);
  });

  it("remainingWriteLimit does not consume attempts", () => {
    const key = writeRateLimitKey(1, "10.0.0.1");
    hitWriteLimit(key);

    expect(remainingWriteLimit(key, 5, 60)).toBe(4);
    expect(remainingWriteLimit(key, 5, 60)).toBe(4);
    expect(remainingWriteLimit(key, 5, 60)).toBe(4);
  });

  it("window expiry restores the full allowance", () => {
    vi.useFakeTimers();
    const key = writeRateLimitKey(1, "10.0.0.2");

    for (let i = 0; i < DASHBOARD_WRITE_LIMIT + 2; i++) hitWriteLimit(key);
    expect(tooManyWriteAttempts(key)).toBe(true);

    vi.advanceTimersByTime((DASHBOARD_WRITE_WINDOW_SECONDS + 1) * 1000);

    expect(tooManyWriteAttempts(key)).toBe(false);
    expect(remainingWriteLimit(key)).toBe(DASHBOARD_WRITE_LIMIT);
  });

  it("a blocked window keeps counting down rather than resetting", () => {
    vi.useFakeTimers();
    const key = writeRateLimitKey(1, "10.0.0.4");
    for (let i = 0; i < 200; i++) hitWriteLimit(key);

    expect(tooManyWriteAttempts(key)).toBe(true);
    expect(writeLimitRetryAfter(key)).toBe(DASHBOARD_WRITE_WINDOW_SECONDS);

    vi.advanceTimersByTime(30 * 1000);
    expect(tooManyWriteAttempts(key)).toBe(true);
    expect(writeLimitRetryAfter(key)).toBe(30);

    vi.advanceTimersByTime(31 * 1000);
    expect(tooManyWriteAttempts(key)).toBe(false);
  });

  it("clearWriteLimit resets a blocked bucket", () => {
    const key = writeRateLimitKey(1, "10.0.0.3");
    for (let i = 0; i < DASHBOARD_WRITE_LIMIT + 2; i++) hitWriteLimit(key);
    expect(tooManyWriteAttempts(key)).toBe(true);

    clearWriteLimit(key);

    expect(tooManyWriteAttempts(key)).toBe(false);
    expect(rateLimiterSnapshot()).toEqual({});
  });

  it("buckets are isolated per user and per ip", () => {
    const a = writeRateLimitKey(1, "1.1.1.1");
    const b = writeRateLimitKey(2, "1.1.1.1");
    const c = writeRateLimitKey(1, "9.9.9.9");

    for (let i = 0; i < 200; i++) hitWriteLimit(a);

    expect(tooManyWriteAttempts(a)).toBe(true);
    expect(tooManyWriteAttempts(b)).toBe(false);
    expect(tooManyWriteAttempts(c)).toBe(false);
  });

  it("an expired bucket is evicted rather than leaking memory", () => {
    vi.useFakeTimers();
    const key = writeRateLimitKey(1, "10.0.0.6");
    hitWriteLimit(key);
    expect(Object.keys(rateLimiterSnapshot())).toHaveLength(1);

    vi.advanceTimersByTime((DASHBOARD_WRITE_WINDOW_SECONDS + 1) * 1000);
    tooManyWriteAttempts(key);

    expect(Object.keys(rateLimiterSnapshot())).toHaveLength(0);
  });
});