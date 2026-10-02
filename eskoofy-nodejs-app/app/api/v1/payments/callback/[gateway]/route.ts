import { NextRequest, NextResponse } from "next/server";
import { redactPayload } from "@/lib/payments/gateways";
import { paymentResource, readGatewayPayload } from "@/lib/payments/http";
import { processCallback } from "@/lib/payments/service";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

/**
 * POST /api/v1/payments/callback/{gateway} — public gateway return endpoint.
 * Mirrors `PaymentController::callback()`: redirect to the stored return/cancel
 * URL when present, otherwise answer with raw JSON (callbacks are never wrapped
 * in the API envelope — see `isWebhookPath`).
 */
export async function POST(request: NextRequest, { params }: { params: Promise<{ gateway: string }> }) {
  const { gateway } = await params;
  const payload = await readGatewayPayload(request);

  try {
    const payment = await processCallback(gateway, payload);
    const details = payment.payment_details;

    const completed = payment.payment_status === "completed";
    let redirectUrl = typeof details.return_url === "string" ? details.return_url : null;
    if (completed) {
      redirectUrl = (typeof details.success_url === "string" ? details.success_url : null) ?? redirectUrl;
    } else {
      redirectUrl = (typeof details.cancel_url === "string" ? details.cancel_url : null) ?? redirectUrl;
    }

    if (!redirectUrl) {
      return NextResponse.json({
        success: completed,
        message: completed ? "Payment completed successfully" : "Payment failed or was cancelled",
        payment: paymentResource(payment),
      });
    }

    const url = new URL(redirectUrl, request.nextUrl.origin);
    url.searchParams.set("payment_id", String(payment.id));
    url.searchParams.set("status", payment.payment_status);
    url.searchParams.set("transaction_id", payment.transaction_id ?? "");
    url.searchParams.set("invoice_number", payment.invoice_number);
    return NextResponse.redirect(url, 303);
  } catch (error) {
    console.error("Payment callback failed:", { gateway, payload: redactPayload(payload), error });
    return NextResponse.json({ success: false, message: "Payment processing failed. Please try again later." }, { status: 400 });
  }
}
