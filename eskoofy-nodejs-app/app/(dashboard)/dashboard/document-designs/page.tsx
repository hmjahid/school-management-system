import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { PageHeader } from "@/components/ui/PageHeader";
import { t } from "@/lib/i18n";
import { prisma } from "@/lib/prisma";
import { DOCUMENT_TEMPLATES, DOCUMENT_TYPES, isType } from "@/lib/document-designs";
import { deleteDesignAction, saveDesignAction } from "./actions";

export const dynamic = "force-dynamic";

const COLOR_FIELDS = [
  { field: "primary_color", label: "Primary" },
  { field: "secondary_color", label: "Secondary" },
  { field: "accent_color", label: "Accent" },
  { field: "text_color", label: "Text" },
  { field: "muted_color", label: "Muted" },
  { field: "background_color", label: "Background" },
  { field: "border_color", label: "Border" },
];

export default async function DocumentDesignsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const sp = await searchParams;
  const user = await currentUser();

  if (!can(user?.role, "manage_document_designs")) {
    return (
      <p className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-700" role="alert">
        403 — {t("dashboard.document_designs")}
      </p>
    );
  }

  const rows = await prisma.document_designs.findMany({
    orderBy: [{ document_type: "asc" }, { is_default: "desc" }, { name: "asc" }],
  });

  const activeByType: Record<string, (typeof rows)[number]> = {};
  for (const row of rows) {
    if (!activeByType[row.document_type] && row.is_default && row.is_active) activeByType[row.document_type] = row;
  }

  const statusByKey: Record<string, string> = {
    saved: t("document_designs.saved"),
    deleted: t("document_designs.deleted"),
  };

  const formTypeRaw = sp.edit
    ? rows.find((r) => r.id === Number(sp.edit))?.document_type
    : sp.type ?? "certificate";
  const formType = isType(formTypeRaw) ? formTypeRaw : "certificate";
  const editing = sp.edit ? rows.find((r) => r.id === Number(sp.edit)) : null;

  const defaults = editing
    ? JSON.parse(editing.settings ?? "{}")
    : {};

  return (
    <div>
      <PageHeader title={t("dashboard.document_designs")} description={t("document_designs.page_description")} />

      {sp.status && statusByKey[sp.status] && (
        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{statusByKey[sp.status]}</div>
      )}

      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
        {DOCUMENT_TYPES.map((type) => {
          const active = activeByType[type];
          return (
            <div key={type} className="rounded-xl border border-slate-200 bg-white p-6">
              <h3 className="text-base font-semibold text-slate-900">{t(`document_designs.type_${type}`)}</h3>
              {active ? (
                <p className="mt-1 text-sm text-slate-600">
                  {active.name}
                  <span className="text-xs text-slate-400">({active.template})</span>
                </p>
              ) : (
                <p className="mt-1 text-sm text-slate-500">{t("document_designs.default_design")}</p>
              )}
              <div className="mt-4 flex gap-2">
                <a
                  href={`/dashboard/document-designs?type=${type}`}
                  className="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-600"
                >
                  {t("document_designs.add")}
                </a>
                {active && (
                  <a
                    href={`/dashboard/document-designs?edit=${active.id}`}
                    className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                  >
                    {t("document_designs.edit")}
                  </a>
                )}
              </div>
            </div>
          );
        })}
      </div>

      <div className="mt-6 rounded-xl border border-slate-200 bg-white p-6">
        <h2 className="mb-4 text-lg font-semibold text-slate-900">
          {editing ? t("document_designs.edit") : t("document_designs.add")} — {t(`document_designs.type_${formType}`)}
        </h2>

        <form action={saveDesignAction}>
          {editing && <input type="hidden" name="id" value={editing.id} />}
          <input type="hidden" name="document_type" value={formType} />

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.name")}</label>
              <input name="name" type="text" defaultValue={editing?.name ?? ""} className="w-full rounded-lg border-slate-300 text-sm" required />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.template")}</label>
              <select name="template" defaultValue={editing?.template ?? "classic"} className="w-full rounded-lg border-slate-300 text-sm">
                {DOCUMENT_TEMPLATES.map((template) => (
                  <option key={template} value={template}>
                    {t(`document_designs.template_${template}`)}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <h3 className="mt-6 mb-2 text-sm font-semibold text-slate-700">{t("document_designs.settings_title")}</h3>
          <div className="grid gap-4 sm:grid-cols-3">
            {COLOR_FIELDS.map(({ field, label }) => (
              <div key={field}>
                <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label>
                <input
                  name={`settings[${field}]`}
                  type="color"
                  defaultValue={String(defaults[field] ?? (field === "text_color" ? "#1f2937" : "#1e40af"))}
                  className="h-10 w-full rounded-lg border-slate-300"
                />
              </div>
            ))}
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.base_font_size")}</label>
              <input name="settings[base_font_size]" type="number" min={8} max={32} defaultValue={String(defaults.base_font_size ?? 16)} className="w-full rounded-lg border-slate-300 text-sm" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.title_font_size")}</label>
              <input name="settings[title_font_size]" type="number" min={10} max={72} defaultValue={String(defaults.title_font_size ?? 24)} className="w-full rounded-lg border-slate-300 text-sm" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.accent_bar")}</label>
              <select name="settings[accent_bar]" defaultValue={String(defaults.accent_bar ?? "none")} className="w-full rounded-lg border-slate-300 text-sm">
                {["none", "top", "bottom", "left", "right"].map((bar) => (
                  <option key={bar} value={bar}>{bar}</option>
                ))}
              </select>
            </div>
          </div>

          <h3 className="mt-6 mb-2 text-sm font-semibold text-slate-700">{t("document_designs.watermark_title")}</h3>
          <div className="grid gap-4 sm:grid-cols-3">
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.watermark_text")}</label>
              <input name="watermark[text]" type="text" defaultValue={String((editing ? JSON.parse(editing.watermark ?? "{}") : {}).text ?? "")} className="w-full rounded-lg border-slate-300 text-sm" maxLength={120} />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.watermark_opacity")}</label>
              <input name="watermark[opacity]" type="number" step="0.05" min={0.05} max={1} defaultValue={String((editing ? JSON.parse(editing.watermark ?? "{}") : {}).opacity ?? 0.18)} className="w-full rounded-lg border-slate-300 text-sm" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.watermark_rotation")}</label>
              <input name="watermark[rotation]" type="number" min={-180} max={180} defaultValue={String((editing ? JSON.parse(editing.watermark ?? "{}") : {}).rotation ?? 45)} className="w-full rounded-lg border-slate-300 text-sm" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.watermark_color")}</label>
              <input name="watermark[color]" type="color" defaultValue={String((editing ? JSON.parse(editing.watermark ?? "{}") : {}).color ?? "#0f172a")} className="h-10 w-full rounded-lg border-slate-300" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-slate-700">{t("document_designs.watermark_position")}</label>
              <select name="watermark[position]" defaultValue={String((editing ? JSON.parse(editing.watermark ?? "{}") : {}).position ?? "diagonal")} className="w-full rounded-lg border-slate-300 text-sm">
                {["center", "diagonal", "tile", "top", "bottom"].map((p) => (
                  <option key={p} value={p}>{p}</option>
                ))}
              </select>
            </div>
          </div>

          <h3 className="mt-6 mb-2 text-sm font-semibold text-slate-700">{t("document_designs.custom_css")}</h3>
          <textarea name="custom_css" rows={4} defaultValue={editing?.custom_css ?? ""} className="w-full rounded-lg border-slate-300 font-mono text-sm" />

          <label className="mt-3 flex items-center gap-2 text-sm text-slate-700">
            <input name="is_default" type="checkbox" value="1" defaultChecked={editing ? editing.is_default : true} />
            {t("document_designs.is_default")}
          </label>
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <input name="is_active" type="checkbox" value="1" defaultChecked={editing ? editing.is_active : true} />
            {t("document_designs.active")}
          </label>

          <div className="mt-4 flex flex-wrap gap-2">
            <button type="submit" className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-600">
              {t("document_designs.save")}
            </button>
            {editing && (
              <form action={deleteDesignAction}>
                <input type="hidden" name="id" value={editing.id} />
                <button className="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                  {t("document_designs.delete")}
                </button>
              </form>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}