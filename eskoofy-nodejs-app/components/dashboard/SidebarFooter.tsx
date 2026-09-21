"use client";

import { useEffect, useState } from "react";
import { logoutAction } from "@/app/(site)/login/actions";

interface InstallPromptEvent extends Event {
  prompt: () => Promise<void>;
}

/**
 * Sidebar footer — mirrors the app's Install App / Dark mode / Logout block in
 * `partials/dashboard/sidebar.blade.php` (same localStorage key `school-dark-mode`).
 */
export function SidebarFooter({
  labels,
}: {
  labels: { install: string; darkMode: string; logout: string };
}) {
  const [installEvent, setInstallEvent] = useState<InstallPromptEvent | null>(null);
  const [dark, setDark] = useState(false);

  useEffect(() => {
    setDark(document.documentElement.classList.contains("dark"));
    const handler = (event: Event) => {
      event.preventDefault();
      setInstallEvent(event as InstallPromptEvent);
    };
    window.addEventListener("beforeinstallprompt", handler);
    return () => window.removeEventListener("beforeinstallprompt", handler);
  }, []);

  function toggleDark() {
    const next = !document.documentElement.classList.contains("dark");
    document.documentElement.classList.toggle("dark", next);
    localStorage.setItem("school-dark-mode", next ? "1" : "0");
    setDark(next);
  }

  return (
    <div className="mt-auto border-t border-slate-200/80 p-3 dark:border-slate-700/80">
      {installEvent ? (
        <button
          type="button"
          onClick={() => void installEvent.prompt()}
          className="mb-2 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700"
        >
          <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>{labels.install}</span>
        </button>
      ) : null}

      <button
        type="button"
        onClick={toggleDark}
        className="mb-2 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700"
      >
        <svg className={`h-4 w-4 ${dark ? "hidden" : ""}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <svg className={`h-4 w-4 ${dark ? "" : "hidden"}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <span>{labels.darkMode}</span>
      </button>

      <form action={logoutAction}>
        <button
          type="submit"
          className="flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
        >
          <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clipRule="evenodd" />
          </svg>
          {labels.logout}
        </button>
      </form>
    </div>
  );
}
