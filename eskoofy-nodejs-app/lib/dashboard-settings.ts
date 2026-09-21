import { prisma } from "@/lib/prisma";

/**
 * Dashboard settings access — reads/writes the rows the app's settings screen
 * edits (`website_settings` + `library_settings`). Mirrors the Laravel
 * `WebsiteSetting::getSettings()` singleton and `LibrarySetting` row.
 */

export type WebsiteSettingsRow = Awaited<ReturnType<typeof prisma.website_settings.findFirst>>;
export type LibrarySettingsRow = Awaited<ReturnType<typeof prisma.library_settings.findFirst>>;

const BOOLEAN_FIELDS = new Set(["bkash_sandbox", "maintenance_mode", "mail_enabled", "send_absence_sms"]);
const NUMBER_FIELDS = new Set(["established_year", "academic_start_month", "mail_port"]);

export async function getWebsiteSettingsRow(): Promise<WebsiteSettingsRow> {
  try {
    return await prisma.website_settings.findFirst();
  } catch {
    return null;
  }
}

export async function getLibrarySettingsRow(): Promise<LibrarySettingsRow> {
  try {
    return (await prisma.library_settings.findFirst()) ?? null;
  } catch {
    return null;
  }
}

function coerce(data: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = {};
  for (const [key, value] of Object.entries(data)) {
    if (value === undefined) continue;
    const text = String(value);
    if (BOOLEAN_FIELDS.has(key)) {
      out[key] = text === "1" || text === "true" || text === "on";
    } else if (NUMBER_FIELDS.has(key)) {
      const parsed = Number(text);
      out[key] = Number.isFinite(parsed) ? parsed : null;
    } else {
      out[key] = text === "" ? null : text;
    }
  }
  return out;
}

/** Update (or create) the singleton website_settings row. */
export async function updateWebsiteSettings(data: Record<string, unknown>): Promise<void> {
  const payload = coerce(data);
  if (Object.keys(payload).length === 0) return;
  payload.updated_at = new Date();

  const existing = await prisma.website_settings.findFirst();
  if (existing) {
    await prisma.website_settings.update({ where: { id: existing.id }, data: payload });
  } else {
    await prisma.website_settings.create({
      data: {
        school_name: "Example School",
        established_year: new Date().getFullYear(),
        address: "",
        city: "",
        state: "",
        country: "Bangladesh",
        postal_code: "",
        phone: "",
        email: "",
        ...payload,
      },
    });
  }
}

/** Update (or create) the singleton library_settings row. */
export async function updateLibrarySettings(data: Record<string, unknown>): Promise<void> {
  const payload: Record<string, unknown> = {};
  for (const key of ["late_fee_per_day", "max_books_per_student", "max_books_per_teacher", "issue_duration_days"]) {
    if (data[key] === undefined) continue;
    const parsed = Number(data[key]);
    if (Number.isFinite(parsed)) payload[key] = Math.max(0, parsed);
  }
  if (Object.keys(payload).length === 0) return;

  const existing = await prisma.library_settings.findFirst();
  if (existing) {
    await prisma.library_settings.update({ where: { id: existing.id }, data: payload });
  } else {
    await prisma.library_settings.create({ data: payload });
  }
}
