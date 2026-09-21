import { prisma } from "@/lib/prisma";

/**
 * Safe public-site queries. Every call degrades to an empty result when the
 * database is unreachable, so pages still render on a fresh checkout.
 */

export async function safe<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
  try {
    return await fn();
  } catch {
    return fallback;
  }
}

export type Row = Record<string, unknown>;

export const getLatestNews = (limit = 6) =>
  safe(
    () =>
      prisma.news.findMany({
        where: { is_published: true, is_event: false },
        orderBy: { published_at: "desc" },
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getNewsBySlug = (slug: string) =>
  safe(
    () =>
      prisma.news.findFirst({ where: { slug, is_published: true } }) as unknown as Promise<Row | null>,
    null as Row | null,
  );

export const getNotices = (limit = 6) =>
  safe(
    () =>
      prisma.notices.findMany({ orderBy: { id: "desc" }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** Home hero panel order: pinned first, then newest (mirrors HomeController). */
export const getRecentNotices = (limit = 5) =>
  safe(
    () =>
      prisma.notices.findMany({
        orderBy: [{ pinned: "desc" }, { id: "desc" }],
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getEvents = (limit = 6) =>
  safe(
    () =>
      prisma.events.findMany({ where: { deleted_at: null }, orderBy: { start_date: "asc" }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** Home events section: published events starting today or later (mirrors HomeController). */
export const getUpcomingEvents = (limit = 6) =>
  safe(
    () =>
      prisma.events.findMany({
        where: { status: "published", deleted_at: null, start_date: { gte: new Date() } },
        orderBy: { start_date: "asc" },
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** Past events section (mirrors SiteNewsController@index): published events that already started. */
export const getPastEvents = (limit = 8) =>
  safe(
    () =>
      prisma.events.findMany({
        where: { status: "published", deleted_at: null, start_date: { lt: new Date() } },
        orderBy: { start_date: "desc" },
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** News-based events strip (mirrors SiteNewsController@index): published news marked as events. */
export const getNewsEvents = (limit = 8) =>
  safe(
    () =>
      prisma.news.findMany({
        where: { is_published: true, is_event: true },
        orderBy: { event_date: "desc" },
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** Home photo slider fallback: published events that carry an image (mirrors HomeController). */
export const getSliderFallback = (limit = 6) =>
  safe(
    () =>
      prisma.events.findMany({
        where: { status: "published", deleted_at: null, image: { not: null }, NOT: { image: "" } },
        orderBy: { id: "desc" },
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getGallery = (limit = 12) =>
  safe(
    () =>
      prisma.galleries.findMany({ where: { is_published: true }, orderBy: { id: "desc" }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getTestimonials = (limit = 6) =>
  safe(
    () =>
      prisma.testimonials.findMany({ where: { is_visible: true }, orderBy: { sort_order: "asc" }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getCommittee = (limit = 20) =>
  safe(
    () =>
      prisma.committee_members.findMany({ where: { is_active: true }, orderBy: { sort_order: "asc" }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** Teachers come from the `teachers` profile joined to `users` (name), active only. */
export const getTeachers = (limit = 12) =>
  safe(
    () =>
      prisma.teachers.findMany({
        where: { status: "active", deleted_at: null },
        include: { users: true },
        orderBy: { id: "desc" },
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getStudentsNotable = (limit = 8) =>
  safe(
    () =>
      prisma.students.findMany({
        where: { is_notable: true, deleted_at: null },
        include: { users: true, school_classes: true },
        orderBy: { id: "desc" },
        take: limit,
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** `users` with the `student` role (used for /students showcase pages). */
export const getStudents = (limit = 12) =>
  safe(
    () => prisma.users.findMany({ where: { role: "student", deleted_at: null }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** Home CMS content (website_contents page = home), JSON-parsed incl. en/bn variants. */
export const getHomeContent = (locale = "en") =>
  safe(
    () =>
      prisma.website_contents
        .findFirst({
          where: { page: "home", is_active: true },
        })
        .then((row): Row | null => {
          if (!row) return null;
          const raw = locale === "bn" && row.content_bn ? row.content_bn : row.content;
          let content: Record<string, unknown> = {};
          try {
            const parsed = JSON.parse(raw ?? "{}");
            if (parsed && typeof parsed === "object") content = parsed as Record<string, unknown>;
          } catch {
            content = {};
          }
          return {
            id: row.id,
            title: row.title,
            meta_description: row.meta_description,
            content,
          };
        }) as unknown as Promise<Row | null>,
    null as Row | null,
  );

/** Sections to show on the home page (website_settings.section_visibility JSON). */
export const getSectionVisibility = () =>
  safe(
    () =>
      prisma.website_settings
        .findFirst()
        .then((row): Record<string, boolean> => {
          if (!row?.section_visibility) return {};
          try {
            const parsed = JSON.parse(row.section_visibility);
            if (parsed && typeof parsed === "object") {
              const out: Record<string, boolean> = {};
              for (const [key, value] of Object.entries(parsed)) out[key] = Boolean(value);
              return out;
            }
          } catch {
            /* malformed JSON → all sections visible */
          }
          return {};
        }),
    {} as Record<string, boolean>,
  );

/** Admissions open gate (admission_settings.is_open), defaulting to open. */
export const getAdmissionsOpen = () =>
  safe(
    () =>
      prisma.admission_settings.findFirst().then((row) => (row ? Boolean(row.is_open) : true)),
    true,
  );

export const getTransportRoutes = () =>
  safe(
    () =>
      prisma.transport_routes.findMany({
        where: { is_active: true },
        orderBy: { name: "asc" },
        include: { vehicles: true, transport_stops: { orderBy: { sort: "asc" } } },
      }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getRoutinesForClass = (classId: number) =>
  safe(
    () =>
      prisma.routines.findMany({ where: { school_class_id: classId, is_active: true }, orderBy: { start_time: "asc" } }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getClasses = () =>
  safe(() => prisma.school_classes.findMany({ orderBy: { id: "asc" } }) as unknown as Promise<Row[]>, [] as Row[]);

/** CMS page content (falls back to nothing when the page is not authored). */
export const getPageContent = (page: string) =>
  safe(
    () =>
      prisma.website_contents.findFirst({ where: { page, is_active: true } }) as unknown as Promise<Row | null>,
    null as Row | null,
  );

/** Home stats bar (mirrors HomeController): students, teachers, years, awards. */
export function getSchoolStats(): Promise<{ students: number; teachers: number; years: number | null; awards: number }> {
  return safe(
    async () => {
      const [students, teachers, settings] = await Promise.all([
        prisma.students.count({ where: { deleted_at: null } }),
        prisma.users.count({ where: { role: "teacher", deleted_at: null } }),
        prisma.website_settings.findFirst(),
      ]);
      const established = settings?.established_year ? Number(settings.established_year) : null;
      const years = established ? Math.max(0, new Date().getFullYear() - established) : null;
      return { students, teachers, years, awards: 0 };
    },
    { students: 0, teachers: 0, years: null, awards: 0 },
  );
}

export interface SearchResult {
  type_key: "news" | "notice" | "event" | "page";
  title: string;
  excerpt: string;
  url: string;
  type: string;
  date: string | null;
}

function stripTags(value: unknown): string {
  return String(value ?? "").replace(/<[^>]*>/g, " ");
}

function excerpt(value: unknown, length = 150): string {
  const text = stripTags(value).replace(/\s+/g, " ").trim();
  return text.length > length ? `${text.slice(0, length)}…` : text;
}

function fmtDate(value: unknown): string | null {
  if (!value) return null;
  const d = new Date(String(value));
  if (Number.isNaN(d.getTime())) return null;
  return d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
}

/** Site search — mirrors SiteSearchController: min 2 chars, title+content match, limit 10 per source. */
export async function searchSite(term: string): Promise<SearchResult[]> {
  const q = term.trim();
  if (q.length < 2) return [];

  const [news, notices, events, pageRows] = await Promise.all([
    safe(
      () =>
        prisma.news.findMany({
          where: { is_published: true, OR: [{ title: { contains: q } }, { content: { contains: q } }] },
          take: 10,
        }) as unknown as Promise<Row[]>,
      [] as Row[],
    ),
    safe(
      () =>
        prisma.notices.findMany({
          where: { OR: [{ title: { contains: q } }, { content: { contains: q } }] },
          take: 10,
        }) as unknown as Promise<Row[]>,
      [] as Row[],
    ),
    safe(
      () =>
        prisma.events.findMany({
          where: { status: "published", OR: [{ title: { contains: q } }, { description: { contains: q } }] },
          take: 10,
        }) as unknown as Promise<Row[]>,
      [] as Row[],
    ),
    safe(
      () =>
        prisma.website_contents.findMany({ where: { is_active: true }, take: 100 }) as unknown as Promise<Row[]>,
      [] as Row[],
    ),
  ]);

  const results: SearchResult[] = [
    ...news.map((item) => ({
      type_key: "news" as const,
      title: String(item.title ?? ""),
      excerpt: excerpt(item.content),
      url: `/news/${encodeURIComponent(String(item.slug ?? ""))}`,
      type: "News",
      date: fmtDate(item.published_at),
    })),
    ...notices.map((item) => ({
      type_key: "notice" as const,
      title: String(item.title ?? ""),
      excerpt: excerpt(item.content),
      url: "/notices",
      type: "Notices",
      date: fmtDate(item.created_at),
    })),
    ...events.map((item) => ({
      type_key: "event" as const,
      title: String(item.title ?? ""),
      excerpt: excerpt(item.description),
      url: "/events",
      type: "Events",
      date: fmtDate(item.start_date),
    })),
  ];

  const pages: SearchResult[] = [];
  for (const row of pageRows) {
    const page = String(row.page ?? "");
    const urlByPage: Record<string, string> = {
      about: "/about",
      academics: "/academics",
      admissions: "/admissions",
      faculty: "/faculty",
      committee: "/committee",
      contact: "/contact",
    };
    const url = urlByPage[page] ?? `/${page}`;
    const title = String(row.title ?? "");
    const haystack = `${title} ${String(row.content ?? "")}`.toLowerCase();
    if (haystack.includes(q.toLowerCase())) {
      pages.push({
        type_key: "page",
        title: title || page,
        excerpt: excerpt(row.content),
        url,
        type: "Page",
        date: null,
      });
    }
  }

  return [...results, ...pages];
}
