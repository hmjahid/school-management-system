import Link from "next/link";
import { NAV_SECTIONS, type NavGroup, type NavItem, type NavSection } from "@/lib/nav";
import { can } from "@/lib/permissions";
import { t } from "@/lib/i18n";
import { NavIcon } from "@/components/dashboard/NavIcon";
import { SidebarSearch } from "@/components/dashboard/SidebarSearch";
import { SidebarFooter } from "@/components/dashboard/SidebarFooter";
import { FavoritesList, type Favorite } from "@/components/dashboard/FavoritesList";

export type SidebarBadges = Record<string, number>;

function isActive(pathname: string, path: string): boolean {
  return pathname === path || pathname.startsWith(`${path}/`);
}

function itemVisible(item: NavItem, role: string): boolean {
  return !item.permission || can(role, item.permission);
}

function groupHasActive(group: NavGroup, pathname: string, role: string): boolean {
  return (group.items ?? []).some((item) => itemVisible(item, role) && isActive(pathname, item.path));
}

function Badge({ count }: { count: number }) {
  if (count <= 0) return null;
  return (
    <span className="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">
      {count}
    </span>
  );
}

function NavLink({ item, pathname, role, badges }: { item: NavItem; pathname: string; role: string; badges: SidebarBadges }) {
  if (!itemVisible(item, role)) return null;
  const active = isActive(pathname, item.path);
  const count = item.badgeKey ? badges[item.badgeKey] ?? 0 : 0;

  return (
    <div data-nav-item>
      <Link href={item.path} className={`admin-nav-link ${active ? "admin-nav-link--active" : ""}`}>
        <span className="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
          <NavIcon name={item.key} />
        </span>
        <span className="flex-1 truncate">{t(`dashboard.${item.key}`)}</span>
        {item.status === "planned" ? (
          <span className="rounded-full bg-slate-100 px-1.5 py-0.5 text-[9px] uppercase tracking-wide text-slate-500 dark:bg-slate-700 dark:text-slate-400">
            {t("common.planned")}
          </span>
        ) : (
          <Badge count={count} />
        )}
      </Link>
    </div>
  );
}

function NavGroupView({ group, pathname, role, badges }: { group: NavGroup; pathname: string; role: string; badges: SidebarBadges }) {
  const items = (group.items ?? []).filter((item) => itemVisible(item, role));
  if (items.length === 0) return null;

  const open = groupHasActive(group, pathname, role);

  return (
    <details data-nav-group open={open} className="group">
      <summary className={`admin-nav-link cursor-pointer list-none [&::-webkit-details-marker]:hidden ${open ? "admin-nav-link--active" : ""}`}>
        <span className="flex h-5 w-5 shrink-0 items-center justify-center opacity-80">
          <NavIcon name={group.key} />
        </span>
        <span className="flex-1 truncate">{t(`dashboard.${group.key}`)}</span>
        <svg className="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-90 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
        </svg>
      </summary>
      <div className="ml-4 mt-1 space-y-0.5 border-l border-slate-200 pl-3 dark:border-slate-700">
        {items.map((item) => {
          const active = isActive(pathname, item.path);
          const count = item.badgeKey ? badges[item.badgeKey] ?? 0 : 0;
          return (
            <div key={`${group.key}-${item.key}-${item.path}`} data-nav-item>
              <Link href={item.path} className={`admin-nav-sublink ${active ? "admin-nav-sublink--active" : ""}`}>
                <span className="inline-flex w-full items-center justify-between gap-2">
                  <span>{t(`dashboard.${item.key}`)}</span>
                  <Badge count={count} />
                </span>
              </Link>
            </div>
          );
        })}
      </div>
    </details>
  );
}

function Section({ section, pathname, role, badges }: { section: NavSection; pathname: string; role: string; badges: SidebarBadges }) {
  if (section.adminOnly && role !== "admin") return null;

  return (
    <div data-nav-section>
      <p className="admin-nav-section">{t(`dashboard.${section.key}`)}</p>
      <div className="space-y-0.5">
        {section.nodes.map((node, index) =>
          node.type === "item" ? (
            <NavLink key={`${section.key}-i-${node.item.key}-${index}`} item={node.item} pathname={pathname} role={role} badges={badges} />
          ) : (
            <NavGroupView key={`${section.key}-g-${node.group.key}`} group={node.group} pathname={pathname} role={role} badges={badges} />
          ),
        )}
      </div>
    </div>
  );
}

export function Sidebar({
  user,
  pathname,
  favorites = [],
  badges = {},
}: {
  user: { name: string; role: string };
  pathname: string;
  favorites?: Favorite[];
  badges?: SidebarBadges;
}) {
  return (
    <aside
      id="sidebar"
      data-sidebar
      className="no-print flex w-64 shrink-0 -translate-x-full flex-col border-r border-slate-200/80 bg-white transition-transform dark:border-slate-700/80 dark:bg-slate-800 lg:translate-x-0"
    >
      <div className="flex h-[4.25rem] shrink-0 items-center gap-3 border-b border-slate-200/80 px-4 dark:border-slate-700/80">
        <Link href="/dashboard" className="flex min-w-0 items-center gap-3">
          <span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-600 font-bold text-white ring-1 ring-slate-200 dark:ring-slate-600">
            {t("brand.name").slice(0, 1).toUpperCase()}
          </span>
          <span className="min-w-0">
            <span className="block truncate text-sm font-bold text-slate-900 dark:text-slate-100">{t("brand.name")}</span>
            <span className="block truncate text-[0.65rem] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {t("dashboard.admin_panel")}
            </span>
          </span>
        </Link>
      </div>

      <nav data-sidebar-nav data-no-loading className="admin-sidebar-nav text-sm">
        <SidebarSearch labels={{ search: t("dashboard.search_menu"), type: t("dashboard.type_to_search") }} />
        <FavoritesList favorites={favorites} labels={{ title: t("dashboard.favorites"), unpin: t("dashboard.unpin") }} />
        {NAV_SECTIONS.map((section) => (
          <Section key={section.key} section={section} pathname={pathname} role={user.role} badges={badges} />
        ))}
      </nav>

      <SidebarFooter
        labels={{ install: t("dashboard.install_app"), darkMode: t("dashboard.dark_mode"), logout: t("dashboard.logout") }}
      />
    </aside>
  );
}
