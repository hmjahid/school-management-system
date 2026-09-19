"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { logoutAction } from "@/app/(site)/login/actions";

export interface TopbarLabels {
  website: string;
  search: string;
  help: string;
  darkMode: string;
  notifications: string;
  dashboard: string;
  setup: string;
  profile: string;
  settings: string;
  logout: string;
  locale: string;
}

/**
 * Mirrors the app's dashboard topbar (`partials/dashboard/topbar.blade.php`):
 * website link, search, locale indicator, help, dark-mode toggle, notifications
 * and the user dropdown, plus the live clock.
 */
export function Topbar({
  user,
  labels,
}: {
  user: { name: string; role: string };
  labels: TopbarLabels;
}) {
  const [langOpen, setLangOpen] = useState(false);
  const [userOpen, setUserOpen] = useState(false);
  const [dark, setDark] = useState(false);
  const [clock, setClock] = useState("");
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const stored = localStorage.getItem("esk-dark");
    const prefers = window.matchMedia?.("(prefers-color-scheme: dark)").matches ?? false;
    const initial = stored === "1" ? true : stored === "0" ? false : prefers;
    setDark(initial);
    document.documentElement.classList.toggle("dark", initial);
  }, []);

  useEffect(() => {
    const tick = () => setClock(new Date().toLocaleString());
    tick();
    const id = window.setInterval(tick, 1000);
    return () => window.clearInterval(id);
  }, []);

  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setLangOpen(false);
        setUserOpen(false);
      }
    };
    document.addEventListener("click", onClick);
    return () => document.removeEventListener("click", onClick);
  }, []);

  // Mobile sidebar toggle (the app wires this in the topbar partial).
  useEffect(() => {
    const button = document.querySelector("[data-sidebar-toggle]");
    const sidebar = document.querySelector("[data-sidebar]");
    if (!button || !sidebar) return;
    const toggle = () => {
      sidebar.classList.toggle("-translate-x-full");
      sidebar.classList.toggle("translate-x-0");
    };
    button.addEventListener("click", toggle);
    return () => button.removeEventListener("click", toggle);
  }, []);

  function toggleDark() {
    const next = !dark;
    setDark(next);
    document.documentElement.classList.toggle("dark", next);
    localStorage.setItem("esk-dark", next ? "1" : "0");
  }

  const iconButton =
    "rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200";

  return (
    <div ref={rootRef} className="flex flex-1 items-center gap-2">
      <button
        type="button"
        data-sidebar-toggle
        className={`${iconButton} -ml-2 lg:hidden`}
        aria-label="Toggle sidebar"
      >
        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <div className="hidden items-center gap-2 text-xs text-slate-500 md:flex">
        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span suppressHydrationWarning>{clock}</span>
      </div>

      <div className="flex-1" />

      <Link href="/" className={`${iconButton} hidden items-center gap-1.5 sm:flex`} title={labels.website}>
        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3" />
        </svg>
        <span className="hidden text-sm font-medium md:inline">{labels.website}</span>
      </Link>

      <button type="button" className={iconButton} title={labels.search} aria-label={labels.search}>
        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>

      <div className="relative">
        <button
          type="button"
          className={iconButton}
          onClick={(event) => {
            event.stopPropagation();
            setLangOpen((value) => !value);
            setUserOpen(false);
          }}
          title={labels.locale}
          aria-label={labels.locale}
        >
          <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3" />
          </svg>
        </button>
        {langOpen ? (
          <div className="absolute right-0 top-full z-50 mt-2 w-36 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-slate-700 dark:bg-slate-800">
            <span className="block rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
              {labels.locale}
            </span>
          </div>
        ) : null}
      </div>

      <button type="button" className={iconButton} title={labels.help} aria-label={labels.help}>
        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </button>

      <button type="button" className={iconButton} onClick={toggleDark} title={labels.darkMode} aria-label={labels.darkMode}>
        <svg className={`h-5 w-5 ${dark ? "hidden" : ""}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <svg className={`h-5 w-5 ${dark ? "" : "hidden"}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      </button>

      <Link href="/dashboard/notifications" className={`${iconButton} relative`} title={labels.notifications} aria-label={labels.notifications}>
        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
      </Link>

      <div className="relative">
        <button
          type="button"
          className="flex items-center gap-2 rounded-lg p-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700"
          aria-expanded={userOpen}
          onClick={(event) => {
            event.stopPropagation();
            setUserOpen((value) => !value);
            setLangOpen(false);
          }}
        >
          <span className="grid h-7 w-7 place-items-center rounded-full bg-blue-600 text-xs font-bold text-white">
            {user.name.slice(0, 1).toUpperCase()}
          </span>
          <span className="hidden md:inline">{user.name}</span>
          <svg className="hidden h-4 w-4 text-slate-400 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        {userOpen ? (
          <div className="absolute right-0 top-full z-50 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-slate-700 dark:bg-slate-800" role="menu">
            <MenuLink href="/dashboard">{labels.dashboard}</MenuLink>
            <MenuLink href="/dashboard/onboarding">{labels.setup}</MenuLink>
            <MenuLink href="/dashboard/profile">{labels.profile}</MenuLink>
            <MenuLink href="/dashboard/settings">{labels.settings}</MenuLink>
            <hr className="my-1 border-slate-100 dark:border-slate-700" />
            <form action={logoutAction}>
              <button
                type="submit"
                role="menuitem"
                className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
              >
                {labels.logout}
              </button>
            </form>
          </div>
        ) : null}
      </div>
    </div>
  );
}

function MenuLink({ href, children }: { href: string; children: React.ReactNode }) {
  return (
    <Link
      href={href}
      role="menuitem"
      className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"
    >
      {children}
    </Link>
  );
}
