"use client";

import type { ReactNode } from "react";

/**
 * Client-side print trigger. Print buttons live inside async server components
 * in several screens, where an inline `onClick` handler is a silent no-op —
 * this tiny client component carries the handler across the boundary.
 */
export function PrintButton({
  children,
  className,
  title,
}: {
  children: ReactNode;
  className?: string;
  title?: string;
}) {
  return (
    <button type="button" title={title} onClick={() => window.print()} className={className}>
      {children}
    </button>
  );
}
