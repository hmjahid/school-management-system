import { prisma } from "@/lib/prisma";
import EventsClient, { type SiteEvent } from "@/components/site/EventsClient";

export const dynamic = "force-dynamic";

type EventRow = { id: number; title: string; description: string | null; location: string | null; start_date: Date; is_virtual: boolean };

function toSiteEvent(event: EventRow): SiteEvent {
  return {
    id: String(event.id),
    title: String(event.title ?? ""),
    description: event.description,
    location: event.location,
    startDate: event.start_date.toISOString(),
    endDate: null,
    isVirtual: event.is_virtual,
  };
}

export default async function EventsPage() {
  const [upcoming, past] = await Promise.all([
    prisma.events.findMany({
      where: { start_date: { gte: new Date() }, status: "published", deleted_at: null },
      orderBy: { start_date: "asc" },
      take: 50,
    }),
    prisma.events.findMany({
      where: { start_date: { lt: new Date() }, status: "published", deleted_at: null },
      orderBy: { start_date: "desc" },
      take: 20,
    }),
  ]);

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">Events &amp; Calendar</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">Upcoming school events, open days, and important dates.</p>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <EventsClient upcoming={upcoming.map(toSiteEvent)} past={past.map(toSiteEvent)} />
      </div>
    </div>
  );
}