import { NextRequest, NextResponse } from "next/server";
import {
  DASHBOARD_WRITE_LIMIT,
  DASHBOARD_WRITE_WINDOW_SECONDS,
  isWriteMethod,
  remainingWriteLimit,
  tooManyWriteAttempts,
  hitWriteLimit,
  writeLimitRetryAfter,
  writeRateLimitKey,
} from "@/lib/rate-limit";

/**
 * Edge middleware, mirroring the Laravel app's web middleware:
 *  - request-id header on every response (`request.id`),
 *  - dashboard/account guards redirect to `/login` when unauthenticated,
 *  - dashboard write throttle (`DashboardWriteThrottle`, 120 writes / 60s),
 *  - gateway webhook/callback paths are left untouched (never rewrapped).
 *
 * Full session verification happens in the server components / route handlers;
 * middleware only does the cheap cookie presence check so it can stay on the edge.
 *
 * Server actions are POSTs to the page URL, so the write throttle also covers
 * every `action.ts` mutation under /dashboard and /account.
 */

const SESSION_COOKIE = process.env.SESSION_COOKIE ?? "eskoofy_session";

const PROTECTED_PREFIXES = ["/dashboard", "/account"];

function isProtectedPath(pathname: string): boolean {
  return PROTECTED_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`));
}

function clientIp(request: NextRequest): string {
  const forwarded = request.headers.get("x-forwarded-for");
  if (forwarded) return forwarded.split(",")[0]!.trim();
  return request.headers.get("x-real-ip") ?? "unknown";
}

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Expose the pathname to server components (for sidebar/topbar active states)
  // and the `?lang=` override so layouts can resolve the request locale (the
  // App Router does not hand layouts the query string).
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set("x-pathname", pathname);

  const lang = request.nextUrl.searchParams.get("lang");
  if (lang) requestHeaders.set("x-lang", lang);

  const response = NextResponse.next({ request: { headers: requestHeaders } });
  response.headers.set("x-request-id", crypto.randomUUID());

  if (!isProtectedPath(pathname)) {
    return response;
  }

  if (!request.cookies.get(SESSION_COOKIE)?.value) {
    const url = request.nextUrl.clone();
    url.pathname = "/login";
    url.searchParams.set("redirect", pathname);
    return NextResponse.redirect(url);
  }

  if (isWriteMethod(request.method)) {
    // The session cookie is a JWT; only the payload id matters as a bucket
    // discriminator, and the unverified token tail is enough for that. A
    // tampered cookie still lands in its own bucket, so it cannot borrow
    // another user's allowance.
    const token = request.cookies.get(SESSION_COOKIE)?.value ?? "";
    const key = writeRateLimitKey(token.slice(-16), clientIp(request));

    // Check before recording, exactly like the app's DashboardWriteThrottle.
    if (tooManyWriteAttempts(key, DASHBOARD_WRITE_LIMIT, DASHBOARD_WRITE_WINDOW_SECONDS)) {
      const retryAfter = writeLimitRetryAfter(key, DASHBOARD_WRITE_WINDOW_SECONDS);
      const blocked = NextResponse.json(
        {
          success: false,
          message: "Too many requests. Please try again later.",
          data: null,
        },
        { status: 429, headers: { "Retry-After": String(retryAfter) } },
      );
      blocked.headers.set("x-request-id", response.headers.get("x-request-id") ?? crypto.randomUUID());
      return blocked;
    }

    response.headers.set(
      "x-ratelimit-limit",
      String(DASHBOARD_WRITE_LIMIT),
    );
    response.headers.set(
      "x-ratelimit-remaining",
      String(remainingWriteLimit(key, DASHBOARD_WRITE_LIMIT, DASHBOARD_WRITE_WINDOW_SECONDS)),
    );

    hitWriteLimit(key, DASHBOARD_WRITE_WINDOW_SECONDS);
  }

  return response;
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp|ico|css|js|map)$).*)"],
};