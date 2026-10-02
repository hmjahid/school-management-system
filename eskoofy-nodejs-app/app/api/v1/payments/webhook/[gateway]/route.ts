import { NextRequest, NextResponse } from "next/server";
import { redactPayload, verifyWebhookSignature } from "@/lib/payments/gateways";
import { findGateway, handleWebhook } from "@/lib/payments/service";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

function parseRawBody(rawBody: string, contentType: string): Record<string, unknown> {
  if (contentType.includes("application/json")) {
    try {
      const parsed: unknown = JSON.parse(rawBody);
      return parsed && typeof parsed === "object" && !Array.isArray(parsed) ? (parsed as Record<string, unknown>) : {};
    } catch {
      return {};
    }
  }
  const out: Record<string, unknown> = {};
  for (const [key, value] of new URLSearchParams(rawBody).entries()) out[key] = value;
  return out;
}

/**
 * POST /api/v1/payments/webhook/{gateway} — public, signature-verified,
 * idempotent. Mirrors `PaymentController::webhook()`: fail closed on an unknown
 * gateway or bad signature, dedupe by payload hash, then process the callback.
 * Responses are never wrapped in the API envelope.
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ gateway: string }> }) {
  const { gateway } = await params;
  const rawBody = await request.text();
  const contentType = request.headers.get("content-type") ?? "";

  const gatewayRow = await findGateway(gateway);
  if (!gatewayRow) {
    return NextResponse.json({ success: false, message: "Unknown payment gateway" }, { status: 403 });
  }

  if (!verifyWebhookSignature(gatewayRow, rawBody, (name) => request.headers.get(name))) {
    return NextResponse.json({ success: false, message: "Invalid webhook signature" }, { status: 403 });
  }

  const payload = parseRawBody(rawBody, contentType);
  const headers: Record<string, string> = {};
  request.headers.forEach((value, key) => {
    headers[key] = value;
  });

  try {
    const outcome = await handleWebhook(gateway, rawBody, payload, headers);

    if (outcome.duplicate) {
      return NextResponse.json({ success: true, message: "Duplicate webhook ignored" });
    }

    return NextResponse.json({
      success: true,
      message: "Webhook processed successfully",
      payment_id: outcome.payment_id,
      status: outcome.status,
    });
  } catch (error) {
    console.error("Webhook processing failed:", { gateway, payload: redactPayload(payload), error });
    return NextResponse.json({ success: false, message: "Webhook processing failed. Please try again later." }, { status: 400 });
  }
}
