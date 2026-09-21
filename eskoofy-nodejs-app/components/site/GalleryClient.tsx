"use client";

import { useState } from "react";

export interface GalleryImage {
  id: string;
  title: string;
  description: string | null;
  src: string | null;
  category: string;
}

export function categorySlug(value: string): string {
  return value.trim().toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "") || "general";
}

export default function GalleryClient({
  items,
  emptyText,
}: {
  items: GalleryImage[];
  emptyText: string;
}) {
  const [active, setActive] = useState("all");
  const [lightbox, setLightbox] = useState<GalleryImage | null>(null);

  const categories = [...new Set(items.map((item) => categorySlug(item.category)))].sort();

  const visible = active === "all" ? items : items.filter((item) => categorySlug(item.category) === active);

  return (
    <>
      {categories.length > 0 ? (
        <div className="mb-10 flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => setActive("all")}
            className={`rounded-full px-5 py-2 text-sm font-semibold transition ${active === "all" ? "bg-blue-600 text-white" : "bg-slate-100 text-slate-700 hover:bg-blue-50 hover:text-blue-700"}`}
          >
            All
          </button>
          {categories.map((category) => (
            <button
              key={category}
              type="button"
              onClick={() => setActive(category)}
              className={`rounded-full px-5 py-2 text-sm font-medium capitalize transition ${active === category ? "bg-blue-600 text-white" : "bg-slate-100 text-slate-700 hover:bg-blue-50 hover:text-blue-700"}`}
            >
              {category}
            </button>
          ))}
        </div>
      ) : null}

      {items.length === 0 ? (
        <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
          <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p className="mt-4 text-sm text-slate-500">{emptyText}</p>
        </div>
      ) : (
        <div className="columns-1 space-y-6 gap-6 sm:columns-2 lg:columns-3 xl:columns-4">
          {visible.map((item) => (
            <figure
              key={item.id}
              className="group relative overflow-hidden rounded-2xl bg-slate-100 shadow-md ring-1 ring-slate-100 break-inside-avoid transition-all duration-300 hover:shadow-xl"
              style={{ display: active === "all" ? undefined : "block" }}
            >
              {item.src ? (
                <button type="button" onClick={() => setLightbox(item)} className="block h-full w-full text-left">
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img src={item.src} alt={item.title} className="w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />
                  <div className="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-black/60 via-black/10 to-transparent p-5 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    <h3 className="text-lg font-semibold text-white">{item.title}</h3>
                    {item.description ? <p className="mt-1 text-sm text-white/80">{item.description}</p> : null}
                    <span className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-white/70">
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      Click to view
                    </span>
                  </div>
                </button>
              ) : (
                <div className="flex aspect-[4/3] w-full items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-50">
                  <svg className="h-10 w-10 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              )}
            </figure>
          ))}
        </div>
      )}

      {lightbox?.src ? (
        <div
          role="dialog"
          aria-modal="true"
          aria-label={lightbox.title}
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-6"
          onClick={() => setLightbox(null)}
        >
          <button
            type="button"
            aria-label="Close"
            className="absolute right-5 top-5 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-xl text-white transition hover:bg-white/20"
            onClick={() => setLightbox(null)}
          >
            ×
          </button>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={lightbox.src} alt={lightbox.title} className="max-h-[85vh] max-w-full rounded-xl object-contain shadow-2xl" />
          <div className="absolute inset-x-0 bottom-6 text-center text-white">
            <h3 className="text-lg font-semibold">{lightbox.title}</h3>
            {lightbox.description ? <p className="mt-1 text-sm text-white/80">{lightbox.description}</p> : null}
          </div>
        </div>
      ) : null}
    </>
  );
}