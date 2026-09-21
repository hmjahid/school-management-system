import { prisma } from "@/lib/prisma";
import { locale, t } from "@/lib/i18n";
import Link from "next/link";

export const dynamic = "force-dynamic";

const PER_PAGE = 15;

function parseAudience(raw: unknown): string[] {
  try {
    const parsed = typeof raw === "string" ? JSON.parse(raw) : raw;
    if (Array.isArray(parsed)) return parsed.map((a) => String(a)).filter(Boolean);
  } catch {
    /* not JSON — ignore */
  }
  return [];
}

export default async function NoticesPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const n = locale();
  const sp = await searchParams;
  const page = Math.max(1, Number(Array.isArray(sp.page) ? sp.page[0] : sp.page) || 1);

  const [total, notices] = await Promise.all([
    prisma.notices.count(),
    prisma.notices.findMany({
      orderBy: [{ pinned: "desc" }, { id: "desc" }],
      skip: (page - 1) * PER_PAGE,
      take: PER_PAGE,
    }),
  ]);

  const lastPage = Math.max(1, Math.ceil(total / PER_PAGE));

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.nav.notices")}</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-blue-100">Stay informed with the latest notices and announcements.</p>
        </div>
      </div>

      <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        {notices.length === 0 ? (
          <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
            <svg className="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <p className="mt-4 text-sm text-slate-500">No notices have been published yet.</p>
          </div>
        ) : (
          <>
            <div className="space-y-4">
              {notices.map((notice) => {
                const title = n === "bn" && notice.title_bn ? notice.title_bn : notice.title;
                const content = n === "bn" && notice.content_bn ? notice.content_bn : notice.content;
                const audience = parseAudience(notice.audience);
                const createdAt = notice.created_at instanceof Date ? notice.created_at : new Date(notice.created_at ?? Date.now());
                const dateLabel = createdAt.toLocaleDateString(n === "bn" ? "en-US" : "en-US", {
                  month: "short",
                  day: "numeric",
                  year: "numeric",
                }) + ` at ${createdAt.toLocaleTimeString(n === "bn" ? "en-US" : "en-US", { hour: "numeric", minute: "2-digit" })}`;
                return (
                  <div key={notice.id} className={`rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md ${notice.pinned ? "border-l-4 border-l-amber-500" : ""}`}>
                    <div className="flex items-start gap-4">
                      <div className="shrink-0 pt-1">
                        {notice.pinned ? (
                          <svg className="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2V7a5 5 0 00-5-5zm3 7V7a3 3 0 00-6 0v2h6z" />
                          </svg>
                        ) : (
                          <span className="inline-flex h-3 w-3 items-center justify-center rounded-full bg-blue-500/60" />
                        )}
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                          <h2 className="text-lg font-semibold text-slate-900">{String(title ?? "")}</h2>
                          {notice.pinned ? (
                            <span className="rounded bg-amber-50 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-amber-600">Pinned</span>
                          ) : null}
                        </div>
                        <p className="mt-1 text-xs text-slate-400">{dateLabel}</p>
                        <div className="mt-3 text-sm leading-relaxed whitespace-pre-line text-slate-600">{String(content ?? "")}</div>
                        {audience.length > 0 ? (
                          <div className="mt-3 flex flex-wrap gap-1.5">
                            {audience.map((aud) => (
                              <span key={aud} className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium capitalize text-slate-600">
                                {aud}
                              </span>
                            ))}
                          </div>
                        ) : null}
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            {lastPage > 1 ? (
              <div className="mt-8 flex items-center justify-center gap-2">
                {page > 1 ? (
                  <Link href={`/notices?page=${page - 1}`} className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                    Previous
                  </Link>
                ) : null}
                <span className="px-2 text-sm text-slate-500">
                  Page {page} of {lastPage}
                </span>
                {page < lastPage ? (
                  <Link href={`/notices?page=${page + 1}`} className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                    Next
                  </Link>
                ) : null}
              </div>
            ) : null}
          </>
        )}
      </div>
    </div>
  );
}