import { NextRequest } from "next/server";
import type { PaymentRecord } from "@/lib/payments/service";

/** Accept both JSON and form-encoded gateway posts. */
export async function readGatewayPayload(request: NextRequest): Promise<Record<string, unknown>> {
  const contentType = request.headers.get("content-type") ?? "";
  const clone = request.clone();

  if (contentType.includes("application/json")) {
    const parsed = await clone.json().catch(() => null);
    return parsed && typeof parsed === "object" && !Array.isArray(parsed) ? (parsed as Record<string, unknown>) : {};
  }

  const form = await clone.formData().catch(() => null);
  if (!form) return {};
  const out: Record<string, unknown> = {};
  for (const [key, value] of form.entries()) out[key] = typeof value === "string" ? value : value.name;
  return out;
}

/** Subset of a payment returned to gateway-facing endpoints. */
export function paymentResource(payment: PaymentRecord) {
  return {
    id: payment.id,
    invoice_number: payment.invoice_number,
    amount: payment.amount,
    total_amount: payment.total_amount,
    payment_method: payment.payment_method,
    payment_status: payment.payment_status,
    transaction_id: payment.transaction_id,
    payment_details: payment.payment_details,
    metadata: payment.metadata,
  };
}
