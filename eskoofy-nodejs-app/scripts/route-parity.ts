#!/usr/bin/env tsx
/**
 * App ↔ Node parity gate.
 *
 *  1. Route surface: re-runs `php artisan route:list --json` in the Laravel
 *     reference app and diffs it against `lib/routes.generated.ts`. Fails if the
 *     generated registry is stale — i.e. the app gained/lost/renamed a route.
 *  2. Sidebar: compares the app sidebar's `dashboard.*` keys against `lib/nav.ts`.
 *  3. Prints implementation coverage (models, resources, route split).
 *
 * Usage: npm run route:parity
 */
import { execFileSync } from "node:child_process";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { APP_ROUTES } from "../lib/routes.generated";
import { navLabelKeys } from "../lib/nav";
import { MODELS } from "../lib/schema";
import { resolveModel } from "../lib/resources";

const APP_DIR = resolve(process.cwd(), "../eskoofy-laravel-app");
const SIDEBAR = resolve(APP_DIR, "resources/views/partials/dashboard/sidebar.blade.php");

interface LiveRoute {
  method: string;
  uri: string;
  name: string | null;
  action: string;
  middleware: string[];
}

function liveRoutes(): LiveRoute[] {
  const json = execFileSync("php", ["artisan", "route:list", "--json"], {
    cwd: APP_DIR,
    encoding: "utf8",
    maxBuffer: 32 * 1024 * 1024,
  });
  const parsed = JSON.parse(json) as Array<Record<string, unknown>>;
  return parsed.map((row) => ({
    method: String(row.method),
    uri: `/${String(row.uri ?? "").replace(/^\//, "")}`,
    name: (row.name as string) ?? null,
    action: String(row.action ?? ""),
    middleware: (row.middleware as string[]) ?? [],
  }));
}

function appDashboardKeys(): string[] {
  const source = readFileSync(SIDEBAR, "utf8");
  const keys = new Set<string>();
  for (const match of source.matchAll(/__\('dashboard\.([a-z_]+)'\)/g)) keys.add(`dashboard.${match[1]}`);
  return [...keys].sort();
}

const key = (route: { method: string; uri: string }) => `${route.method} ${route.uri}`;

let failed = false;

// ── 1. Route surface ─────────────────────────────────────────────────────────
const live = liveRoutes();
const liveKeys = new Set(live.map(key));
const generatedKeys = new Set(APP_ROUTES.map(key));

const addedInApp = [...liveKeys].filter((k) => !generatedKeys.has(k)).sort();
const removedFromApp = [...generatedKeys].filter((k) => !liveKeys.has(k)).sort();

console.log(`App routes (live)     : ${live.length}`);
console.log(`Node registry         : ${APP_ROUTES.length}`);
console.log("");

if (addedInApp.length || removedFromApp.length) {
  failed = true;
  console.error("ROUTE PARITY FAILURE — lib/routes.generated.ts is stale.");
  if (addedInApp.length) {
    console.error(`\n  In the app but missing from the registry (${addedInApp.length}):`);
    for (const k of addedInApp.slice(0, 25)) console.error(`    + ${k}`);
  }
  if (removedFromApp.length) {
    console.error(`\n  In the registry but no longer in the app (${removedFromApp.length}):`);
    for (const k of removedFromApp.slice(0, 25)) console.error(`    - ${k}`);
  }
  console.error("\n  Regenerate the registry from the app's route:list output.");
  console.error("");
} else {
  console.log("Route surface OK — the registry matches the app's route:list.");
  console.log("");
}

// ── 2. Sidebar keys ──────────────────────────────────────────────────────────
const sidebarKeys = appDashboardKeys();
const nodeKeys = new Set(navLabelKeys());
const missingSidebar = sidebarKeys.filter((k) => !nodeKeys.has(k));

if (missingSidebar.length) {
  failed = true;
  console.error("SIDEBAR PARITY FAILURE — the app renders keys lib/nav.ts does not list:");
  for (const k of missingSidebar) console.error(`  - ${k}`);
  console.error("");
} else {
  console.log(`Sidebar OK — all ${sidebarKeys.length} app sidebar keys are represented in lib/nav.ts.`);
  console.log("");
}

// ── 3. Coverage ──────────────────────────────────────────────────────────────
const dashboard = APP_ROUTES.filter((r) => r.uri === "/dashboard" || r.uri.startsWith("/dashboard/"));
const api = APP_ROUTES.filter((r) => r.uri.startsWith("/api/"));
const site = APP_ROUTES.filter((r) => !dashboard.includes(r) && !api.includes(r));

const resources = new Set<string>();
for (const route of dashboard) {
  const segments = route.uri.replace(/^\/dashboard\/?/, "").split("/").filter(Boolean);
  const model = resolveModel(segments);
  if (model) resources.add(model.table);
}

console.log("Coverage:");
console.log(`  tables in Prisma schema : ${MODELS.length}`);
console.log(`  dashboard routes        : ${dashboard.length}`);
console.log(`  api routes              : ${api.length}`);
console.log(`  site routes             : ${site.length}`);
console.log(`  dashboard resources     : ${resources.size} resolved to a table`);
console.log("");

if (failed) process.exit(1);
console.log("PARITY OK.");
