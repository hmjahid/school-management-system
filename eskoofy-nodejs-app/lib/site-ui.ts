/**
 * Public-site UI label resolution — the Node mirror of the app's
 * `App\Support\SiteFrontend` plus the parts of `WebsiteContent` that decide
 * which CMS overrides win.
 *
 * Three responsibilities:
 *  1. `siteUiDefaults(locale)` — the generated label tree for a locale.
 *  2. `getSiteUi(locale)` — defaults deep-merged with the `website_contents`
 *     row where `page = 'site-ui'` (JSON in `content_en` / `content_bn`).
 *     This is what the public site renders; without it the Global Labels editor
 *     would save rows nothing ever reads.
 *  3. `flattenSiteLabels()` / `normalizeListValues()` — the editor's flattening
 *     and list-normalisation rules, ported from
 *     `DashboardSettingController` so both products offer the same fields.
 */
import { prisma } from "@/lib/prisma";
import { SITE_UI_DEFAULTS, type SiteLabelTree, type SiteUiLocale } from "@/lib/site-labels";

export type { SiteLabelTree, SiteUiLocale };

export const SITE_UI_PAGE = "site-ui";

/** Deep merge, equivalent to PHP's `array_replace_recursive`. */
export function deepMerge<T extends Record<string, unknown>>(base: T, override: Record<string, unknown>): T {
  const out: Record<string, unknown> = { ...base };

  for (const [key, value] of Object.entries(override)) {
    const existing = out[key];
    if (isPlainObject(existing) && isPlainObject(value)) {
      out[key] = deepMerge(existing, value);
    } else {
      out[key] = value;
    }
  }

  return out as T;
}

export function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

/** Dot-path lookup, equivalent to Laravel's `Arr::get`. */
export function getPath(source: Record<string, unknown>, path: string): unknown {
  let cursor: unknown = source;

  for (const segment of path.split(".")) {
    if (!isPlainObject(cursor)) return undefined;
    cursor = cursor[segment];
  }

  return cursor;
}

export function setPath(target: Record<string, unknown>, path: string, value: unknown): void {
  const segments = path.split(".");
  let cursor = target;

  for (let i = 0; i < segments.length - 1; i++) {
    const segment = segments[i]!;
    if (!isPlainObject(cursor[segment])) cursor[segment] = {};
    cursor = cursor[segment] as Record<string, unknown>;
  }

  cursor[segments[segments.length - 1]!] = value;
}

/**
 * A "simple list" is an array the editor renders as a newline-separated
 * textarea. Port of `DashboardSettingController::isSimpleList()`: every key must
 * be an integer (a PHP sequential array), and any nested array must be empty.
 *
 * Non-sequential keys or non-empty nested arrays mean the value is structured
 * content, so it is walked as a tree instead of edited as free text.
 */
export function isSimpleList(value: unknown): value is unknown[] {
  if (!Array.isArray(value)) return false;
  if (value.length === 0) return true;

  for (const key of Object.keys(value)) {
    if (!/^\d+$/.test(key)) return false;
  }

  return value.every((entry) => !Array.isArray(entry) || entry.length === 0);
}

export interface FlatLabel {
  section: string;
  label: string;
  path: string;
  enDefault: string;
  enOverride: string;
  isList: boolean;
  listDefaults?: string[];
}

/**
 * Flatten the label tree into editable rows, grouped by top-level section.
 * Port of `DashboardSettingController::flattenLabels()`.
 */
export function flattenSiteLabels(defaults: SiteLabelTree, overrides: SiteLabelTree): FlatLabel[] {
  const rows: FlatLabel[] = [];

  const walk = (node: Record<string, unknown>, prefix: string): void => {
    for (const [key, value] of Object.entries(node)) {
      const path = prefix ? `${prefix}.${key}` : key;
      const parts = path.split(".");
      const section = parts[0]!;
      const label = parts.slice(1).join(".") || key;
      const override = getPath(overrides, path);

      if (typeof value === "string") {
        rows.push({
          section,
          label,
          path,
          enDefault: value,
          enOverride: typeof override === "string" ? override : "",
          isList: false,
        });
        continue;
      }

      if (isSimpleList(value)) {
        rows.push({
          section,
          label,
          path,
          enDefault: "",
          enOverride: Array.isArray(override)
            ? override.filter((v): v is string => typeof v === "string").join("\n")
            : "",
          isList: true,
          listDefaults: value.filter((v): v is string => typeof v === "string"),
        });
        continue;
      }

      if (isPlainObject(value)) {
        walk(value, path);
      }
    }
  };

  walk(defaults, "");
  return rows;
}

/** Every list-valued path in the tree — used to normalise textarea input. */
export function listPaths(defaults: SiteLabelTree): string[] {
  const paths: string[] = [];

  const walk = (node: Record<string, unknown>, prefix: string): void => {
    for (const [key, value] of Object.entries(node)) {
      const path = prefix ? `${prefix}.${key}` : key;
      if (isSimpleList(value)) {
        paths.push(path);
      } else if (isPlainObject(value)) {
        walk(value, path);
      }
    }
  };

  walk(defaults, "");
  return paths;
}

/**
 * Turn newline-separated textarea values back into arrays for the list paths,
 * leaving every other key untouched. Port of
 * `DashboardSettingController::normalizeListValues()`.
 */
export function normalizeListValues(
  data: Record<string, unknown>,
  lists: string[],
  prefix = "",
): Record<string, unknown> {
  const out: Record<string, unknown> = {};

  for (const [key, value] of Object.entries(data)) {
    const path = prefix ? `${prefix}.${key}` : key;

    if (lists.includes(path)) {
      out[key] = typeof value === "string" ? value.split("\n").map((v) => v.trim()).filter((v) => v !== "") : value;
    } else if (isPlainObject(value)) {
      out[key] = normalizeListValues(value, lists, path);
    } else {
      out[key] = value;
    }
  }

  return out;
}

export function siteUiDefaults(locale: string): SiteLabelTree {
  return SITE_UI_DEFAULTS[locale as SiteUiLocale] ?? SITE_UI_DEFAULTS.en;
}

/**
 * Drop BN leaves identical to their EN counterpart.
 *
 * Port of `WebsiteContent::pruneIdentical()`. Without this an untranslated
 * Bengali CMS row (seeder/migration artefacts copy the English text) would
 * shadow the real translation coming from the language file.
 */
export function pruneIdentical(bn: Record<string, unknown>, en: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = {};

  for (const [key, value] of Object.entries(bn)) {
    const enValue = en[key];

    if (isPlainObject(value) && isPlainObject(enValue)) {
      const sub = pruneIdentical(value, enValue);
      if (Object.keys(sub).length > 0) out[key] = sub;
    } else if (isPlainObject(value)) {
      out[key] = value;
    } else {
      const bnText = scalarText(value);
      const enText = scalarText(enValue);
      if (bnText !== "" && bnText !== enText) out[key] = value;
    }
  }

  return out;
}

function scalarText(value: unknown): string {
  if (typeof value === "string") return value.trim();
  if (typeof value === "number" || typeof value === "boolean") return String(value);
  return "";
}

const cache = new Map<string, SiteLabelTree>();

export function clearSiteUiCache(): void {
  cache.clear();
}

export interface SiteUiRow {
  content_en: string | null;
  content_bn: string | null;
  content: string | null;
  is_active: boolean | null;
}

/**
 * Resolved label tree for a locale: defaults deep-merged with the active
 * `site-ui` CMS row. Port of `SiteFrontend::merged()`.
 */
export async function getSiteUi(locale: string): Promise<SiteLabelTree> {
  const key = locale === "bn" ? "bn" : "en";
  const hit = cache.get(key);
  if (hit) return hit;

  const defaults = siteUiDefaults(key);
  const merged = await applyOverrides(defaults, key);
  cache.set(key, merged);

  return merged;
}

export async function readSiteUiRow(): Promise<SiteUiRow | null> {
  try {
    return await prisma.website_contents.findUnique({ where: { page: SITE_UI_PAGE } });
  } catch {
    return null;
  }
}

/** Same merge as `getSiteUi()` but without the cache — for the editor. */
export async function resolveSiteUiForEditor(locale: string): Promise<{ defaults: SiteLabelTree; overrides: SiteLabelTree }> {
  const key = locale === "bn" ? "bn" : "en";
  const row = await readSiteUiRow();

  const en = parseJsonObject(row?.content_en) ?? parseJsonObject(row?.content) ?? {};
  const bn = parseJsonObject(row?.content_bn) ?? {};

  return { defaults: siteUiDefaults(key), overrides: key === "bn" ? pruneIdentical(bn, en) : en };
}

export async function applyOverrides(defaults: SiteLabelTree, locale: string): Promise<SiteLabelTree> {
  const row = await readSiteUiRow();
  if (!row || row.is_active === false) return defaults;

  const en = parseJsonObject(row.content_en) ?? parseJsonObject(row.content) ?? {};
  const bn = parseJsonObject(row.content_bn) ?? {};
  const custom = locale === "bn" ? pruneIdentical(bn, en) : en;

  if (Object.keys(custom).length === 0) return defaults;

  return deepMerge(defaults, custom);
}

function parseJsonObject(raw: string | null | undefined): Record<string, unknown> | null {
  if (!raw) return null;

  try {
    const parsed: unknown = JSON.parse(raw);
    return isPlainObject(parsed) ? parsed : null;
  } catch {
    return null;
  }
}