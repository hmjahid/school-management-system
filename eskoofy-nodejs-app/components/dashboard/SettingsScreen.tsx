import { getSiteSettings } from "@/lib/site-settings";
import { t } from "@/lib/i18n";

/**
 * Bespoke settings screen — mirrors the app's `dashboard/settings/general.blade.php`
 * (School Info form: name, logo, favicon, contact, social links, meta).
 */

export async function SettingsScreen() {
  const settings = await getSiteSettings();

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-slate-900">School Info</h1>
        <p className="mt-1 text-sm text-slate-600">School name, logos, contact details, social links, and meta.</p>
      </div>

      <form method="post" action="/dashboard/settings/update" className="max-w-3xl space-y-6">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">General info</h2>
          <p className="mb-5 text-sm text-slate-500">School name, contact details, social links, and meta.</p>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-medium text-slate-700">School name</label>
              <input name="school_name" defaultValue={settings.schoolName} className="admin-input w-full" />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-medium text-slate-700">Tagline</label>
              <input name="tagline" defaultValue={settings.tagline} className="admin-input w-full" />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-medium text-slate-700">Address</label>
              <input name="address" defaultValue={settings.address} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Phone</label>
              <input name="phone" defaultValue={settings.phone} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Email</label>
              <input name="email" defaultValue={settings.email} type="email" className="admin-input w-full" />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-medium text-slate-700">Website</label>
              <input name="website" defaultValue={settings.website} className="admin-input w-full" />
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">Social links</h2>
          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Facebook</label>
              <input name="facebook_url" defaultValue={settings.facebookUrl ?? ""} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Instagram</label>
              <input name="instagram_url" defaultValue={settings.instagramUrl ?? ""} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">YouTube</label>
              <input name="youtube_url" defaultValue={settings.youtubeUrl ?? ""} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">LinkedIn</label>
              <input name="linkedin_url" defaultValue={settings.linkedinUrl ?? ""} className="admin-input w-full" />
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-slate-900">Meta &amp; SEO</h2>
          <div className="mt-4 grid gap-4">
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Meta title</label>
              <input name="meta_title" defaultValue={settings.metaTitle} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Meta description</label>
              <textarea name="meta_description" rows={3} className="admin-input w-full">{settings.metaDescription}</textarea>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button className="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
            {t("common.save")}
          </button>
          <span className="text-xs text-slate-400">Save is wired in the Laravel variant; the Node variant keeps settings read-only for now.</span>
        </div>
      </form>
    </div>
  );
}
