"use client";

import Link from "next/link";
import { useEffect, useRef, useState, type ReactNode } from "react";

/** Loose row shape for slider cards. */
export interface SliderCardProps {
  id?: unknown;
  name?: string;
  title?: string;
  designation?: string;
  qualification?: string;
  subjects?: unknown;
  photo?: unknown;
  phone?: string;
  image?: unknown;
  image_path?: unknown;
  caption?: string;
  href?: string;
}

/** Initials avatar, mirroring the Blade `implode('', array_map(...))` logic. */
export function initialsOf(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .map((word) => word[0] ?? "")
    .join("")
    .toUpperCase()
    .slice(0, 2);
}

/**
 * Auto-scrolling vertical notice list (mirrors the home hero `notice-scroll`
 * block + the CSS `noticeScroll` keyframes: two identical copies → -50% loop).
 */
export function NoticeScroller({
  notices,
  noticesTitle,
  viewAllHref,
  viewAllLabel,
}: {
  notices: Array<{ id: unknown; title?: unknown; content?: unknown; pinned?: unknown }>;
  noticesTitle: string;
  viewAllHref: string;
  viewAllLabel: string;
}) {
  const noticeHeight = 88;
  const gap = 10;
  const visibleCount = 4;
  const visibleHeight = visibleCount * noticeHeight + (visibleCount - 1) * gap;
  const total = notices.length;
  const scrollDuration = Math.max(8, total * 3);

  if (notices.length === 0) return null;

  const renderItem = (notice: { id: unknown; title?: unknown; content?: unknown; pinned?: unknown }, key: string) => (
    <div
      key={key}
      className="rounded-xl border border-slate-100 bg-slate-50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-slate-100"
      style={{ minHeight: noticeHeight }}
    >
      <div className="flex items-start gap-3">
        {notice.pinned ? (
          <svg className="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z" /></svg>
        ) : (
          <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500/60" />
        )}
        <div className="min-w-0 flex-1">
          <h4 className="text-sm font-semibold leading-snug text-slate-900">{String(notice.title ?? "")}</h4>
          <p className="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">{String(notice.content ?? "")}</p>
        </div>
      </div>
    </div>
  );

  return (
    <div className="rounded-2xl border border-slate-200 bg-white shadow-lg">
      <div className="flex items-center justify-between px-6 pt-5 pb-4 sm:px-7">
        <div className="flex items-center gap-2.5">
          <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50">
            <svg className="h-5 w-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clipRule="evenodd" /></svg>
          </span>
          <h3 className="text-base font-bold text-slate-900">{noticesTitle}</h3>
        </div>
      </div>

      <div className="relative px-6 pb-5 sm:px-7">
        <div className="pointer-events-none absolute inset-x-6 sm:inset-x-7 top-0 z-10 h-6 bg-gradient-to-b from-white to-transparent" />
        <div className="pointer-events-none absolute inset-x-6 sm:inset-x-7 bottom-5 z-10 h-6 bg-gradient-to-t from-white to-transparent" />

        <div className="notice-scroll-container overflow-hidden" style={{ height: visibleHeight, "--scroll-duration": `${scrollDuration}s` } as React.CSSProperties}>
          <div className="notice-scroll-content">
            {notices.map((n) => renderItem(n, `a-${String(n.id)}`))}
            {notices.map((n) => renderItem(n, `b-${String(n.id)}`))}
          </div>
        </div>
      </div>
      <div className="px-6 pb-5 sm:px-7">
        <Link href={viewAllHref} className="flex items-center justify-center gap-1.5 rounded-lg bg-slate-100 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-200 hover:text-slate-900">
          {viewAllLabel}
          <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
        </Link>
      </div>
    </div>
  );
}

/**
 * Horizontal snap slider with prev/next + autoscroll (mirrors the Blade
 * `data-*-slider` blocks and the companion JS in `home.blade.php`).
 */
export function CardSlider({
  children,
  className = "",
  hint,
}: {
  children: ReactNode;
  className?: string;
  hint?: string;
}) {
  const trackRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const track = trackRef.current;
    if (!track) return;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    let auto: ReturnType<typeof setInterval> | undefined;
    const getScrollAmount = () => {
      const w = window.innerWidth;
      if (w >= 1024) return track.clientWidth / 3 + 24;
      if (w >= 768) return track.clientWidth / 2 + 24;
      return track.clientWidth;
    };
    const step = () => {
      if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) {
        track.scrollTo({ left: 0, behavior: "smooth" });
      } else {
        track.scrollBy({ left: getScrollAmount(), behavior: "smooth" });
      }
    };
    const stop = () => auto && clearInterval(auto);
    const start = () => {
      auto = setInterval(step, 4000);
    };
    start();
    track.addEventListener("mouseenter", stop);
    track.addEventListener("touchstart", stop, { passive: true });
    return () => {
      stop();
      track.removeEventListener("mouseenter", stop);
      track.removeEventListener("touchstart", stop);
    };
  }, []);

  return (
    <div className="relative">
      <button
        type="button"
        aria-label="Previous"
        className="absolute -left-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-left-5"
        onClick={() => trackRef.current?.scrollBy({ left: -(trackRef.current.clientWidth / 3 + 24), behavior: "smooth" })}
      >
        <svg className="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" /></svg>
      </button>
      <button
        type="button"
        aria-label="Next"
        className="absolute -right-4 top-1/2 z-10 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-gray-200 transition hover:bg-gray-50 hover:shadow-xl sm:-right-5"
        onClick={() => trackRef.current?.scrollBy({ left: trackRef.current.clientWidth / 3 + 24, behavior: "smooth" })}
      >
        <svg className="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" /></svg>
      </button>
      <div
        ref={trackRef}
        className={`flex gap-6 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-4 -mx-2 px-2 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] ${className}`}
      >
        {children}
      </div>
      {hint ? (
        <p className="mt-2 flex items-center justify-center gap-1.5 text-xs text-gray-400 md:hidden">
          <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
          {hint}
        </p>
      ) : null}
    </div>
  );
}

/** Animated number (mirrors the `data-countup` hero stats). */
export function CountUp({ target, suffix = "", duration = 1600 }: { target: number; suffix?: string; duration?: number }) {
  const [value, setValue] = useState(0);
  const ref = useRef<HTMLDivElement>(null);
  const started = useRef(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      setValue(target);
      return;
    }
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting) && !started.current) {
          started.current = true;
          const startTime = performance.now();
          const tick = (now: number) => {
            const progress = Math.min(1, (now - startTime) / duration);
            setValue(Math.round(target * (1 - Math.pow(1 - progress, 3))));
            if (progress < 1) requestAnimationFrame(tick);
          };
          requestAnimationFrame(tick);
          observer.disconnect();
        }
      },
      { threshold: 0.3 },
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, [target, duration]);

  return (
    <div ref={ref} className="text-4xl font-bold">
      {value}
      {suffix}
    </div>
  );
}