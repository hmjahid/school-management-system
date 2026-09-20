import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { availableLocales, LOCALE_COOKIE, DASHBOARD_LOCALE_COOKIE } from "@/lib/i18n";

export const dynamic = "force-dynamic";

/**
 * Locale switch — mirrors the app's LocaleController: validate the locale,
 * persist it (cookie = the Node equivalent of Laravel's session), redirect back.
 */
export async function GET(
  _request: Request,
  { params }: { params: Promise<{ locale: string }> },
): Promise<never> {
  const { locale } = await params;
  const referer = _request.headers.get("referer") ?? "/";
  const isDashboard = referer.includes("/dashboard");

  const store = await cookies();
  if (availableLocales().includes(locale)) {
    store.set(isDashboard ? DASHBOARD_LOCALE_COOKIE : LOCALE_COOKIE, locale, {
      path: "/",
      httpOnly: false,
      maxAge: 60 * 60 * 24 * 365,
      sameSite: "lax",
    });
    // Also mirror the dashboard/site split: keep the non-target cookie too.
    store.set(isDashboard ? LOCALE_COOKIE : DASHBOARD_LOCALE_COOKIE, locale, {
      path: "/",
      httpOnly: false,
      maxAge: 60 * 60 * 24 * 365,
      sameSite: "lax",
    });
  }

  redirect(referer.startsWith("/") ? referer : "/");
}
