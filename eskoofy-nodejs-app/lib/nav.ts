/**
 * Dashboard sidebar map — the app↔node PARITY CONTRACT.
 *
 * Mirrors `eskoofy-laravel-app/resources/views/partials/dashboard/sidebar.blade.php`
 * exactly: same group order, same item order, same permission gates. Both the
 * sidebar component and `scripts/route-parity.ts` read from this one list, so
 * the port cannot silently drift from the Laravel reference product.
 *
 * `status`:
 *   - "done"    : route + page implemented in this Node variant
 *   - "planned" : listed for parity, not yet ported (rendered disabled in the UI)
 */

import type { Permission } from "@/lib/permissions";

export type NavStatus = "done" | "planned";

export interface NavItem {
  /** i18n key under `dashboard.*`, identical to the app's `__()` key suffix. */
  key: string;
  /** Route path in this variant. */
  path: string;
  permission?: Permission;
  status: NavStatus;
  badgeKey?: string;
}

export interface NavGroup {
  key: string;
  /** Direct items. Optional because some groups only hold child groups. */
  items?: NavItem[];
  /** Nested sub-groups (the app's collapsible `<details>` sections). */
  children?: NavGroup[];
  adminOnly?: boolean;
}

export const NAV_GROUPS: NavGroup[] = [
  {
    key: "main",
    items: [
      { key: "dashboard", path: "/dashboard", status: "done" },
      { key: "messages", path: "/dashboard/messages", status: "planned", badgeKey: "unreadMessages" },
      { key: "communications", path: "/dashboard/communications", permission: "bulk_sms", status: "planned" },
      { key: "bulk_sms", path: "/dashboard/sms", permission: "bulk_sms", status: "planned" },
      { key: "notification_templates", path: "/dashboard/notifications/templates", status: "planned" },
      { key: "notification_preferences", path: "/dashboard/notifications/preferences", status: "planned" },
    ],
  },
  {
    key: "academic",
    children: [
      {
        key: "people",
        items: [
          { key: "students", path: "/dashboard/students", permission: "manage_students", status: "done" },
          { key: "teachers", path: "/dashboard/teachers", permission: "manage_teachers", status: "done" },
          { key: "parents", path: "/dashboard/parents", status: "planned" },
          { key: "staff_directory", path: "/dashboard/staff", status: "planned" },
          { key: "all_users", path: "/dashboard/users", permission: "manage_users", status: "planned" },
        ],
      },
      {
        key: "academics",
        items: [
          { key: "classes", path: "/dashboard/classes", permission: "manage_classes", status: "done" },
          { key: "exams", path: "/dashboard/exams", permission: "manage_exams", status: "done" },
          { key: "my_results", path: "/dashboard/exams/my-results", status: "planned" },
          { key: "assignments", path: "/dashboard/assignments", status: "planned" },
          { key: "class_routine", path: "/dashboard/routines", status: "planned" },
        ],
      },
      {
        key: "admissions",
        items: [{ key: "admissions", path: "/dashboard/admissions", status: "planned", badgeKey: "admissions" }],
      },
      {
        key: "daily",
        items: [
          { key: "attendance", path: "/dashboard/attendance", permission: "manage_attendance", status: "done" },
          { key: "bulk_mark", path: "/dashboard/attendance/bulk", permission: "manage_attendance", status: "planned" },
          { key: "staff_attendance", path: "/dashboard/staff-attendance", status: "planned" },
        ],
      },
    ],
  },
  {
    key: "finance",
    items: [
      { key: "fees", path: "/dashboard/fees", permission: "manage_fees", status: "done" },
      { key: "payments", path: "/dashboard/fee-payments", permission: "manage_payments", status: "planned" },
      { key: "expenses", path: "/dashboard/expenses", permission: "manage_expenses", status: "planned" },
      { key: "expense_categories", path: "/dashboard/expense-categories", permission: "manage_expenses", status: "planned" },
      { key: "budgets", path: "/dashboard/budgets", permission: "manage_expenses", status: "planned" },
      { key: "bank_reconciliation", path: "/dashboard/bank-reconciliation", permission: "manage_expenses", status: "planned" },
      { key: "income_statement", path: "/dashboard/reports/income-statement", permission: "manage_expenses", status: "planned" },
      { key: "balance_sheet", path: "/dashboard/reports/balance-sheet", permission: "manage_expenses", status: "planned" },
      { key: "cash_flow", path: "/dashboard/reports/cash-flow", permission: "manage_expenses", status: "planned" },
      { key: "ledger", path: "/dashboard/ledger", permission: "manage_ledger", status: "planned" },
    ],
  },
  {
    key: "hr",
    items: [
      { key: "leaves", path: "/dashboard/leaves", permission: "manage_leaves", status: "planned", badgeKey: "leaves" },
      { key: "payroll", path: "/dashboard/payroll", permission: "manage_payroll", status: "planned" },
      { key: "staff_directory", path: "/dashboard/staff", status: "planned" },
    ],
  },
  {
    key: "documents",
    items: [
      { key: "admit_cards", path: "/dashboard/admit-cards", status: "planned" },
      { key: "student_id_cards", path: "/dashboard/student-id-cards", status: "planned" },
      { key: "certificates", path: "/dashboard/certificates", status: "planned" },
      { key: "testimonials", path: "/dashboard/testimonials", status: "planned" },
      { key: "committee_members", path: "/dashboard/committee", status: "planned" },
    ],
  },
  {
    key: "library",
    items: [
      { key: "books", path: "/dashboard/library/books", permission: "manage_library", status: "planned" },
      { key: "book_categories", path: "/dashboard/library/categories", permission: "manage_library", status: "planned" },
      { key: "book_issues", path: "/dashboard/library/issues", permission: "manage_library", status: "planned" },
      { key: "library_reports", path: "/dashboard/library/reports", permission: "manage_library", status: "planned" },
    ],
  },
  {
    key: "operations",
    items: [
      { key: "events", path: "/dashboard/events", permission: "manage_events", status: "planned" },
      { key: "calendar", path: "/dashboard/events/calendar", permission: "manage_events", status: "planned" },
      { key: "transport", path: "/dashboard/transport/vehicles", permission: "manage_transport", status: "planned" },
      { key: "hostel_management", path: "/dashboard/hostels", permission: "manage_hostels", status: "planned" },
    ],
  },
  {
    key: "system",
    adminOnly: true,
    items: [
      { key: "activity_log", path: "/dashboard/activity", status: "planned" },
      { key: "visitor_logs", path: "/dashboard/visitor-logs", status: "planned" },
      { key: "backups", path: "/dashboard/backup", status: "done" },
    ],
  },
  {
    key: "website",
    adminOnly: true,
    children: [
      {
        key: "website_cms",
        items: [
          { key: "all_pages", path: "/dashboard/cms/pages", permission: "manage_cms", status: "planned" },
          { key: "cms_settings", path: "/dashboard/settings/cms", permission: "manage_cms", status: "planned" },
          { key: "global_labels", path: "/dashboard/settings/global-labels", permission: "manage_cms", status: "planned" },
          { key: "news_events", path: "/dashboard/news", permission: "manage_cms", status: "planned" },
          { key: "gallery", path: "/dashboard/gallery", permission: "manage_cms", status: "planned" },
          { key: "announcements", path: "/dashboard/announcements", permission: "manage_cms", status: "planned" },
          { key: "notices", path: "/dashboard/notices", permission: "manage_notices", status: "planned" },
          { key: "documents_label", path: "/dashboard/documents", permission: "manage_documents", status: "planned" },
          { key: "media_library", path: "/dashboard/media", permission: "manage_cms", status: "planned" },
          { key: "form_submissions", path: "/dashboard/contact-submissions", permission: "manage_cms", status: "planned" },
          { key: "careers", path: "/dashboard/careers", permission: "manage_cms", status: "planned" },
          { key: "job_applications", path: "/dashboard/careers/applications", permission: "manage_cms", status: "planned" },
        ],
      },
      { key: "school_info", items: [{ key: "school_info", path: "/dashboard/settings", permission: "manage_settings", status: "planned" }] },
    ],
  },
  {
    key: "administration",
    adminOnly: true,
    children: [
      {
        key: "users_and_roles",
        items: [
          { key: "users", path: "/dashboard/users", permission: "manage_users", status: "planned" },
          { key: "roles", path: "/dashboard/roles", permission: "manage_users", status: "planned" },
          { key: "permissions", path: "/dashboard/permissions", permission: "manage_users", status: "planned" },
        ],
      },
    ],
  },
  {
    key: "configuration",
    adminOnly: true,
    items: [
      { key: "settings", path: "/dashboard/settings", permission: "manage_settings", status: "planned" },
      { key: "reports", path: "/dashboard/reports", permission: "view_reports", status: "planned" },
      { key: "report_builder", path: "/dashboard/reports/builder", permission: "view_reports", status: "planned" },
      { key: "analytics", path: "/dashboard/analytics", permission: "view_reports", status: "planned" },
      { key: "bulk_import_export", path: "/dashboard/bulk", status: "done" },
    ],
  },
  {
    key: "help_group",
    items: [
      { key: "about", path: "/dashboard/about", status: "planned" },
      { key: "help_documentation", path: "/dashboard/help", status: "planned" },
    ],
  },
];

/**
 * Sidebar CHROME keys the app's sidebar renders in addition to nav items
 * (search, favorites, install, dark mode, logout…). Listed here so the parity
 * check accounts for them even though they are not dashboard routes.
 */
export const SIDEBAR_CHROME_KEYS = [
  "admin_panel",
  "dark_mode",
  "favorites",
  "install_app",
  "logout",
  "search_menu",
  "type_to_search",
  "unpin",
] as const;

/** Flatten a group tree into a single item list (groups, then their children). */
export function flattenNav(groups: NavGroup[] = NAV_GROUPS): NavItem[] {
  const out: NavItem[] = [];
  for (const group of groups) {
    out.push(...(group.items ?? []));
    if (group.children) out.push(...flattenNav(group.children));
  }
  return out;
}

/** All i18n keys the sidebar references (the parity comparison set). */
export function navLabelKeys(): string[] {
  const keys: string[] = [];
  const walk = (groups: NavGroup[]) => {
    for (const group of groups) {
      keys.push(`dashboard.${group.key}`);
      for (const item of group.items ?? []) keys.push(`dashboard.${item.key}`);
      if (group.children) walk(group.children);
    }
  };
  walk(NAV_GROUPS);
  for (const chrome of SIDEBAR_CHROME_KEYS) keys.push(`dashboard.${chrome}`);
  return keys;
}

/** Paths that are actually implemented in this variant. */
export function implementedPaths(): string[] {
  return flattenNav().filter((item) => item.status === "done").map((item) => item.path);
}
