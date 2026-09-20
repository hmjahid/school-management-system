import { t } from "@/lib/i18n";
import { getNotices } from "@/lib/site-data";
import { Empty, Section, formatDate, PageHero } from "@/components/site/Sections";
import { Badge } from "@/components/ui/Badge";

export const dynamic = "force-dynamic";

export default async function NoticesPage() {
  const notices = await getNotices(50);

  return (
    <>
      <PageHero title={t("site.nav.notices")} />
      <Section>
        {notices.length === 0 ? (
          <Empty>No notices published yet.</Empty>
        ) : (
          <ul className="space-y-3">
            {notices.map((notice) => (
              <li key={String(notice.id)} className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <h2 className="font-semibold text-slate-900">
                    {notice.pinned ? <Badge variant="warning">pinned</Badge> : null} {String(notice.title ?? "")}
                  </h2>
                  <span className="text-xs text-slate-400">{formatDate(notice.created_at)}</span>
                </div>
                {notice.content ? <p className="mt-2 whitespace-pre-line text-sm text-slate-600">{String(notice.content)}</p> : null}
              </li>
            ))}
          </ul>
        )}
      </Section>
    </>
  );
}
