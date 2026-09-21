import { NextResponse, type NextRequest } from "next/server";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";

export const dynamic = "force-dynamic";

/**
 * Pin/unpin a dashboard page — mirrors `dashboard.favorites.toggle`.
 * Accepts a form-encoded `url` (+ optional `label`) and toggles the row for the
 * signed-in user in `dashboard_favorites`.
 */
export async function POST(request: NextRequest) {
  const user = await currentUser();
  if (!user) return NextResponse.json({ success: false, message: "Unauthorized" }, { status: 401 });

  let url = "";
  let label = "";
  const contentType = request.headers.get("content-type") ?? "";
  if (contentType.includes("application/json")) {
    const body = (await request.json().catch(() => ({}))) as { url?: string; label?: string };
    url = body.url ?? "";
    label = body.label ?? "";
  } else {
    const body = await request.formData();
    url = String(body.get("url") ?? "");
    label = String(body.get("label") ?? "");
  }

  if (!url || !url.startsWith("/")) {
    return NextResponse.json({ success: false, message: "Invalid url" }, { status: 422 });
  }

  const existing = await prisma.dashboard_favorites.findFirst({ where: { user_id: user.id, url } });

  if (existing) {
    await prisma.dashboard_favorites.delete({ where: { id: existing.id } });
    return NextResponse.json({ success: true, favorited: false });
  }

  await prisma.dashboard_favorites.create({ data: { user_id: user.id, url, label: label || url } });
  return NextResponse.json({ success: true, favorited: true });
}
