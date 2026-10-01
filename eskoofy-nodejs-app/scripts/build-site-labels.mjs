#!/usr/bin/env node
/**
 * Turn the JSON dumped by `scripts/dump-site-labels.php` into `lib/site-labels.ts`.
 *
 * Usage:
 *   node scripts/build-site-labels.mjs /tmp/labels.json > lib/site-labels.ts
 *
 * Generated file — do not hand-edit. Keys are emitted unquoted when they are
 * valid identifiers so the output matches the repo's TS style; everything else
 * is JSON-escaped.
 */

import { readFileSync } from "node:fs";

const IDENT = /^[A-Za-z_$][A-Za-z0-9_$]*$/;
const LOCALES = ["en", "bn"];

const input = process.argv[2];

if (!input) {
  console.error("usage: node scripts/build-site-labels.mjs <labels.json>");
  process.exit(1);
}

const data = JSON.parse(readFileSync(input, "utf8"));

const key = (k) => (IDENT.test(k) ? k : JSON.stringify(k));

/** @param value unknown @param indent number */
function ts(value, indent) {
  const pad = "  ".repeat(indent);

  if (typeof value === "string") return JSON.stringify(value);
  if (typeof value === "boolean") return value ? "true" : "false";
  if (typeof value === "number") return String(value);
  if (value === null) return "null";

  if (Array.isArray(value)) {
    if (value.length === 0) return "[]";
    return `[\n${value.map((v) => `${pad}  ${ts(v, indent + 1)}`).join(",\n")},\n${pad}]`;
  }

  if (typeof value === "object") {
    const entries = Object.entries(value);
    if (entries.length === 0) return "{}";
    return `{\n${entries.map(([k, v]) => `${pad}  ${key(k)}: ${ts(v, indent + 1)}`).join(",\n")},\n${pad}}`;
  }

  throw new TypeError(`Unsupported value: ${typeof value}`);
}

for (const locale of LOCALES) {
  if (!(locale in data)) {
    console.error(`missing locale "${locale}" in ${input}`);
    process.exit(1);
  }
}

const banner = `/**
 * Canonical public-site UI label tree — GENERATED, do not hand-edit.
 *
 * Source of truth: \`eskoofy-laravel-app/lang/{en,bn}/site_frontend.php\`, which the
 * app loads in \`App\\Support\\SiteFrontend::defaultsForLocale()\`. This file is the
 * byte-for-byte JSON dump of both locales, so the Node dashboard's Global Labels
 * editor offers exactly the same sections/leaves as the app and the defaults
 * cannot drift.
 *
 * Regenerate with:
 *   php scripts/dump-site-labels.php > /tmp/labels.json
 *   node scripts/build-site-labels.mjs /tmp/labels.json > lib/site-labels.ts
 *
 * Overrides live in \`website_contents\` where \`page = 'site-ui'\`
 * (\`content_en\` / \`content_bn\`, JSON) and are deep-merged on top of this tree by
 * \`lib/site-ui.ts\` — the mirror of \`SiteFrontend::siteUi()\`.
 *
 * Shape rules (must match \`DashboardSettingController::flattenLabels()\`):
 *  - a string leaf is a single editable label,
 *  - an array is a "simple list" (newline-separated textarea) unless it holds
 *    objects/associative keys,
 *  - anything else is recursed into.
 */

export type SiteLabelTree = Record<string, unknown>;

export const SITE_UI_DEFAULTS: Record<"en" | "bn", SiteLabelTree> = {
`;

const body = LOCALES.map((locale) => `  ${locale}: ${ts(data[locale], 1)},\n`).join("");

process.stdout.write(
  `${banner}${body}};\n\nexport const SITE_UI_LOCALES = ["en", "bn"] as const;\n\nexport type SiteUiLocale = (typeof SITE_UI_LOCALES)[number];\n`,
);