/**
 * Configurable document designs / watermarks for the Node variant — mirrors
 * eskoofy-laravel-app/app/Services/DocumentDesignService.php.
 *
 * A `document_designs` row per document type holds `settings` + `watermark`
 * (JSON strings) + `custom_css`. The helpers below resolve the active design,
 * normalise/clamp values, and emit a dompdf-safe CSS block that print screens
 * inject, so printed certificates stay branded in every variant.
 */
import { prisma } from "@/lib/prisma";

export const DOCUMENT_TYPES = ["certificate", "testimonial", "marksheet", "admit_card", "id_card"] as const;
export const DOCUMENT_TEMPLATES = ["classic", "modern", "minimal", "bordered"] as const;

export type DocumentType = (typeof DOCUMENT_TYPES)[number];
export type DocumentTemplate = (typeof DOCUMENT_TEMPLATES)[number];

export interface DocumentDesignRow {
  id: number;
  document_type: string;
  name: string;
  template: string;
  is_default: boolean;
  settings: Record<string, unknown>;
  watermark: Record<string, unknown>;
  custom_css: string;
  is_active: boolean;
}

export function isType(type: unknown): type is DocumentType {
  return typeof type === "string" && (DOCUMENT_TYPES as readonly string[]).includes(type);
}

export function isTemplate(template: unknown): template is DocumentTemplate {
  return typeof template === "string" && (DOCUMENT_TEMPLATES as readonly string[]).includes(template);
}

/** The active design row for a document type, or null. */
export async function activeDesign(type: string): Promise<DocumentDesignRow | null> {
  if (!isType(type)) return null;
  const row = await prisma.document_designs.findFirst({
    where: { document_type: type, is_default: true, is_active: true },
    orderBy: { updated_at: "desc" },
  });
  if (!row) return null;
  return {
    id: row.id,
    document_type: row.document_type,
    name: row.name,
    template: row.template,
    is_default: row.is_default,
    settings: parseJson(row.settings),
    watermark: parseJson(row.watermark),
    custom_css: row.custom_css ?? "",
    is_active: row.is_active,
  };
}

function parseJson(value: string | null): Record<string, unknown> {
  if (!value) return {};
  try {
    const parsed = JSON.parse(value) as unknown;
    return typeof parsed === "object" && parsed !== null ? (parsed as Record<string, unknown>) : {};
  } catch {
    return {};
  }
}

/** Per-type config defaults (the shipped bd-profile look). */
export function defaultsFor(type: string): Record<string, unknown> {
  const shared = {
    template: "classic",
    font_family: "Georgia, serif",
    base_font_size: 16,
    title_font_size: 24,
    border_style: "double",
    border_width: 3,
    border_radius: 0,
    page_size: "a4",
    orientation: "landscape",
    padding: 48,
    accent_bar: "none",
    primary_color: "#1e40af",
    secondary_color: "#1e3a8a",
    accent_color: "#2563eb",
    muted_color: "#64748b",
    background_color: "#ffffff",
    border_color: "#2563eb",
    text_color: "#1f2937",
    show_header: true,
    show_footer: true,
    show_logo: true,
    show_number: true,
    show_issue_date: true,
    show_signature: true,
    show_notes: true,
    logo_position: "top-center",
    header_text: null,
    footer_text: null,
    signature_label: null,
  };
  const perType: Record<string, Record<string, unknown>> = {
    certificate: shared,
    testimonial: { ...shared, primary_color: "#16a34a", secondary_color: "#166534", accent_color: "#16a34a", border_color: "#16a34a" },
    marksheet: {
      template: "modern",
      primary_color: "#1f2937",
      secondary_color: "#4b5563",
      accent_color: "#2563eb",
      muted_color: "#6b7280",
      background_color: "#ffffff",
      font_family: "Helvetica, Arial, sans-serif",
      base_font_size: 13,
      title_font_size: 22,
      border_style: "solid",
      border_width: 1,
      border_color: "#d1d5db",
      border_radius: 8,
      page_size: "a4",
      orientation: "portrait",
      padding: 40,
      accent_bar: "bottom",
      text_color: "#1f2937",
    },
    admit_card: {
      template: "modern",
      primary_color: "#1e40af",
      secondary_color: "#1e3a8a",
      accent_color: "#1e40af",
      muted_color: "#6b7280",
      background_color: "#ffffff",
      font_family: "Arial, Helvetica, sans-serif",
      base_font_size: 14,
      title_font_size: 24,
      border_style: "solid",
      border_width: 3,
      border_color: "#1e40af",
      border_radius: 12,
      page_size: "a4",
      orientation: "portrait",
      padding: 30,
      accent_bar: "top",
      text_color: "#1f2937",
    },
    id_card: {
      template: "modern",
      primary_color: "#1e40af",
      secondary_color: "#1e3a8a",
      accent_color: "#1e40af",
      muted_color: "#6b7280",
      background_color: "#ffffff",
      font_family: "Arial, Helvetica, sans-serif",
      base_font_size: 13,
      title_font_size: 18,
      border_style: "solid",
      border_width: 3,
      border_color: "#1e40af",
      border_radius: 12,
      page_size: "credit-card",
      orientation: "landscape",
      padding: 20,
      accent_bar: "none",
      text_color: "#1f2937",
    },
  };
  return perType[type] ?? shared;
}

/** Normalised theme for a document type. */
export async function theme(type: string): Promise<Record<string, unknown>> {
  const defaults = defaultsFor(type);
  const design = await activeDesign(type);
  const settings = design?.settings ?? {};
  const theme = sanitizeTheme({ ...defaults, ...settings });
  const template = design?.template ?? settings.template ?? defaults.template ?? "classic";
  theme.template = isTemplate(template) ? template : "classic";
  theme.document_type = type;
  theme.name = design?.name ?? type.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
  theme.has_custom_design = design !== null;
  return theme;
}

/** Normalised watermark for a document type. */
export async function watermark(type: string): Promise<Record<string, unknown>> {
  const config = {
    enabled: process.env.DOCUMENT_WATERMARK_ENABLED === "true",
    type: "text" as const,
    text: process.env.DOCUMENT_WATERMARK_TEXT ?? null,
    image_path: null,
    opacity: 0.18,
    rotation: 45,
    position: "diagonal",
    font_size: 48,
    color: "#0f172a",
    font_family: "inherit",
  };
  const design = await activeDesign(type);
  const override = design?.watermark ?? {};
  const merged = { ...config, ...override } as Record<string, unknown>;
  const global = "enabled" in override ? Boolean(override.enabled) : config.enabled;
  merged.enabled = global;
  return sanitizeWatermark(merged, type);
}

const FONTS = [
  "Georgia, serif",
  "Helvetica, Arial, sans-serif",
  "Arial, Helvetica, sans-serif",
  "Times New Roman, serif",
  "Verdana, sans-serif",
  "inherit",
];

function color(value: unknown, fallback: string): string {
  return typeof value === "string" && /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(value.trim())
    ? value.trim().toLowerCase()
    : fallback;
}

function clampInt(value: unknown, min: number, max: number, fallback: number): number {
  const n = Number(value);
  if (!Number.isFinite(n)) return fallback;
  return Math.max(min, Math.min(max, Math.round(n)));
}

function clampFloat(value: unknown, min: number, max: number, fallback: number): number {
  const n = Number(value);
  if (!Number.isFinite(n)) return fallback;
  return Math.round(Math.max(min, Math.min(max, n)) * 1000) / 1000;
}

function flag(value: unknown, fallback: boolean): boolean {
  if (value === null || value === undefined || value === "") return fallback;
  if (typeof value === "string") {
    return ["1", "true", "on", "yes"].includes(value.trim().toLowerCase());
  }
  return Boolean(value);
}

function line(value: unknown, max: number): string | null {
  if (typeof value !== "string") return null;
  const v = value.trim();
  return v === "" ? null : v.slice(0, max);
}

function tint(hex: string, alpha: number): string {
  let h = hex.replace("#", "");
  if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
  if (h.length !== 6 && h.length !== 8) return `rgba(37,99,235,${alpha})`;
  const r = parseInt(h.slice(0, 2), 16);
  const g = parseInt(h.slice(2, 4), 16);
  const b = parseInt(h.slice(4, 6), 16);
  return `rgba(${r},${g},${b},${alpha})`;
}

/** Clamp/sanitise a submitted settings payload. */
export function sanitizeTheme(input: Record<string, unknown>): Record<string, unknown> {
  const merged = {
    primary_color: "#1e40af",
    secondary_color: "#1e3a8a",
    accent_color: "#2563eb",
    text_color: "#1f2937",
    muted_color: "#64748b",
    background_color: "#ffffff",
    border_color: "#1e40af",
    font_family: "inherit",
    base_font_size: 14,
    title_font_size: 20,
    border_style: "solid",
    border_width: 2,
    border_radius: 0,
    padding: 32,
    page_size: "a4",
    orientation: "portrait",
    accent_bar: "none",
    show_header: true,
    show_footer: true,
    show_logo: true,
    show_number: true,
    show_issue_date: true,
    show_signature: true,
    show_notes: true,
    logo_position: "top-center",
    header_text: null,
    footer_text: null,
    signature_label: null,
    ...input,
  };
  return {
    primary_color: color(merged.primary_color, "#1e40af"),
    secondary_color: color(merged.secondary_color, "#1e3a8a"),
    accent_color: color(merged.accent_color, "#2563eb"),
    text_color: color(merged.text_color, "#1f2937"),
    muted_color: color(merged.muted_color, "#64748b"),
    background_color: color(merged.background_color, "#ffffff"),
    border_color: color(merged.border_color, "#1e40af"),
    font_family: FONTS.includes(String(merged.font_family ?? "")) ? String(merged.font_family) : "inherit",
    base_font_size: clampInt(merged.base_font_size, 8, 32, 14),
    title_font_size: clampInt(merged.title_font_size, 10, 72, 20),
    border_style: ["none", "solid", "double", "dashed", "dotted"].includes(String(merged.border_style)) ? String(merged.border_style) : "solid",
    border_width: clampInt(merged.border_width, 0, 12, 2),
    border_radius: clampInt(merged.border_radius, 0, 40, 0),
    padding: clampInt(merged.padding, 0, 160, 32),
    page_size: ["a4", "letter", "legal", "credit-card"].includes(String(merged.page_size)) ? String(merged.page_size) : "a4",
    orientation: ["portrait", "landscape"].includes(String(merged.orientation)) ? String(merged.orientation) : "portrait",
    accent_bar: ["none", "top", "bottom", "left", "right"].includes(String(merged.accent_bar)) ? String(merged.accent_bar) : "none",
    show_header: flag(merged.show_header, true),
    show_footer: flag(merged.show_footer, true),
    show_logo: flag(merged.show_logo, true),
    show_number: flag(merged.show_number, true),
    show_issue_date: flag(merged.show_issue_date, true),
    show_signature: flag(merged.show_signature, true),
    show_notes: flag(merged.show_notes, true),
    logo_position: ["top-left", "top-center"].includes(String(merged.logo_position)) ? String(merged.logo_position) : "top-center",
    header_text: line(merged.header_text, 255),
    footer_text: line(merged.footer_text, 255),
    signature_label: line(merged.signature_label, 120),
  };
}

/** Clamp/sanitise a submitted watermark payload. */
export function sanitizeWatermark(input: Record<string, unknown>, type?: string): Record<string, unknown> {
  const typeValue = ["text", "image", "logo"].includes(String(input.type ?? "")) ? String(input.type) : "text";
  const text = line(input.text, 120);
  const docs = input.documents as Record<string, unknown> | undefined;

  return {
    enabled: Boolean(input.enabled ?? false),
    type: typeValue,
    text: text ?? (typeValue === "text" ? "Eskoofy" : null),
    image_path: null,
    opacity: clampFloat(input.opacity, 0.05, 1.0, 0.18),
    rotation: clampInt(input.rotation, -180, 180, 45),
    position: ["center", "diagonal", "tile", "top", "bottom"].includes(String(input.position)) ? String(input.position) : "diagonal",
    font_size: clampInt(input.font_size, 8, 120, 48),
    color: color(input.color ?? "#0f172a", "#0f172a"),
    font_family: line(input.font_family, 120) ?? "inherit",
    documents: type && docs && "documents" in input ? { ...(docs ?? {}), [type]: Boolean(docs?.[type]) } : docs ?? {},
  };
}

/**
 * The dompdf-safe CSS block for a document type (theme + watermark + custom CSS).
 */
export function documentCss(theme: Record<string, unknown>, wm: Record<string, unknown>, customCss = ""): string {
  const root = `.doc-root.doc-${String(theme.document_type ?? "certificate").replace(/[^a-z0-9_-]/gi, "")}`;

  const vars: Record<string, string> = {
    "--doc-primary": String(theme.primary_color),
    "--doc-secondary": String(theme.secondary_color),
    "--doc-accent": String(theme.accent_color),
    "--doc-text": String(theme.text_color),
    "--doc-muted": String(theme.muted_color),
    "--doc-bg": String(theme.background_color),
    "--doc-border": String(theme.border_color),
    "--doc-font": String(theme.font_family),
    "--doc-base-size": `${theme.base_font_size}px`,
    "--doc-title-size": `${theme.title_font_size}px`,
    "--doc-border-style": String(theme.border_style),
    "--doc-border-width": `${theme.border_width}px`,
    "--doc-radius": `${theme.border_radius}px`,
    "--doc-padding": `${theme.padding}px`,
    "--doc-tint": tint(String(theme.accent_color), 0.08),
    "--doc-tint-strong": tint(String(theme.primary_color), 0.1),
  };
  const declarations = Object.entries(vars).map(([k, v]) => `${k}:${v};`).join("");

  let css = `${root}{${declarations}}`;
  css += templateCss(theme);
  css += accentBarCss(theme);
  css += pageCss(theme);
  css += watermarkCss(wm, root);
  if (customCss) css += `\n${sanitizeCss(customCss)}`;
  return css;
}

function rootFor(theme: Record<string, unknown>): string {
  return `.doc-root.doc-${String(theme.document_type ?? "certificate").replace(/[^a-z0-9_-]/gi, "")}`;
}

function templateCss(theme: Record<string, unknown>): string {
  const root = rootFor(theme);
  const base = `${root}{`
    + `font-family:${theme.font_family};font-size:${theme.base_font_size}px;color:${theme.text_color};`
    + `background:${theme.background_color};`
    + `border:${theme.border_width}px ${theme.border_style} ${theme.border_color};`
    + `border-radius:${theme.border_radius}px;padding:${theme.padding}px;`
    + `position:relative;width:100%;box-sizing:border-box;}`;
  const content = `${root} > *{position:relative;z-index:1;}`;
  const headings = `${root} .doc-title{font-size:${theme.title_font_size}px;color:${theme.secondary_color};font-weight:700;margin:0 0 .4em;}`;
  const muted = `${root} .doc-muted{color:${theme.muted_color};}`;
  const name = `${root} .doc-name{color:${theme.primary_color};font-weight:700;text-decoration:underline;}`;
  const number = `${root} .doc-number{color:${theme.muted_color};font-size:.85em;}`;
  const notes = `${root} .doc-notes{background:${tint(String(theme.accent_color), 0.08)};border-left:3px solid ${theme.accent_color};padding:10px 12px;text-align:left;color:${theme.text_color};}`;
  const signature = `${root} .doc-signature{border-top:1px solid ${theme.text_color};padding-top:6px;text-align:center;color:${theme.text_color};}`;
  const table = `${root} table.doc-table{width:100%;border-collapse:collapse;}`;
  const tableCells = `${root} .doc-table th,.doc-table td{border:1px solid ${theme.border_color};padding:8px 10px;text-align:left;}`;
  const tableHead = `${root} .doc-table th{background:${tint(String(theme.primary_color), 0.1)};color:${theme.secondary_color};}`;
  const logo = `${root} .doc-logo{max-height:60px;object-fit:contain;margin:0 auto 8px;}`;
  const logoLeft = `${root} .doc-logo-left{margin:0 12px 8px 0;float:left;}`;
  const panel = `${root} .doc-panel{background:${tint(String(theme.accent_color), 0.06)};border:1px solid ${theme.border_color};border-radius:${theme.border_radius}px;padding:12px 16px;}`;
  const panelMuted = `${root} .doc-panel-muted{background:${tint(String(theme.muted_color), 0.08)};}`;
  const pass = `${root} .doc-pass{color:${tint(String(theme.primary_color), 1)};font-weight:700;}`;
  const fail = `${root} .doc-fail{color:#dc2626;font-weight:700;}`;

  const templates: Record<string, string> = {
    classic: `${root}{text-align:center;}${root} .doc-body{text-align:left;}`,
    modern: `${root}{box-shadow:0 1px 3px rgba(15,23,42,.12);}${root} .doc-title{letter-spacing:1px;text-transform:uppercase;}${root} .doc-body{text-align:left;line-height:1.7;}`,
    minimal: `${root}{border-width:0;border-radius:0;background:transparent;}${root} .doc-title{font-weight:400;letter-spacing:2px;}${root} .doc-name{text-decoration:none;}`,
    bordered: `${root}{border-style:double;}.doc-inner-frame{position:absolute;top:8px;right:8px;bottom:8px;left:8px;border:1px solid ${theme.accent_color};border-radius:${theme.border_radius}px;pointer-events:none;opacity:.6;}`,
  };

  return base + content + headings + muted + name + number + notes + signature + table + tableCells + tableHead + logo + logoLeft + panel + panelMuted + pass + fail + (templates[String(theme.template)] ?? templates.classic);
}

function accentBarCss(theme: Record<string, unknown>): string {
  if (theme.accent_bar === "none") return ".doc-accent-bar{display:none;}";
  const positions: Record<string, string> = {
    top: "top:0;left:0;right:0;",
    bottom: "bottom:0;left:0;right:0;",
    left: "top:0;bottom:0;left:0;width:6px;",
    right: "top:0;bottom:0;right:0;width:6px;",
  };
  const position = positions[String(theme.accent_bar)] ?? "top:0;left:0;right:0;";
  const height = theme.accent_bar === "left" || theme.accent_bar === "right" ? "100%" : "6px";
  return `.doc-accent-bar{display:block;position:absolute;${position}height:${height};z-index:2;background:${theme.accent_color};}`;
}

function pageCss(theme: Record<string, unknown>): string {
  const sizes: Record<string, string> = { a4: "210mm", letter: "215.9mm", legal: "215.9mm", "credit-card": "85.6mm" };
  const width = sizes[String(theme.page_size)] ?? "210mm";
  return `@page{size:${theme.orientation} ${width};margin:10mm;}`;
}

function watermarkCss(wm: Record<string, unknown>, root: string): string {
  if (!wm.enabled) return "";
  const layer = `${root} > .doc-watermark{position:absolute;z-index:0;top:0;right:0;bottom:0;left:0;display:flex;align-items:center;justify-content:center;pointer-events:none;overflow:hidden;}`;
  const item = `.doc-watermark-item{opacity:${wm.opacity};color:${wm.color};font-size:${wm.font_size}px;font-family:${wm.font_family};font-weight:700;white-space:nowrap;text-align:center;line-height:1.1;}`;
  let positionCss = `.doc-watermark-item{transform:rotate(${wm.rotation}deg);}`;
  if (wm.position === "center") positionCss = ".doc-watermark-item{transform:rotate(0deg);}";
  else if (wm.position === "top") positionCss = `.doc-watermark{align-items:flex-start;padding-top:8%;}.doc-watermark-item{transform:rotate(${wm.rotation}deg);}`;
  else if (wm.position === "bottom") positionCss = `.doc-watermark{align-items:flex-end;padding-bottom:8%;}.doc-watermark-item{transform:rotate(${wm.rotation}deg);}`;
  else if (wm.position === "tile") positionCss = `.doc-watermark{flex-wrap:wrap;align-content:center;gap:6% 8%;}.doc-watermark-item{transform:rotate(${wm.rotation}deg);font-size:${Math.max(10, Math.round(Number(wm.font_size) * 0.5))}px;}`;
  return layer + item + positionCss;
}

/** Strip anything executable from custom CSS. */
export function sanitizeCss(css: string): string {
  let out = css
    .replace(/<[^>]*>/g, "")
    .replace(/javascript:|vbscript:|expression\(|@import|-moz-binding|behavior:|@charset/gi, "")
    .replace(/url\(([^)]*)\)/gi, (_m, raw: string) => {
      const value = raw.trim().replace(/^["']|["']$/g, "");
      return value === "" || /^(https?:|data:|\/\/|\.\.\/)/i.test(value) ? "none" : `url(${value})`;
    })
    .replace(/[<>]/g, "")
    .replace(/\/\*|\*\//g, "");
  if (/[{}]/.test(out) && (out.match(/{/g) ?? []).length !== (out.match(/}/g) ?? []).length) out = "";
  return out.trim();
}

/** The CSS block for a print screen, keyed by document type. */
export async function documentStyle(type: string): Promise<string> {
  if (!isType(type)) return "";
  const t = await theme(type);
  const wm = await watermark(type);
  const design = await activeDesign(type);
  return documentCss(t, wm, design?.custom_css ?? "");
}

/** The watermark markup for a print screen, keyed by document type. */
export async function watermarkMarkup(type: string): Promise<{ enabled: boolean; inner: string }> {
  if (!isType(type)) return { enabled: false, inner: "" };
  const wm = await watermark(type);
  if (!wm.enabled) return { enabled: false, inner: "" };
  let inner = "";
  if (wm.type === "text") inner = String(wm.text ?? "");
  else if (wm.image_path) inner = `<img src="${String(wm.image_path)}" alt="" />`;
  return { enabled: inner !== "", inner };
}