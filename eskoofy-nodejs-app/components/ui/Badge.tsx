import type { ReactNode } from "react";

/** Mirrors the app's `<x-badge>` variants. */
const VARIANTS = {
  default: "bg-slate-100 text-slate-700 ring-slate-200",
  success: "bg-emerald-50 text-emerald-700 ring-emerald-200",
  warning: "bg-amber-50 text-amber-800 ring-amber-200",
  danger: "bg-red-50 text-red-700 ring-red-200",
  info: "bg-sky-50 text-sky-700 ring-sky-200",
  brand: "bg-blue-50 text-blue-700 ring-blue-200",
} as const;

export type BadgeVariant = keyof typeof VARIANTS;

/** Map a status string to a badge variant (shared by all resource tables). */
export function statusVariant(status: unknown): BadgeVariant {
  switch (String(status ?? "").toLowerCase()) {
    case "active":
    case "paid":
    case "present":
    case "published":
    case "approved":
    case "completed":
    case "issued":
      return "success";
    case "pending":
    case "draft":
    case "warning":
    case "late":
    case "partial":
      return "warning";
    case "inactive":
    case "failed":
    case "cancelled":
    case "absent":
    case "rejected":
    case "overdue":
    case "expired":
      return "danger";
    case "info":
    case "new":
      return "info";
    default:
      return "default";
  }
}

export function Badge({
  children,
  variant = "default",
  dot = false,
}: {
  children: ReactNode;
  variant?: BadgeVariant;
  dot?: boolean;
}) {
  return (
    <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${VARIANTS[variant]}`}>
      {dot ? <span className="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true" /> : null}
      {children}
    </span>
  );
}

export function StatusBadge({ value }: { value: unknown }) {
  const text = value === null || value === undefined || value === "" ? "—" : String(value);
  return <Badge variant={statusVariant(text)}>{text}</Badge>;
}
