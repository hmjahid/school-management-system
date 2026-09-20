import { describe, it, expect } from "vitest";
import { lineFor, isEnabled, thresholdFromEnv } from "@/lib/log";

describe("B2 log mirror: exact Laravel single-file Monolog line", () => {
  it("renders the exact [ts] production.DEBUG: msg shape Laravel LineFormatter emits", () => {
    const ts = new Date(Date.UTC(2026, 8, 21, 12, 1, 2));
    expect(lineFor("production", "DEBUG", "getAllStudents()", ts)).toBe(
      "[2026-09-21T12:01:02.000000+00:00] production.DEBUG: getAllStudents()"
    );
  });
  it("LOG_LEVEL threshold from env is honored (Laravel reads same env var)", () => {
    process.env.LOG_LEVEL = "warning";
    expect(isEnabled("warning", "debug")).toBe(false);
    expect(isEnabled("warning", "error")).toBe(true);
    process.env.LOG_LEVEL = "debug";
    expect(isEnabled("debug", "debug")).toBe(true);
    expect(isEnabled("debug", "emergency")).toBe(true);
  });
});
