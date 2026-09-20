/**
 * Site settings + navigation content mirror.
 *
 * Mirrors the Laravel app's `$siteSettings` (website_settings table seeded by
 * WebsiteSettingSeeder) and its `config/school.php` ministry/contact data.
 * Falls back to the exact seeded values so the Node site shows the SAME
 * contents as the Laravel app out of the box.
 */
import { prisma } from "@/lib/prisma";
import { eskoolfy } from "@/config/eskoolfy";

export interface SiteSettings {
  schoolName: string;
  tagline: string;
  address: string;
  phone: string;
  email: string;
  website: string;
  facebookUrl: string | null;
  twitterUrl: string | null;
  instagramUrl: string | null;
  linkedinUrl: string | null;
  youtubeUrl: string | null;
  metaTitle: string;
  metaDescription: string;
}

/** Exact values from eskoofy-laravel-app/database/seeders/WebsiteSettingSeeder.php. */
const FALLBACK: SiteSettings = {
  schoolName: "Example School",
  tagline: "Empowering Future Leaders",
  address: "123 Education Street, Learning City, Education State 12345",
  phone: "+1 (555) 123-4567",
  email: "info@exampleschool.edu",
  website: "https://www.exampleschool.edu",
  facebookUrl: "https://facebook.com/exampleschool",
  twitterUrl: "https://twitter.com/exampleschool",
  instagramUrl: "https://instagram.com/exampleschool",
  linkedinUrl: "https://linkedin.com/school/exampleschool",
  youtubeUrl: "https://youtube.com/exampleschool",
  metaTitle: "Example School - Quality Education for All",
  metaDescription:
    "Example School provides quality education with a focus on academic excellence, character building and holistic development of every student.",
};

/** BD-only ministry / government links (config/school.php; gated by the bd profile). */
export const MINISTRY_LINKS: ReadonlyArray<{ key: string; url: string; labelKey: string }> = [
  { key: "education_ministry", url: "https://moedu.gov.bd/", labelKey: "site.footer.link_ministry_education_ministry" },
  { key: "primary_education", url: "https://www.dpe.gov.bd/", labelKey: "site.footer.link_ministry_primary_education" },
  { key: "secondary_higher_secondary", url: "https://www.educationboard.gov.bd/", labelKey: "site.footer.link_ministry_secondary_higher_secondary" },
  { key: "national_info_center", url: "https://bangladesh.gov.bd/", labelKey: "site.footer.link_ministry_national_info_center" },
];

let cached: SiteSettings | null = null;

/** Load settings from the DB; fall back to the seeder values when empty. */
export async function getSiteSettings(): Promise<SiteSettings> {
  if (cached) return cached;
  try {
    const row = await prisma.website_settings.findFirst();
    if (row) {
      cached = {
        schoolName: row.school_name || FALLBACK.schoolName,
        tagline: row.tagline || FALLBACK.tagline,
        address: [row.address, row.city, row.state, row.postal_code].filter(Boolean).join(", ") || FALLBACK.address,
        phone: row.phone || FALLBACK.phone,
        email: row.email || FALLBACK.email,
        website: row.website || FALLBACK.website,
        facebookUrl: row.facebook_url || FALLBACK.facebookUrl,
        twitterUrl: row.twitter_url || FALLBACK.twitterUrl,
        instagramUrl: row.instagram_url || FALLBACK.instagramUrl,
        linkedinUrl: row.linkedin_url || FALLBACK.linkedinUrl,
        youtubeUrl: row.youtube_url || FALLBACK.youtubeUrl,
        metaTitle: row.meta_title || FALLBACK.metaTitle,
        metaDescription: row.meta_description || FALLBACK.metaDescription,
      };
      return cached;
    }
  } catch {
    /* DB unavailable — fall through to the seeded defaults. */
  }
  cached = FALLBACK;
  return cached;
}

/** First word + rest (Laravel renders the brand as two-colour text). */
export function splitSchoolName(name: string): { first: string; rest: string } {
  const words = name.trim().split(/\s+/);
  const first = words[0] ?? name;
  const rest = words.slice(1).join(" ");
  return { first, rest };
}

/** Ministry links active only for the bd profile (same gate as the Laravel config). */
export function ministryLinks(): ReadonlyArray<{ key: string; url: string; labelKey: string }> {
  return eskoolfy.features.ministryLinks ? MINISTRY_LINKS : [];
}

export function clearSiteSettingsCache(): void {
  cached = null;
}
