import { APP_ROUTES, type AppRoute } from "@/lib/routes.generated";

/**
 * Helpers over the generated app route surface (`lib/routes.generated.ts`).
 * Used by the catch-all pages/handlers and by `scripts/route-parity.ts`.
 */

export function normalizePath(path: string): string {
  const trimmed = `/${path}`.replace(/\/+/g, "/").replace(/\/$/, "");
  return trimmed === "" ? "/" : trimmed;
}

/** Turn an app URI (`/dashboard/students/{student}`) into a path regex. */
export function uriToRegex(uri: string): RegExp {
  const escaped = uri.replace(/[.+*?^$()|[\]\\]/g, "\\$&");
  const pattern = escaped.replace(/\{[^/}]+\}/g, "[^/]+");
  return new RegExp(`^${pattern}/?$`, "i");
}

export function matchRoute(method: string, path: string): AppRoute | undefined {
  const target = normalizePath(path);
  return APP_ROUTES.find(
    (route) => route.method.split("|").includes(method) && uriToRegex(route.uri).test(target),
  );
}

export function isDashboardRoute(route: AppRoute): boolean {
  return route.uri === "/dashboard" || route.uri.startsWith("/dashboard/");
}

export function isApiRoute(route: AppRoute): boolean {
  return route.uri === "/api" || route.uri.startsWith("/api/");
}

export const DASHBOARD_ROUTES = APP_ROUTES.filter(isDashboardRoute);
export const API_ROUTES = APP_ROUTES.filter(isApiRoute);
export const SITE_ROUTES = APP_ROUTES.filter((route) => !isDashboardRoute(route) && !isApiRoute(route));

/** Distinct dashboard resource slugs (first path segment), excluding the home. */
export function dashboardResourcePaths(): string[] {
  const paths = new Set<string>();
  for (const route of DASHBOARD_ROUTES) {
    const [, , ...rest] = route.uri.split("/");
    if (rest.length > 0 && rest[0] !== "") paths.add(rest[0]);
  }
  return [...paths].sort();
}

export interface RouteCoverage {
  total: number;
  dashboard: number;
  api: number;
  site: number;
  resources: number;
}

export function routeCoverage(resourceCount: number): RouteCoverage {
  return {
    total: APP_ROUTES.length,
    dashboard: DASHBOARD_ROUTES.length,
    api: API_ROUTES.length,
    site: SITE_ROUTES.length,
    resources: resourceCount,
  };
}
