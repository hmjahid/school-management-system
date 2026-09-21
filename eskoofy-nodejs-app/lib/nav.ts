/**
 * Dashboard sidebar map — the app↔node PARITY CONTRACT.
 *
 * Mirrors `eskoofy-laravel-app/resources/views/partials/dashboard/sidebar.blade.php`
 * exactly: same group order, same item order, same permission gates. Both the
 * sidebar component and `scripts/route-parity.ts` read from this one list, so
 * the port cannot silently drift from the Laravel reference product.
 *
 * Two shapes are exported:
 *   - `NAV_GROUPS`   : the flat, parity-checked group list (kept stable — the
 *                      parity gate and tests assert its keys/order).
 *   - `NAV_SECTIONS` : the app's *visual* layout (section headers + collapsible
 *                      groups), which the sidebar renders. It is built from the
 *                      same items so the two can never disagree.
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

/** A node inside a section — a flat link or a collapsible group. */
export type NavNode =
  | { type: "item"; item: NavItem }
  | { type: "group"; group: NavGroup };

/** A visible sidebar section (the app's `<p class="…uppercase…">` header + body). */
export interface NavSection {
  key: string;
  adminOnly?: boolean;
  nodes: NavNode[];
}

// ── Items ────────────────────────────────────────────────────────────────────

const MAIN_ITEMS: NavItem[] = [
  { key: "dashboard", path: "/dashboard", status: "done" },
  { key: "messages", path: "/dashboard/messages", status: "done", badgeKey: "unreadMessages" },
  { key: "communications", path: "/dashboard/communications", permission: "bulk_sms", status: "done" },
  { key: "bulk_sms", path: "/dashboard/sms", permission: "bulk_sms", status: "done" },
  { key: "notification_templates", path: "/dashboard/notifications/templates", status: "done" },
  { key: "notification_preferences", path: "/dashboard/notifications/preferences", status: "done" },
];

const PEOPLE: NavGroup = {
  key: "people",
  items: [
    { key: "students", path: "/dashboard/students", permission: "manage_students", status: "done" },
    { key: "teachers", path: "/dashboard/teachers", permission: "manage_teachers", status: "done" },
    { key: "parents", path: "/dashboard/parents", status: "done" },
    { key: "staff_directory", path: "/dashboard/staff", status: "done" },
    { key: "all_users", path: "/dashboard/users", permission: "manage_users", status: "done" },
  ],
};

const ACADEMICS: NavGroup = {
  key: "academics",
  items: [
    { key: "classes", path: "/dashboard/classes", permission: "manage_classes", status: "done" },
    { key: "exams", path: "/dashboard/exams", permission: "manage_exams", status: "done" },
    { key: "my_results", path: "/dashboard/exams/my-results", status: "done" },
    { key: "assignments", path: "/dashboard/assignments", status: "done" },
    { key: "class_routine", path: "/dashboard/routines", status: "done" },
  ],
};

const ADMISSIONS: NavGroup = {
  key: "admissions",
  items: [{ key: "admissions", path: "/dashboard/admissions", status: "done", badgeKey: "admissions" }],
};

const DAILY: NavGroup = {
  key: "daily",
  items: [
    { key: "attendance", path: "/dashboard/attendance", permission: "manage_attendance", status: "done" },
    { key: "bulk_mark", path: "/dashboard/attendance/bulk", permission: "manage_attendance", status: "done" },
    { key: "staff_attendance", path: "/dashboard/staff-attendance", status: "done" },
  ],
};

const FINANCE: NavGroup = {
  key: "finance",
  items: [
    { key: "fees", path: "/dashboard/fees", permission: "manage_fees", status: "done" },
    { key: "payments", path: "/dashboard/fee-payments", permission: "manage_payments", status: "done" },
    { key: "expenses", path: "/dashboard/expenses", permission: "manage_expenses", status: "done" },
    { key: "expense_categories", path: "/dashboard/expense-categories", permission: "manage_expenses", status: "done" },
    { key: "budgets", path: "/dashboard/budgets", permission: "manage_expenses", status: "done" },
    { key: "bank_reconciliation", path: "/dashboard/bank-reconciliation", permission: "manage_expenses", status: "done" },
    { key: "income_statement", path: "/dashboard/reports/income-statement", permission: "manage_expenses", status: "done" },
    { key: "balance_sheet", path: "/dashboard/reports/balance-sheet", permission: "manage_expenses", status: "done" },
    { key: "cash_flow", path: "/dashboard/reports/cash-flow", permission: "manage_expenses", status: "done" },
    { key: "ledger", path: "/dashboard/ledger", permission: "manage_ledger", status: "done" },
  ],
};

const HR: NavGroup = {
  key: "hr",
  items: [
    { key: "leaves", path: "/dashboard/leaves", permission: "manage_leaves", status: "done", badgeKey: "leaves" },
    { key: "payroll", path: "/dashboard/payroll", permission: "manage_payroll", status: "done" },
    { key: "staff_directory", path: "/dashboard/staff", status: "done" },
  ],
};

const DOCUMENTS: NavGroup = {
  key: "documents",
  items: [
    { key: "admit_cards", path: "/dashboard/admit-cards", status: "done" },
    { key: "student_id_cards", path: "/dashboard/student-id-cards", status: "done" },
    { key: "certificates", path: "/dashboard/certificates", status: "done" },
    { key: "testimonials", path: "/dashboard/testimonials", status: "done" },
    { key: "committee_members", path: "/dashboard/committee", status: "done" },
  ],
};

const LIBRARY: NavGroup = {
  key: "library",
  items: [
    { key: "books", path: "/dashboard/library/books", permission: "manage_library", status: "done" },
    { key: "book_categories", path: "/dashboard/library/categories", permission: "manage_library", status: "done" },
    { key: "book_issues", path: "/dashboard/library/issues", permission: "manage_library", status: "done" },
    { key: "library_reports", path: "/dashboard/library/reports", permission: "manage_library", status: "done" },
  ],
};

const OPERATIONS_ITEMS: NavItem[] = [
  { key: "events", path: "/dashboard/events", permission: "manage_events", status: "done" },
  { key: "calendar", path: "/dashboard/events/calendar", permission: "manage_events", status: "done" },
  { key: "transport", path: "/dashboard/transport/vehicles", permission: "manage_transport", status: "done" },
  { key: "hostel_management", path: "/dashboard/hostels", permission: "manage_hostels", status: "done" },
];

const SYSTEM_ITEMS: NavItem[] = [
  { key: "activity_log", path: "/dashboard/activity", status: "done" },
  { key: "visitor_logs", path: "/dashboard/visitor-logs", status: "done" },
  { key: "backups", path: "/dashboard/backup", status: "done" },
];

const WEBSITE_CMS: NavGroup = {
  key: "website_cms",
  items: [
    { key: "all_pages", path: "/dashboard/cms/pages", permission: "manage_cms", status: "done" },
    { key: "cms_settings", path: "/dashboard/settings/cms", permission: "manage_cms", status: "done" },
    { key: "global_labels", path: "/dashboard/settings/global-labels", permission: "manage_cms", status: "done" },
    { key: "news_events", path: "/dashboard/news", permission: "manage_cms", status: "done" },
    { key: "gallery", path: "/dashboard/gallery", permission: "manage_cms", status: "done" },
    { key: "announcements", path: "/dashboard/announcements", permission: "manage_cms", status: "done" },
    { key: "notices", path: "/dashboard/notices", permission: "manage_notices", status: "done" },
    { key: "documents_label", path: "/dashboard/documents", permission: "manage_documents", status: "done" },
    { key: "media_library", path: "/dashboard/media", permission: "manage_cms", status: "done" },
    { key: "form_submissions", path: "/dashboard/contact-submissions", permission: "manage_cms", status: "done" },
    { key: "careers", path: "/dashboard/careers", permission: "manage_cms", status: "done" },
    { key: "job_applications", path: "/dashboard/careers/applications", permission: "manage_cms", status: "done" },
  ],
};

const SCHOOL_INFO: NavGroup = {
  key: "school_info",
  items: [{ key: "school_info", path: "/dashboard/settings", permission: "manage_settings", status: "done" }],
};

const USERS_AND_ROLES: NavGroup = {
  key: "users_and_roles",
  items: [
    { key: "users", path: "/dashboard/users", permission: "manage_users", status: "done" },
    { key: "roles", path: "/dashboard/roles", permission: "manage_users", status: "done" },
    { key: "permissions", path: "/dashboard/permissions", permission: "manage_users", status: "done" },
  ],
};

const CONFIGURATION_ITEMS: NavItem[] = [
  { key: "settings", path: "/dashboard/settings", permission: "manage_settings", status: "done" },
  { key: "reports", path: "/dashboard/reports", permission: "view_reports", status: "done" },
  { key: "report_builder", path: "/dashboard/reports/builder", permission: "view_reports", status: "done" },
  { key: "analytics", path: "/dashboard/analytics", permission: "view_reports", status: "done" },
  { key: "bulk_import_export", path: "/dashboard/bulk", status: "done" },
];

const HELP_ITEMS: NavItem[] = [
  { key: "about", path: "/dashboard/about", status: "done" },
  { key: "help_documentation", path: "/dashboard/help", status: "done" },
];

// ── Flat parity contract (keys/order asserted by tests + route:parity) ───────

export const NAV_GROUPS: NavGroup[] = [
  { key: "main", items: MAIN_ITEMS },
  { key: "academic", children: [PEOPLE, ACADEMICS, ADMISSIONS, DAILY] },
  { key: "finance", items: FINANCE.items },
  { key: "hr", items: HR.items },
  { key: "documents", items: DOCUMENTS.items },
  { key: "library", items: LIBRARY.items },
  { key: "operations", items: OPERATIONS_ITEMS },
  { key: "system", adminOnly: true, items: SYSTEM_ITEMS },
  { key: "website", adminOnly: true, children: [WEBSITE_CMS, SCHOOL_INFO] },
  { key: "administration", adminOnly: true, children: [USERS_AND_ROLES] },
  { key: "configuration", adminOnly: true, items: CONFIGURATION_ITEMS },
  { key: "help_group", items: HELP_ITEMS },
];

// ── Visual layout (the app's section headers + collapsible groups) ────────────

const group = (g: NavGroup): NavNode => ({ type: "group", group: g });
const item = (i: NavItem): NavNode => ({ type: "item", item: i });

export const NAV_SECTIONS: NavSection[] = [
  { key: "main", nodes: MAIN_ITEMS.map(item) },
  {
    key: "academic",
    // Order mirrors the app: People, Academics, Admissions, Daily, Finance, HR,
    // Documents, Library, then the flat Events/Calendar/Transport/Hostels links.
    nodes: [
      group(PEOPLE),
      group(ACADEMICS),
      ...ADMISSIONS.items!.map(item),
      group(DAILY),
      group(FINANCE),
      group(HR),
      group(DOCUMENTS),
      group(LIBRARY),
      ...OPERATIONS_ITEMS.map(item),
    ],
  },
  { key: "system", adminOnly: true, nodes: SYSTEM_ITEMS.map(item) },
  { key: "website", adminOnly: true, nodes: [group(WEBSITE_CMS), ...SCHOOL_INFO.items!.map(item)] },
  { key: "administration", adminOnly: true, nodes: [group(USERS_AND_ROLES)] },
  { key: "configuration", adminOnly: true, nodes: CONFIGURATION_ITEMS.map(item) },
  { key: "help_group", nodes: HELP_ITEMS.map(item) },
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
