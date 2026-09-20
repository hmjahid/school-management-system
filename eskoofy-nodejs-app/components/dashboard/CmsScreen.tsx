import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";

/**
 * CMS screens — mirror the app's `dashboard/cms/pages.blade.php` (pages grid)
 * and `dashboard/cms/edit.blade.php` (per-page field editor).
 */

export async function CmsPages() {
  const pages = await prisma.website_contents.findMany({ orderBy: { page: "asc" } });

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Website pages</h1>
          <p className="mt-1 max-w-2xl text-sm text-slate-500">
            Pick a page to edit its text and images. Every page has a fixed structure, so you only ever see fields that matter for that page.
          </p>
        </div>
        <Link href="/dashboard/settings/global-labels" className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700">
          Global labels
        </Link>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {pages.map((p) => (
          <Link key={p.id} href={`/dashboard/cms/${p.page}/edit`} className="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-md">
            <h3 className="text-base font-semibold text-slate-900 capitalize group-hover:text-brand-700">{String(p.page).replace(/-/g, " ")}</h3>
            <p className="mt-1 flex-1 text-sm text-slate-500">{String(p.title ?? "No title yet")}</p>
            <span className="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand-600">
              Edit page
              <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
            </span>
          </Link>
        ))}
        {pages.length === 0 ? (
          <div className="col-span-full rounded-xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-400">
            No website pages yet.
          </div>
        ) : null}
      </div>
    </div>
  );
}

export async function CmsEdit({ page }: { page: string }) {
  const row = await prisma.website_contents.findUnique({ where: { page } });

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 capitalize">{page.replace(/-/g, " ")}</h1>
          <p className="mt-1 text-sm text-slate-500">Edit the text and images for this page.</p>
        </div>
        <Link href="/dashboard/cms/pages" className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">← All pages</Link>
      </div>

      <form method="post" action={`/dashboard/cms/${page}/save`} className="max-w-3xl space-y-6">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">Page title</label>
            <input name="title" defaultValue={row?.title ?? ""} className="admin-input w-full" />
          </div>
          <div className="mt-4">
            <label className="mb-1 block text-sm font-medium text-slate-700">Content</label>
            <textarea name="content" rows={6} defaultValue={row?.content ?? ""} className="admin-input w-full" />
          </div>
          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Meta description</label>
              <textarea name="meta_description" rows={2} defaultValue={row?.meta_description ?? ""} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Meta keywords</label>
              <input name="meta_keywords" defaultValue={row?.meta_keywords ?? ""} className="admin-input w-full" />
            </div>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <button className="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">{t("common.save")}</button>
          <span className="text-xs text-slate-400">Save is wired in the Laravel variant; the Node variant keeps the editor read-only.</span>
        </div>
      </form>
    </div>
  );
}
