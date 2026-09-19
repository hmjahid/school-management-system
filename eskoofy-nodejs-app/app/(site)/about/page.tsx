import { t } from "@/lib/i18n";
import { ContentPage } from "@/components/site/ContentPage";

export const dynamic = "force-dynamic";

export default function AboutPage() {
  return (
    <ContentPage
      page="about"
      title={t("site.nav.about")}
      description={t("site.footer.about_fallback")}
      fallback={t("site.footer.about_fallback")}
    />
  );
}
