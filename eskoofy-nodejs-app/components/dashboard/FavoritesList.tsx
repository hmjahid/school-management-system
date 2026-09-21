"use client";

import Link from "next/link";
import { useState } from "react";

export interface Favorite {
  url: string;
  label: string;
}

/**
 * Pinned favorites — mirrors the app's `[data-favorites-group]` block in
 * `partials/dashboard/sidebar.blade.php` (per-user rows from `dashboard_favorites`,
 * unpin via `dashboard.favorites.toggle`).
 */
export function FavoritesList({ favorites, labels }: { favorites: Favorite[]; labels: { title: string; unpin: string } }) {
  const [items, setItems] = useState(favorites);
  if (items.length === 0) return null;

  async function unpin(url: string) {
    setItems((current) => current.filter((fav) => fav.url !== url));
    await fetch("/dashboard/favorites/toggle", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ url }).toString(),
    }).catch(() => {});
  }

  return (
    <div className="mb-3">
      <p className="mb-2 flex items-center gap-1.5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
        <svg className="h-3.5 w-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
        {labels.title}
      </p>
      <div className="space-y-0.5">
        {items.map((fav) => (
          <div key={fav.url} className="group relative">
            <Link
              href={fav.url}
              className="block rounded-lg py-2 pl-2 pr-8 text-sm text-slate-600 transition hover:text-brand-600 dark:text-slate-400 dark:hover:text-brand-400"
            >
              <span className="truncate">{fav.label || fav.url}</span>
            </Link>
            <button
              type="button"
              onClick={() => void unpin(fav.url)}
              title={labels.unpin}
              aria-label={labels.unpin}
              className="absolute right-1 top-1/2 hidden -translate-y-1/2 rounded p-1 text-slate-400 transition hover:bg-slate-100 hover:text-red-500 group-hover:block dark:hover:bg-slate-700"
            >
              <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}
