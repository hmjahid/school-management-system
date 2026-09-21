import { t } from "@/lib/i18n";
import { getTransportRoutes, getSectionVisibility } from "@/lib/site-data";
import { TransportRoutes } from "@/components/site/TransportRoutes";

export const dynamic = "force-dynamic";

const BUS_ICON = (
  <svg className="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
  </svg>
);

export default async function TransportPage() {
  const routes = await getTransportRoutes();
  const visibility = await getSectionVisibility();

  const showHero = visibility.transport_hero !== false;
  const showRoutes = visibility.transport_routes !== false;
  const showFleet = visibility.transport_fleet !== false;
  const showMap = visibility.transport_map !== false;

  return (
    <div className="bg-white">
      {showHero ? (
        <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
          <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h1 className="text-4xl font-bold md:text-5xl">{t("dashboard.transport")}</h1>
            <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">Bus routes, stops, and fare information.</p>
          </div>
        </div>
      ) : null}

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        {showRoutes ? (
          routes.length === 0 ? (
            <div className="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center reveal">
              <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
              <p className="mt-4 text-sm text-slate-500">Transport routes will be published soon.</p>
            </div>
          ) : (
            <TransportRoutes routes={routes} />
          )
        ) : null}

        {showFleet ? (
          <section className="mt-16 reveal">
            <h2 className="text-2xl font-bold text-slate-900">Our Fleet</h2>
            <div className="mt-2 h-1 w-16 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500" />
            <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600">{BUS_ICON}</div>
                <h3 className="mt-4 text-lg font-semibold text-slate-900">Standard Bus</h3>
                <ul className="mt-3 space-y-1.5 text-sm text-slate-600">
                  <li>Capacity: 50 seats</li>
                  <li>AC/Non-AC: Both available</li>
                  <li>GPS tracked</li>
                </ul>
              </div>
              <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600">{BUS_ICON}</div>
                <h3 className="mt-4 text-lg font-semibold text-slate-900">Mini Bus</h3>
                <ul className="mt-3 space-y-1.5 text-sm text-slate-600">
                  <li>Capacity: 30 seats</li>
                  <li>Ideal for shorter routes</li>
                  <li>CCTV equipped</li>
                </ul>
              </div>
              <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600">{BUS_ICON}</div>
                <h3 className="mt-4 text-lg font-semibold text-slate-900">Micro Bus</h3>
                <ul className="mt-3 space-y-1.5 text-sm text-slate-600">
                  <li>Capacity: 14 seats</li>
                  <li>For special trips</li>
                  <li>Flexible scheduling</li>
                </ul>
              </div>
            </div>
          </section>
        ) : null}

        {showMap ? (
          <section className="mt-16 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 reveal">
            <div className="flex h-64 items-center justify-center bg-slate-200 md:h-80">
              <div className="text-center">
                <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <p className="mt-4 text-sm font-medium text-slate-500">Route Map</p>
                <p className="text-xs text-slate-400">Interactive map will be available soon.</p>
              </div>
            </div>
          </section>
        ) : null}
      </div>
    </div>
  );
}