import Link from "next/link";
import { getLibrarySettingsRow, getWebsiteSettingsRow } from "@/lib/dashboard-settings";
import { saveGeneralSettings, saveSettingsTab, clearCacheAction } from "@/app/(dashboard)/dashboard/actions";
import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";

/**
 * Tabbed settings — mirrors `dashboard/settings/index.blade.php`
 * (theme / localization / payment / library / academic / sms / mail) and, via
 * `SchoolInfoScreen`, the `settings/general.blade.php` form. Each tab posts to
 * the `saveSettingsTab` server action.
 */

const TABS: Array<{ key: string; label: string }> = [
  { key: "theme", label: "Theme" },
  { key: "localization", label: "Localization" },
  { key: "payment", label: "Payment" },
  { key: "library", label: "Library" },
  { key: "academic", label: "Academic" },
  { key: "sms", label: "SMS" },
  { key: "mail", label: "Mail / SMTP" },
];

const TIMEZONES = [
  "UTC",
  "Asia/Dhaka",
  "Asia/Kolkata",
  "Asia/Karachi",
  "Asia/Dubai",
  "Asia/Singapore",
  "Europe/London",
  "Europe/Paris",
  "America/New_York",
  "America/Chicago",
  "America/Los_Angeles",
  "Australia/Sydney",
];

const FONT_FAMILIES = ["", "Inter, sans-serif", "Roboto, sans-serif", "Poppins, sans-serif", "Open Sans, sans-serif", "Lato, sans-serif", "Montserrat, sans-serif", "Georgia, serif"];
const RADII = ["", "0", "0.25rem", "0.5rem", "0.75rem", "1rem"];
const MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

function Field({ label, children, hint }: { label: string; children: React.ReactNode; hint?: string }) {
  return (
    <div>
      <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">{label}</label>
      {children}
      {hint ? <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{hint}</p> : null}
    </div>
  );
}

function TextField({ name, label, value, type = "text", placeholder, hint }: { name: string; label: string; value: string; type?: string; placeholder?: string; hint?: string }) {
  return (
    <Field label={label} hint={hint}>
      <input name={name} type={type} defaultValue={value} placeholder={placeholder} className="admin-input" />
    </Field>
  );
}

function SelectField({ name, label, value, options, hint }: { name: string; label: string; value: string; options: Array<{ value: string; label: string }>; hint?: string }) {
  return (
    <Field label={label} hint={hint}>
      <select name={name} defaultValue={value} className="admin-input">
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </Field>
  );
}

function SaveRow({ tab }: { tab: string }) {
  return (
    <div className="mt-6 flex justify-end">
      <input type="hidden" name="__tab" value={tab} />
      <button type="submit" className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700">
        {t("dashboard.save_settings")}
      </button>
    </div>
  );
}

export async function SettingsScreen({ tab = "theme" }: { tab?: string }) {
  const settings = await getWebsiteSettingsRow();
  const library = await getLibrarySettingsRow();
  const active = TABS.some((item) => item.key === tab) ? tab : "theme";
  const s = settings ?? ({} as NonNullable<typeof settings>);

  return (
    <div>
      <PageHeader title={t("dashboard.settings")} description="Theme, localization, payment gateways, and library rules." />

      <div className="mb-6 border-b border-slate-200 dark:border-slate-700">
        <nav className="-mb-px flex flex-wrap gap-x-6 gap-y-2 text-sm font-medium">
          {TABS.map((item) => (
            <Link
              key={item.key}
              href={`/dashboard/settings?tab=${item.key}`}
              className={`whitespace-nowrap border-b-2 px-1 pb-3 transition ${
                active === item.key
                  ? "border-brand-600 text-brand-600"
                  : "border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400"
              }`}
            >
              {item.label}
            </Link>
          ))}
        </nav>
      </div>

      {active === "theme" ? (
        <form action={saveSettingsTab} className="max-w-3xl">
          <div className="admin-card">
            <div className="admin-card-body">
              <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Theme customization</h2>
              <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">Brand colors, font, radius and theme style used across the app.</p>
              <div className="grid gap-4 sm:grid-cols-2">
                <SelectField name="theme_font_family" label="Font family" value={s.theme_font_family ?? ""} options={FONT_FAMILIES.map((f) => ({ value: f, label: f || "Default (Inter)" }))} />
                <SelectField name="theme_border_radius" label="Border radius" value={s.theme_border_radius ?? ""} options={RADII.map((r) => ({ value: r, label: r || "Default (rounded)" }))} />
                <SelectField name="theme_header_style" label="Header style" value={s.theme_header_style ?? ""} options={[{ value: "transparent", label: "Transparent (over hero)" }, { value: "white", label: "Solid white" }, { value: "dark", label: "Solid dark" }]} />
                <SelectField name="theme_footer_style" label="Footer style" value={s.theme_footer_style ?? ""} options={[{ value: "dark", label: "Dark" }, { value: "light", label: "Light" }]} />
                <SelectField name="theme_button_style" label="Button style" value={s.theme_button_style ?? ""} options={[{ value: "rounded", label: "Rounded" }, { value: "square", label: "Square" }, { value: "pill", label: "Pill" }]} />
                <SelectField name="theme_section_spacing" label="Section spacing" value={s.theme_section_spacing ?? ""} options={[{ value: "compact", label: "Compact" }, { value: "default", label: "Default" }, { value: "spacious", label: "Spacious" }]} />
                <SelectField
                  name="theme_style"
                  label="Theme style"
                  value={s.theme_style ?? "default"}
                  options={[{ value: "default", label: "Default" }, { value: "modern", label: "Modern" }, { value: "classic", label: "Classic" }, { value: "minimal", label: "Minimal" }]}
                  hint="Adjusts the dashboard look (radius, heading weight, card shadow)."
                />
                <TextField name="theme_primary_color" label="Primary color" value={s.theme_primary_color ?? ""} placeholder="#2563eb" />
                <TextField name="theme_secondary_color" label="Secondary color" value={s.theme_secondary_color ?? ""} placeholder="#f97316" />
              </div>
            </div>
          </div>
          <SaveRow tab="theme" />
        </form>
      ) : null}

      {active === "localization" ? (
        <form action={saveSettingsTab} className="max-w-3xl">
          <div className="admin-card">
            <div className="admin-card-body">
              <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Localization</h2>
              <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">Timezone, date format, time format, and default language.</p>
              <div className="grid gap-4 sm:grid-cols-2">
                <SelectField name="timezone" label="Timezone" value={s.timezone ?? "UTC"} options={TIMEZONES.map((tz) => ({ value: tz, label: tz }))} />
                <SelectField name="default_locale" label="Default site language" value={s.default_locale ?? "en"} options={[{ value: "en", label: "English" }, { value: "bn", label: "বাংলা (Bengali)" }]} />
                <TextField name="date_format" label="Date format" value={s.date_format ?? "Y-m-d"} hint="PHP-style date format, e.g. d/m/Y." />
                <TextField name="time_format" label="Time format" value={s.time_format ?? "H:i"} hint="PHP-style time format, e.g. h:i A." />
              </div>
            </div>
          </div>
          <SaveRow tab="localization" />
        </form>
      ) : null}

      {active === "payment" ? (
        <form action={saveSettingsTab} className="max-w-3xl">
          <div className="admin-card">
            <div className="admin-card-body">
              <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Payment settings</h2>
              <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">Configure gateways for online fee collection.</p>

              <div className="mb-6 rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                <h3 className="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">bKash</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                  <TextField name="bkash_merchant_number" label="bKash Merchant Number" value={s.bkash_merchant_number ?? ""} placeholder="01XXXXXXXXX" />
                  <TextField name="bkash_api_key" label="bKash API Key" value={s.bkash_api_key ?? ""} />
                  <TextField name="bkash_api_secret" label="bKash API Secret" value={s.bkash_api_secret ?? ""} type="password" />
                  <TextField name="bkash_username" label="bKash Username" value={s.bkash_username ?? ""} />
                  <TextField name="bkash_password" label="bKash Password" value={s.bkash_password ?? ""} type="password" />
                  <TextField name="bkash_app_key" label="bKash App Key" value={s.bkash_app_key ?? ""} />
                  <TextField name="bkash_app_secret" label="bKash App Secret" value={s.bkash_app_secret ?? ""} type="password" />
                  <div className="flex items-center">
                    <label className="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                      <input type="hidden" name="bkash_sandbox" value="0" />
                      <input type="checkbox" name="bkash_sandbox" value="1" defaultChecked={s.bkash_sandbox ?? true} className="h-4 w-4 rounded border-slate-300 text-brand-600" />
                      Sandbox mode
                    </label>
                  </div>
                </div>
              </div>

              <div className="mb-6 rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                <h3 className="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nagad</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                  <TextField name="nagad_merchant_number" label="Nagad Merchant Number" value={s.nagad_merchant_number ?? ""} placeholder="01XXXXXXXXX" />
                </div>
              </div>

              <div className="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                <h3 className="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">General</h3>
                <div className="grid gap-4 sm:grid-cols-2">
                  <SelectField name="currency" label="Currency" value={s.currency ?? "BDT"} options={["BDT", "USD", "INR", "PKR", "EUR", "GBP"].map((c) => ({ value: c, label: c }))} />
                  <SelectField name="default_payment_method" label="Default Payment Method" value={s.default_payment_method ?? "bkash"} options={[{ value: "bkash", label: "bKash" }, { value: "nagad", label: "Nagad" }, { value: "cash", label: "Cash" }, { value: "bank", label: "Bank" }]} />
                </div>
              </div>
            </div>
          </div>
          <SaveRow tab="payment" />
        </form>
      ) : null}

      {active === "library" ? (
        <form action={saveSettingsTab} className="max-w-3xl">
          <div className="admin-card">
            <div className="admin-card-body">
              <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Library settings</h2>
              <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">Borrowing limits and late fees.</p>
              <div className="grid gap-4 sm:grid-cols-2">
                <TextField name="late_fee_per_day" label="Late Fee (per day)" value={String(library?.late_fee_per_day ?? "5")} type="number" />
                <TextField name="max_books_per_student" label="Max Books per Student" value={String(library?.max_books_per_student ?? "3")} type="number" />
                <TextField name="max_books_per_teacher" label="Max Books per Teacher" value={String(library?.max_books_per_teacher ?? "10")} type="number" />
                <TextField name="issue_duration_days" label="Issue Duration (days)" value={String(library?.issue_duration_days ?? "14")} type="number" />
              </div>
            </div>
          </div>
          <SaveRow tab="library" />
        </form>
      ) : null}

      {active === "academic" ? (
        <div className="max-w-3xl">
          <form action={saveSettingsTab}>
            <div className="admin-card">
              <div className="admin-card-body">
                <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Academic settings</h2>
                <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">School identity and academic year.</p>
                <div className="grid gap-4 sm:grid-cols-2">
                  <TextField name="established_year" label="Established year" value={String(s.established_year ?? "")} type="number" />
                  <TextField name="tagline" label="School motto" value={s.tagline ?? ""} />
                  <TextField name="website" label="Website URL" value={s.website ?? ""} type="url" />
                  <SelectField name="academic_start_month" label="Academic start month" value={String(s.academic_start_month ?? 1)} options={MONTHS.map((m, i) => ({ value: String(i + 1), label: m }))} />
                </div>
              </div>
            </div>
            <SaveRow tab="academic" />
          </form>

          <form action={clearCacheAction} className="mt-4">
            <div className="admin-card">
              <div className="admin-card-body">
                <h3 className="text-sm font-bold text-slate-900 dark:text-slate-100">Frontend cache</h3>
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Flushes cached pages so settings take effect immediately.</p>
                <button type="submit" className="mt-3 rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">
                  Clear cache
                </button>
              </div>
            </div>
          </form>
        </div>
      ) : null}

      {active === "sms" ? (
        <form action={saveSettingsTab} className="max-w-3xl">
          <div className="admin-card">
            <div className="admin-card-body">
              <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">SMS settings</h2>
              <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">Sender ID and absence notifications (provider credentials come from the environment).</p>
              <div className="grid gap-4 sm:grid-cols-2">
                <TextField name="sms_sender_id" label="SMS sender ID" value={s.sms_sender_id ?? ""} />
                <Field label="Absence SMS template" hint="Placeholders: :student_name, :date, :class">
                  <textarea name="absence_sms_template" rows={2} defaultValue={s.absence_sms_template ?? ""} className="admin-input" />
                </Field>
                <div className="flex items-center">
                  <label className="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="hidden" name="send_absence_sms" value="0" />
                    <input type="checkbox" name="send_absence_sms" value="1" defaultChecked={s.send_absence_sms ?? false} className="h-4 w-4 rounded border-slate-300 text-brand-600" />
                    Send absence SMS to guardians
                  </label>
                </div>
              </div>
            </div>
          </div>
          <SaveRow tab="sms" />
        </form>
      ) : null}

      {active === "mail" ? (
        <form action={saveSettingsTab} className="max-w-3xl">
          <div className="admin-card">
            <div className="admin-card-body">
              <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Mail / SMTP settings</h2>
              <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">Configure an SMTP server for transactional email.</p>
              <div className="mb-6 flex items-center gap-3">
                <input type="hidden" name="mail_enabled" value="0" />
                <input type="checkbox" name="mail_enabled" value="1" id="mail_enabled" defaultChecked={s.mail_enabled ?? false} className="h-4 w-4 rounded border-slate-300 text-brand-600" />
                <label htmlFor="mail_enabled" className="text-sm font-medium text-slate-700 dark:text-slate-300">Enable SMTP sending</label>
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <SelectField name="mail_driver" label="Driver" value={s.mail_driver ?? "smtp"} options={["smtp", "log", "mailgun", "ses", "postmark", "resend"].map((d) => ({ value: d, label: d.toUpperCase() }))} />
                <TextField name="mail_host" label="Host" value={s.mail_host ?? ""} placeholder="smtp.example.com" />
                <TextField name="mail_port" label="Port" value={s.mail_port ?? ""} placeholder="587" />
                <SelectField name="mail_encryption" label="Encryption" value={s.mail_encryption ?? ""} options={[{ value: "", label: "None" }, { value: "tls", label: "TLS" }, { value: "ssl", label: "SSL" }]} />
                <TextField name="mail_username" label="Username" value={s.mail_username ?? ""} />
                <TextField name="mail_password" label="Password" value={s.mail_password ?? ""} type="password" />
                <TextField name="mail_from_address" label="From address" value={s.mail_from_address ?? ""} type="email" placeholder="noreply@example.com" />
                <TextField name="mail_from_name" label="From name" value={s.mail_from_name ?? ""} />
                <div className="sm:col-span-2">
                  <TextField name="mail_test_recipient" label="Test recipient" value={s.mail_test_recipient ?? ""} type="email" />
                </div>
              </div>
            </div>
          </div>
          <SaveRow tab="mail" />
        </form>
      ) : null}
    </div>
  );
}

/** School Info — mirrors `dashboard/settings/general.blade.php`. */
export async function SchoolInfoScreen() {
  const settings = await getWebsiteSettingsRow();
  const s = settings ?? ({} as NonNullable<typeof settings>);

  return (
    <div>
      <PageHeader
        title={t("dashboard.school_info")}
        description="School name, logos, contact details, social links, and meta."
        actions={
          <Link href="/dashboard/settings" className="text-sm font-medium text-brand-600 hover:text-brand-800">
            All settings →
          </Link>
        }
      />

      <form action={saveGeneralSettings} className="max-w-3xl space-y-6">
        <div className="admin-card">
          <div className="admin-card-body">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">General info</h2>
            <p className="mb-5 text-sm text-slate-500 dark:text-slate-400">School name, contact details and location.</p>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <TextField name="school_name" label="School name" value={s.school_name ?? ""} />
              </div>
              <TextField name="school_name_bn" label="School name (Bangla)" value={s.school_name_bn ?? ""} />
              <TextField name="tagline" label="Tagline" value={s.tagline ?? ""} />
              <TextField name="tagline_bn" label="Tagline (Bangla)" value={s.tagline_bn ?? ""} />
              <TextField name="phone" label="Phone" value={s.phone ?? ""} />
              <TextField name="email" label="Email" value={s.email ?? ""} type="email" />
              <div className="sm:col-span-2">
                <TextField name="address" label="Address" value={s.address ?? ""} />
              </div>
              <TextField name="city" label="City" value={s.city ?? ""} />
              <TextField name="state" label="State / division" value={s.state ?? ""} />
              <TextField name="country" label="Country" value={s.country ?? ""} />
              <TextField name="postal_code" label="Postal code" value={s.postal_code ?? ""} />
              <div className="sm:col-span-2">
                <TextField name="website" label="Website" value={s.website ?? ""} />
              </div>
            </div>
          </div>
        </div>

        <div className="admin-card">
          <div className="admin-card-body">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Social links</h2>
            <div className="mt-4 grid gap-4 sm:grid-cols-2">
              <TextField name="facebook_url" label="Facebook" value={s.facebook_url ?? ""} />
              <TextField name="instagram_url" label="Instagram" value={s.instagram_url ?? ""} />
              <TextField name="twitter_url" label="X / Twitter" value={s.twitter_url ?? ""} />
              <TextField name="youtube_url" label="YouTube" value={s.youtube_url ?? ""} />
              <TextField name="linkedin_url" label="LinkedIn" value={s.linkedin_url ?? ""} />
            </div>
          </div>
        </div>

        <div className="admin-card">
          <div className="admin-card-body">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Meta &amp; SEO</h2>
            <div className="mt-4 grid gap-4">
              <TextField name="meta_title" label="Meta title" value={s.meta_title ?? ""} />
              <Field label="Meta description">
                <textarea name="meta_description" rows={3} defaultValue={s.meta_description ?? ""} className="admin-input" />
              </Field>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button type="submit" className="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            {t("common.save")}
          </button>
        </div>
      </form>
    </div>
  );
}
