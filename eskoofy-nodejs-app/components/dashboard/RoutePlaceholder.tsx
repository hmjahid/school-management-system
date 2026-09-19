import { t } from "@/lib/i18n";

/**
 * Shown for app routes that exist in the reference product but whose Blade view
 * has no dedicated Node page yet (see docs/PORTING-STATUS.md). Keeping the route
 * resolvable means the route surface stays a real clone.
 */
export function RoutePlaceholder({
  uri,
  name,
  action,
  method,
}: {
  uri: string;
  name: string | null;
  action: string;
  method: string;
}) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-6">
      <p className="text-sm text-slate-600">{t("admin.placeholder", { name: name ?? uri })}</p>
      <dl className="mt-4 grid gap-2 text-sm sm:grid-cols-2">
        <div className="flex gap-2">
          <dt className="text-slate-400">{t("admin.route")}:</dt>
          <dd className="font-mono text-slate-700">{uri}</dd>
        </div>
        <div className="flex gap-2">
          <dt className="text-slate-400">{t("admin.method")}:</dt>
          <dd className="font-mono text-slate-700">{method}</dd>
        </div>
        <div className="flex gap-2 sm:col-span-2">
          <dt className="text-slate-400">{t("admin.controller")}:</dt>
          <dd className="font-mono text-xs text-slate-700">{action}</dd>
        </div>
      </dl>
    </div>
  );
}
