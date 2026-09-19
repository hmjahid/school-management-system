import Link from "next/link";
import type { ReactNode } from "react";
import { EmptyState } from "@/components/ui/EmptyState";

/** Mirrors the app's `<x-admin-data-table>`. */
export interface Column {
  key: string;
  label: string;
  align?: "left" | "right" | "center";
}

export function DataTable({
  columns,
  rows,
  emptyTitle,
  emptyMessage,
  pagination,
}: {
  columns: Column[];
  rows: ReactNode[][];
  emptyTitle: string;
  emptyMessage?: string;
  pagination?: {
    page: number;
    lastPage: number;
    basePath: string;
    query?: Record<string, string | undefined>;
  };
}) {
  const align = (value?: Column["align"]) =>
    value === "right" ? "text-right" : value === "center" ? "text-center" : "text-left";

  return (
    <div className="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead>
            <tr className="bg-slate-50/80">
              {columns.map((column) => (
                <th
                  key={column.key}
                  scope="col"
                  className={`px-4 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-600 ${align(column.align)}`}
                >
                  {column.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 bg-white">
            {rows.length === 0 ? (
              <tr>
                <td colSpan={columns.length} className="px-4 py-16">
                  <EmptyState title={emptyTitle} message={emptyMessage} />
                </td>
              </tr>
            ) : (
              rows.map((cells, index) => (
                <tr key={index} className="hover:bg-slate-50/60">
                  {cells.map((cell, cellIndex) => (
                    <td key={cellIndex} className={`px-4 py-3 align-middle ${align(columns[cellIndex]?.align)}`}>
                      {cell}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {pagination && pagination.lastPage > 1 ? (
        <div className="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm">
          <span className="text-slate-400">
            Page {pagination.page} of {pagination.lastPage}
          </span>
          <div className="flex items-center gap-2">
            <PageLink
              path={pagination.basePath}
              query={pagination.query}
              page={pagination.page - 1}
              disabled={pagination.page <= 1}
            >
              Previous
            </PageLink>
            <PageLink
              path={pagination.basePath}
              query={pagination.query}
              page={pagination.page + 1}
              disabled={pagination.page >= pagination.lastPage}
            >
              Next
            </PageLink>
          </div>
        </div>
      ) : null}
    </div>
  );
}

function PageLink({
  path,
  query,
  page,
  disabled,
  children,
}: {
  path: string;
  query?: Record<string, string | undefined>;
  page: number;
  disabled: boolean;
  children: ReactNode;
}) {
  if (disabled) {
    return <span className="rounded-lg border border-slate-200 px-3 py-1.5 text-slate-300">{children}</span>;
  }
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query ?? {})) {
    if (value) params.set(key, value);
  }
  params.set("page", String(page));
  return (
    <Link href={`${path}?${params.toString()}`} className="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:border-blue-400">
      {children}
    </Link>
  );
}
