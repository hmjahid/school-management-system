import { NextResponse, type NextRequest } from "next/server";
import { DASHBOARD_LOCALE_COOKIE, availableLocales } from "@/lib/i18n";

export const dynamic = "force-dynamic";

/**
 * Dashboard language switch — mirrors `DashboardLocaleController@switch`
 * (`GET /dashboard/locale/{locale}`): validates the locale, stores the
 * dashboard_locale cookie and redirects back to the referring page.
 */
export async function GET(request: NextRequest, { params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const referer = request.headers.get("referer") ?? "/dashboard";
  const target = new URL(referer, request.url);

  const response = NextResponse.redirect(target);
  if (availableLocales().includes(locale)) {
    response.cookies.set(DASHBOARD_LOCALE_COOKIE, locale, {
      path: "/",
      maxAge: 60 * 60 * 24 * 365,
      sameSite: "lax",
    });
  }
  return response;
}
