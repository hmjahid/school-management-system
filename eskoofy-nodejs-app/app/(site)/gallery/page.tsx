import { t } from "@/lib/i18n";
import { getGallery } from "@/lib/site-data";

export const dynamic = "force-dynamic";

/**
 * Gallery — mirrors the real Laravel `site/gallery.blade.php`: gradient hero,
 * category filter tabs, masonry columns grid with lightbox-ready figures.
 */
export default async function GalleryPage() {
  const images = await getGallery(60);
  const albums = [...new Set(images.map((image) => String(image.category ?? "general")))];

  return (
    <div className="bg-white">
      <div className="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold md:text-5xl">{t("site.nav.gallery")}</h1>
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        {albums.length > 0 ? (
          <div className="mb-10 flex flex-wrap gap-2">
            <span className="rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white">All</span>
            {albums.map((album) => (
              <span key={album} className="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium capitalize text-slate-700">
                {album}
              </span>
            ))}
          </div>
        ) : null}

        {images.length === 0 ? (
          <div className="rounded-xl border-2 border-dashed border-slate-200 p-16 text-center">
            <p className="text-sm text-slate-500">No gallery images yet.</p>
          </div>
        ) : (
          <div className="columns-1 gap-6 space-y-6 sm:columns-2 lg:columns-3 xl:columns-4">
            {images.map((image) => (
              <figure key={String(image.id)} className="group relative overflow-hidden rounded-2xl bg-slate-100 shadow-md ring-1 ring-slate-100 break-inside-avoid transition-all duration-300 hover:shadow-xl">
                <div className="aspect-[4/3] bg-slate-100">
                  {image.image_path ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={String(image.image_path)} alt={String(image.title ?? "")} className="h-full w-full object-cover" loading="lazy" />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-50">
                      <svg className="h-10 w-10 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                  )}
                </div>
                <figcaption className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-4 pb-3 pt-8 text-sm font-medium text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                  {String(image.title ?? "")}
                </figcaption>
              </figure>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
