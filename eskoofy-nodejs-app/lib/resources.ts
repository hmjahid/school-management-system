import { MODEL_BY_TABLE, type ModelMeta } from "@/lib/schema";

/**
 * Maps a dashboard/API path to a Prisma model (i.e. an app table).
 *
 * The Laravel app exposes ~40 dashboard resources over 100+ tables; we resolve
 * them by table name so the mapping tracks the app's schema instead of a
 * hand-maintained list. `OVERRIDES` fixes the handful of paths whose segment
 * does not spell the table name.
 */

const OVERRIDES: Record<string, string> = {
  classes: "school_classes",
  class: "school_classes",
  "fee-payments": "fee_payments",
  "fee-payment": "fee_payments",
  "staff-attendance": "staff_attendances",
  "expense-categories": "expense_categories",
  "bank-reconciliation": "ledger_entries",
  "income-statement": "ledger_entries",
  "balance-sheet": "ledger_entries",
  "cash-flow": "ledger_entries",
  notifications: "notification_templates",
  "notification-templates": "notification_templates",
  "visitor-logs": "visitor_logs",
  "activity-log": "activity_logs",
  admissions: "admissions",
  "admit-cards": "admit_cards",
  "student-id-cards": "student_id_cards",
  "hostel-management": "hostels",
  transport: "transport_routes",
  "contact-submissions": "contact_submissions",
  "job-applications": "job_applications",
  "email-templates": "notification_templates",
  "all-pages": "website_contents",
  "cms-settings": "website_settings",
  "global-labels": "website_settings",
  "media-library": "website_media",
  news: "news",
  gallery: "galleries",
  announcements: "announcements",
  notices: "notices",
  documents: "website_documents",
  careers: "careers",
  "school-info": "website_settings",
  users: "users",
  roles: "roles",
  permissions: "roles",
  settings: "website_settings",
  backup: "activity_logs",
  backups: "activity_logs",
  analytics: "activity_logs",
  "bulk-import-export": "students",
  about: "about_contents",
  help: "about_contents",
  dashboard: "students",
  parents: "guardians",
  parent: "guardians",
  staff: "users",
  leaves: "leave_requests",
  leave: "leave_requests",
  ledger: "ledger_entries",
  committee: "committee_members",
  payroll: "payslips",
  "library-books": "books",
  "library-categories": "book_categories",
  "library-issues": "book_issues",
  "library-reports": "library_settings",
  activity: "activity_log",
  "activity-logs": "activity_log",
  media: "website_media",
  "library/books": "books",
  "library/categories": "book_categories",
  "library/issues": "book_issues",
  bank_reconciliation: "ledger_entries",
  income_statement: "ledger_entries",
  balance_sheet: "ledger_entries",
  cash_flow: "ledger_entries",
  fee_payments: "fee_payments",
  staff_attendance: "staff_attendances",
  expense_categories: "expense_categories",
  notification_templates: "notification_templates",
  visitor_logs: "visitor_logs",
  activity_log: "activity_log",
  admit_cards: "admit_cards",
  student_id_cards: "student_id_cards",
  hostel_management: "hostels",
  contact_submissions: "contact_submissions",
  job_applications: "job_applications",
  email_templates: "notification_templates",
  all_pages: "website_contents",
  cms_settings: "website_settings",
  global_labels: "website_settings",
  media_library: "website_media",
  library_books: "books",
  library_categories: "book_categories",
  library_issues: "book_issues",
  library_reports: "library_settings",
  reports_income_statement: "ledger_entries",
  reports_balance_sheet: "ledger_entries",
  reports_cash_flow: "ledger_entries",
  reports_analytics: "ledger_entries",
  cms_pages: "website_contents",
};

/** Underscore form of a path segment (`fee-payments` → `fee_payments`). */
export function slugToTable(segment: string): string {
  return segment.replace(/[^a-z0-9]+/gi, "_").toLowerCase();
}

/**
 * Resolve the model for a path's segments (the part after `/dashboard/`
 * or `/api/v1/`, dropping ids/actions).
 */
export function resolveModel(segments: string[]): ModelMeta | undefined {
  const cleaned = segments.filter((segment) => segment && !/^\d+$/.test(segment));
  if (cleaned.length === 0) return undefined;

  const candidates: string[] = [];
  if (cleaned[0]) candidates.push(slugToTable(cleaned[0]));
  if (cleaned[0] && cleaned[1]) candidates.push(`${slugToTable(cleaned[0])}_${slugToTable(cleaned[1])}`);

  for (const candidate of candidates) {
    const override = OVERRIDES[candidate];
    if (override && MODEL_BY_TABLE.has(override)) return MODEL_BY_TABLE.get(override);

    const direct = MODEL_BY_TABLE.get(candidate);
    if (direct) return direct;

    const toggled = candidate.endsWith("s") ? candidate.slice(0, -1) : `${candidate}s`;
    const alt = MODEL_BY_TABLE.get(toggled);
    if (alt) return alt;
  }

  return undefined;
}

/** Every dashboard resource slug the app exposes, with its resolved model. */
export function dashboardResourceSlugs(slugs: string[]): Array<{ slug: string; model?: ModelMeta }> {
  return slugs.map((slug) => ({ slug, model: resolveModel(slug.split("/")) }));
}
