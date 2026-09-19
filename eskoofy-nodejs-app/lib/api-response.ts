import { NextResponse } from "next/server";

/**
 * API envelope, mirroring the Laravel app's `StandardizeApiResponse`
 * middleware: every `/api/*` JSON response is `{success, message, data[, meta]}`.
 *
 * Gateway webhook/callback paths are NEVER rewrapped (see `isWebhookPath`).
 */
export interface ApiMeta {
  pagination?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
  [key: string]: unknown;
}

export interface ApiEnvelope<T> {
  success: boolean;
  message: string;
  data: T;
  meta?: ApiMeta;
}

const JSON_HEADERS = { "content-type": "application/json; charset=utf-8" };

export function success<T>(data: T, message = "Success", meta?: ApiMeta, status = 200) {
  return NextResponse.json<ApiEnvelope<T>>(
    { success: true, message, data, ...(meta ? { meta } : {}) },
    { status, headers: JSON_HEADERS },
  );
}

export function created<T>(data: T, message = "Created", meta?: ApiMeta) {
  return success(data, message, meta, 201);
}

export function paginated<T>(
  rows: T[],
  pagination: { current_page: number; per_page: number; total: number },
  message = "Success",
) {
  const lastPage = Math.max(1, Math.ceil(pagination.total / Math.max(1, pagination.per_page)));
  return success(rows, message, {
    pagination: {
      current_page: pagination.current_page,
      per_page: pagination.per_page,
      total: pagination.total,
      last_page: lastPage,
    },
  });
}

export function error(message = "Error", status = 400, data: unknown = null) {
  return NextResponse.json<ApiEnvelope<unknown>>(
    { success: false, message, data },
    { status, headers: JSON_HEADERS },
  );
}

export function unauthorized(message = "Unauthenticated.") {
  return error(message, 401);
}

export function forbidden(message = "This action is unauthorized.") {
  return error(message, 403);
}

export function notFound(message = "Not found.") {
  return error(message, 404);
}

export function validationError(errors: Record<string, string[]>, message = "The given data was invalid.") {
  return error(message, 422, { errors });
}

/** Gateway webhook/callback paths must never be enveloped by the normalizer. */
export function isWebhookPath(pathname: string): boolean {
  return /(^|\/)(webhooks?|callbacks?)(\/|$)/.test(pathname);
}
