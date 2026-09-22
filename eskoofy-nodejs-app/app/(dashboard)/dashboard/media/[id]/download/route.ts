import path from "node:path";
import { readFile, stat } from "node:fs/promises";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";

export const dynamic = "force-dynamic";

const MEDIA_DIR = path.join(process.cwd(), "public", "uploads", "media");

/**
 * Media download — mirrors `DashboardMediaController@download`
 * (`/dashboard/media/{media}/download`): streams the stored file as an
 * attachment (guarded by CMS manage permission).
 */
export async function GET(_request: Request, ctx: { params: Promise<{ id: string }> }): Promise<Response> {
  const user = await currentUser();
  if (!can(user?.role, "manage_cms")) {
    return new Response("Forbidden", { status: 403 });
  }

  const id = Number((await ctx.params).id);
  const row = id ? await prisma.website_media.findUnique({ where: { id } }).catch(() => null) : null;
  if (!row) {
    return new Response("Not Found", { status: 404 });
  }

  const filePath = path.isAbsolute(String(row.file_path))
    ? path.join(MEDIA_DIR, path.basename(String(row.file_path)))
    : path.join(process.cwd(), "public", ...String(row.file_path).split("/").filter(Boolean));

  try {
    await stat(filePath);
  } catch {
    return new Response("Not Found", { status: 404 });
  }

  const bytes = await readFile(filePath);
  const basename = path.basename(String(row.file_path));
  const filename = basename.includes("-") ? basename.slice(basename.indexOf("-") + 1) : basename;

  return new Response(bytes, {
    headers: {
      "Content-Type": String(row.mime_type ?? "application/octet-stream"),
      "Content-Disposition": `attachment; filename="${filename}"`,
      "Content-Length": String(bytes.length),
    },
  });
}