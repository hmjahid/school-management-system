import { notFound } from "next/navigation";
import Link from "next/link";
import { t } from "@/lib/i18n";
import { getNewsBySlug } from "@/lib/site-data";
import { formatDate } from "@/components/site/Sections";

export const dynamic = "force-dynamic";

export default async function NewsShowPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const item = await getNewsBySlug(slug);
  if (!item) notFound();

  return (
    <article className="mx-auto max-w-3xl px-4 py-16">
      <Link href="/news" className="text-sm font-semibold text-blue-600 hover:underline">
        ← {t("site.home.news_view_all")}
      </Link>

      <p className="mt-6 text-xs font-semibold uppercase tracking-wide text-blue-600">
        {item.category ? String(item.category) : t("site.home.news_badge")}
      </p>
      <h1 className="mt-2 text-3xl font-extrabold text-slate-900">{String(item.title ?? "")}</h1>
      <p className="mt-2 text-sm text-slate-400">
        {formatDate(item.published_at)}
        {item.author_name ? ` · ${String(item.author_name)}` : ""}
      </p>

      {item.image_url ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={String(item.image_url)} alt={String(item.title ?? "")} className="mt-6 w-full rounded-2xl border border-slate-200" />
      ) : null}

      <div className="prose prose-slate mt-8 max-w-none whitespace-pre-line text-slate-700">
        {String(item.content ?? "")}
      </div>
    </article>
  );
}
