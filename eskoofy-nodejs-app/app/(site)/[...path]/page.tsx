import { notFound } from "next/navigation";
import { matchRoute } from "@/lib/route-registry";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

/**
 * Catch-all for the app's public-site route surface. Routes with a dedicated
 * Node page (`/`, `/login`) win by specificity; every other Laravel web route
 * resolves here so the public route surface stays a clone.
 */
export default async function SiteCatchAll({ params }: { params: Promise<{ path: string[] }> }) {
  const { path } = await params;
  const uri = `/${path.join("/")}`;
  const route = matchRoute("GET", uri);

  if (!route) notFound();

  const label = route.name ?? uri;

  return (
    <section className="mx-auto max-w-3xl px-4 py-20">
      <p className="text-xs font-semibold uppercase tracking-widest text-blue-600">{t("brand.name")}</p>
      <h1 className="mt-2 text-3xl font-extrabold capitalize text-slate-900">{label.replace(/[._-]+/g, " ")}</h1>
      <p className="mt-4 text-slate-600">{t("admin.placeholder", { name: label })}</p>
      <p className="mt-3 font-mono text-xs text-slate-400">
        {route.method} {route.uri}
      </p>
    </section>
  );
}
