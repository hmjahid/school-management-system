"use server";

import { redirect } from "next/navigation";
import { revalidatePath } from "next/cache";
import bcrypt from "bcryptjs";
import { prisma } from "@/lib/prisma";
import { MODEL_BY_NAME } from "@/lib/schema";
import { createRow, deleteRow, updateRow } from "@/lib/db-query";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { updateLibrarySettings, updateWebsiteSettings } from "@/lib/dashboard-settings";

/**
 * Generic create/update/delete actions behind every resource form — the Node
 * equivalent of the app's `store` / `update` / `destroy` controller methods.
 */

function payloadFrom(formData: FormData): Record<string, unknown> {
  const payload: Record<string, unknown> = {};
  for (const [key, value] of formData.entries()) {
    if (key.startsWith("__")) continue;
    payload[key] = typeof value === "string" ? value : "";
  }
  return payload;
}

export async function saveResource(formData: FormData): Promise<void> {
  const user = await currentUser();
  const base = String(formData.get("__base") ?? "/dashboard");
  if (!user) redirect(`/login?redirect=${encodeURIComponent(base)}`);

  const model = MODEL_BY_NAME.get(String(formData.get("__table") ?? ""));
  if (!model) redirect(base);

  const id = String(formData.get("__id") ?? "");
  const payload = payloadFrom(formData);

  if (id) await updateRow(model, id, payload);
  else await createRow(model, payload);

  revalidatePath(base);
  redirect(base);
}

export async function deleteResource(formData: FormData): Promise<void> {
  const user = await currentUser();
  const base = String(formData.get("__base") ?? "/dashboard");
  if (!user) redirect(`/login?redirect=${encodeURIComponent(base)}`);

  const model = MODEL_BY_NAME.get(String(formData.get("__table") ?? ""));
  const id = String(formData.get("__id") ?? "");
  if (model && id) await deleteRow(model, id);

  revalidatePath(base);
  redirect(base);
}

/**
 * Settings save — mirrors the app's `dashboard.settings.update.*` routes.
 * The tab is carried in `__tab`; each tab writes a whitelisted field set to
 * `website_settings` (library writes `library_settings`).
 */
const TAB_FIELDS: Record<string, string[]> = {
  theme: [
    "theme_primary_color",
    "theme_secondary_color",
    "theme_font_family",
    "theme_border_radius",
    "theme_header_style",
    "theme_footer_style",
    "theme_button_style",
    "theme_section_spacing",
    "theme_style",
  ],
  localization: ["timezone", "default_locale", "date_format", "time_format"],
  payment: [
    "bkash_merchant_number",
    "bkash_api_key",
    "bkash_api_secret",
    "bkash_username",
    "bkash_password",
    "bkash_app_key",
    "bkash_app_secret",
    "bkash_sandbox",
    "nagad_merchant_number",
    "currency",
    "default_payment_method",
  ],
  academic: ["established_year", "tagline", "website", "academic_start_month"],
  sms: ["sms_sender_id", "absence_sms_template", "send_absence_sms"],
  mail: [
    "mail_enabled",
    "mail_driver",
    "mail_host",
    "mail_port",
    "mail_encryption",
    "mail_username",
    "mail_password",
    "mail_from_address",
    "mail_from_name",
    "mail_test_recipient",
  ],
};

export async function saveSettingsTab(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/dashboard/settings");
  if (!can(user.role, "manage_settings")) redirect("/dashboard/settings?error=1");

  const tab = String(formData.get("__tab") ?? "");
  const target = `/dashboard/settings?tab=${encodeURIComponent(tab || "theme")}&saved=1`;

  if (tab === "library") {
    const payload: Record<string, unknown> = {};
    for (const field of ["late_fee_per_day", "max_books_per_student", "max_books_per_teacher", "issue_duration_days"]) {
      if (formData.has(field)) payload[field] = formData.get(field);
    }
    await updateLibrarySettings(payload);
  } else {
    const fields = TAB_FIELDS[tab] ?? [];
    const payload: Record<string, unknown> = {};
    for (const field of fields) {
      if (formData.has(field)) payload[field] = formData.get(field);
    }
    await updateWebsiteSettings(payload);
  }

  revalidatePath("/dashboard/settings");
  redirect(target);
}

/** School Info (settings/general) — mirrors the app's general update route. */
export async function saveGeneralSettings(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/dashboard/settings/general");
  if (!can(user.role, "manage_settings")) redirect("/dashboard/settings/general?error=1");

  const fields = [
    "school_name",
    "school_name_bn",
    "tagline",
    "tagline_bn",
    "address",
    "city",
    "state",
    "country",
    "postal_code",
    "phone",
    "email",
    "website",
    "facebook_url",
    "twitter_url",
    "instagram_url",
    "linkedin_url",
    "youtube_url",
    "meta_title",
    "meta_description",
  ];
  const payload: Record<string, unknown> = {};
  for (const field of fields) {
    if (formData.has(field)) payload[field] = formData.get(field);
  }
  await updateWebsiteSettings(payload);

  revalidatePath("/dashboard/settings");
  redirect("/dashboard/settings/general?saved=1");
}

/** Mirrors DashboardSettingsController@clearCache. */
export async function clearCacheAction(): Promise<void> {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/dashboard/settings");
  revalidatePath("/", "layout");
  redirect("/dashboard/settings?tab=academic&saved=1");
}

/** Mirrors DashboardProfileController@update (PUT /dashboard/profile). */
export async function updateProfile(formData: FormData): Promise<void> {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/dashboard/profile");

  const value = (key: string) => String(formData.get(key) ?? "").trim();
  const name = value("name");
  const email = value("email");

  if (!name || !email) {
    redirect("/dashboard/profile?error=1");
  }

  const data: Record<string, unknown> = {
    name,
    email,
    phone: value("phone") || null,
    address: value("address") || null,
    gender: value("gender") || null,
  };
  const dob = value("date_of_birth");
  if (dob) data.date_of_birth = new Date(dob);

  const password = value("password");
  if (password) {
    if (password.length < 8) redirect("/dashboard/profile?error=1");
    data.password = await bcrypt.hash(password, 12);
  }

  try {
    await prisma.users.update({ where: { id: user.id }, data });
  } catch {
    redirect("/dashboard/profile?error=1");
  }

  revalidatePath("/dashboard/profile");
  redirect("/dashboard/profile?sent=1");
}
