import { prisma } from "@/lib/prisma";
import { safe } from "@/lib/site-data";
import { t } from "@/lib/i18n";
import GalleryClient, { type GalleryImage } from "@/components/site/GalleryClient";

export const dynamic = "force-dynamic";

type GalleryRow = {
  id: number;
  title: string | null;
  description: string | null;
  image_path: string | null;
  category: string | null;
};

export default async function GalleryPage() {
  const images = await safe(
    () =>
      prisma.galleries.findMany({
        where: { is_published: true },
        orderBy: { id: "desc" },
        take: 120,
      }),
    [] as GalleryRow[],
  );

  const items: GalleryImage[] = images.map((image) => ({
    id: String(image.id),
    title: String(image.title ?? ""),
    description: image.description,
    src: image.image_path || null,
    category: String(image.category ?? "general"),
  }));

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.nav.gallery")}</h1>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <GalleryClient items={items} emptyText={t("site.gallery.empty")} />

        <section className="mt-16 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
          <svg className="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <h2 className="mt-4 text-lg font-semibold text-slate-900">{t("site.gallery.video_section_title")}</h2>
          <p className="mt-2 text-sm text-slate-600">{t("site.gallery.video_section_body")}</p>
        </section>
      </div>
    </div>
  );
}