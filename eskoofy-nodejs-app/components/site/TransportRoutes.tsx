"use client";

import { useState } from "react";

interface Stop {
  name?: unknown;
  pickup_time?: unknown;
  drop_time?: unknown;
}

interface Route {
  id?: unknown;
  code?: unknown;
  name?: unknown;
  fare?: unknown;
  vehicles?: { number?: unknown } | null;
  transport_stops?: Stop[];
}

function slugify(value: string): string {
  return value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function fmtTime(value: unknown): string | null {
  if (!value) return null;
  const raw = String(value);
  if (/^\d{2}:\d{2}/.test(raw)) return raw.slice(0, 5);
  const d = new Date(raw);
  if (Number.isNaN(d.getTime())) return null;
  return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
}

function fmtFare(value: unknown): string {
  const n = Number(value);
  if (Number.isNaN(n)) return "0.00";
  return n.toFixed(2);
}

export function TransportRoutes({ routes }: { routes: Route[] }) {
  const [active, setActive] = useState<string>("all");

  const visible = active === "all" ? routes : routes.filter((r) => slugify(String(r.name ?? "")) === active);

  return (
    <>
      <div className="mb-10 flex flex-wrap gap-2 reveal">
        <button
          type="button"
          onClick={() => setActive("all")}
          className={`rounded-full px-5 py-2 text-sm font-semibold transition ${
            active === "all" ? "bg-blue-600 text-white hover:bg-blue-700" : "bg-slate-100 text-slate-700 hover:bg-blue-50 hover:text-blue-700"
          }`}
        >
          All Routes
        </button>
        {routes.map((route) => (
          <button
            key={String(route.id ?? "")}
            type="button"
            onClick={() => setActive(slugify(String(route.name ?? "")))}
            className={`rounded-full px-5 py-2 text-sm font-medium transition ${
              active === slugify(String(route.name ?? ""))
                ? "bg-blue-600 text-white hover:bg-blue-700"
                : "bg-slate-100 text-slate-700 hover:bg-blue-50 hover:text-blue-700"
            }`}
          >
            {String(route.name ?? "")}
          </button>
        ))}
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {visible.map((route) => {
          const stops = route.transport_stops ?? [];
          return (
            <div key={String(route.id ?? "")} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-xl reveal">
              <div className="flex items-center justify-between">
                <span className="font-mono text-xs font-semibold text-slate-400">
                  {route.code ? String(route.code) : `RT-${String(route.id ?? 0).padStart(2, "0")}`}
                </span>
                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                  <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                  Active
                </span>
              </div>
              <h2 className="mt-3 text-lg font-semibold text-slate-900">{String(route.name ?? "")}</h2>
              <div className="mt-4 flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                  <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                  </svg>
                </div>
                <div>
                  <p className="text-xs text-slate-500">Vehicle</p>
                  <p className="text-sm font-medium text-slate-900">{route.vehicles?.number ? String(route.vehicles.number) : "—"}</p>
                </div>
                <div className="ml-auto text-right">
                  <p className="text-xs text-slate-500">Fare</p>
                  <p className="text-sm font-bold text-blue-600">৳ {fmtFare(route.fare)}</p>
                </div>
              </div>
              {stops.length > 0 ? (
                <div className="mt-5">
                  <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Stops &amp; Timings</h3>
                  <div className="space-y-0">
                    {stops.map((stop, i) => {
                      const isLast = i === stops.length - 1;
                      const pickup = fmtTime(stop.pickup_time);
                      const drop = fmtTime(stop.drop_time);
                      return (
                        <div key={String(i)} className={`relative flex items-start gap-3 pb-3 ${isLast ? "ml-4 pl-4" : "border-l-2 border-blue-200 pl-4"}`}>
                          <span className="absolute -ml-[18px] mt-1.5 h-3 w-3 rounded-full border-2 border-blue-500 bg-white" />
                          <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium text-slate-900">{String(stop.name ?? "")}</p>
                            <p className="text-xs text-slate-500">
                              {pickup ? <span className="font-medium text-blue-600">Pickup: {pickup}</span> : null}
                              {pickup && drop ? <span className="mx-1">|</span> : null}
                              {drop ? <span className="font-medium text-orange-600">Drop: {drop}</span> : null}
                            </p>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              ) : null}
            </div>
          );
        })}
      </div>
    </>
  );
}