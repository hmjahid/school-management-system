import { readFile } from "node:fs/promises";
import { NextResponse } from "next/server";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { backupPathFor } from "@/lib/backup";

export const dynamic = "force-dynamic";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ file: string }> },
): Promise<NextResponse> {
  const user = await currentUser();
  if (!can(user?.role, "backup_database")) {
    return new NextResponse("Forbidden", { status: 403 });
  }

  const { file } = await params;
  try {
    const data = await readFile(backupPathFor(file));
    return new NextResponse(new Uint8Array(data), {
      headers: {
        "Content-Type": "application/zip",
        "Content-Disposition": `attachment; filename="${file}"`,
      },
    });
  } catch {
    return new NextResponse("Not found", { status: 404 });
  }
}