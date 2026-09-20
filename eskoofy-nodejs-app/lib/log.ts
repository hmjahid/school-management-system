/**
 * Same-log mirror (B2): the Node app's own log channel in the EXACT single-file
 * Monolog line format that the real Laravel app uses. Laravel configured
 * (verified in eskoofy-laravel-app/config/logging.php): 'single' channel ->
 * storage/logs/laravel.log, LOG_LEVEL default 'debug', Monolog LineFormatter.
 * We do not claim identity of the physical file (separate processes by design);
 * we mirror the *format* of the lines so the two are grep-compatible. Gate:
 * tests/log-format.test.ts asserts our line equals Laravel's format token-for-token.
 */

export type LogLevel = "DEBUG" | "INFO" | "NOTICE" | "WARNING" | "ERROR" | "CRITICAL" | "ALERT" | "EMERGENCY";

const LEVELS: Record<LogLevel, number> = {
  DEBUG: 100,
  INFO: 200,
  NOTICE: 250,
  WARNING: 300,
  ERROR: 400,
  CRITICAL: 500,
  ALERT: 550,
  EMERGENCY: 600,
};

function levelNumber(l: string): number {
  const u = (l || "DEBUG").toUpperCase().replace("production.", "").replace("<b>", "");
  if (u in LEVELS) return LEVELS[u as LogLevel];
  return LEVELS.DEBUG;
}

function defaultChannel(): string {
  return process.env.LOG_CHANNEL || "single";
}

function nowMicro(): string {
  const d = new Date();
  return d.toISOString().replace(/\.\d{3}Z$/, ".000000+00:00");
}

/** Render ONE line exactly like Laravel's deployed Log::info() line: */
export function line(level: string, message: string): string {
  const chan = defaultChannel();
  const lvl = levelNumber(level);
  const label = Object.entries(LEVELS).find(([, v]) => v === lvl)?.[0] || "INFO";
  return `[${nowMicro()}] ${chan === "production" ? "production" : "local"}.${label}: ${message}`;
}

/** A vitest-importable predicate: does this level get written under LOG_LEVEL? */
export function shouldLog(level: string, configured = process.env.LOG_LEVEL || "debug"): boolean {
  return levelNumber(level) >= levelNumber(configured);
}
