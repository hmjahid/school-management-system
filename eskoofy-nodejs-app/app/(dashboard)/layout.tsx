import { headers } from "next/headers";
import { redirect } from "next/navigation";
import { currentUser } from "@/lib/auth";
import { Sidebar } from "@/components/dashboard/Sidebar";
import { Topbar } from "@/components/dashboard/Topbar";
import { t } from "@/lib/i18n";
import { eskoolfy } from "@/config/eskoolfy";

export default async function DashboardLayout({ children }: { children: React.ReactNode }) {
  const user = await currentUser();
  if (!user) redirect("/login?redirect=/dashboard");

  const headerList = await headers();
  const pathname = headerList.get("x-pathname") ?? "/dashboard";

  return (
    <div className="flex min-h-screen bg-slate-100">
      <Sidebar user={user} pathname={pathname} />

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 md:px-6">
          <Topbar
            user={{ name: user.name, role: user.role }}
            labels={{
              website: t("dashboard.dashboard"),
              search: t("common.search"),
              help: t("dashboard.help_documentation"),
              darkMode: t("dashboard.dark_mode"),
              notifications: t("site.nav.notices"),
              dashboard: t("dashboard.dashboard"),
              setup: t("site.home.hero_cta_secondary"),
              profile: t("site.nav.profile"),
              settings: t("dashboard.settings"),
              logout: t("auth.logout"),
              locale: eskoolfy.locale === "bn" ? "বাংলা" : "English",
            }}
          />
        </header>

        <main className="flex-1 p-4 md:p-6">{children}</main>
      </div>
    </div>
  );
}
