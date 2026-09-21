import { t } from "@/lib/i18n";
import { ContentPage } from "@/components/site/ContentPage";

export const dynamic = "force-dynamic";

export default function AcademicsPage() {
  return <ContentPage page="academics" title={t("site.nav.academics")} description={t("site.pages.academics.meta_fallback_bn")} />;
}