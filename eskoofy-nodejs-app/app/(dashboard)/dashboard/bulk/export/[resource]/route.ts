import { NextResponse } from "next/server";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { BULK_RESOURCES, type BulkResource } from "@/lib/bulk";
import { exportCsv } from "@/lib/bulk-db";

export const dynamic = "force-dynamic";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ resource: string }> },
): Promise<NextResponse> {
  const user = await currentUser();
  if (!can(user?.role, "export_student_data")) {
    return new NextResponse("Forbidden", { status: 403 });
  }

  const { resource } = await params;
  const key = resource as BulkResource;
  if (!(key in BULK_RESOURCES)) {
    return new NextResponse("Not found", { status: 404 });
  }

  try {
    const { filename, text } = await exportCsv(key);
    return new NextResponse(text, {
      headers: {
        "Content-Type": "text/csv; charset=UTF-8",
        "Content-Disposition": `attachment; filename="${filename}"`,
      },
    });
  } catch {
    return new NextResponse("Export failed", { status: 500 });
  }
}