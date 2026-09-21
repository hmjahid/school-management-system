import { locale, tOr } from "@/lib/i18n";
import { getPageContent } from "@/lib/site-data";
import { PAGE_SECTIONS_DEFAULTS, type CmsSection } from "@/lib/site-cms-defaults";

function asSections(value: unknown): CmsSection[] {
  if (!Array.isArray(value)) return [];
  return value.flatMap((s) => {
    if (!s || typeof s !== "object") return [];
    const row = s as Record<string, unknown>;
    const section: CmsSection = {};
    if (typeof row.heading === "string") section.heading = row.heading;
    if (Array.isArray(row.paragraphs)) section.paragraphs = row.paragraphs.filter((p): p is string => typeof p === "string");
    if (Array.isArray(row.bullets)) section.bullets = row.bullets.filter((b): b is string => typeof b === "string");
    if (Array.isArray(row.cards)) {
      section.cards = row.cards
        .filter((c) => c && typeof c === "object")
        .map((c) => {
          const card = c as Record<string, unknown>;
          return {
            title: typeof card.title === "string" ? card.title : undefined,
            body: typeof card.body === "string" ? card.body : undefined,
          };
        });
    }
    if (Array.isArray(row.faq)) {
      section.faq = row.faq
        .filter((f) => f && typeof f === "object")
        .map((f) => {
          const faq = f as Record<string, unknown>;
          return {
            q: typeof faq.q === "string" ? faq.q : "",
            a: typeof faq.a === "string" ? faq.a : "",
          };
        });
    }
    return [section];
  });
}

/**
 * Renders the CMS `website_contents.content.sections` tree for a page with the
 * `page_sections` lang fallback — mirrors `site/partials/sections.blade.php`.
 */
export async function CMSContentSections({ page }: { page: string }) {
  const n = locale();
  const content = await getPageContent(page);

  const body = content?.content ? String(content.content) : "";
  let parsed: Record<string, unknown> = {};
  try {
    const data = JSON.parse(body);
    if (data && typeof data === "object") parsed = data as Record<string, unknown>;
  } catch {
    parsed = {};
  }

  const sections = asSections(parsed.sections);
  const resolvedSections = sections.length > 0 ? sections : (PAGE_SECTIONS_DEFAULTS[n] ?? PAGE_SECTIONS_DEFAULTS.en)?.[page] ?? [];
  const intro = typeof parsed.intro === "string" ? parsed.intro : tOr(`site.pages.${page}.intro_fallback_bn`, "");

  return (
    <>
      {intro ? <p className="mb-8 max-w-3xl text-lg leading-relaxed text-gray-600">{intro}</p> : null}
      {resolvedSections.map((section, i) => (
        <section key={i} className="mb-12 scroll-mt-24">
          {section.heading ? (
            <>
              <h2 className="text-2xl font-bold text-gray-900">{section.heading}</h2>
              <div className="mt-2 h-1 w-20 bg-blue-600" />
            </>
          ) : null}
          {section.paragraphs?.map((paragraph, p) => (
            <p key={p} className="mt-4 max-w-3xl leading-relaxed text-gray-600">
              {paragraph}
            </p>
          ))}
          {section.bullets && section.bullets.length > 0 ? (
            <ul className="mt-4 list-inside list-disc space-y-2 text-gray-600">
              {section.bullets.map((item, b) => (
                <li key={b}>{item}</li>
              ))}
            </ul>
          ) : null}
          {section.cards && section.cards.length > 0 ? (
            <div className="mt-6 grid gap-4 sm:grid-cols-2">
              {section.cards.map((card, c) => (
                <div key={c} className="rounded-lg border border-gray-200 bg-gray-50 p-5 shadow-sm">
                  {card.title ? <h3 className="font-semibold text-gray-900">{card.title}</h3> : null}
                  {card.body ? <p className="mt-2 text-sm text-gray-600">{card.body}</p> : null}
                </div>
              ))}
            </div>
          ) : null}
          {section.faq && section.faq.length > 0 ? (
            <dl className="mt-4 space-y-4">
              {section.faq.map((row, f) => (
                <div key={f} className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                  <dt className="font-medium text-gray-900">{row.q}</dt>
                  <dd className="mt-2 text-sm text-gray-600">{row.a}</dd>
                </div>
              ))}
            </dl>
          ) : null}
        </section>
      ))}
    </>
  );
}