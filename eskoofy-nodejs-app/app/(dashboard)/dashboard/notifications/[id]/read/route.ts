import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";

export const dynamic = "force-dynamic";

/**
 * Mark a notification read — mirrors the app's `GET /dashboard/notifications/
 * {id}/read` (`NotificationController@markRead`): mark the row read then jump
 * to its destination link.
 */
export async function GET(_request: Request, ctx: { params: Promise<{ id: string }> }): Promise<void> {
  const user = await currentUser();
  const notificationId = (await ctx.params).id;

  const row = user
    ? await prisma.notifications
        .findFirst({ where: { id: notificationId, notifiable_id: user.id }, select: { id: true, data: true } })
        .catch(() => null)
    : null;

  if (row) {
    await prisma.notifications
      .updateMany({ where: { id: row.id, read_at: null }, data: { read_at: new Date() } })
      .catch(() => {});
  }

  let url = "/dashboard/notifications";
  if (row) {
    try {
      const parsed = JSON.parse(String(row.data)) as { url?: string };
      if (parsed.url) url = parsed.url;
    } catch {
      // non-JSON payload — fall back to the inbox
    }
  }

  redirect(url);
}