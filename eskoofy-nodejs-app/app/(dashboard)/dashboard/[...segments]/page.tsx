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
import { SettingsScreen, SchoolInfoScreen } from "@/components/dashboard/SettingsScreen";
import { NotificationTemplates, NotificationPreferences, NotificationsInbox } from "@/components/dashboard/NotificationsScreen";
import { PermissionsMatrix } from "@/components/dashboard/PermissionsScreen";
import { MediaLibrary } from "@/components/dashboard/MediaScreen";
import { ActivityScreen } from "@/components/dashboard/ActivityScreen";
import { StaffAttendanceScreen } from "@/components/dashboard/StaffAttendanceScreen";
import { HelpScreen } from "@/components/dashboard/HelpScreen";
import { helpContent } from "@/lib/help-content";
import { AdmissionReview } from "@/components/dashboard/AdmissionsScreen";
import { prisma } from "@/lib/prisma";
import { PromoteStudents, MyResults, Onboarding, ProfileScreen } from "@/components/dashboard/MiscScreens";
import { CmsPages, CmsEdit } from "@/components/dashboard/CmsScreen";
import { EventsCalendar, LedgerBook } from "@/components/dashboard/CalendarLedgerScreen";
import { BulkAttendance, GeneratePayslips, CareersApplications } from "@/components/dashboard/BulkScreens";
import { BalanceSheet, IncomeStatement, CashFlow } from "@/components/dashboard/FinancialReports";
import { ExamResults, Payslips } from "@/components/dashboard/ExamPayrollScreens";
import { SettingsTab, AttendanceReport, StudentResults, StaffAttendanceReport, AssignmentsSubmissions, BatchGenerate } from "@/components/dashboard/ExtraScreens";
import { SmsTemplates, DueFeeReminder } from "@/components/dashboard/SmsScreens";
import { MarksheetPdf, PayslipShow, PayrollStructures, SoftwareAbout, TestimonialsPrint, CareersForm } from "@/components/dashboard/FinalScreens";
import { ProgressReportsIndex, ProgressReportShow, SeatPlansIndex, SeatPlanShow } from "@/components/dashboard/ProgressSeatPlanScreens";
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

  const q = query as Record<string, string | undefined>;

  // Bespoke screens whose path name matches a table, so the generic engine
  // would otherwise hijack them (notifications inbox, permissions matrix,
  // media library picker, admissions review).
  if (segments[0] === "notifications" && segments.length === 1 && resource.mode === "index") {
    return <NotificationsInbox />;
  }
  if (segments[0] === "activity" && segments.length === 1 && resource.mode === "index") {
    return <ActivityScreen filters={q} />;
  }
  if (segments[0] === "staff-attendance" && segments.length === 1 && resource.mode === "index") {
    return <StaffAttendanceScreen filters={q} />;
  }
  if (segments[0] === "help" && segments.length === 1) {
    const help = helpContent();
    return <HelpScreen sections={help.sections} strings={help.strings} />;
  }
  if (segments[0] === "permissions" && segments.length === 1 && resource.mode === "index") {
    return <PermissionsMatrix />;
  }
  if (segments[0] === "media" && segments.length === 1 && resource.mode === "index") {
    const [rows, categoryRows] = await Promise.all([
      prisma.website_media.findMany({ orderBy: { created_at: "desc" }, take: 200 }).catch(() => []),
      prisma.website_media.findMany({ distinct: ["category"], select: { category: true } }).catch(() => []),
    ]);
    const categories = categoryRows.map((row) => row.category).filter((value): value is string => Boolean(value));
    return <MediaLibrary rows={rows} categories={categories} select={q.select === "1"} />;
  }
  if (segments[0] === "admissions" && resource.mode === "show" && resource.id) {
    return <AdmissionReview id={Number(resource.id)} />;
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
  if (segments[0] === "my-results") {
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

  // Financial reports (balance-sheet, income-statement, cash-flow)
  if (segments[0] === "reports" && segments[1] === "balance-sheet") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <BalanceSheet asOf={q.as_of} />
      </div>
    );
  }
  if (segments[0] === "reports" && segments[1] === "income-statement") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <IncomeStatement from={q.from} to={q.to} />
      </div>
    );
  }
  if (segments[0] === "reports" && segments[1] === "cash-flow") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <CashFlow from={q.from} to={q.to} />
      </div>
    );
  }

  // Exam results + payroll payslips
  if (segments[0] === "exams" && segments[2] === "results") {
    const q = query as Record<string, string | undefined>;
    const examId = segments[1] ? Number(segments[1]) : q.exam_id ? Number(q.exam_id) : undefined;
    return (
      <div>
        <ExamResults examId={examId} />
      </div>
    );
  }
  if (segments[0] === "payroll" && segments[1] === "payslips") {
    return (
      <div>
        <Payslips />
      </div>
    );
  }

  // Settings tabs
  if (segments[0] === "settings" && segments[1] === "about") {
    return (<div><SettingsTab tab="about" /></div>);
  }
  if (segments[0] === "settings" && segments[1] === "cms") {
    return (<div><SettingsTab tab="cms" /></div>);
  }
  if (segments[0] === "settings" && segments[1] === "global-labels") {
    return (<div><SettingsTab tab="global-labels" /></div>);
  }

  // SMS screens
  if (segments[0] === "sms" && segments[1] === "templates") {
    return (<div><SmsTemplates /></div>);
  }
  if (segments[0] === "sms" && segments[1] === "due-reminder") {
    return (<div><DueFeeReminder searchParams={Promise.resolve(query as Record<string, string>)} /></div>);
  }

  // Attendance report
  if (segments[0] === "reports" && segments[1] === "attendance") {
    const q = query as Record<string, string | undefined>;
    return (<div><AttendanceReport from={q.from} to={q.to} /></div>);
  }

  // Student results (dashboard/students/{id}/results)
  if (segments[0] === "students" && segments.length === 3 && segments[2] === "results") {
    return (<div><StudentResults studentId={Number(segments[1])} /></div>);
  }

  // Staff attendance report
  if (segments[0] === "staff-attendance" && segments[1] === "report") {
    const q = query as Record<string, string | undefined>;
    return (<div><StaffAttendanceReport from={q.from} to={q.to} /></div>);
  }

  // Assignments submissions
  if (segments[0] === "assignments" && segments[1] === "submissions") {
    return (<div><AssignmentsSubmissions /></div>);
  }

  // Batch generation
  if (segments[0] === "admit-cards" && segments[1] === "batch") {
    return (<div><BatchGenerate kind="admit-cards" /></div>);
  }
  if (segments[0] === "student-id-cards" && segments[1] === "batch") {
    return (<div><BatchGenerate kind="student-id-cards" /></div>);
  }

  // Marksheet PDF print (dashboard/exams/{id}/results/{result}/marksheet)
  if (segments[0] === "exams" && segments.includes("marksheet")) {
    const q = query as Record<string, string | undefined>;
    return (<div><MarksheetPdf studentId={q.student_id ? Number(q.student_id) : undefined} examId={segments[1] ? Number(segments[1]) : undefined} /></div>);
  }

  // Payslip show + structures
  if (segments[0] === "payroll" && segments[1] === "payslips" && segments[2]) {
    return (<div><PayslipShow id={Number(segments[2])} /></div>);
  }
  if (segments[0] === "payroll" && segments[1] === "structures") {
    return (<div><PayrollStructures /></div>);
  }

  // SMS preview is served by its static page (app/(dashboard)/dashboard/sms/preview)

  // Software about page
  if (segments[0] === "software") {
    return (<div><SoftwareAbout /></div>);
  }

  // Testimonials print
  if (segments[0] === "testimonials" && segments.includes("print")) {
    return (<div><TestimonialsPrint /></div>);
  }

  // Careers form
  if (segments[0] === "careers" && segments[1] === "form") {
    return (<div><CareersForm /></div>);
  }

  // Progress reports
  if (segments[0] === "progress-reports" && segments.length === 1) {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <ProgressReportsIndex classId={q.class_id ? Number(q.class_id) : undefined} sectionId={q.section_id ? Number(q.section_id) : undefined} batchId={q.batch_id ? Number(q.batch_id) : undefined} page={q.page ? Number(q.page) : undefined} />
      </div>
    );
  }
  if (segments[0] === "progress-reports" && segments.length === 3 && segments[2] === "generate") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <ProgressReportShow studentId={Number(segments[1])} view={q.view === "1"} />
      </div>
    );
  }

  // Seat plans
  if (segments[0] === "seat-plans" && segments.length === 1) {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <SeatPlansIndex published={q.published} page={q.page ? Number(q.page) : undefined} />
      </div>
    );
  }
  if (segments[0] === "seat-plans" && segments.length === 3 && segments[2] === "generate") {
    const q = query as Record<string, string | undefined>;
    return (
      <div>
        <SeatPlanShow examId={Number(segments[1])} perRoom={q.per_room ? Number(q.per_room) : undefined} view={q.view === "1"} />
      </div>
    );
  }

  // Tabbed settings (dashboard/settings) + School Info (dashboard/settings/general)
  if (segments[0] === "settings" && segments.length === 1) {
    return <SettingsScreen tab={typeof q.tab === "string" ? q.tab : "theme"} />;
  }
  if (segments[0] === "settings" && segments[1] === "general") {
    return <SchoolInfoScreen />;
  }

  // Profile edit (dashboard/profile)
  if (segments[0] === "profile" && segments.length === 1) {
    return (
      <div>
        <ProfileScreen searchParams={Promise.resolve(query)} />
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
