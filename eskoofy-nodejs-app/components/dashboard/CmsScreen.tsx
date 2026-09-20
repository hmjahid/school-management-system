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

/** Renders a typed CMS field widget (mirrors dashboard/cms/fields/*). */
function FieldWidget({ type, name, value, label }: { type: string; name: string; value: unknown; label?: string }) {
  const val = value === null || value === undefined ? "" : String(value);
  const base = "admin-input w-full";
  switch (type) {
    case "textarea":
      return (
        <div>
          {label ? <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label> : null}
          <textarea name={name} rows={4} className={base} defaultValue={val} />
        </div>
      );
    case "select":
      return (
        <div>
          {label ? <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label> : null}
          <select name={name} className="admin-select w-full" defaultValue={val}>
            <option value="">Select…</option>
          </select>
        </div>
      );
    case "image":
      return (
        <div>
          {label ? <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label> : null}
          <input name={name} className={base} defaultValue={val} placeholder="Image URL or path" />
        </div>
      );
    case "hero":
      return (
        <div className="space-y-3 rounded-lg border border-amber-200 bg-amber-50/40 p-4">
          <p className="text-xs font-semibold uppercase tracking-wide text-amber-700">{label ?? "Hero"}</p>
          {val ? <textarea name={name} rows={4} className={base} defaultValue={val} /> : <input name={name} className={base} defaultValue="" placeholder="Headline" />}
        </div>
      );
    default:
      return (
        <div>
          {label ? <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label> : null}
          <input name={name} className={base} defaultValue={val} />
        </div>
      );
  }
}

/** Renders a content block as a typed field group (mirrors cms/fields/*). */
function renderFields(content: unknown, prefix: string): React.ReactNode[] {
  const nodes: React.ReactNode[] = [];
  if (content && typeof content === "object") {
    for (const [key, value] of Object.entries(content)) {
      const name = `${prefix}[${key}]`;
      const type = Array.isArray(value) ? "list" : typeof value === "object" && value !== null ? "group" : "text";
      if (type === "list") {
        nodes.push(
          <div key={name} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{key}</p>
            {(value as unknown[]).map((item, i) => renderFields(item, `${name}[${i}]`))}
          </div>,
        );
      } else if (type === "group") {
        nodes.push(
          <div key={name} className="rounded-lg border border-brand-100 bg-brand-50/40 p-4">
            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-brand-700">{key}</p>
            {renderFields(value, name)}
          </div>,
        );
      } else {
        nodes.push(<FieldWidget key={name} type={type} name={name} value={value} label={key} />);
      }
    }
  } else if (content !== undefined && content !== null) {
    nodes.push(<FieldWidget key={prefix} type="textarea" name={prefix} value={content} />);
  }
  return nodes;
}

export async function CmsEdit({ page }: { page: string }) {
  const row = await prisma.website_contents.findUnique({ where: { page } });
  let content: unknown = row?.content ?? "";
  try { content = JSON.parse(String(row?.content ?? "")); } catch { /* plain text */ }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
          <Link href="/dashboard/cms/pages" className="text-sm font-medium text-brand-600 hover:text-brand-800">← Back to pages</Link>
          <h1 className="mt-2 flex items-center gap-2 text-2xl font-bold text-slate-900">
            <span>Edit: {page.replace(/-/g, " ")}</span>
            <span className="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-600">{page}</span>
          </h1>
        </div>
      </div>

      <form method="post" action={`/dashboard/cms/${page}/save`} className="max-w-3xl space-y-6" encType="multipart/form-data">
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Page title</label>
              <input name="title" defaultValue={row?.title ?? ""} className="admin-input w-full" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">Meta description</label>
              <input name="meta_description" defaultValue={row?.meta_description ?? ""} className="admin-input w-full" />
            </div>
            <div className="sm:col-span-2">
              <label className="mb-1 block text-sm font-medium text-slate-700">Meta keywords</label>
              <input name="meta_keywords" defaultValue={row?.meta_keywords ?? ""} className="admin-input w-full" />
            </div>
          </div>
        </div>

        <div className="space-y-6">
          {renderFields(content, "content")}
          {!content || (typeof content === "string" && content === "") ? (
            <textarea name="content" rows={6} className="admin-input w-full" placeholder="Page content…" />
          ) : null}
        </div>

        <div className="flex items-center gap-3">
          <button className="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Save</button>
          <span className="text-xs text-slate-400">Save is wired in the Laravel variant; the Node variant keeps the editor read-only.</span>
        </div>
      </form>
    </div>
  );
}
