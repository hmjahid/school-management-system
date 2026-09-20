import { getEvents } from "@/lib/site-data";

export const dynamic = "force-dynamic";

/**
 * Events & calendar — mirrors the real Laravel `site/events.blade.php`:
 * gradient hero, filter pills (All/Upcoming/Past), grid of event cards.
 */
export default async function EventsPage() {
  const events = await getEvents(48);
  const now = new Date();
  const upcoming = events.filter((e) => new Date(String(e.start_date ?? "")).getTime() >= now.getTime());

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">Events &amp; Calendar</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">Upcoming school events, open days, and important dates.</p>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
          <div className="flex flex-wrap gap-2">
            <span className="rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white">All</span>
            <span className="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium text-slate-700">Upcoming</span>
            <span className="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium text-slate-700">Past</span>
          </div>
        </div>

        {upcoming.length === 0 ? (
          <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
            <p className="text-sm text-slate-500">No upcoming events.</p>
          </div>
        ) : (
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {upcoming.map((event) => {
              const start = event.start_date ? new Date(String(event.start_date)) : null;
              return (
                <article key={String(event.id)} className="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-100 transition-all duration-300 hover:shadow-xl">
                  <div className="absolute right-0 top-0 h-20 w-20 translate-x-6 -translate-y-6 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 opacity-50" />
                  <div className="mb-4 flex items-start justify-between">
                    <div className="rounded-xl bg-blue-50 px-3 py-2 text-center">
                      <div className="text-lg font-bold leading-none text-blue-700">{start ? start.getDate() : "–"}</div>
                      <div className="text-[10px] font-semibold uppercase tracking-wide text-blue-500">{start ? start.toLocaleString("en", { month: "short" }) : ""}</div>
                    </div>
                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Upcoming</span>
                  </div>
                  <h2 className="text-lg font-semibold text-slate-900 transition-colors group-hover:text-blue-600">{String(event.title ?? "")}</h2>
                  {event.location ? <p className="mt-1 text-sm text-slate-500">{String(event.location)}</p> : null}
                  <p className="mt-3 text-sm leading-relaxed text-slate-600">{String(event.description ?? "").slice(0, 140)}</p>
                </article>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
