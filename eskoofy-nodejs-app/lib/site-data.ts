import { prisma } from "@/lib/prisma";

/**
 * Safe public-site queries. Every call degrades to an empty result when the
 * database is unreachable, so pages still render on a fresh checkout.
 */

async function safe<T>(fn: () => Promise<T>, fallback: T): Promise<T> {
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
        where: { is_published: true },
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

export const getEvents = (limit = 6) =>
  safe(
    () =>
      prisma.events.findMany({ where: { deleted_at: null }, orderBy: { start_date: "asc" }, take: limit }) as unknown as Promise<Row[]>,
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

export const getCommittee = () =>
  safe(
    () =>
      prisma.committee_members.findMany({ where: { is_active: true }, orderBy: { sort_order: "asc" } }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

/** Teachers are `users` with role `teacher` in the app's model. */
export const getTeachers = (limit = 12) =>
  safe(
    () => prisma.users.findMany({ where: { role: "teacher", deleted_at: null }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getStudentsNotable = (limit = 8) =>
  safe(
    () =>
      prisma.students.findMany({ where: { is_notable: true, deleted_at: null }, take: limit }) as unknown as Promise<Row[]>,
    [] as Row[],
  );

export const getTransportRoutes = () =>
  safe(
    () => prisma.transport_routes.findMany({ where: { is_active: true }, orderBy: { name: "asc" } }) as unknown as Promise<Row[]>,
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

export async function getSchoolStats(): Promise<{ students: number; teachers: number; classes: number; events: number }> {
  const [students, teachers, classes, events] = await Promise.all([
    safe(() => prisma.students.count({ where: { deleted_at: null } }), 0),
    safe(() => prisma.users.count({ where: { role: "teacher", deleted_at: null } }), 0),
    safe(() => prisma.school_classes.count(), 0),
    safe(() => prisma.events.count({ where: { deleted_at: null } }), 0),
  ]);
  return { students, teachers, classes, events };
}

export async function searchSite(term: string): Promise<{ news: Row[]; notices: Row[]; pages: Row[] }> {
  const q = term.trim();
  if (!q) return { news: [], notices: [], pages: [] };
  const [news, notices, pages] = await Promise.all([
    safe(() => prisma.news.findMany({ where: { is_published: true, title: { contains: q } }, take: 20 }) as unknown as Promise<Row[]>, [] as Row[]),
    safe(() => prisma.notices.findMany({ where: { title: { contains: q } }, take: 20 }) as unknown as Promise<Row[]>, [] as Row[]),
    safe(() => prisma.website_contents.findMany({ where: { title: { contains: q }, is_active: true }, take: 20 }) as unknown as Promise<Row[]>, [] as Row[]),
  ]);
  return { news, notices, pages };
}
