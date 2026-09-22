"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { helpIconColor, type HelpSection, type HelpStrings } from "@/lib/help-content";

/**
 * Browseable help docs — mirrors `dashboard/help/index.blade.php`: live search,
 * quick-nav pills and an accordion of numbered steps per section.
 */
export function HelpScreen({ sections, strings }: { sections: HelpSection[]; strings: HelpStrings }) {
  const [query, setQuery] = useState("");
  const [openKey, setOpenKey] = useState<string | null>(null);
  const [feedback, setFeedback] = useState<string | null>(null);

  const results = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return sections.map((s) => ({ section: s, match: true }));
    return sections.map((s) => {
      const haystack = [s.title, s.description, ...s.steps].join(" ").toLowerCase();
      return { section: s, match: haystack.includes(q) };
    });
  }, [query, sections]);

  const visibleKeys = new Set(results.filter((r) => r.match).map((r) => r.section.key));
  const noResults = results.length > 0 && visibleKeys.size === 0;

  const toggle = (key: string) => setOpenKey((current) => (current === key ? null : key));

  return (
    <div>
      <div className="mb-6">
        <div className="mb-3 flex items-center gap-1.5 text-xs text-slate-500">
          <Link href="/dashboard" className="hover:text-brand-600">Dashboard</Link>
          <span>/</span>
          <span className="text-slate-700">Help &amp; Documentation</span>
        </div>
        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Help &amp; Documentation</h1>
        <p className="mt-1 text-sm text-slate-600">{strings.pageDescription}</p>
      </div>

      <div className="relative mb-8 max-w-xl">
        <svg className="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={strings.searchPlaceholder}
          className="w-full rounded-xl border border-slate-200 bg-white py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
        />
      </div>

      <div className="mb-8 flex flex-wrap gap-2">
        {sections.map((section) => (
          <a
            key={section.key}
            href={`#help-${section.key}`}
            onClick={(e) => {
              e.preventDefault();
              if (!visibleKeys.has(section.key)) return;
              setOpenKey(section.key);
              requestAnimationFrame(() => document.getElementById(`help-${section.key}`)?.scrollIntoView({ behavior: "smooth", block: "start" }));
            }}
            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-brand-600"
          >
            {section.title}
          </a>
        ))}
      </div>

      {noResults && (
        <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-6 text-center text-sm text-amber-700">
          {strings.noResults}
        </div>
      )}

      <div className="space-y-4">
        {results.map(({ section, match }) => {
          const open = openKey === section.key;
          const color = helpIconColor(section.key);
          return (
            <div
              key={section.key}
              id={`help-${section.key}`}
              className={`overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition dark:border-slate-700 dark:bg-slate-800 ${match ? "" : "hidden"}`}
            >
              <button
                type="button"
                onClick={() => toggle(section.key)}
                className="flex w-full items-center gap-4 px-6 py-5 text-left transition hover:bg-slate-50"
              >
                <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${color.bg} ${color.text}`}>
                  <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" dangerouslySetInnerHTML={{ __html: color.icon }} />
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block text-base font-semibold text-slate-900">{section.title}</span>
                  <span className="mt-0.5 block truncate text-sm text-slate-500">{section.description}</span>
                </span>
                <svg
                  className={`h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200 ${open ? "rotate-180" : ""}`}
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              {open && (
                <div className="border-t border-slate-100 px-6 py-5 dark:border-slate-700">
                  <ol className="space-y-3">
                    {section.steps.map((step, i) => (
                      <li key={i} className="flex items-start gap-3">
                        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-900/40 dark:text-brand-400">
                          {i + 1}
                        </span>
                        <span className="pt-0.5 text-sm text-slate-700">{step}</span>
                      </li>
                    ))}
                  </ol>

                  <div className="mt-6 flex items-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
                    {feedback ? (
                      <span className="text-xs font-medium text-emerald-600">Thank you for your feedback!</span>
                    ) : (
                      <>
                        <span className="text-xs text-slate-500">{strings.wasThisHelpful}</span>
                        <button
                          type="button"
                          onClick={() => setFeedback("yes")}
                          className="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-600"
                        >
                          {strings.yes}
                        </button>
                        <button
                          type="button"
                          onClick={() => setFeedback("no")}
                          className="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                        >
                          {strings.no}
                        </button>
                      </>
                    )}
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}