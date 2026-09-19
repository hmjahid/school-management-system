import Link from "next/link";
import { NAV_GROUPS, type NavGroup, type NavItem } from "@/lib/nav";
import { can } from "@/lib/permissions";
import { t } from "@/lib/i18n";
import { logoutAction } from "@/app/(site)/login/actions";

const ICONS: Record<string, string> = {
  dashboard: "M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10",
  students: "M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z",
  teachers: "M12 14l9-5-9-5-9 5 9 5zm0 0v6m-7-9v5a7 3 0 0014 0v-5",
  classes: "M4 6h16M4 10h16M4 14h10M4 18h7",
  attendance: "M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z",
  fees: "M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6",
  exams: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z",
  library: "M12 6.25A8 8 0 006 4v12a8 8 0 016 2.25M12 6.25A8 8 0 0118 4v12a8 8 0 00-6 2.25",
  messages: "M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z",
  settings: "M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.6 1.1 1.7 1.7 0 012.4 2.4 1.7 1.7 0 001.1 2.6 1.7 1.7 0 010 3.4 1.7 1.7 0 00-1.1 2.6 1.7 1.7 0 01-2.4 2.4 1.7 1.7 0 00-2.6 1.1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.6-1.1 1.7 1.7 0 01-2.4-2.4 1.7 1.7 0 00-1.1-2.6 1.7 1.7 0 010-3.4 1.7 1.7 0 001.1-2.6 1.7 1.7 0 012.4-2.4 1.7 1.7 0 002.6-1.1zM15 12a3 3 0 11-6 0 3 3 0 016 0z",
  reports: "M3 13h4v8H3zM10 8h4v13h-4zM17 3h4v18h-4z",
  events: "M8 7V3m8 4V3M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM3 11h18",
  users: "M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8z",
  transport: "M3 16V8l2-4h10l2 4v8M3 16h14M6 20a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z",
  hostels: "M3 21V9l9-6 9 6v12M9 21v-6h6v6",
  documents: "M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6",
  website: "M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3 12h18M12 3a15 15 0 010 18 15 15 0 010-18z",
  default: "M4 6h16M4 12h16M4 18h10",
};

function iconFor(item: NavItem): string {
  const path = ICONS[item.key];
  if (path) return path;
  if (item.path.includes("/library")) return ICONS.library;
  if (item.path.includes("/report")) return ICONS.reports;
  if (item.path.includes("/settings")) return ICONS.settings;
  return ICONS.default;
}

function isActive(pathname: string, item: NavItem): boolean {
  return pathname === item.path || pathname.startsWith(`${item.path}/`);
}

function groupHasActive(group: NavGroup, pathname: string, role: string): boolean {
  const items = group.items ?? [];
  if (items.some((item) => (!item.permission || can(role, item.permission)) && isActive(pathname, item))) return true;
  return (group.children ?? []).some((child) => groupHasActive(child, pathname, role));
}

function Item({ item, pathname, role }: { item: NavItem; pathname: string; role: string }) {
  if (item.permission && !can(role, item.permission)) return null;
  const active = isActive(pathname, item);
  const label = t(`dashboard.${item.key}`);

  return (
    <li>
      <Link
        href={item.path}
        className={`flex items-center gap-2.5 rounded-lg px-3 py-2 transition ${
          active
            ? "bg-blue-600 font-semibold text-white"
            : "text-slate-300 hover:bg-slate-800 hover:text-white"
        }`}
      >
        <svg viewBox="0 0 24 24" className="h-4 w-4 shrink-0 opacity-90" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
          <path d={iconFor(item)} />
        </svg>
        <span className="flex-1 truncate">{label}</span>
        {item.status === "planned" ? (
          <span className="rounded-full bg-slate-800 px-1.5 py-0.5 text-[9px] uppercase tracking-wide text-slate-500">
            {t("common.planned")}
          </span>
        ) : null}
      </Link>
    </li>
  );
}

function Group({ group, pathname, role }: { group: NavGroup; pathname: string; role: string }) {
  if (group.adminOnly && role !== "admin") return null;

  const items = (group.items ?? []).filter((item) => !item.permission || can(role, item.permission));
  const children = (group.children ?? []).filter((child) => !child.adminOnly || role === "admin");
  if (items.length === 0 && children.length === 0) return null;

  const open = groupHasActive(group, pathname, role);
  const label = t(`dashboard.${group.key}`);

  return (
    <details open={open} className="group mt-1">
      <summary className="flex cursor-pointer list-none items-center gap-1 rounded-lg px-3 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-slate-500 hover:text-slate-300 [&::-webkit-details-marker]:hidden">
        <svg viewBox="0 0 24 24" className="h-3 w-3 transition group-open:rotate-90" fill="none" stroke="currentColor" strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
          <path d="M9 5l7 7-7 7" />
        </svg>
        {label}
      </summary>

      {items.length > 0 ? (
        <ul className="mt-1 space-y-0.5">
          {items.map((item) => (
            <Item key={`${group.key}-${item.key}-${item.path}`} item={item} pathname={pathname} role={role} />
          ))}
        </ul>
      ) : null}

      {children.length > 0 ? (
        <div className="ml-2 mt-1 border-l border-slate-800 pl-2">
          {children.map((child) => (
            <Group key={child.key} group={child} pathname={pathname} role={role} />
          ))}
        </div>
      ) : null}
    </details>
  );
}

export function Sidebar({ user, pathname }: { user: { name: string; role: string }; pathname: string }) {
  return (
    <aside
      data-sidebar
      className="flex w-64 shrink-0 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform lg:translate-x-0"
    >
      <Link href="/dashboard" className="flex items-center gap-2.5 px-6 py-5 text-white">
        <span className="grid h-9 w-9 place-items-center rounded-lg bg-blue-600 font-bold">E</span>
        <span className="leading-tight">
          <span className="block font-bold">{t("brand.name")}</span>
          <span className="block text-[10px] font-semibold uppercase tracking-widest text-slate-500">
            {t("dashboard.admin_panel")}
          </span>
        </span>
      </Link>

      <nav className="flex-1 overflow-y-auto px-3 pb-6 text-sm font-medium">
        {NAV_GROUPS.map((group) => (
          <Group key={group.key} group={group} pathname={pathname} role={user.role} />
        ))}
        <Link href="/" className="mt-4 block rounded-lg px-3 py-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">
          {t("nav.view_site")}
        </Link>
      </nav>

      <div className="border-t border-slate-800 px-4 py-4">
        <div className="flex items-center gap-3">
          <span className="grid h-9 w-9 place-items-center rounded-full bg-slate-700 text-xs font-bold text-white">
            {user.name.slice(0, 2).toUpperCase()}
          </span>
          <div className="min-w-0">
            <div className="truncate text-sm text-slate-200">{user.name}</div>
            <div className="truncate text-xs capitalize text-slate-500">{user.role}</div>
          </div>
        </div>
        <form action={logoutAction} className="mt-3">
          <button type="submit" className="w-full rounded-lg bg-slate-800 px-3 py-2 text-sm text-slate-300 transition hover:bg-red-600 hover:text-white">
            {t("auth.logout")}
          </button>
        </form>
      </div>
    </aside>
  );
}
