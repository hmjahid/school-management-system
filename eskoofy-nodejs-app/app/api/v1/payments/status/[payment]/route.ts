import { NextRequest } from "next/server";
import { currentUser } from "@/lib/auth";
import { forbidden, notFound, success, unauthorized } from "@/lib/api-response";
import { paymentResource } from "@/lib/payments/http";
import { findGateway, findPayment, verifyPayment } from "@/lib/payments/service";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

/**
 * GET /api/v1/payments/status/{payment} — owner or admin; re-verifies a
 * non-final payment with the gateway. Mirrors `PaymentController::status()`.
 */
export async function GET(_request: NextRequest, { params }: { params: Promise<{ payment: string }> }) {
  const user = await currentUser();
  if (!user) return unauthorized();

  const { payment: idOrInvoice } = await params;
  let payment = await findPayment(idOrInvoice);
  if (!payment) return notFound();

  if (payment.created_by !== user.id && user.role !== "admin") return forbidden();

  if (!["completed", "refunded"].includes(payment.payment_status)) {
    const gateway = await findGateway(payment.payment_method);
    if (gateway) {
      try {
        payment = await verifyPayment(payment, gateway);
      } catch {
        /* show the current state even when verification fails */
      }
    }
  }

  return success(paymentResource(payment));
}
