"use client";

import { useState } from "react";

interface LoadMoreItem {
  id?: unknown;
  title?: unknown;
  slug?: unknown;
  image_url?: unknown;
  published_at?: unknown;
}

function fmtDate(value: unknown): string {
  if (!value) return "";
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
}

export function NewsLoadMore({ items }: { items: LoadMoreItem[] }) {
  const [expanded, setExpanded] = useState(false);
  const visible = expanded ? items : items.slice(0, 9);

  return (
    <>
      <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        {visible.map((item) => (
          <a
            key={String(item.id ?? "")}
            href={`/news/${String(item.slug ?? "")}`}
            className="group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl reveal"
          >
            <div className="relative h-48 overflow-hidden bg-slate-200">
              {item.image_url ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={String(item.image_url)} alt="" className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />
              ) : (
                <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-50">
                  <svg className="h-10 w-10 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
                  </svg>
                </div>
              )}
            </div>
            <div className="p-5">
              {item.published_at ? <time className="text-xs text-slate-500">{fmtDate(item.published_at)}</time> : null}
              <h3 className="mt-2 text-lg font-semibold text-slate-900 transition-colors group-hover:text-blue-600">{String(item.title ?? "")}</h3>
            </div>
          </a>
        ))}
      </div>
      {!expanded && items.length > 9 ? (
        <div className="mt-12 text-center reveal">
          <button
            type="button"
            onClick={() => setExpanded(true)}
            className="inline-flex items-center gap-2 rounded-xl border-2 border-blue-600 bg-white px-8 py-3 text-sm font-semibold text-blue-700 transition-all hover:bg-blue-50"
          >
            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Load More
          </button>
        </div>
      ) : null}
    </>
  );
}