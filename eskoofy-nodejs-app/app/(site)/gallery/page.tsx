import { t } from "@/lib/i18n";
import { getGallery } from "@/lib/site-data";
import { Empty, Hero, Section } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function GalleryPage() {
  const images = await getGallery(60);
  const albums = [...new Set(images.map((image) => String(image.category ?? "general")))];

  return (
    <>
      <Hero eyebrow={t("site.nav.gallery")} title={t("site.nav.gallery")} />
      <Section>
        {images.length === 0 ? (
          <Empty>No gallery images yet.</Empty>
        ) : (
          <div className="space-y-10">
            {albums.map((album) => (
              <div key={album}>
                <h2 className="mb-4 text-lg font-bold capitalize text-slate-800">{album}</h2>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  {images
                    .filter((image) => String(image.category ?? "general") === album)
                    .map((image) => (
                      <figure key={String(image.id)} className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div className="aspect-[4/3] bg-slate-100">
                          {image.image_path ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img src={String(image.image_path)} alt={String(image.title ?? "")} className="h-full w-full object-cover" />
                          ) : null}
                        </div>
                        <figcaption className="px-3 py-2 text-xs text-slate-500">{String(image.title ?? "")}</figcaption>
                      </figure>
                    ))}
                </div>
              </div>
            ))}
          </div>
        )}
      </Section>
    </>
  );
}
