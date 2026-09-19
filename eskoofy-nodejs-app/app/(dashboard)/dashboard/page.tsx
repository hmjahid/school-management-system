import { prisma } from "@/lib/prisma";
import { t } from "@/lib/i18n";
import { can } from "@/lib/permissions";
import { currentUser } from "@/lib/auth";
import { MODELS } from "@/lib/schema";
import { API_ROUTES, DASHBOARD_ROUTES, SITE_ROUTES } from "@/lib/route-registry";
import { flattenNav, implementedPaths } from "@/lib/nav";

export const dynamic = "force-dynamic";

async function safeCount(fn: () => Promise<number>): Promise<number> {
  try {
    return await fn();
  } catch {
    return 0;
  }
}

export default async function DashboardPage() {
  const user = await currentUser();

  const [students, teachers, classes, fees] = await Promise.all([
    safeCount(() => prisma.students.count({ where: { deleted_at: null } })),
    safeCount(() => prisma.teachers.count({ where: { deleted_at: null } })),
    safeCount(() => prisma.school_classes.count()),
    safeCount(() => prisma.fees.count({ where: { deleted_at: null } })),
  ]);

  const cards = [
    { label: t("dashboard.students"), value: students, permission: "manage_students" as const },
    { label: t("dashboard.teachers"), value: teachers, permission: "manage_teachers" as const },
    { label: t("dashboard.classes"), value: classes, permission: "manage_classes" as const },
    { label: t("dashboard.fees"), value: fees, permission: "manage_fees" as const },
  ].filter((card) => can(user?.role, card.permission));

  const stats = [
    { label: t("dashboard.dashboard"), value: flattenNav().length, hint: "sidebar items" },
    { label: t("dashboard.reports"), value: MODELS.length, hint: "tables ported" },
    { label: t("dashboard.settings"), value: DASHBOARD_ROUTES.length, hint: "dashboard routes" },
    { label: t("dashboard.website"), value: SITE_ROUTES.length + API_ROUTES.length, hint: "site + api routes" },
  ];

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {cards.map((card) => (
          <div key={card.label} className="rounded-xl border border-slate-200 bg-white p-5">
            <div className="text-xs uppercase tracking-wide text-slate-400">{card.label}</div>
            <div className="mt-1 text-3xl font-extrabold">{card.value}</div>
          </div>
        ))}
      </div>

      <div className="rounded-xl border border-slate-200 bg-white p-6">
        <h2 className="font-bold">{t("dashboard.analytics")}</h2>
        <div className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
          {stats.map((stat) => (
            <div key={stat.label + stat.hint} className="rounded-lg border border-slate-100 bg-slate-50 p-4">
              <div className="text-2xl font-extrabold text-slate-800">{stat.value}</div>
              <div className="text-xs text-slate-500">{stat.hint}</div>
            </div>
          ))}
        </div>
        <p className="mt-4 text-xs text-slate-400">
          {implementedPaths().length} of {flattenNav().length} sidebar items have a dedicated Node page; the rest
          resolve through the generic engine.
        </p>
      </div>
    </div>
  );
}
