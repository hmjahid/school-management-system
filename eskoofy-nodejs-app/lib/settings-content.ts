/**
 * Persistence for the app's Dashboard → Settings sub-tabs that Node previously
 * left as dead forms (`about`, `cms`, `global-labels`).
 *
 * Each function writes the same columns the reference app writes:
 *  - `cms`            → `website_settings` singleton (the app's `updateGeneral`)
 *  - `global-labels`  → `website_contents` where `page = 'site-ui'`
 *  - `about`          → `website_contents` where `page = 'about'`
 *
 * `website_contents.content_en` / `content_bn` are JSON columns in the schema,
 * so the label/section trees are JSON-encoded here and parsed by `lib/site-ui.ts`.
 */
import { prisma } from "@/lib/prisma";
import {
  SITE_UI_PAGE,
  clearSiteUiCache,
  deepMerge,
  isPlainObject,
  listPaths,
  normalizeListValues,
  siteUiDefaults,
} from "@/lib/site-ui";

export const ABOUT_PAGE = "about";

function parseJsonObject(raw: string | null | undefined): Record<string, unknown> {
  if (!raw) return {};
  try {
    const parsed: unknown = JSON.parse(raw);
    return isPlainObject(parsed) ? parsed : {};
  } catch {
    return {};
  }
}

/** Read the `website_contents` row for a page slug, or null. */
export async function readContentRow(page: string) {
  try {
    return await prisma.website_contents.findUnique({ where: { page } });
  } catch {
    return null;
  }
}

async function upsertContentRow(page: string, data: Record<string, unknown>): Promise<void> {
  const existing = await readContentRow(page);

  if (existing) {
    await prisma.website_contents.update({
      where: { id: existing.id },
      data: { ...data, updated_at: new Date() },
    });
    return;
  }

  await prisma.website_contents.create({
    data: { page, is_active: true, ...data } as never,
  });
}

/* -------------------------------------------------------------------------- */
/* CMS settings — website_settings singleton                                   */
/* -------------------------------------------------------------------------- */

const CMS_URL_FIELDS = ["website", "facebook_url", "twitter_url", "instagram_url", "linkedin_url", "youtube_url"];

export const CMS_TEXT_FIELDS = [
  "school_name",
  "school_name_bn",
  "tagline",
  "tagline_bn",
  "email",
  "phone",
  "address",
  "city",
  "state",
  "country",
  "postal_code",
  "meta_title",
  "meta_description",
  "default_locale",
] as const;

export const CMS_BOOL_FIELDS = [
  "show_facebook",
  "show_twitter",
  "show_instagram",
  "show_linkedin",
  "show_youtube",
] as const;

/**
 * Normalise a posted CMS payload.
 *
 * The app nulls out blank URL/social fields before validating (so an emptied
 * social link actually clears instead of failing the `url` rule), and coerces
 * booleans from checkbox input.
 */
export function normalizeCmsPayload(form: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = {};

  for (const field of CMS_TEXT_FIELDS) {
    if (!(field in form)) continue;
    const value = String(form[field] ?? "").trim();
    out[field] = value === "" ? null : value;
  }

  for (const field of CMS_URL_FIELDS) {
    if (!(field in form)) continue;
    const value = String(form[field] ?? "").trim();
    out[field] = value === "" ? null : value;
  }

  for (const field of CMS_BOOL_FIELDS) {
    if (!(field in form)) continue;
    const raw = form[field];
    out[field] = raw === "1" || raw === "on" || raw === true || raw === "true";
  }

  return out;
}

/* -------------------------------------------------------------------------- */
/* Global labels — website_contents page 'site-ui'                             */
/* -------------------------------------------------------------------------- */

/**
 * Merge a posted labels payload over the existing overrides for one locale.
 *
 * Only keys the editor actually rendered are written, and blank strings are
 * dropped so clearing a label falls back to the language-file default rather
 * than persisting an empty string over it.
 */
export function mergeLabelOverrides(
  existing: Record<string, unknown>,
  incoming: Record<string, unknown>,
  locale: "en" | "bn",
): Record<string, unknown> {
  const defaults = siteUiDefaults(locale);
  const lists = listPaths(defaults);
  const normalized = normalizeListValues(incoming, lists);
  const touched = listPathsOfKeys(normalized);

  const merged = deepMerge(existing, normalized);

  // An empty string means "revert to default" — remove the override instead of
  // writing a blank label that would render as an empty heading.
  for (const path of touched) {
    if (readPath(merged, path) === "") deletePath(merged, path);
  }

  return merged;
}

function listPathsOfKeys(data: Record<string, unknown>): string[] {
  const paths: string[] = [];

  const walk = (node: Record<string, unknown>, prefix: string): void => {
    for (const [key, value] of Object.entries(node)) {
      const path = prefix ? `${prefix}.${key}` : key;
      if (isPlainObject(value)) walk(value, path);
      else paths.push(path);
    }
  };

  walk(data, "");
  return paths;
}

function readPath(source: Record<string, unknown>, path: string): unknown {
  let cursor: unknown = source;
  for (const segment of path.split(".")) {
    if (!isPlainObject(cursor)) return undefined;
    cursor = cursor[segment];
  }
  return cursor;
}

function deletePath(target: Record<string, unknown>, path: string): void {
  const segments = path.split(".");
  let cursor = target;
  for (let i = 0; i < segments.length - 1; i++) {
    const next = cursor[segments[i]!];
    if (!isPlainObject(next)) return;
    cursor = next;
  }
  delete cursor[segments[segments.length - 1]!];
}

export interface SiteUiPayload {
  en: Record<string, unknown>;
  bn: Record<string, unknown>;
}

/** Persist both locale override trees for the `site-ui` page. */
export async function saveSiteUiLabels(payload: SiteUiPayload): Promise<void> {
  const existing = (await readContentRow(SITE_UI_PAGE)) ?? null;
  const currentEn = parseJsonObject(existing?.content_en) || parseJsonObject(existing?.content);
  const currentBn = parseJsonObject(existing?.content_bn);

  await upsertContentRow(SITE_UI_PAGE, {
    title_en: existing?.title_en ?? "Site UI",
    content_en: JSON.stringify(mergeLabelOverrides(currentEn, payload.en, "en")),
    content_bn: JSON.stringify(mergeLabelOverrides(currentBn, payload.bn, "bn")),
    is_active: true,
  });

  clearSiteUiCache();
}

/* -------------------------------------------------------------------------- */
/* About — website_contents page 'about'                                       */
/* -------------------------------------------------------------------------- */

export interface AboutSectionRow {
  heading_en: string;
  paragraphs_en: string;
  heading_bn: string;
  paragraphs_bn: string;
}

export interface AboutForm {
  intro_en: unknown;
  intro_bn: unknown;
  rows: AboutSectionRow[];
}

export interface AboutLocaleTree {
  intro: string;
  sections: { heading: string; paragraphs: string[] }[];
}

/** Split on blank lines, trim, drop empties — the app's paragraph splitter. */
function splitParagraphs(text: string): string[] {
  if (text === "") return [];
  return text
    .split(/\n{2,}/)
    .map((p) => p.trim())
    .filter((p) => p !== "");
}

/**
 * Turn one posted editor form into the two locale trees.
 *
 * Port of `DashboardSettingController::updateAbout()`. Note the app builds the
 * EN and BN section lists independently from the same rows: a row contributes
 * to EN only if it has an English heading or paragraph, and to BN only if it
 * has a Bengali one. So a section can exist in one language and not the other,
 * and that asymmetry is preserved here.
 */
export function normalizeAboutForm(form: AboutForm): { en: AboutLocaleTree; bn: AboutLocaleTree } {
  const sectionsEn: AboutLocaleTree["sections"] = [];
  const sectionsBn: AboutLocaleTree["sections"] = [];

  for (const row of form.rows) {
    const headingEn = String(row.heading_en ?? "").trim();
    const headingBn = String(row.heading_bn ?? "").trim();
    const paragraphsEn = splitParagraphs(String(row.paragraphs_en ?? "").trim());
    const paragraphsBn = splitParagraphs(String(row.paragraphs_bn ?? "").trim());

    if (headingEn !== "" || paragraphsEn.length > 0) {
      sectionsEn.push({ heading: headingEn, paragraphs: paragraphsEn });
    }

    if (headingBn !== "" || paragraphsBn.length > 0) {
      sectionsBn.push({ heading: headingBn, paragraphs: paragraphsBn });
    }
  }

  return {
    en: { intro: String(form.intro_en ?? "").trim(), sections: sectionsEn },
    bn: { intro: String(form.intro_bn ?? "").trim(), sections: sectionsBn },
  };
}

/**
 * Merge a locale tree over the stored one so keys the form does not edit
 * (e.g. a section's `bullets`, written by the CMS page editor) survive a save
 * from the About tab — the app merges into `$content->content_en` for the
 * same reason.
 */
export function mergeAboutTree(existing: Record<string, unknown>, incoming: AboutLocaleTree): Record<string, unknown> {
  return { ...existing, ...incoming };
}

/** Editor rows from a stored tree, for one locale. */
export function aboutRowsFromTree(tree: Record<string, unknown>, locale: "en" | "bn"): AboutSectionRow[] {
  const sections = Array.isArray(tree.sections) ? tree.sections : [];

  return sections.filter(isPlainObject).map((section) => {
    const heading = String(section.heading ?? "");
    const paragraphs = Array.isArray(section.paragraphs) ? section.paragraphs.filter((p): p is string => typeof p === "string") : [];

    return locale === "en"
      ? { heading_en: heading, paragraphs_en: paragraphs.join("\n\n"), heading_bn: "", paragraphs_bn: "" }
      : { heading_en: "", paragraphs_en: "", heading_bn: heading, paragraphs_bn: paragraphs.join("\n\n") };
  });
}

export function aboutIntroFromTree(tree: Record<string, unknown>): string {
  return String(tree.intro ?? "");
}

/** Persist the about page body for both locales. */
export async function saveAboutContent(
  trees: { en: AboutLocaleTree; bn: AboutLocaleTree },
): Promise<void> {
  const existing = await readContentRow(ABOUT_PAGE);
  const currentEn = parseJsonObject(existing?.content_en);
  const currentBn = parseJsonObject(existing?.content_bn);

  await upsertContentRow(ABOUT_PAGE, {
    title_en: existing?.title_en ?? "About Us",
    title_bn: existing?.title_bn ?? "আমাদের সম্পর্কে",
    content_en: JSON.stringify(mergeAboutTree(currentEn, trees.en)),
    content_bn: JSON.stringify(mergeAboutTree(currentBn, trees.bn)),
    is_active: true,
  });
}

export { parseJsonObject };
