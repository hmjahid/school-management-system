import { t } from "@/lib/i18n";
import { getPageContent } from "@/lib/site-data";
import { CMSContentSections } from "@/components/site/CMSContentSections";
import { Hero, Section } from "@/components/site/Sections";

/**
 * CMS-backed content page: renders `website_contents` for the given page key,
 * falling back to the app's `page_sections` defaults when the page has not been
 * authored (mirrors `site/partials/sections.blade.php`).
 */
export async function ContentPage({
  page,
  title,
  description,
  fallback,
}: {
  page: string;
  title: string;
  description?: string;
  fallback?: string;
}) {
  const content = await getPageContent(page);

  return (
    <>
      <Hero eyebrow={t("brand.name")} title={String(content?.title ?? title)} subtitle={description} />
      <Section>
        <div className="mx-auto max-w-3xl">
          <CMSContentSections page={page} />
          {fallback && !content?.content ? <p className="whitespace-pre-line text-slate-700">{fallback}</p> : null}
        </div>
      </Section>
    </>
  );
}