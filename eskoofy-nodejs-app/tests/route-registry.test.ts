import { describe, expect, it } from "vitest";
import { APP_ROUTES } from "@/lib/routes.generated";
import {
  API_ROUTES,
  DASHBOARD_ROUTES,
  SITE_ROUTES,
  dashboardResourcePaths,
  matchRoute,
  normalizePath,
  uriToRegex,
} from "@/lib/route-registry";

describe("app route registry (clone surface)", () => {
  it("holds the app's full route list", () => {
    expect(APP_ROUTES.length).toBeGreaterThan(500);
  });

  it("splits routes into dashboard / api / site", () => {
    expect(DASHBOARD_ROUTES.length).toBeGreaterThan(0);
    expect(API_ROUTES.length).toBeGreaterThan(0);
    expect(SITE_ROUTES.length).toBeGreaterThan(0);
    expect(DASHBOARD_ROUTES.length + API_ROUTES.length + SITE_ROUTES.length).toBe(APP_ROUTES.length);
  });

  it("normalises paths", () => {
    expect(normalizePath("/dashboard/students/")).toBe("/dashboard/students");
    expect(normalizePath("dashboard")).toBe("/dashboard");
    expect(normalizePath("/")).toBe("/");
  });

  it("turns URIs with parameters into matchers", () => {
    expect(uriToRegex("/dashboard/students/{student}").test("/dashboard/students/12")).toBe(true);
    expect(uriToRegex("/dashboard/students/{student}").test("/dashboard/students/12/edit")).toBe(false);
  });

  it("matches a known app route by method and path", () => {
    expect(matchRoute("GET", "/")?.name).toBe("home");
    expect(matchRoute("GET", "/dashboard/students")?.uri).toContain("/dashboard");
  });

  it("does not match unknown paths", () => {
    expect(matchRoute("GET", "/definitely/not/a/route")).toBeUndefined();
  });

  it("exposes dashboard resource slugs", () => {
    const paths = dashboardResourcePaths();
    expect(paths).toContain("students");
    expect(paths.length).toBeGreaterThan(10);
  });
});
