"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { updateWebsiteSettings } from "@/lib/dashboard-settings";
import { clearSiteSettingsCache } from "@/lib/site-settings";
import {
  normalizeAboutForm,
  normalizeCmsPayload,
  saveAboutContent,
  saveSiteUiLabels,
  type AboutSectionRow,
} from "@/lib/settings-content";

/**
 * Server actions for the Dashboard → Settings sub-tabs that were dead forms
 * (`cms`, `global-labels`, `about`), mirroring the app's
 * `DashboardSettingController` POST routes:
 *
 *   POST /dashboard/settings/cms           -> updateGeneral
 *   POST /dashboard/settings/global-labels -> updateGlobalLabels
 *   POST /dashboard/settings/about         -> updateAbout
 */

const TARGETS = {
  cms: "/dashboard/settings/cms",
  "global-labels": "/dashboard/settings/global-labels",
  about: "/dashboard/settings/about",
} as const;

type SettingsTab = keyof typeof TARGETS;

/**
 * Guard shared by all three actions — the Node equivalent of the app's
 * `abort_unless(auth()->user()?->can('manage_school_settings'), 403)`.
 * Redirects rather than throwing so the form lands somewhere sensible.
 */
async function guard(tab: SettingsTab): Promise<void> {
  const target = TARGETS[tab];
  const user = await currentUser();

  if (!user) redirect(`/login?redirect=${encodeURIComponent(target)}`);
  if (!can(user.role, "manage_settings")) redirect(`${target}?error=1`);
}

/** Group repeated `name[i]` / `name[i][sub]` inputs into records keyed by index. */
function collectIndexed(formData: FormData, prefix: string): Record<number, Record<string, string>> {
  const rows: Record<number, Record<string, string>> = {};
  const pattern = new RegExp(`^${prefix}\\[(\\d+)\\](?:\\[(.+)\\])?$`);

  for (const [key, value] of formData.entries()) {
    const match = pattern.exec(key);
    if (!match) continue;

    const index = Number(match[1]);
    rows[index] ??= {};
    rows[index]![match[2] ?? ""] = typeof value === "string" ? value : "";
  }

  return rows;
}

/** Rebuild a nested record from dotted form keys such as `labels[en][nav.home]`. */
function collectDotted(formData: FormData, prefix: string): Record<string, Record<string, unknown>> {
  const out: Record<string, Record<string, unknown>> = {};
  const pattern = new RegExp(`^${prefix}\\[(en|bn)\\]\\[(.+)\\]$`);

  for (const [key, value] of formData.entries()) {
    const match = pattern.exec(key);
    if (!match) continue;

    const locale = match[1] as "en" | "bn";
    out[locale] ??= {};

    const segments = match[2]!.split(".");
    let cursor = out[locale]!;
    for (let i = 0; i < segments.length - 1; i++) {
      const segment = segments[i]!;
      if (typeof cursor[segment] !== "object" || cursor[segment] === null) cursor[segment] = {};
      cursor = cursor[segment] as Record<string, unknown>;
    }
    cursor[segments[segments.length - 1]!] = typeof value === "string" ? value : "";
  }

  return out;
}

/**
 * `POST /dashboard/settings/cms` — the CMS identity/social/meta fields on the
 * `website_settings` singleton.
 */
export async function saveCmsSettings(formData: FormData): Promise<void> {
  await guard("cms");

  const raw: Record<string, unknown> = {};
  for (const [key, value] of formData.entries()) {
    if (key.startsWith("__")) continue;
    raw[key] = typeof value === "string" ? value : "";
  }

  await updateWebsiteSettings(normalizeCmsPayload(raw));
  clearSiteSettingsCache();

  revalidatePath(TARGETS.cms);
  revalidatePath("/", "layout");
  redirect(`${TARGETS.cms}?saved=1`);
}

/**
 * `POST /dashboard/settings/global-labels` — the `site-ui` CMS row.
 *
 * Fields post as `labels[en][<dotted.path>]` / `labels[bn][<dotted.path>]`,
 * because the editor renders one row per flattened leaf (`flattenSiteLabels`).
 */
export async function saveGlobalLabels(formData: FormData): Promise<void> {
  await guard("global-labels");

  const collected = collectDotted(formData, "labels");

  await saveSiteUiLabels({ en: collected.en ?? {}, bn: collected.bn ?? {} });

  revalidatePath(TARGETS["global-labels"]);
  revalidatePath("/", "layout");
  redirect(`${TARGETS["global-labels"]}?saved=1`);
}

/**
 * `POST /dashboard/settings/about` — the `about` CMS row.
 *
 * Sections post as repeated `sections[i][heading_en]` inputs; fully emptied rows
 * are dropped so deleting a row actually removes the section.
 */
export async function saveAboutSettings(formData: FormData): Promise<void> {
  await guard("about");

  const rows = Object.entries(collectIndexed(formData, "sections"))
    .sort((a, b) => Number(a[0]) - Number(b[0]))
    .map(([, row]) => ({
      heading_en: row.heading_en ?? "",
      paragraphs_en: row.paragraphs_en ?? "",
      heading_bn: row.heading_bn ?? "",
      paragraphs_bn: row.paragraphs_bn ?? "",
    })) as AboutSectionRow[];

  const trees = normalizeAboutForm({
    intro_en: String(formData.get("intro_en") ?? ""),
    intro_bn: String(formData.get("intro_bn") ?? ""),
    rows,
  });

  await saveAboutContent(trees);

  revalidatePath(TARGETS.about);
  revalidatePath("/about");
  redirect(`${TARGETS.about}?saved=1`);
}