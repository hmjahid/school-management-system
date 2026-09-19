import { NextRequest, NextResponse } from "next/server";

/**
 * Edge middleware, mirroring the Laravel app's web middleware:
 *  - request-id header on every response (`request.id`),
 *  - dashboard/account guards redirect to `/login` when unauthenticated,
 *  - gateway webhook/callback paths are left untouched (never rewrapped).
 *
 * Full session verification happens in the server components / route handlers;
 * middleware only does the cheap cookie presence check so it can stay on the edge.
 */

const SESSION_COOKIE = process.env.SESSION_COOKIE ?? "eskoofy_session";

const PROTECTED_PREFIXES = ["/dashboard", "/account"];

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Expose the pathname to server components (for sidebar/topbar active states).
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set("x-pathname", pathname);

  const response = NextResponse.next({ request: { headers: requestHeaders } });
  response.headers.set("x-request-id", crypto.randomUUID());

  const isProtected = PROTECTED_PREFIXES.some(
    (prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`),
  );

  if (isProtected && !request.cookies.get(SESSION_COOKIE)?.value) {
    const url = request.nextUrl.clone();
    url.pathname = "/login";
    url.searchParams.set("redirect", pathname);
    return NextResponse.redirect(url);
  }

  return response;
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp|ico|css|js|map)$).*)"],
};
