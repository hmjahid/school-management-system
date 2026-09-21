"use client";

import Link from "next/link";
import { useCallback, useEffect, useRef, useState } from "react";

interface SearchResult {
  id: number;
  type: string;
  name: string;
  subtitle?: string;
  url: string;
}

const TYPE_ICONS: Record<string, string> = {
  student: "M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z",
  teacher: "M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z",
  class: "M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z",
  notice: "M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z",
  fee: "M10 2a8 8 0 100 16 8 8 0 000-16zm6 9H8.914l1.293 1.293a1 1 0 01-1.414 1.414L5.586 10.41a1 1 0 010-1.414l3.207-3.207a1 1 0 011.414 1.414L9.414 8H16a1 1 0 110 2z",
  payment: "M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm13 4H3v6h14V8z",
  link: "M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5z",
};

export interface CommandPaletteLabels {
  placeholder: string;
  start: string;
  none: string;
  failed: string;
  esc: string;
}

/**
 * Command-palette dashboard search — mirrors the app's
 * `#dashboard-search-modal` (Ctrl/Cmd+K or `/`, debounced GET to
 * `dashboard.search`).
 */
export function CommandPalette({ labels }: { labels: CommandPaletteLabels }) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<SearchResult[]>([]);
  const [state, setState] = useState<"idle" | "loading" | "done" | "error">("idle");
  const inputRef = useRef<HTMLInputElement>(null);
  const timer = useRef<number | null>(null);

  const close = useCallback(() => {
    setOpen(false);
    setQuery("");
    setResults([]);
    setState("idle");
  }, []);

  useEffect(() => {
    const openSearch = () => {
      setOpen(true);
      requestAnimationFrame(() => inputRef.current?.focus());
    };
    window.addEventListener("esk:open-search", openSearch);

    const onKey = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "k") {
        event.preventDefault();
        openSearch();
      }
      if (event.key === "Escape") close();
      if (event.key === "/" && !event.ctrlKey && !event.metaKey && !event.altKey) {
        const target = event.target as HTMLElement | null;
        const tag = target?.tagName?.toLowerCase() ?? "";
        const editable = target?.isContentEditable || ["input", "textarea", "select"].includes(tag);
        if (!editable) {
          event.preventDefault();
          openSearch();
        }
      }
    };
    document.addEventListener("keydown", onKey);
    return () => {
      window.removeEventListener("esk:open-search", openSearch);
      document.removeEventListener("keydown", onKey);
    };
  }, [close]);

  useEffect(() => {
    if (timer.current) window.clearTimeout(timer.current);
    const q = query.trim();
    if (q.length < 2) {
      setResults([]);
      setState("idle");
      return;
    }
    setState("loading");
    timer.current = window.setTimeout(async () => {
      try {
        const response = await fetch(`/dashboard/search?q=${encodeURIComponent(q)}`, {
          headers: { Accept: "application/json" },
        });
        const payload = (await response.json()) as { data?: SearchResult[] };
        setResults(payload.data ?? []);
        setState("done");
      } catch {
        setResults([]);
        setState("error");
      }
    }, 300);
    return () => {
      if (timer.current) window.clearTimeout(timer.current);
    };
  }, [query]);

  if (!open) return null;

  return (
    <div id="dashboard-search-modal" className="fixed inset-0 z-[100]" role="dialog" aria-modal="true">
      <div className="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onClick={close} />
      <div className="relative mx-auto mt-[10vh] w-full max-w-xl px-4">
        <div className="rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800">
          <div className="flex items-center gap-3 border-b border-slate-200 px-5 py-3 dark:border-slate-700">
            <svg className="h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              ref={inputRef}
              type="text"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder={labels.placeholder}
              autoComplete="off"
              className="flex-1 bg-transparent text-sm text-slate-900 placeholder-slate-400 focus:outline-none dark:text-slate-100 dark:placeholder-slate-500"
            />
            <kbd className="hidden rounded border border-slate-300 bg-slate-100 px-1.5 py-0.5 text-[0.65rem] font-medium text-slate-500 sm:inline dark:border-slate-600 dark:bg-slate-700 dark:text-slate-400">
              {labels.esc}
            </kbd>
          </div>
          <div className="max-h-[50vh] overflow-y-auto p-2">
            {state === "idle" ? (
              <p className="px-3 py-6 text-center text-sm text-slate-400">{labels.start}</p>
            ) : state === "error" ? (
              <p className="px-3 py-6 text-center text-sm text-red-400">{labels.failed}</p>
            ) : state === "done" && results.length === 0 ? (
              <p className="px-3 py-6 text-center text-sm text-slate-400">{labels.none}</p>
            ) : (
              results.map((item, index) => (
                <Link
                  key={`${item.type}-${item.id}-${index}`}
                  href={item.url}
                  onClick={close}
                  className="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700"
                >
                  <svg className="h-5 w-5 shrink-0 text-brand-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d={TYPE_ICONS[item.type] ?? TYPE_ICONS.link} />
                  </svg>
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-medium text-slate-900 dark:text-slate-100">{item.name}</span>
                    {item.subtitle ? (
                      <span className="block truncate text-xs text-slate-500 dark:text-slate-400">{item.subtitle}</span>
                    ) : null}
                  </span>
                  <span className="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[0.6rem] font-semibold uppercase text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                    {item.type}
                  </span>
                </Link>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
