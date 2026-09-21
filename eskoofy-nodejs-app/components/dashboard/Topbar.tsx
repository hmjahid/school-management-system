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
  pin: string;
  unpin: string;
}

const LOCALE_LABELS: Record<string, string> = { en: "English", bn: "বাংলা" };

/**
 * Dashboard topbar — mirrors `partials/dashboard/topbar.blade.php`: mobile
 * sidebar toggle, live timezone clock, website link, search (command palette),
 * locale switcher, pin-page, contextual help, dark-mode toggle, notifications
 * and the user dropdown.
 */
export function Topbar({
  user,
  pathname,
  locale,
  locales,
  favorited,
  unreadCount,
  timezone,
  labels,
}: {
  user: { name: string; role: string };
  pathname: string;
  locale: string;
  locales: string[];
  favorited: boolean;
  unreadCount: number;
  timezone: string;
  labels: TopbarLabels;
}) {
  const [langOpen, setLangOpen] = useState(false);
  const [userOpen, setUserOpen] = useState(false);
  const [dark, setDark] = useState(false);
  const [clock, setClock] = useState("");
  const [pinned, setPinned] = useState(favorited);
  const rootRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const stored = localStorage.getItem("school-dark-mode");
    const prefers = window.matchMedia?.("(prefers-color-scheme: dark)").matches ?? false;
    const initial = stored === "1" ? true : stored === "0" ? false : prefers;
    setDark(initial);
    document.documentElement.classList.toggle("dark", initial);
  }, []);

  useEffect(() => {
    const tick = () => {
      try {
        setClock(
          new Intl.DateTimeFormat("en-US", {
            timeZone: timezone,
            weekday: "long",
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: true,
          }).format(new Date()),
        );
      } catch {
        setClock(new Date().toLocaleString());
      }
    };
    tick();
    const id = window.setInterval(tick, 1000);
    return () => window.clearInterval(id);
  }, [timezone]);

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

  useEffect(() => {
    const button = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebar-overlay");
    if (!button || !sidebar || !overlay) return;
    const toggle = () => {
      sidebar.classList.toggle("-translate-x-full");
      sidebar.classList.toggle("lg:translate-x-0");
      overlay.classList.toggle("hidden");
      document.body.classList.toggle("overflow-hidden");
    };
    const closeDrawer = () => {
      sidebar.classList.add("-translate-x-full");
      sidebar.classList.remove("lg:translate-x-0");
      overlay.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    };
    button.addEventListener("click", toggle);
    overlay.addEventListener("click", closeDrawer);
    return () => {
      button.removeEventListener("click", toggle);
      overlay.removeEventListener("click", closeDrawer);
    };
  }, []);

  function toggleDark() {
    const next = !dark;
    setDark(next);
    document.documentElement.classList.toggle("dark", next);
    localStorage.setItem("school-dark-mode", next ? "1" : "0");
  }

  async function togglePin() {
    const next = !pinned;
    setPinned(next);
    await fetch("/dashboard/favorites/toggle", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ url: pathname }).toString(),
    }).catch(() => setPinned(!next));
  }

  const iconButton =
    "rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200";

  return (
    <div ref={rootRef} className="flex flex-1 items-center gap-2">
      <button type="button" id="sidebar-toggle" className={`${iconButton} -ml-2 lg:hidden`} aria-label="Toggle sidebar">
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

      <Link href="/" target="_blank" className={`${iconButton} hidden items-center gap-1.5 sm:flex`} title={labels.website}>
        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
        </svg>
        <span className="hidden text-sm font-medium md:inline">{labels.website}</span>
      </Link>

      <button
        type="button"
        className={iconButton}
        title={labels.search}
        aria-label={labels.search}
        onClick={() => window.dispatchEvent(new Event("esk:open-search"))}
      >
        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>

      {locales.length > 2 ? (
        <select
          value={locale}
          onChange={(event) => {
            window.location.href = `/dashboard/locale/${event.target.value}`;
          }}
          aria-label={labels.locale}
          className="appearance-none rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-7 text-sm font-medium text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
        >
          {locales.map((loc) => (
            <option key={loc} value={loc}>
              {LOCALE_LABELS[loc] ?? loc.toUpperCase()}
            </option>
          ))}
        </select>
      ) : (
        <div className="relative">
          <button
            type="button"
            className={iconButton}
            title={labels.locale}
            aria-label={labels.locale}
            onClick={(event) => {
              event.stopPropagation();
              setLangOpen((value) => !value);
              setUserOpen(false);
            }}
          >
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
            </svg>
          </button>
          {langOpen ? (
            <div className="absolute right-0 top-full z-50 mt-2 w-36 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-slate-700 dark:bg-slate-800">
              {locales.map((loc) => (
                <Link
                  key={loc}
                  href={`/dashboard/locale/${loc}`}
                  className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm ${
                    locale === loc
                      ? "bg-brand-50 font-semibold text-brand-700 dark:bg-brand-900/20 dark:text-brand-400"
                      : "text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700"
                  }`}
                >
                  {locale === loc ? (
                    <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4L8.5 12l6.8-6.7a1 1 0 011.4 0z" clipRule="evenodd" />
                    </svg>
                  ) : null}
                  <span className="flex-1">{LOCALE_LABELS[loc] ?? loc.toUpperCase()}</span>
                </Link>
              ))}
            </div>
          ) : null}
        </div>
      )}

      <button
        type="button"
        onClick={() => void togglePin()}
        title={pinned ? labels.unpin : labels.pin}
        aria-label={pinned ? labels.unpin : labels.pin}
        aria-pressed={pinned}
        className="rounded-lg p-2 text-slate-500 transition hover:bg-amber-100 hover:text-amber-600 dark:text-slate-400 dark:hover:bg-amber-900/30 dark:hover:text-amber-400"
      >
        <svg className={`h-5 w-5 ${pinned ? "text-amber-500" : ""}`} fill={pinned ? "currentColor" : "none"} stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
      </button>

      <button
        type="button"
        className={iconButton}
        title={labels.help}
        aria-label={labels.help}
        onClick={() => window.dispatchEvent(new Event("esk:open-help"))}
      >
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
        {unreadCount > 0 ? (
          <span className="absolute right-1 top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white">
            {unreadCount}
          </span>
        ) : null}
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
          <span className="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white ring-2 ring-slate-200 dark:ring-slate-600">
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
