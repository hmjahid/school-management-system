import { describe, it, expect } from "vitest";
import { line, shouldLog } from "@/lib/log";

describe("log-format: exact Laravel single-file Monolog line", () => {
  it("emits [ts] production.DEBUG: msg like Laravel line formatter", () => {
    const out = line("DEBUG", "getAllStudents()");
    expect(out).toMatch(/^\[\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}\+00:00\] production.DEBUG: getAllStudents\(\)$/);
  });
  it("LOG_LEVEL env threshold mirrors Laravel LOG_LEVEL", () => {
    process.env.LOG_LEVEL = "warning";
    expect(shouldLog("ERROR")).toBe(true);
    expect(shouldLog("DEBUG")).toBe(false);
    delete process.env.LOG_LEVEL;
  });
});
