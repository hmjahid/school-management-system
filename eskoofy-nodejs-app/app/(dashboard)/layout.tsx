import { headers, cookies } from "next/headers";
import { redirect } from "next/navigation";
import { currentUser } from "@/lib/auth";
import { prisma } from "@/lib/prisma";
import { Sidebar, type SidebarBadges } from "@/components/dashboard/Sidebar";
import { Topbar, type TopbarLabels } from "@/components/dashboard/Topbar";
import { CommandPalette } from "@/components/dashboard/CommandPalette";
import { HelpModal, type HelpTopic } from "@/components/dashboard/HelpModal";
import type { Favorite } from "@/components/dashboard/FavoritesList";
import { t, resolveRequestLocale, setRequestLocale, availableLocales } from "@/lib/i18n";
import { eskoolfy } from "@/config/eskoolfy";

interface ShellData {
  favorites: Favorite[];
  badges: SidebarBadges;
  timezone: string;
  themeStyle: string;
  themePrimary: string;
  themeSecondary: string;
}

async function loadShellData(userId: number): Promise<ShellData> {
  const data: ShellData = {
    favorites: [],
    badges: { unreadMessages: 0, admissions: 0, leaves: 0, pendingFeeApprovals: 0, unreadNotifications: 0 },
    timezone: eskoolfy.timezone,
    themeStyle: "default",
    themePrimary: "#2563eb",
    themeSecondary: "#f97316",
  };

  const safe = async <T,>(fn: () => Promise<T>): Promise<T | null> => {
    try {
      return await fn();
    } catch {
      return null;
    }
  };

  const [favorites, unreadMessages, admissions, leaves, pendingFeeApprovals, unreadNotifications, settings] = await Promise.all([
    safe(() => prisma.dashboard_favorites.findMany({ where: { user_id: userId }, orderBy: { id: "desc" }, take: 12 })),
    safe(() => prisma.messages.count({ where: { receiver_id: userId, read_at: null } })),
    safe(() => prisma.admissions.count({ where: { status: { in: ["pending", "submitted", "under_review"] } } })),
    safe(() => prisma.leave_requests.count({ where: { status: "pending" } })),
    safe(() => prisma.fee_payments.count({ where: { status: "pending" } })),
    safe(() => prisma.notifications.count({ where: { notifiable_id: userId, read_at: null } })),
    safe(() => prisma.website_settings.findFirst()),
  ]);

  if (favorites) {
    data.favorites = favorites.map((row) => ({ url: row.url, label: row.label ?? row.url }));
  }
  data.badges = {
    unreadMessages: unreadMessages ?? 0,
    admissions: admissions ?? 0,
    leaves: leaves ?? 0,
    pendingFeeApprovals: pendingFeeApprovals ?? 0,
    unreadNotifications: unreadNotifications ?? 0,
  };
  if (settings) {
    data.timezone = settings.timezone || data.timezone;
    data.themeStyle = settings.theme_style || "default";
    data.themePrimary = settings.theme_primary_color || data.themePrimary;
    data.themeSecondary = settings.theme_secondary_color || data.themeSecondary;
  }
  return data;
}

export default async function DashboardLayout({ children }: { children: React.ReactNode }) {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/dashboard");

  const headerList = await headers();
  const pathname = headerList.get("x-pathname") ?? "/dashboard";
  const store = await cookies();
  const locale = await resolveRequestLocale({
    get: (name) => store.get(name)?.value ?? null,
    isDashboard: true,
  });
  setRequestLocale(locale);

  const shell = await loadShellData(user.id);
  const locales = availableLocales();

  const labels: TopbarLabels = {
    website: t("site.nav.dashboard"),
    search: t("common.search"),
    help: t("dashboard.help_documentation"),
    darkMode: t("dashboard.dark_mode"),
    notifications: t("site.nav.notices"),
    dashboard: t("dashboard.dashboard"),
    setup: t("dashboard.my_profile"),
    profile: t("dashboard.profile"),
    settings: t("dashboard.school_settings"),
    logout: t("dashboard.logout"),
    locale: locale === "bn" ? "বাংলা" : "English",
    pin: t("dashboard.pin_page"),
    unpin: t("dashboard.unpin"),
  };

  const helpTopics: HelpTopic[] = [
    { title: t("dashboard.about"), description: t("brand.tagline"), url: "/dashboard/about" },
    { title: t("dashboard.help_documentation"), description: t("dashboard.admin_panel"), url: "/dashboard/help" },
    { title: t("dashboard.reports"), description: t("dashboard.analytics"), url: "/dashboard/reports" },
    { title: t("dashboard.settings"), description: t("dashboard.school_settings"), url: "/dashboard/settings" },
  ];

  const favorited = shell.favorites.some((fav) => fav.url.split("?")[0] === pathname);

  const themeVars = `:root{--brand-50:color-mix(in srgb,${shell.themePrimary} 10%,white);--brand-100:color-mix(in srgb,${shell.themePrimary} 20%,white);--brand-500:${shell.themePrimary};--brand-600:color-mix(in srgb,${shell.themePrimary} 80%,black);--brand-700:color-mix(in srgb,${shell.themePrimary} 65%,black);--accent-500:${shell.themeSecondary};--accent-600:color-mix(in srgb,${shell.themeSecondary} 80%,black);--theme-primary:${shell.themePrimary};}`;

  return (
    <div className={`${locale === "bn" ? "font-bengali" : "font-sans"} theme-${shell.themeStyle} text-slate-900 dark:text-slate-100`}>
      {/* Restore dark mode before paint (localStorage key `school-dark-mode`). */}
      <script
        dangerouslySetInnerHTML={{
          __html:
            "(function(){try{var k='school-dark-mode',v=localStorage.getItem(k);if(v==='1'||(v===null&&matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.classList.add('dark');}catch(e){}})();",
        }}
      />
      <style dangerouslySetInnerHTML={{ __html: themeVars }} />

      <a href="#main-content" className="skip-link">
        {t("common.view")}
      </a>
      <div id="loading-bar" className="fixed left-0 top-0 z-[200] h-1 bg-brand-600 transition-all duration-300 ease-out" style={{ width: 0, opacity: 0 }} />

      <div className="admin-shell flex h-screen overflow-hidden">
        <Sidebar user={{ name: user.name, role: user.role }} pathname={pathname} favorites={shell.favorites} badges={shell.badges} />

        <div id="sidebar-overlay" className="no-print fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden" />

        <div className="flex flex-1 flex-col overflow-hidden">
          <header className="no-print flex h-16 flex-shrink-0 items-center gap-4 border-b border-slate-200/80 bg-white/95 px-4 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-800/95">
            <Topbar
              user={{ name: user.name, role: user.role }}
              pathname={pathname}
              locale={locale}
              locales={locales}
              favorited={favorited}
              unreadCount={shell.badges.unreadNotifications ?? 0}
              timezone={shell.timezone}
              labels={labels}
            />
          </header>

          <main id="main-content" tabIndex={-1} className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            {children}
          </main>
        </div>
      </div>

      <div id="toast-root" className="toast-container" aria-live="polite" aria-atomic="true" />
      <CommandPalette
        labels={{
          placeholder: t("site.nav.search_placeholder"),
          start: t("dashboard.type_to_search"),
          none: t("common.empty"),
          failed: t("common.empty"),
          esc: "ESC",
        }}
      />
      <HelpModal topics={helpTopics} labels={{ title: t("dashboard.help_documentation"), close: t("common.cancel") }} />
    </div>
  );
}
