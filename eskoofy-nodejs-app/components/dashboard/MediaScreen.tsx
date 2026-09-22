"use client";

import { useMemo, useState } from "react";
import { deleteMedia, uploadMedia } from "@/app/(dashboard)/dashboard/screen-actions";

export interface MediaRow {
  id: number;
  title: string | null;
  category: string | null;
  file_path: string;
  mime_type: string | null;
  file_size: number | null;
}

function isImage(mime: string | null): boolean {
  return !!mime?.startsWith("image/");
}

function formatSize(bytes: number | null): string {
  if (!bytes) return "—";
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * Media library — mirrors `dashboard/media/index.blade.php`: upload + category
 * filter + card grid (Copy URL / Download / Delete), and an iframe "select"
 * mode that posts the picked URL to the parent window.
 */
export function MediaLibrary({
  rows,
  categories,
  select = false,
}: {
  rows: MediaRow[];
  categories: string[];
  select?: boolean;
}) {
  const [active, setActive] = useState("");
  const [copied, setCopied] = useState<number | null>(null);

  const filtered = useMemo(() => (active ? rows.filter((row) => row.category === active) : rows), [rows, active]);

  function pick(row: MediaRow) {
    if (select && window.parent) {
      window.parent.postMessage({ type: "media-selected", url: row.file_path }, "*");
      window.parent.postMessage({ type: "media-close" }, "*");
    }
  }

  async function copy(row: MediaRow) {
    try {
      await navigator.clipboard.writeText(row.file_path);
      setCopied(row.id);
      window.setTimeout(() => setCopied(null), 1500);
    } catch {
      /* clipboard unavailable */
    }
  }

  return (
    <div>
      {!select ? (
        <div className="admin-card mb-6">
          <div className="admin-card-header">
            <h2 className="text-base font-semibold text-slate-900 dark:text-slate-100">Upload media</h2>
          </div>
          <div className="admin-card-body">
            <form action={uploadMedia} className="grid gap-4 sm:grid-cols-3">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Title</label>
                <input name="title" className="admin-input" />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                <input name="category" list="media-categories" className="admin-input" placeholder="general" />
                <datalist id="media-categories">
                  {categories.map((category) => (
                    <option key={category} value={category} />
                  ))}
                </datalist>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">File</label>
                <input type="file" name="file" required className="admin-input" />
              </div>
              <div className="sm:col-span-3">
                <button type="submit" className="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-brand-700">
                  Upload
                </button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      <div className="mb-4 flex flex-wrap gap-2">
        <button
          type="button"
          onClick={() => setActive("")}
          className={`rounded-full px-3 py-1.5 text-xs font-medium ${active === "" ? "bg-brand-600 text-white" : "bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300"}`}
        >
          All
        </button>
        {categories.map((category) => (
          <button
            key={category}
            type="button"
            onClick={() => setActive(category)}
            className={`rounded-full px-3 py-1.5 text-xs font-medium ${active === category ? "bg-brand-600 text-white" : "bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300"}`}
          >
            {category}
          </button>
        ))}
      </div>

      {filtered.length === 0 ? (
        <p className="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-800">
          No media yet.
        </p>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {filtered.map((row) => (
            <div
              key={row.id}
              className={`admin-card overflow-hidden ${select ? "cursor-pointer hover:ring-2 hover:ring-brand-400" : ""}`}
              onClick={() => pick(row)}
            >
              <div className="flex h-32 items-center justify-center bg-slate-100 dark:bg-slate-700">
                {isImage(row.mime_type) ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={row.file_path} alt={row.title ?? ""} className="h-full w-full object-cover" />
                ) : (
                  <svg className="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6" />
                  </svg>
                )}
              </div>
              <div className="p-3">
                <p className="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{row.title ?? row.file_path.split("/").pop()}</p>
                <p className="mt-0.5 text-xs text-slate-400">
                  {row.category ?? "general"} · {formatSize(row.file_size)}
                </p>
                {!select ? (
                  <div className="mt-3 flex items-center gap-2 text-xs">
                    <button type="button" onClick={() => void copy(row)} className="font-medium text-brand-600 hover:underline">
                      {copied === row.id ? "Copied!" : "Copy URL"}
                    </button>
                    <a href={`/dashboard/media/${row.id}/download`} className="font-medium text-slate-500 hover:underline">
                      Download
                    </a>
                    <form action={deleteMedia} className="ml-auto">
                      <input type="hidden" name="id" value={row.id} />
                      <button type="submit" className="font-medium text-red-600 hover:underline">
                        Delete
                      </button>
                    </form>
                  </div>
                ) : null}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
