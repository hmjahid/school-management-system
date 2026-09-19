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

export function locale(): string {
  return eskoolfy.locale;
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
