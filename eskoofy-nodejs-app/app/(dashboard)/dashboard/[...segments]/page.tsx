import { notFound } from "next/navigation";
import { matchRoute } from "@/lib/route-registry";
import { resolveResource } from "@/lib/resource-route";
import { findRow } from "@/lib/db-query";
import { ResourceTable } from "@/components/dashboard/ResourceTable";
import { ResourceForm } from "@/components/dashboard/ResourceForm";
import { ResourceDetail } from "@/components/dashboard/ResourceDetail";
import { RoutePlaceholder } from "@/components/dashboard/RoutePlaceholder";
import { PrintScreen } from "@/components/dashboard/PrintScreen";
import { FeesReport, StudentsReport } from "@/components/dashboard/ReportsScreen";
import { SettingsScreen } from "@/components/dashboard/SettingsScreen";
import { NotificationTemplates, NotificationPreferences } from "@/components/dashboard/NotificationsScreen";
import { PromoteStudents, MyResults, Onboarding } from "@/components/dashboard/MiscScreens";
import { CmsPages, CmsEdit } from "@/components/dashboard/CmsScreen";
import { EventsCalendar, LedgerBook } from "@/components/dashboard/CalendarLedgerScreen";
import { BulkAttendance, GeneratePayslips, CareersApplications } from "@/components/dashboard/BulkScreens";
import { PageHeader } from "@/components/ui/PageHeader";
import { ButtonLink } from "@/components/ui/Button";
import { currentUser } from "@/lib/auth";
import { can, permissionForTable } from "@/lib/permissions";
import { t } from "@/lib/i18n";

export const dynamic = "force-dynamic";

/**
 * Catch-all for the app's whole dashboard route surface.
 *
 *  - resource index / create / show / edit  → generic CRUD screens
 *  - everything else                        → parity placeholder
 *
 * Mirrors the app's `index/create/store/show/edit/update/destroy` resource
 * routes for all ~34 dashboard resources (see docs/PORTING-STATUS.md).
 */
export default async function DashboardCatchAll({
  params,
  searchParams,
}: {
  params: Promise<{ segments: string[] }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { segments } = await params;
  const query = await searchParams;
  const uri = `/dashboard/${segments.join("/")}`;
  const resource = resolveResource(segments);
  const route = matchRoute("GET", uri);

  // A resource CRUD screen is served even when the app names the route slightly
  // differently (create/edit/show all resolve generically).
  if (!route && !resource.model) notFound();

  const user = await currentUser();
  const key = (segments[0] ?? "").replace(/-/g, "_");
  const titleKey = `dashboard.${key}`;
  const routeLabel = route?.name ?? uri;
  const title = t(titleKey) === titleKey ? routeLabel : t(titleKey);

  if (!resource.model) {
    if (!route) notFound();
    return (
      <div>
        <PageHeader title={title} description={routeLabel} />
        <RoutePlaceholder uri={route.uri} name={route.name} action={route.action} method={route.method} />
      </div>
    );
  }

  const model = resource.model;

  if (!can(user?.role, permissionForTable(model.table))) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — {title}
      </p>
    );
  }

  if (resource.mode === "create" || resource.mode === "edit") {
    const row = resource.mode === "edit" && resource.id ? await findRow(model, resource.id) : null;
    if (resource.mode === "edit" && !row) notFound();

    return (
      <div>
        <PageHeader
          title={`${resource.mode === "create" ? t("common.create") : t("common.save")} · ${title}`}
          description={`${model.table}${resource.id ? ` #${resource.id}` : ""}`}
        />
        <ResourceForm model={model} basePath={resource.basePath} mode={resource.mode} row={row} />
      </div>
    );
  }

  if (resource.mode === "show" && resource.id) {
    const row = await findRow(model, resource.id);
    if (!row) notFound();

    return (
      <div>
        <PageHeader
          title={`${title} #${resource.id}`}
          description={model.table}
          actions={
            <>
              <ButtonLink href={`${resource.basePath}/${resource.id}/edit`} size="sm">
                {t("common.save")}
              </ButtonLink>
              <ButtonLink href={resource.basePath} variant="secondary" size="sm">
                {t("common.cancel")}
              </ButtonLink>
            </>
          }
        />
        <ResourceDetail model={model} row={row} />
      </div>
    );
  }

  // Notification screens
  if (segments[0] === "notifications" && segments[1] === "templates") {
    return (
      <div>
        <NotificationTemplates />
      </div>
    );
  }
  if (segments[0] === "notifications" && segments[1] === "preferences") {
    return (
      <div>
        <NotificationPreferences />
      </div>
    );
  }

  // Misc bespoke screens
  if (segments[0] === "students" && segments[1] === "promote") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <PromoteStudents fromClassId={q.from_class_id ? Number(q.from_class_id) : undefined} />
      </div>
    );
  }
  if (segments[0] === "exams" && segments[1] === "my-results") {
    return (
      <div>
        <MyResults />
      </div>
    );
  }
  if (segments[0] === "onboarding") {
    return (
      <div>
        <Onboarding />
      </div>
    );
  }

  // CMS screens
  if (segments[0] === "cms" && segments[1] === "pages" && segments.length === 2) {
    return (
      <div>
        <CmsPages />
      </div>
    );
  }
  if (segments[0] === "cms" && segments.length === 3 && segments[2] === "edit") {
    return (
      <div>
        <CmsEdit page={segments[1]} />
      </div>
    );
  }

  // Events calendar + ledger books
  if (segments[0] === "events" && segments[1] === "calendar") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <EventsCalendar month={q.month} />
      </div>
    );
  }
  if (segments[0] === "ledger" && (segments[1] === "cashbook" || segments[1] === "bankbook" || segments[1] === "journal")) {
    return (
      <div>
        <LedgerBook kind={segments[1] as "cashbook" | "bankbook" | "journal"} />
      </div>
    );
  }

  // Bulk workflow screens
  if (segments[0] === "attendance" && segments[1] === "bulk") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <BulkAttendance date={q.date} />
      </div>
    );
  }
  if (segments[0] === "payroll" && segments[1] === "generate") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <GeneratePayslips month={q.month} year={q.year} />
      </div>
    );
  }
  if (segments[0] === "careers" && segments[1] === "applications") {
    return (
      <div>
        <CareersApplications />
      </div>
    );
  }

  // Bespoke settings screen (dashboard/settings = school info)
  if (segments[0] === "settings" && segments.length === 1) {
    return (
      <div>
        <SettingsScreen />
      </div>
    );
  }

  // Bespoke report screens (dashboard/reports/*)
  if (segments[0] === "reports" && segments[1] === "fees") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <FeesReport from={q.from} to={q.to} />
      </div>
    );
  }
  if (segments[0] === "reports" && segments[1] === "students") {
    return (
      <div>
        <StudentsReport />
      </div>
    );
  }

  // Bespoke print screens (admit-cards, certificates, student-id-cards)
  const printIdx = segments.indexOf("print");
  if (printIdx >= 0 && resource.id) {
    const printTables = new Set(["admit_cards", "certificates", "student_id_cards"]);
    if (printTables.has(model.table)) {
      return (
        <div>
          <PrintScreen table={model.table} id={Number(resource.id)} />
        </div>
      );
    }
  }

  const search = typeof query.q === "string" ? query.q : undefined;
  const page = Math.max(1, Number(typeof query.page === "string" ? query.page : 1) || 1);

  return (
    <div>
      <PageHeader
        title={title}
        description={model.table}
        actions={
          <>
            <form method="get" className="flex items-center gap-2">
              <input
                name="q"
                defaultValue={search ?? ""}
                placeholder={t("common.search")}
                className="admin-input"
              />
              <button className="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">
                {t("common.search")}
              </button>
            </form>
            <ButtonLink href={`${resource.basePath}/create`} size="sm">
              + {t("common.create")}
            </ButtonLink>
          </>
        }
      />
      <ResourceTable model={model} basePath={resource.basePath} search={search} page={page} />
    </div>
  );
}
