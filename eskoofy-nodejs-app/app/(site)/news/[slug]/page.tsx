import { notFound } from "next/navigation";
import Link from "next/link";
import type { Metadata } from "next";
import { headers } from "next/headers";
import { t } from "@/lib/i18n";
import { getNewsBySlug } from "@/lib/site-data";
import { getSiteSettings } from "@/lib/site-settings";
import { CopyLinkButton } from "@/components/site/CopyLinkButton";

export const dynamic = "force-dynamic";

interface NewsShowProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: NewsShowProps): Promise<Metadata> {
  const { slug } = await params;
  const item = await getNewsBySlug(slug);
  const settings = await getSiteSettings();
  const title = item ? `${String(item.title ?? "")} — ${settings.schoolName}` : settings.metaTitle;
  const description = item
    ? String(item.content ?? "").replace(/<[^>]*>/g, "").slice(0, 160)
    : settings.metaDescription;

  return {
    title,
    description,
    openGraph: {
      title: item ? String(item.title ?? "") : settings.schoolName,
      description,
      images: item?.image_url ? [String(item.image_url)] : [],
      type: "article",
      publishedTime: item?.published_at ? new Date(String(item.published_at)).toISOString() : undefined,
    },
  };
}

function readingMinutes(content: string): number {
  const words = content.replace(/<[^>]*>/g, "").trim().split(/\s+/).filter(Boolean).length;
  return Math.max(1, Math.ceil(words / 200));
}

export default async function NewsShowPage({ params }: NewsShowProps) {
  const { slug } = await params;
  const item = await getNewsBySlug(slug);
  if (!item) notFound();

  const settings = await getSiteSettings();
  const minutes = readingMinutes(String(item.content ?? ""));
  const content = String(item.content ?? "");
  const publishedAt = item.published_at ? new Date(String(item.published_at)) : null;
  const authorName = item.author_name ? String(item.author_name) : settings.schoolName;
  const hostHeader = (await headers()).get("host") || "localhost:3000";
  const shareUrl = `https://${hostHeader}/news/${encodeURIComponent(slug)}`;

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: String(item.title ?? ""),
    datePublished: publishedAt ? publishedAt.toISOString() : undefined,
    dateModified: item.updated_at ? new Date(String(item.updated_at)).toISOString() : undefined,
    author: {
      "@type": "Person",
      name: authorName,
    },
    publisher: {
      "@type": "Organization",
      name: settings.schoolName,
    },
    image: item.image_url ? [String(item.image_url)] : null,
    mainEntityOfPage: shareUrl,
  };

  return (
    <article className="bg-white">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />

      {item.image_url ? (
        <div className="relative h-[40vh] overflow-hidden md:h-[55vh]">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={String(item.image_url)} alt={String(item.title ?? "")} className="h-full w-full object-cover" loading="eager" />
          <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" />
          <div className="absolute bottom-0 left-0 right-0 p-6 md:p-12">
            <div className="mx-auto max-w-3xl">
              {item.category ? <span className="inline-block rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white">{String(item.category)}</span> : null}
            </div>
          </div>
        </div>
      ) : null}

      <div className="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <Link href="/news" className="inline-flex items-center gap-1 text-sm font-medium text-blue-600 transition-colors hover:text-blue-800">
          <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          {t("site.news_show.back")}
        </Link>

        <header className="mt-6">
          <h1 className="text-3xl font-bold leading-tight text-slate-900 md:text-4xl lg:text-5xl">{String(item.title ?? "")}</h1>

          <div className="mt-4 flex flex-wrap items-center gap-4 text-sm text-slate-500">
            <span className="flex items-center gap-1.5">
              <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
              </svg>
              {authorName}
            </span>
            <span className="h-1 w-1 rounded-full bg-slate-300" />
            {publishedAt ? (
              <span className="flex items-center gap-1.5">
                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <time dateTime={publishedAt.toISOString()}>
                  {publishedAt.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}
                </time>
              </span>
            ) : null}
            <span className="flex items-center gap-1.5">
              <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              {t("site.news_show.read_min", { min: String(minutes) })}
            </span>
            <span className="h-1 w-1 rounded-full bg-slate-300" />
            <CopyLinkButton />
          </div>
        </header>

        <div className="mt-10 max-w-none text-base leading-relaxed text-slate-700 lg:text-lg">
          <div
            className="[&>p:first-of-type]:first-letter:float-left [&>p:first-of-type]:first-letter:mr-3 [&>p:first-of-type]:first-letter:mt-1 [&>p:first-of-type]:first-letter:text-5xl [&>p:first-of-type]:first-letter:font-bold [&>p:first-of-type]:first-letter:leading-none [&>p:first-of-type]:first-letter:text-blue-600"
            dangerouslySetInnerHTML={{ __html: content }}
          />
        </div>

        <div className="mt-10 border-t border-slate-100 pt-8">
          <div className="flex flex-wrap items-center gap-3">
            <span className="text-sm font-medium text-slate-600">Share this article:</span>
            <a
              href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
            >
              <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
              </svg>
              Facebook
            </a>
            <a
              href={`https://twitter.com/intent/tweet?text=${encodeURIComponent(String(item.title ?? ""))}&url=${encodeURIComponent(shareUrl)}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 rounded-full bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-900"
            >
              <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
              </svg>
              X (Twitter)
            </a>
            <a
              href={`https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(shareUrl)}&title=${encodeURIComponent(String(item.title ?? ""))}`}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 rounded-full bg-blue-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-800"
            >
              <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
              </svg>
              LinkedIn
            </a>
          </div>
        </div>
      </div>
    </article>
  );
}