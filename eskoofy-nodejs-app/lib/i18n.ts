import { eskoolfy } from "@/config/eskoolfy";
import en from "@/lang/en";
import bn from "@/lang/bn";

/**
 * Minimal i18n, mirroring the Laravel app's `site_ui()` helper and the
 * `lang/{en,bn}` dictionaries. Keys use the same dotted convention
 * (`nav.students`, `home.title`, …).
 */
export type Dictionary = Record<string, string>;

const DICTIONARIES: Record<string, Dictionary> = { en, bn: bn as Dictionary };

import { cache } from "react";

/** Request-scoped locale, set by the (site)/(dashboard) layouts from cookies. */
const requestLocale = cache(() => ({ value: "" as string }));

/** Store the resolved request locale (called by layouts). */
export function setRequestLocale(locale: string): void {
  requestLocale().value = locale;
}

export function locale(): string {
  const set = requestLocale().value;
  return set || eskoolfy.locale;
}

export function dictionary(forLocale: string = locale()): Dictionary {
  return DICTIONARIES[forLocale] ?? DICTIONARIES[eskoolfy.fallbackLocale] ?? en;
}

/** Translate a key, with `:name` placeholder substitution. Falls back to the key. */
export function t(key: string, params: Record<string, string | number> = {}, forLocale?: string): string {
  const dict = dictionary(forLocale);
  let text = dict[key] ?? dictionary(eskoolfy.fallbackLocale)[key] ?? key;
  for (const [name, value] of Object.entries(params)) {
    text = text.replaceAll(`:${name}`, String(value));
  }
  return text;
}

/** True when a key exists in the dictionary (so callers can supply fallbacks). */
export function has(key: string, forLocale?: string): boolean {
  return key in dictionary(forLocale);
}

/** Translate, or return `fallback` when the key is missing. */
export function tOr(key: string, fallback: string, params: Record<string, string | number> = {}, forLocale?: string): string {
  return has(key, forLocale) ? t(key, params, forLocale) : fallback;
}

/** Locales available in this profile (bd ships en+bn, int ships en only). */
export function availableLocales(): string[] {
  return eskoolfy.profile.locales.filter((code) => code in DICTIONARIES);
}

/** Locale cookie names — mirror the Laravel session keys (locale / dashboard_locale). */
export const LOCALE_COOKIE = "eskoofy_locale";
export const DASHBOARD_LOCALE_COOKIE = "eskoofy_dashboard_locale";

/**
 * Resolve the effective locale for the current request, mirroring the app's
 * SetLocaleFromSession middleware:
 *  1. `?lang=` query param wins (when valid).
 *  2. Dashboard paths read the dashboard_locale cookie, then the site cookie.
 *  3. Site paths read the site cookie.
 *  4. Fall back to the profile default.
 * Returns null when the cookies/headers APIs are unavailable (build time).
 */
export async function resolveRequestLocale(store?: {
  get: (name: string) => string | null;
  searchParams?: Record<string, string | string[] | undefined>;
  isDashboard?: boolean;
}): Promise<string> {
  const locales = availableLocales();
  const fallback = eskoolfy.locale;

  if (!store) return fallback;

  const lang = store.searchParams?.lang;
  const langStr = Array.isArray(lang) ? lang[0] : lang;
  if (langStr && locales.includes(langStr)) return langStr;

  const cookieName = store.isDashboard ? DASHBOARD_LOCALE_COOKIE : LOCALE_COOKIE;
  const picked = store.get(cookieName) ?? store.get(LOCALE_COOKIE);
  if (picked && locales.includes(picked)) return picked;

  return fallback;
}
