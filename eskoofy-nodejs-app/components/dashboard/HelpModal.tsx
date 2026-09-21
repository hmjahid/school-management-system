"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

export interface HelpTopic {
  title: string;
  description: string;
  url: string;
}

/**
 * Contextual help modal — mirrors `partials/dashboard/help-modal.blade.php`
 * (opens from `[data-help-modal-open]`, topic list from the dashboard help data).
 */
export function HelpModal({ topics, labels }: { topics: HelpTopic[]; labels: { title: string; close: string } }) {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const openHelp = () => setOpen(true);
    window.addEventListener("esk:open-help", openHelp);
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") setOpen(false);
    };
    document.addEventListener("keydown", onKey);
    return () => {
      window.removeEventListener("esk:open-help", openHelp);
      document.removeEventListener("keydown", onKey);
    };
  }, []);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center" role="dialog" aria-modal="true">
      <div className="modal-backdrop" onClick={() => setOpen(false)} />
      <div className="modal-panel max-w-lg">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-slate-900 dark:text-slate-100">{labels.title}</h3>
          <button
            type="button"
            onClick={() => setOpen(false)}
            aria-label={labels.close}
            className="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700"
          >
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div className="space-y-2">
          {topics.map((topic) => (
            <Link
              key={topic.url}
              href={topic.url}
              onClick={() => setOpen(false)}
              className="block rounded-xl border border-slate-200 px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50/40 dark:border-slate-700 dark:hover:bg-slate-700/40"
            >
              <span className="block text-sm font-semibold text-slate-900 dark:text-slate-100">{topic.title}</span>
              <span className="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{topic.description}</span>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}
