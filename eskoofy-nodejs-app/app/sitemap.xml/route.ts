import { prisma } from "@/lib/prisma";

export const dynamic = "force-dynamic";

export async function GET() {
  const base = "https://eskoofy.example.org";
  const now = new Date().toISOString().slice(0, 10);

  const staticUrls = ["/", "/about", "/academics", "/admissions", "/admissions/apply", "/admissions/status", "/committee", "/contact", "/events", "/faculty", "/gallery", "/news", "/notices", "/portal", "/results", "/routine", "/search", "/transport"];

  const [news, notices, events] = await Promise.all([
    prisma.news.findMany({ select: { slug: true, updated_at: true } }),
    prisma.notices.findMany({ select: { id: true, updated_at: true } }),
    prisma.events.findMany({ select: { id: true, updated_at: true } }),
  ]);

  const urls = [
    ...staticUrls.map((loc) => ({ loc: base + loc, lastmod: now })),
    ...news.map((n) => ({ loc: base + '/news/' + n.slug, lastmod: n.updated_at ? new Date(n.updated_at).toISOString().slice(0, 10) : now })),
    ...notices.map((n) => ({ loc: base + '/notices/' + n.id, lastmod: n.updated_at ? new Date(n.updated_at).toISOString().slice(0, 10) : now })),
    ...events.map((e) => ({ loc: base + '/events/' + e.id, lastmod: e.updated_at ? new Date(e.updated_at).toISOString().slice(0, 10) : now })),
  ];

  const rows = urls
    .map((u) => "  <url>\n    <loc>" + u.loc + "</loc>\n    <lastmod>" + u.lastmod + "</lastmod>\n  </url>")
    .join("\n");
  const xml = '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + rows + "\n</urlset>";

  return new Response(xml, { headers: { "Content-Type": "application/xml; charset=utf-8" } });
}
