import { t } from "@/lib/i18n";
import { getPageContent } from "@/lib/site-data";
import { Hero, Section } from "@/components/site/Sections";

/**
 * CMS-backed content page: renders `website_contents` for the given page key,
 * falling back to the app's i18n copy when the page has not been authored.
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
  const body = content?.content
    ? String(content.content)
    : fallback ?? t("admin.placeholder", { name: title });

  return (
    <>
      <Hero eyebrow={t("brand.name")} title={String(content?.title ?? title)} subtitle={description} />
      <Section>
        <div className="mx-auto max-w-3xl whitespace-pre-line text-slate-700">{body}</div>
      </Section>
    </>
  );
}
