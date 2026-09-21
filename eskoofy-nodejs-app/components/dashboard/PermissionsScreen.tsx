import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { PageHeader } from "@/components/ui/PageHeader";
import { EmptyState } from "@/components/ui/EmptyState";
import { t } from "@/lib/i18n";

/**
 * Permissions matrix — mirrors `dashboard/permissions/index.blade.php`:
 * every permission grouped by module, each showing the roles that hold it,
 * plus the role list with member counts.
 */
export async function PermissionsMatrix() {
  const [permissions, links, roles, memberRows] = await Promise.all([
    prisma.permissions.findMany({ orderBy: { name: "asc" } }).catch(() => []),
    prisma.role_has_permissions.findMany().catch(() => []),
    prisma.roles.findMany({ orderBy: { name: "asc" } }).catch(() => []),
    prisma.model_has_roles.groupBy({ by: ["role_id"], _count: { _all: true } }).catch(() => []),
  ]);

  const roleName = new Map(roles.map((role) => [role.id, role.name]));
  const members = new Map(memberRows.map((row) => [row.role_id, row._count._all]));
  const holders = new Map<number, string[]>();
  for (const link of links) {
    const name = roleName.get(link.role_id);
    if (!name) continue;
    const list = holders.get(link.permission_id) ?? [];
    list.push(name);
    holders.set(link.permission_id, list);
  }

  const groups = new Map<string, typeof permissions>();
  for (const permission of permissions) {
    const prefix = permission.name.includes("_") ? permission.name.split("_")[0] : "other";
    const list = groups.get(prefix) ?? [];
    list.push(permission);
    groups.set(prefix, list);
  }

  return (
    <div>
      <PageHeader
        title={t("dashboard.permissions")}
        description="Every permission grouped by module, with the roles that hold it."
        actions={
          <Link href="/dashboard/roles" className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200">
            {t("dashboard.roles")}
          </Link>
        }
      />

      <div className="admin-card mb-6">
        <div className="admin-card-header">
          <h2 className="text-base font-semibold text-slate-900 dark:text-slate-100">{t("dashboard.roles")}</h2>
        </div>
        {roles.length === 0 ? (
          <div className="admin-card-body">
            <EmptyState title={t("dashboard.no_roles_found")} />
          </div>
        ) : (
          <table className="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
            <thead>
              <tr className="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-700/40 dark:text-slate-300">
                <th className="px-5 py-3.5">{t("common.name")}</th>
                <th className="px-5 py-3.5">Members</th>
                <th className="px-5 py-3.5">Guard</th>
                <th className="px-5 py-3.5 text-right">{t("common.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
              {roles.map((role) => (
                <tr key={role.id} className="admin-table-row">
                  <td className="px-5 py-3 font-medium capitalize text-slate-900 dark:text-slate-100">{role.name}</td>
                  <td className="px-5 py-3 text-slate-600 dark:text-slate-400">{members.get(role.id) ?? 0}</td>
                  <td className="px-5 py-3 text-slate-500">{role.guard_name}</td>
                  <td className="px-5 py-3 text-right">
                    <Link href={`/dashboard/roles/${role.id}`} className="text-sm font-medium text-brand-600 hover:underline">
                      {t("common.view")}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {permissions.length === 0 ? (
        <div className="admin-card">
          <div className="admin-card-body">
            <EmptyState title={t("common.empty")} message="No permissions have been seeded yet." />
          </div>
        </div>
      ) : (
        <div className="grid gap-6 lg:grid-cols-2">
          {[...groups.entries()].map(([prefix, list]) => (
            <div key={prefix} className="admin-card">
              <div className="admin-card-header">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{prefix}</h3>
                <span className="text-xs text-slate-400">{list.length}</span>
              </div>
              <ul className="divide-y divide-slate-100 dark:divide-slate-700">
                {list.map((permission) => (
                  <li key={permission.id} className="flex items-start justify-between gap-3 px-5 py-3">
                    <span className="font-mono text-xs text-slate-700 dark:text-slate-300">{permission.name}</span>
                    <span className="flex flex-wrap justify-end gap-1">
                      {(holders.get(permission.id) ?? []).map((role) => (
                        <span key={role} className="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold uppercase text-brand-700 dark:bg-brand-900/20 dark:text-brand-400">
                          {role}
                        </span>
                      ))}
                      {(holders.get(permission.id) ?? []).length === 0 ? <span className="text-xs text-slate-400">—</span> : null}
                    </span>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
