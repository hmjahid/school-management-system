import { t } from "@/lib/i18n";
import { ContentPage } from "@/components/site/ContentPage";

export const dynamic = "force-dynamic";

export default function StudentsPage() {
  return <ContentPage page="students" title={t("site.nav.students")} description={t("site.pages.students.meta_fallback_bn")} />;
}