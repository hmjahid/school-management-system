import { NextRequest, NextResponse } from "next/server";
import { runDueJobs } from "@/lib/scheduler";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

/**
 * Cron entry point. Point an external scheduler (cron, Vercel Cron, a systemd
 * timer) at this endpoint on a cheap fixed tick; each job decides internally
 * whether it is due.
 *
 * Set `CRON_SECRET` to require `Authorization: Bearer <secret>` (or
 * `?token=<secret>`). Without it the endpoint is open, which is fine for local
 * installs and matches the app's `--force` schedule.
 */
async function handle(request: NextRequest) {
  const secret = process.env.CRON_SECRET;
  if (secret) {
    const bearer = request.headers.get("authorization")?.replace(/^Bearer\s+/i, "");
    const token = request.nextUrl.searchParams.get("token");
    if (bearer !== secret && token !== secret) {
      return NextResponse.json({ success: false, message: "Unauthorized" }, { status: 401 });
    }
  }

  const results = await runDueJobs();
  const failed = results.filter((r) => r.status === "error");

  return NextResponse.json({ success: failed.length === 0, data: results });
}

export const GET = handle;
export const POST = handle;
