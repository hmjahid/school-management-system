import type { Metadata } from "next";
import { t } from "@/lib/i18n";
import { searchSite, type SearchResult } from "@/lib/site-data";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Search — Eskoofy",
};

interface SearchPageProps {
  searchParams: Promise<{ q?: string; type?: string }>;
}

const FILTERS = [
  { key: "all", label: "All" },
  { key: "news", label: "News" },
  { key: "notice", label: "Notices" },
  { key: "event", label: "Events" },
  { key: "page", label: "Pages" },
];

const GROUP_LABELS: Record<string, string> = {
  news: "News",
  notice: "Notices",
  event: "Events",
  page: "Pages",
};

export default async function SearchPage({ searchParams }: SearchPageProps) {
  const params = await searchParams;
  const query = (params.q ?? "").trim();
  const activeType = params.type ?? "all";

  const allResults = query.length >= 2 ? await searchSite(query) : [];
  const typeCounts: Record<string, number> = {};
  for (const r of allResults) typeCounts[r.type_key] = (typeCounts[r.type_key] ?? 0) + 1;

  const filtered = activeType === "all" ? allResults : allResults.filter((r) => r.type_key === activeType);
  const grouped = new Map<string, SearchResult[]>();
  for (const r of filtered) {
    const bucket = grouped.get(r.type_key) ?? [];
    bucket.push(r);
    grouped.set(r.type_key, bucket);
  }

  return (
    <div className="bg-white">
      <section className="bg-gradient-to-br from-blue-600 to-indigo-700 py-16 text-white">
        <div className="mx-auto max-w-4xl px-4 text-center">
          <h1 className="text-3xl font-bold">{t("common.search")}</h1>
          <form action="/search" method="GET" className="mt-6">
            <div className="relative mx-auto max-w-2xl">
              <svg
                className="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="search"
                name="q"
                defaultValue={query}
                autoFocus
                placeholder="Search the school…"
                className="w-full rounded-xl border-0 bg-white py-3.5 pl-12 pr-4 text-sm text-gray-900 shadow-lg placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-white/30"
              />
            </div>
          </form>
        </div>
      </section>

      <section className="mx-auto max-w-4xl px-4 py-12">
        {query.length >= 1 && query.length < 2 ? (
          <p className="text-center text-gray-500">Please enter at least 2 characters to search.</p>
        ) : filtered.length === 0 && query ? (
          <p className="text-center text-gray-500">
            No results found for &ldquo;{query}&rdquo;
          </p>
        ) : filtered.length === 0 ? null : (
          <>
            <div className="mb-8 flex flex-wrap justify-center gap-2">
              {FILTERS.map((f) => {
                const count = f.key === "all" ? allResults.length : typeCounts[f.key];
                const href =
                  f.key === "all"
                    ? `/search?q=${encodeURIComponent(query)}`
                    : `/search?q=${encodeURIComponent(query)}&type=${f.key}`;
                return (
                  <a
                    key={f.key}
                    href={href}
                    className={`rounded-full px-4 py-1.5 text-sm font-medium transition ${
                      activeType === f.key ? "bg-blue-600 text-white shadow" : "bg-blue-50 text-blue-700 hover:bg-blue-100"
                    }`}
                  >
                    {f.label}
                    {count ? <span className="opacity-70"> ({count})</span> : null}
                  </a>
                );
              })}
            </div>

            <p className="mb-6 text-sm text-gray-500">
              {filtered.length} results found for &ldquo;<strong>{query}</strong>&rdquo;
            </p>

            <div className="space-y-8">
              {Array.from(grouped.entries()).map(([typeKey, items]) => (
                <div key={typeKey}>
                  <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                    <span className="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                      {GROUP_LABELS[typeKey] ?? typeKey}
                    </span>
                    {items.length}
                  </h2>
                  <div className="space-y-4">
                    {items.map((item, i) => (
                      <a
                        key={`${typeKey}-${i}`}
                        href={item.url}
                        className="group block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md"
                      >
                        <div className="mb-1 flex items-center gap-2">
                          <span className="inline-block rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                            {item.type}
                          </span>
                          {item.date ? <span className="text-xs text-gray-400">{item.date}</span> : null}
                        </div>
                        <h2 className="text-lg font-semibold text-gray-900 group-hover:text-blue-600">{item.title}</h2>
                        {item.excerpt ? <p className="mt-1 text-sm text-gray-500">{item.excerpt}</p> : null}
                      </a>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </>
        )}
      </section>
    </div>
  );
}