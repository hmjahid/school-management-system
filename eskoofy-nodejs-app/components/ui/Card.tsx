import type { ReactNode } from "react";

/** Mirrors the app's `<x-card>` component. */
export function Card({
  title,
  header,
  footer,
  padding = true,
  className = "",
  children,
}: {
  title?: ReactNode;
  header?: ReactNode;
  footer?: ReactNode;
  padding?: boolean;
  className?: string;
  children: ReactNode;
}) {
  return (
    <div className={`rounded-xl border border-slate-200 bg-white shadow-sm ${className}`}>
      {(title || header) && (
        <div className="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
          {title ? <h2 className="text-base font-semibold text-slate-900">{title}</h2> : null}
          {header}
        </div>
      )}
      <div className={padding ? "p-5" : ""}>{children}</div>
      {footer ? <div className="border-t border-slate-100 px-5 py-3">{footer}</div> : null}
    </div>
  );
}
