import Link from "next/link";
import {
  aboutIntroFromTree,
  aboutRowsFromTree,
  readContentRow,
  ABOUT_PAGE,
} from "@/lib/settings-content";
import {
  flattenSiteLabels,
  resolveSiteUiForEditor,
  siteUiDefaults,
} from "@/lib/site-ui";
import { getWebsiteSettingsRow } from "@/lib/dashboard-settings";
import {
  saveAboutSettings,
  saveCmsSettings,
  saveGlobalLabels,
} from "@/app/(dashboard)/dashboard/settings-actions";

/**
 * Dashboard → Settings sub-tabs `about` / `cms` / `global-labels`.
 *
 * These were dead forms that rendered a handful of hardcoded inputs with no
 * action and no persistence. Each tab here mirrors the app's Blade view and
 * posts to the matching `DashboardSettingController` behaviour:
 *
 *   cms            → `website_settings` identity/social/meta fields
 *   global-labels  → the `site-ui` CMS row, one input per flattened label
 *   about          → the `about` CMS row (intro + repeatable sections)
 */

const TABS = [
  { key: "general", label: "General", href: "/dashboard/settings/general" },
  { key: "cms", label: "CMS", href: "/dashboard/settings/cms" },
  { key: "global-labels", label: "Global labels", href: "/dashboard/settings/global-labels" },
  { key: "about", label: "About", href: "/dashboard/settings/about" },
] as const;

const FIELD_LABELS: Record<string, string> = {
  school_name: "School name",
  school_name_bn: "School name (Bengali)",
  tagline: "Tagline",
  tagline_bn: "Tagline (Bengali)",
  email: "Email",
  phone: "Phone",
  address: "Address",
  city: "City",
  state: "State",
  country: "Country",
  postal_code: "Postal code",
  meta_title: "Meta title",
  meta_description: "Meta description",
  default_locale: "Default locale",
  website: "Website",
  facebook_url: "Facebook URL",
  twitter_url: "Twitter / X URL",
  instagram_url: "Instagram URL",
  linkedin_url: "LinkedIn URL",
  youtube_url: "YouTube URL",
};

function TabNav({ active }: { active: string }) {
  return (
    <nav className="mb-6 flex flex-wrap gap-2 border-b border-slate-200 dark:border-slate-700">
      {TABS.map((tab) => (
        <Link
          key={tab.key}
          href={tab.href}
          className={`-mb-px border-b-2 px-3 py-2 text-sm font-medium transition ${
            active === tab.key
              ? "border-brand-600 text-brand-600"
              : "border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400"
          }`}
        >
          {tab.label}
        </Link>
      ))}
    </nav>
  );
}

function Shell({ active, title, subtitle, children }: { active: string; title: string; subtitle: string; children: React.ReactNode }) {
  return (
    <div>
      <div className="mb-6">
        <Link href="/dashboard/settings" className="text-sm font-medium text-brand-600 hover:text-brand-800">
          ← Settings
        </Link>
        <h1 className="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{title}</h1>
        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{subtitle}</p>
      </div>
      <TabNav active={active} />
      {children}
    </div>
  );
}

const inputClass = "admin-input w-full";
const labelClass = "mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300";

function SavedFlag({ searchParams }: { searchParams: Record<string, string | undefined> }) {
  if (!searchParams.saved) return null;
  return (
    <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" role="status">
      Settings saved.
    </div>
  );
}

function ErrorFlag({ searchParams }: { searchParams: Record<string, string | undefined> }) {
  if (!searchParams.error) return null;
  return (
    <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
      You do not have permission to change these settings.
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/* CMS                                                                         */
/* -------------------------------------------------------------------------- */

export async function CmsSettingsTab({ searchParams }: { searchParams: Record<string, string | undefined> }) {
  const settings = (await getWebsiteSettingsRow()) ?? null;
  const value = (field: string): string => {
    const raw = settings?.[field as keyof typeof settings];
    return raw === null || raw === undefined ? "" : String(raw);
  };

  return (
    <Shell
      active="cms"
      title="CMS settings"
      subtitle="School identity, social links and SEO metadata used across the public site."
    >
      <SavedFlag searchParams={searchParams} />
      <ErrorFlag searchParams={searchParams} />

      <form action={saveCmsSettings} className="max-w-3xl space-y-5">
        <div className="admin-card">
          <div className="admin-card-body space-y-4">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Identity</h2>
            <div className="grid gap-4 sm:grid-cols-2">
              {(["school_name", "school_name_bn", "tagline", "tagline_bn", "email", "phone"] as const).map((field) => (
                <div key={field}>
                  <label className={labelClass} htmlFor={`cms-${field}`}>
                    {FIELD_LABELS[field]}
                  </label>
                  <input id={`cms-${field}`} name={field} defaultValue={value(field)} className={inputClass} />
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="admin-card">
          <div className="admin-card-body space-y-4">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Address</h2>
            <div className="grid gap-4 sm:grid-cols-2">
              {(["address", "city", "state", "country", "postal_code"] as const).map((field) => (
                <div key={field} className={field === "address" ? "sm:col-span-2" : undefined}>
                  <label className={labelClass} htmlFor={`cms-${field}`}>
                    {FIELD_LABELS[field]}
                  </label>
                  <input id={`cms-${field}`} name={field} defaultValue={value(field)} className={inputClass} />
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="admin-card">
          <div className="admin-card-body space-y-4">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">SEO</h2>
            <div>
              <label className={labelClass} htmlFor="cms-meta_title">
                {FIELD_LABELS.meta_title}
              </label>
              <input id="cms-meta_title" name="meta_title" defaultValue={value("meta_title")} className={inputClass} />
            </div>
            <div>
              <label className={labelClass} htmlFor="cms-meta_description">
                {FIELD_LABELS.meta_description}
              </label>
              <textarea id="cms-meta_description" name="meta_description" rows={3} defaultValue={value("meta_description")} className={inputClass} />
            </div>
            <div className="max-w-xs">
              <label className={labelClass} htmlFor="cms-default_locale">
                {FIELD_LABELS.default_locale}
              </label>
              <select id="cms-default_locale" name="default_locale" defaultValue={value("default_locale") || "en"} className={inputClass}>
                <option value="en">English</option>
                <option value="bn">বাংলা (Bengali)</option>
              </select>
            </div>
          </div>
        </div>

        <div className="admin-card">
          <div className="admin-card-body space-y-4">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Social links</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400">Leave a field empty to hide that link from the public site.</p>
            <div>
              <label className={labelClass} htmlFor="cms-website">
                {FIELD_LABELS.website}
              </label>
              <input id="cms-website" name="website" defaultValue={value("website")} className={inputClass} placeholder="https://" />
            </div>
            {(["facebook", "twitter", "instagram", "linkedin", "youtube"] as const).map((network) => {
              const urlField = `${network}_url`;
              const showField = `show_${network}`;
              return (
                <div key={network} className="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                  <div>
                    <label className={labelClass} htmlFor={`cms-${urlField}`}>
                      {FIELD_LABELS[urlField]}
                    </label>
                    <input id={`cms-${urlField}`} name={urlField} defaultValue={value(urlField)} className={inputClass} placeholder="https://" />
                  </div>
                  <label className="flex items-center gap-2 pb-2 text-sm text-slate-700 dark:text-slate-300">
                    <input
                      type="checkbox"
                      name={showField}
                      value="1"
                      defaultChecked={settings ? Boolean(settings[showField as keyof typeof settings]) : true}
                      className="admin-checkbox"
                    />
                    Show
                  </label>
                </div>
              );
            })}
          </div>
        </div>

        <button className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save CMS settings</button>
      </form>
    </Shell>
  );
}

/* -------------------------------------------------------------------------- */
/* Global labels                                                               */
/* -------------------------------------------------------------------------- */

export async function GlobalLabelsTab({ searchParams }: { searchParams: Record<string, string | undefined> }) {
  const en = await resolveSiteUiForEditor("en");
  const bn = await resolveSiteUiForEditor("bn");
  const rows = flattenSiteLabels(siteUiDefaults("en"), en.overrides);

  // Group the flattened leaves by their top-level section for a readable form.
  const sections: { name: string; rows: typeof rows }[] = [];
  for (const row of rows) {
    const last = sections[sections.length - 1];
    if (last && last.name === row.section) last.rows.push(row);
    else sections.push({ name: row.section, rows: [row] });
  }

  const bnAt = (path: string): string => {
    const parts = path.split(".");
    let cursor: unknown = bn.overrides;
    for (const part of parts) {
      if (typeof cursor !== "object" || cursor === null) return "";
      cursor = (cursor as Record<string, unknown>)[part];
    }
    return typeof cursor === "string" ? cursor : Array.isArray(cursor) ? cursor.join("\n") : "";
  };

  return (
    <Shell
      active="global-labels"
      title="Global labels"
      subtitle="Overrides for every public-site string. Leave a field empty to fall back to the language-file default."
    >
      <SavedFlag searchParams={searchParams} />
      <ErrorFlag searchParams={searchParams} />

      <form action={saveGlobalLabels} className="max-w-3xl space-y-5">
        {sections.map((section) => (
          <div key={section.name} className="admin-card">
            <div className="admin-card-body space-y-4">
              <h2 className="text-lg font-semibold capitalize text-slate-900 dark:text-slate-100">{section.name.replace(/_/g, " ")}</h2>
              {section.rows.map((row) => (
                <div key={row.path} className="grid gap-3 sm:grid-cols-2">
                  <div>
                    <label className={labelClass} htmlFor={`en-${row.path}`}>
                      <span className="capitalize">{row.label.replace(/[._]/g, " ")}</span>{" "}
                      <span className="text-xs font-normal text-slate-400">EN</span>
                    </label>
                    {row.isList ? (
                      <textarea
                        id={`en-${row.path}`}
                        name={`labels[en][${row.path}]`}
                        rows={Math.min(8, Math.max(3, row.listDefaults?.length ?? 3))}
                        defaultValue={row.enOverride || (row.listDefaults ?? []).join("\n")}
                        className={inputClass}
                      />
                    ) : (
                      <input
                        id={`en-${row.path}`}
                        name={`labels[en][${row.path}]`}
                        defaultValue={row.enOverride || row.enDefault}
                        className={inputClass}
                      />
                    )}
                  </div>
                  <div>
                    <label className={labelClass} htmlFor={`bn-${row.path}`}>
                      <span className="capitalize">{row.label.replace(/[._]/g, " ")}</span>{" "}
                      <span className="text-xs font-normal text-slate-400">BN</span>
                    </label>
                    {row.isList ? (
                      <textarea id={`bn-${row.path}`} name={`labels[bn][${row.path}]`} rows={3} defaultValue={bnAt(row.path)} className={inputClass} />
                    ) : (
                      <input id={`bn-${row.path}`} name={`labels[bn][${row.path}]`} defaultValue={bnAt(row.path)} className={inputClass} />
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}

        <button className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save global labels</button>
      </form>
    </Shell>
  );
}

/* -------------------------------------------------------------------------- */
/* About                                                                       */
/* -------------------------------------------------------------------------- */

const SECTION_FIELDS = [
  { name: "heading_en", label: "Heading (EN)", rows: 1 },
  { name: "paragraphs_en", label: "Paragraphs (EN) — separate with a blank line", rows: 5 },
  { name: "heading_bn", label: "Heading (BN)", rows: 1 },
  { name: "paragraphs_bn", label: "Paragraphs (BN) — separate with a blank line", rows: 5 },
] as const;

export async function AboutTab({ searchParams }: { searchParams: Record<string, string | undefined> }) {
  const row = await readContentRow(ABOUT_PAGE);
  const enTree = parseTree(row?.content_en);
  const bnTree = parseTree(row?.content_bn);
  const enRows = aboutRowsFromTree(enTree, "en");
  const bnRows = aboutRowsFromTree(bnTree, "bn");

  // The two locales can hold a different number of sections, so pad to the
  // longer list and render one editor row per position.
  const count = Math.max(3, enRows.length, bnRows.length);
  const sectionRows = Array.from({ length: count }, (_, index) => ({
    heading_en: enRows[index]?.heading_en ?? "",
    paragraphs_en: enRows[index]?.paragraphs_en ?? "",
    heading_bn: bnRows[index]?.heading_bn ?? "",
    paragraphs_bn: bnRows[index]?.paragraphs_bn ?? "",
  }));

  return (
    <Shell
      active="about"
      title="About page"
      subtitle="Intro copy and the section list rendered on the public About page."
    >
      <SavedFlag searchParams={searchParams} />
      <ErrorFlag searchParams={searchParams} />

      <form action={saveAboutSettings} className="max-w-3xl space-y-5">
        <div className="admin-card">
          <div className="admin-card-body space-y-4">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Intro</h2>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <label className={labelClass} htmlFor="intro_en">
                  Intro (EN)
                </label>
                <textarea id="intro_en" name="intro_en" rows={5} defaultValue={aboutIntroFromTree(enTree)} className={inputClass} />
              </div>
              <div>
                <label className={labelClass} htmlFor="intro_bn">
                  Intro (BN)
                </label>
                <textarea id="intro_bn" name="intro_bn" rows={5} defaultValue={aboutIntroFromTree(bnTree)} className={inputClass} />
              </div>
            </div>
          </div>
        </div>

        {sectionRows.map((section, index) => (
          <div key={index} className="admin-card">
            <div className="admin-card-body space-y-4">
              <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">Section {index + 1}</h2>
              <div className="grid gap-4 sm:grid-cols-2">
                {SECTION_FIELDS.map((field) => (
                  <div key={field.name}>
                    <label className={labelClass} htmlFor={`section-${index}-${field.name}`}>
                      {field.label}
                    </label>
                    <textarea
                      id={`section-${index}-${field.name}`}
                      name={`sections[${index}][${field.name}]`}
                      rows={field.rows}
                      defaultValue={section[field.name]}
                      className={inputClass}
                    />
                  </div>
                ))}
              </div>
            </div>
          </div>
        ))}

        <p className="text-sm text-slate-500 dark:text-slate-400">
          Clear every field in a section to remove it. Bengali is optional — a section with only English copy
          is stored for English only.
        </p>

        <button className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save about page</button>
      </form>
    </Shell>
  );
}

function parseTree(raw: string | null | undefined): Record<string, unknown> {
  if (!raw) return {};
  try {
    const parsed: unknown = JSON.parse(raw);
    return typeof parsed === "object" && parsed !== null && !Array.isArray(parsed)
      ? (parsed as Record<string, unknown>)
      : {};
  } catch {
    return {};
  }
}

