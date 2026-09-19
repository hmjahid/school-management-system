/**
 * Role → permission map, mirroring the Laravel app's spatie/laravel-permission
 * seed data (`RolesAndPermissionsSeeder`). Keep permission names identical to
 * the app so feature code and route gates port 1:1.
 *
 * This is DATA. Gate checks call `can()`, never `if (role === "admin")`.
 */

export const ROLES = [
  "admin",
  "teacher",
  "staff",
  "accountant",
  "librarian",
  "parent",
  "student",
] as const;

export type Role = (typeof ROLES)[number];

export const PERMISSIONS = [
  "manage_students",
  "manage_teachers",
  "manage_parents",
  "manage_classes",
  "manage_attendance",
  "manage_exams",
  "manage_results",
  "manage_fees",
  "manage_payments",
  "manage_expenses",
  "manage_ledger",
  "manage_library",
  "manage_transport",
  "manage_hostels",
  "manage_events",
  "manage_notices",
  "manage_documents",
  "manage_payroll",
  "manage_leaves",
  "manage_cms",
  "manage_settings",
  "manage_users",
  "view_reports",
  "bulk_sms",
] as const;

export type Permission = (typeof PERMISSIONS)[number];

const ADMIN: Permission[] = [...PERMISSIONS];

export const ROLE_PERMISSIONS: Record<Role, Permission[]> = {
  admin: ADMIN,
  teacher: [
    "manage_attendance",
    "manage_exams",
    "manage_results",
    "view_reports",
  ],
  staff: ["manage_attendance", "manage_notices", "view_reports"],
  accountant: [
    "manage_fees",
    "manage_payments",
    "manage_expenses",
    "manage_ledger",
    "manage_payroll",
    "view_reports",
  ],
  librarian: ["manage_library", "view_reports"],
  parent: [],
  student: [],
};

export function rolePermissions(role: string): Permission[] {
  return ROLE_PERMISSIONS[role as Role] ?? [];
}

export function can(role: string | undefined | null, permission: Permission): boolean {
  if (!role) return false;
  if (role === "admin") return true;
  return rolePermissions(role).includes(permission);
}

export function canAny(role: string | undefined | null, permissions: Permission[]): boolean {
  return permissions.some((permission) => can(role, permission));
}

/**
 * Permission required to open a dashboard resource, keyed by the app table
 * name. Mirrors the `permission:*` / `@can` gates on the app's dashboard
 * routes; unknown tables fall back to a read gate.
 */
const RESOURCE_PERMISSIONS: Record<string, Permission> = {
  students: "manage_students",
  users: "manage_users",
  teachers: "manage_teachers",
  guardians: "manage_parents",
  school_classes: "manage_classes",
  sections: "manage_classes",
  subjects: "manage_classes",
  batches: "manage_classes",
  routines: "manage_classes",
  attendances: "manage_attendance",
  staff_attendances: "manage_attendance",
  fees: "manage_fees",
  fee_payments: "manage_payments",
  invoices: "manage_fees",
  payments: "manage_payments",
  expenses: "manage_expenses",
  expense_categories: "manage_expenses",
  budgets: "manage_expenses",
  ledger_entries: "manage_ledger",
  chart_of_accounts: "manage_ledger",
  exams: "manage_exams",
  exam_results: "manage_results",
  grades: "manage_results",
  books: "manage_library",
  book_categories: "manage_library",
  book_issues: "manage_library",
  transport_routes: "manage_transport",
  vehicles: "manage_transport",
  hostels: "manage_hostels",
  events: "manage_events",
  notices: "manage_notices",
  testimonials: "manage_documents",
  certificates: "manage_documents",
  admit_cards: "manage_documents",
  student_id_cards: "manage_documents",
  payslips: "manage_payroll",
  salary_structures: "manage_payroll",
  leave_requests: "manage_leaves",
  leave_types: "manage_leaves",
  website_contents: "manage_cms",
  website_settings: "manage_settings",
  website_media: "manage_cms",
  announcements: "manage_cms",
  news: "manage_cms",
  galleries: "manage_cms",
};

export function permissionForTable(table: string): Permission {
  return RESOURCE_PERMISSIONS[table] ?? "view_reports";
}
