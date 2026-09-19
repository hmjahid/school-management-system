import { NextRequest } from "next/server";
import { resolveModel } from "@/lib/resources";
import { createRow, deleteRow, findRow, listRows, updateRow } from "@/lib/db-query";
import { created, error, notFound, paginated, success } from "@/lib/api-response";
import { matchRoute } from "@/lib/route-registry";

export const dynamic = "force-dynamic";

const PER_PAGE = 25;

/**
 * Generic `/api/v1` handler. Resolves the app route (parity) and the matching
 * table, then serves REST over it inside the `{success,message,data[,meta]}`
 * envelope. Static handlers (e.g. `/api/v1/ping`) win by specificity.
 */
async function handle(request: NextRequest, method: string) {
  const route = matchRoute(method, request.nextUrl.pathname);
  if (!route) return notFound("Unknown API route.");

  const segments = request.nextUrl.pathname
    .replace(/^\/api\/v1\/?/, "")
    .split("/")
    .filter(Boolean);

  const model = resolveModel(segments);
  if (!model) {
    return error("This endpoint is registered in the reference app but has no Node implementation yet.", 501);
  }

  const last = segments[segments.length - 1];
  const isItem = /^\d+$/.test(last);
  const id = isItem ? last : undefined;

  if (method === "GET") {
    if (id) {
      const row = await findRow(model, id);
      return row ? success(row) : notFound("Not found.");
    }
    const page = Math.max(1, Number(request.nextUrl.searchParams.get("page") ?? 1) || 1);
    const search = request.nextUrl.searchParams.get("q") ?? undefined;
    const { rows, total } = await listRows(model, { take: PER_PAGE, skip: (page - 1) * PER_PAGE, search });
    return paginated(rows, { current_page: page, per_page: PER_PAGE, total });
  }

  if (method === "POST") {
    const payload = (await request.json().catch(() => null)) as Record<string, unknown> | null;
    if (!payload) return error("A JSON body is required.", 422);
    const result = await createRow(model, payload);
    return result.ok ? created(result.data) : error(result.error ?? "Create failed.", 422);
  }

  if (method === "PUT" || method === "PATCH") {
    if (!id) return error("An id is required.", 422);
    const payload = (await request.json().catch(() => null)) as Record<string, unknown> | null;
    if (!payload) return error("A JSON body is required.", 422);
    const result = await updateRow(model, id, payload);
    return result.ok ? success(result.data, "Updated.") : error(result.error ?? "Update failed.", 422);
  }

  if (method === "DELETE") {
    if (!id) return error("An id is required.", 422);
    const result = await deleteRow(model, id);
    return result.ok ? success(null, "Deleted.") : error(result.error ?? "Delete failed.", 422);
  }

  return error("Method not allowed.", 405);
}

export const GET = (request: NextRequest) => handle(request, "GET");
export const POST = (request: NextRequest) => handle(request, "POST");
export const PUT = (request: NextRequest) => handle(request, "PUT");
export const PATCH = (request: NextRequest) => handle(request, "PATCH");
export const DELETE = (request: NextRequest) => handle(request, "DELETE");
