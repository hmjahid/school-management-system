"use client";

import { useEffect, useState } from "react";

export interface SiteEvent {
  id: string;
  title: string;
  description: string | null;
  location: string | null;
  startDate: string | null;
  endDate: string | null;
  isVirtual: boolean;
}

function parseDate(value: string | null): Date | null {
  if (!value) return null;
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? null : d;
}

function formatFull(date: Date): string {
  return date.toLocaleDateString("en-US", { weekday: "short", month: "short", day: "numeric", year: "numeric" }) +
    " · " +
    date.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: false });
}

function CountdownLabel({ target }: { target: Date }) {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 60_000);
    return () => clearInterval(timer);
  }, []);

  const diff = target.getTime() - now;
  if (diff <= 0) return <span>Starts soon</span>;
  const days = Math.floor(diff / 86_400_000);
  const hours = Math.floor((diff % 86_400_000) / 3_600_000);
  const minutes = Math.floor((diff % 3_600_000) / 60_000);
  if (days > 0) return <span>Starts in {days}d {hours}h</span>;
  if (hours > 0) return <span>Starts in {hours}h {minutes}m</span>;
  return <span>Starts in {minutes}m</span>;
}

export default function EventsClient({
  upcoming,
  past,
}: {
  upcoming: SiteEvent[];
  past: SiteEvent[];
}) {
  const [filter, setFilter] = useState<"all" | "upcoming" | "past">("upcoming");
  const [view, setView] = useState<"grid" | "list">("grid");

  const showUpcoming = filter === "all" || filter === "upcoming";
  const showPast = filter === "all" || filter === "past";

  return (
    <>
      <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div className="flex flex-wrap gap-2">
          {(["all", "upcoming", "past"] as const).map((key) => (
            <button
              key={key}
              type="button"
              onClick={() => setFilter(key)}
              className={`rounded-full px-5 py-2 text-sm transition ${
                filter === key ? "bg-blue-600 font-semibold text-white" : "bg-slate-100 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700"
              }`}
            >
              {key === "all" ? "All" : key === "upcoming" ? "Upcoming" : "Past"}
            </button>
          ))}
        </div>
        <div className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-1">
          <button
            type="button"
            onClick={() => setView("grid")}
            aria-label="Grid view"
            className={`rounded-md px-3 py-1.5 transition ${view === "grid" ? "bg-blue-600 text-white" : "text-slate-700 hover:bg-blue-50 hover:text-blue-700"}`}
          >
            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
          </button>
          <button
            type="button"
            onClick={() => setView("list")}
            aria-label="List view"
            className={`rounded-md px-3 py-1.5 transition ${view === "list" ? "bg-blue-600 text-white" : "text-slate-700 hover:bg-blue-50 hover:text-blue-700"}`}
          >
            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      {showUpcoming ? (
        upcoming.length === 0 ? (
          <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
            <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p className="mt-4 text-sm text-slate-500">No upcoming events published yet.</p>
          </div>
        ) : view === "grid" ? (
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {upcoming.map((event) => {
              const start = parseDate(event.startDate);
              return (
                <article key={event.id} className="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl">
                  <div className="absolute right-0 top-0 h-20 w-20 translate-x-6 -translate-y-6 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 opacity-50" />
                  <div className="mb-4 flex items-start justify-between">
                    <div className="flex flex-col items-center rounded-xl bg-gradient-to-b from-orange-400 to-orange-600 px-4 py-2 text-white shadow-lg">
                      <span className="text-2xl font-bold leading-none">{start ? start.getDate() : "–"}</span>
                      <span className="text-xs font-semibold uppercase">{start ? start.toLocaleDateString("en-US", { month: "short" }) : ""}</span>
                    </div>
                    <span className="inline-flex items-center gap-1 rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-semibold text-orange-700">
                      <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-orange-500" />
                      Upcoming
                    </span>
                  </div>
                  <h3 className="text-lg font-semibold text-slate-900 transition-colors group-hover:text-blue-600">{event.title}</h3>
                  <div className="mt-3 space-y-1.5 text-sm text-slate-500">
                    {start ? (
                      <p className="flex items-center gap-1.5">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <time dateTime={start.toISOString()}>{formatFull(start)}</time>
                      </p>
                    ) : null}
                    {event.location ? (
                      <p className="flex items-center gap-1.5">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {event.location}{event.isVirtual ? " · Virtual" : ""}
                      </p>
                    ) : null}
                    {start ? (
                      <p className="flex items-center gap-1.5 font-medium text-blue-600">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <CountdownLabel target={start} />
                      </p>
                    ) : null}
                  </div>
                  {event.description ? <p className="mt-4 text-sm leading-relaxed text-slate-600">{event.description.slice(0, 150)}</p> : null}
                  <div className="mt-5 flex items-center gap-3">
                    <button
                      type="button"
                      onClick={() => alert("Add to Calendar")}
                      className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                    >
                      <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                      Add to Calendar
                    </button>
                  </div>
                </article>
              );
            })}
          </div>
        ) : (
          <div className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
            {upcoming.map((event) => {
              const start = parseDate(event.startDate);
              return (
                <div key={event.id} className="flex flex-col gap-2 px-6 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                  <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-orange-50 text-orange-700">
                      <span className="text-sm font-bold leading-none">{start ? start.getDate() : "–"}</span>
                      <span className="text-[10px] font-semibold uppercase">{start ? start.toLocaleDateString("en-US", { month: "short" }) : ""}</span>
                    </div>
                    <div>
                      <p className="font-medium text-slate-900">{event.title}</p>
                      <p className="text-xs text-slate-500">{event.location || "—"}</p>
                    </div>
                  </div>
                  <p className="text-sm text-slate-500">{start ? start.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—"}</p>
                </div>
              );
            })}
          </div>
        )
      ) : null}

      {showPast && past.length > 0 ? (
        <section className="mt-16">
          <h2 className="text-2xl font-bold text-slate-900">Past Events</h2>
          <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-slate-400 to-slate-500" />
          <div className="mt-6 divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
            {past.map((event) => {
              const start = parseDate(event.startDate);
              return (
                <div key={event.id} className="flex flex-col gap-2 px-6 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                  <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                      <span className="text-sm font-bold leading-none">{start ? start.getDate() : "–"}</span>
                      <span className="text-[10px] font-semibold uppercase">{start ? start.toLocaleDateString("en-US", { month: "short" }) : ""}</span>
                    </div>
                    <div>
                      <p className="font-medium text-slate-900">{event.title}</p>
                      <p className="text-xs text-slate-500">{event.location || "—"}</p>
                    </div>
                  </div>
                  <p className="text-sm text-slate-500">{start ? start.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—"}</p>
                </div>
              );
            })}
          </div>
        </section>
      ) : null}
    </>
  );
}