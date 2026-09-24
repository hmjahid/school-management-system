/**
 * Eskoofy variant configuration — the single source of truth for every BD/INT
 * difference, mirroring the Laravel app's `config/eskoolfy.php` and
 * `build/profiles/profiles.php`.
 *
 * Variants are BUILD-TIME PROFILES: the `bd` / `int` profile is selected by
 * `ESKOOFY_VARIANT` and only changes data (locales, currency, gateways, links).
 * Feature code must never branch with `if (variant === 'bd')`.
 */

export type Variant = "bd" | "int";

export interface VariantProfile {
  label: string;
  locales: string[];
  defaultLocale: string;
  currency: string;
  timezone: string;
  gateways: string[];
  defaultPaymentMethod: string;
  studentDefaults: { nationality: string | null; country: string | null };
  features: {
    ministryLinks: boolean;
    ministryBadge: boolean;
  };
}

export const PROFILES: Record<Variant, VariantProfile> = {
  bd: {
    label: "Bangladesh",
    locales: ["en", "bn"],
    defaultLocale: "bn",
    currency: "BDT",
    timezone: "Asia/Dhaka",
    gateways: ["bkash", "rocket", "nagad"],
    defaultPaymentMethod: "bkash",
    studentDefaults: { nationality: "Bangladeshi", country: "Bangladesh" },
    features: { ministryLinks: true, ministryBadge: true },
  },
  int: {
    label: "International",
    locales: ["en"],
    defaultLocale: "en",
    currency: "USD",
    timezone: "UTC",
    gateways: ["stripe", "paypal", "paddle"],
    defaultPaymentMethod: "stripe",
    studentDefaults: { nationality: null, country: null },
    features: { ministryLinks: false, ministryBadge: false },
  },
};

function envBool(value: string | undefined, fallback: boolean): boolean {
  if (value === undefined || value === "") return fallback;
  return ["1", "true", "yes", "on"].includes(value.toLowerCase());
}

export function activeVariant(): Variant {
  return (process.env.ESKOOFY_VARIANT ?? "bd") === "int" ? "int" : "bd";
}

export const variant: Variant = activeVariant();
export const profile: VariantProfile = PROFILES[variant];

export const eskoolfy = {
  variant,
  profile,

  /** Locale for the current request/profile. */
  locale: process.env.APP_LOCALE ?? profile.defaultLocale,
  fallbackLocale: process.env.APP_FALLBACK_LOCALE ?? "en",
  timezone: process.env.APP_TIMEZONE ?? profile.timezone,
  currency: process.env.PAYMENT_CURRENCY ?? profile.currency,

  /** Enabled payment gateways for this profile (data, not branching). */
  gateways: profile.gateways,

  /** Offline gateway codes that are always eligible regardless of variant. */
  offlineGateways: ["cash", "bank_transfer", "cheque"],

  /**
   * Cross-variant restore reconciliation (mirrors the app's `eskoolfy.restore`).
   * On a restore of a backup tagged with a different variant, variant-owned
   * config rows are re-set to the receiving profile's defaults.
   */
  restore: {
    gateways: Object.fromEntries(
      (Object.keys(PROFILES) as Variant[]).map((v) => [
        v,
        [...PROFILES[v].gateways, ...["cash", "bank_transfer", "cheque"]],
      ]),
    ) as Record<Variant, string[]>,
    settings: {
      currency: Object.fromEntries(
        (Object.keys(PROFILES) as Variant[]).map((v) => [v, PROFILES[v].currency]),
      ) as Record<Variant, string>,
      defaultPaymentMethod: Object.fromEntries(
        (Object.keys(PROFILES) as Variant[]).map((v) => [v, PROFILES[v].defaultPaymentMethod]),
      ) as Record<Variant, string>,
      defaultLocale: { bd: "en", int: "en" } as Record<Variant, string>,
    },
    stripBanglaForInt: true,
  },

  /** Bulk-import column defaults keyed by variant (mirrors the app). */
  import: {
    studentDefaults: Object.fromEntries(
      (Object.keys(PROFILES) as Variant[]).map((v) => [v, PROFILES[v].studentDefaults]),
    ) as Record<Variant, { nationality: string | null; country: string | null }>,
  },

  features: {
    bilingual: profile.locales.length > 1,
    ministryLinks: envBool(process.env.ESKOOFY_MINISTRY_LINKS, profile.features.ministryLinks),
    ministryBadge: envBool(process.env.ESKOOFY_MINISTRY_BADGE, profile.features.ministryBadge),
    smsDriver: process.env.SMS_DRIVER ?? "log",
  },
} as const;

export type EskooflyConfig = typeof eskoolfy;
