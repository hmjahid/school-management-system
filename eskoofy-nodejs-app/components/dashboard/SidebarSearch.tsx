"use client";

import { useRef, useState } from "react";

/**
 * Collapsible sidebar menu search — mirrors the app's
 * `[data-sidebar-search-toggle]` / `[data-sidebar-search-input]` behaviour in
 * `partials/dashboard/sidebar.blade.php`: filters nav items, groups and section
 * headers as you type, and restores everything when cleared.
 */
export function SidebarSearch({ labels }: { labels: { search: string; type: string } }) {
  const [open, setOpen] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  function filter(query: string) {
    const nav = document.querySelector("[data-sidebar-nav]");
    if (!nav) return;
    const q = query.toLowerCase().trim();

    nav.querySelectorAll<HTMLElement>("[data-nav-item]").forEach((el) => {
      el.style.display = !q || el.textContent?.toLowerCase().includes(q) ? "" : "none";
    });

    nav.querySelectorAll<HTMLDetailsElement>("[data-nav-group]").forEach((group) => {
      const hasMatch = Array.from(group.querySelectorAll<HTMLElement>("[data-nav-item]")).some(
        (el) => el.style.display !== "none",
      );
      if (!q) {
        group.style.display = "";
        return;
      }
      group.style.display = hasMatch ? "" : "none";
      if (hasMatch) group.open = true;
    });

    nav.querySelectorAll<HTMLElement>("[data-nav-section]").forEach((section) => {
      const hasMatch = Array.from(section.querySelectorAll<HTMLElement>("[data-nav-item]")).some(
        (el) => el.style.display !== "none",
      );
      section.style.display = !q || hasMatch ? "" : "none";
    });
  }

  function close() {
    setOpen(false);
    if (inputRef.current) inputRef.current.value = "";
    filter("");
  }

  if (!open) {
    return (
      <div className="relative mb-4">
        <button
          type="button"
          onClick={() => {
            setOpen(true);
            requestAnimationFrame(() => inputRef.current?.focus());
          }}
          className="flex w-full items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm text-slate-400 transition hover:border-slate-300 dark:border-slate-600 dark:bg-slate-700/50 dark:text-slate-500 dark:hover:border-slate-500"
        >
          <svg className="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <span className="flex-1 truncate">{labels.search}</span>
        </button>
      </div>
    );
  }

  return (
    <div className="relative mb-4">
      <input
        ref={inputRef}
        type="text"
        placeholder={labels.type}
        onChange={(event) => filter(event.target.value)}
        onBlur={() => {
          if (!inputRef.current?.value.trim()) close();
        }}
        onKeyDown={(event) => {
          if (event.key === "Escape") close();
        }}
        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-500"
      />
    </div>
  );
}
