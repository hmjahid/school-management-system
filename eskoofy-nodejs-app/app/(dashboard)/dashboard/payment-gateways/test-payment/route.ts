import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { can } from "@/lib/permissions";
import { isGatewayConfigured, TEST_GATEWAY_CODE, type GatewayRow } from "@/lib/payments/gateways";
import {
  generatePaymentInvoiceNumber,
  initializePayment,
  parsePaymentRow,
} from "@/lib/payments/service";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

const DIAGNOSTIC_AMOUNT = 10;

/**
 * GET /dashboard/payment-gateways/test-payment?gateway={code}
 *
 * Admin diagnostics action ("Run test payment" in the gateway table): creates
 * a small payment for the chosen gateway and hands it to the normal
 * `initializePayment` path — so `test_gateway` lands on the local sandbox page,
 * hosted gateways open their sandbox checkout, and offline gateways land on
 * the status page. Nothing is charged for `test_gateway`.
 */
export async function GET(request: NextRequest) {
  const user = await currentUser();
  if (!user) {
    return NextResponse.redirect(new URL("/login?redirect=/dashboard/payment-gateways", request.nextUrl.origin), 303);
  }
  if (!can(user.role, "manage_settings")) return new NextResponse("Forbidden", { status: 403 });

  const fail = (reason: string) => {
    const url = new URL("/dashboard/payment-gateways", request.nextUrl.origin);
    url.searchParams.set("test_error", reason);
    return NextResponse.redirect(url, 303);
  };

  const code = request.nextUrl.searchParams.get("gateway") ?? TEST_GATEWAY_CODE;
  const row = await prisma.payment_gateways.findFirst({ where: { code, deleted_at: null } });
  if (!row || !row.is_active) return fail("unavailable");

  const gateway = row as unknown as GatewayRow;
  if (row.is_online && !isGatewayConfigured(gateway)) return fail("not_configured");

  let amount = DIAGNOSTIC_AMOUNT;
  if (row.min_amount !== null && amount < Number(row.min_amount)) amount = Number(row.min_amount);
  if (row.max_amount !== null && amount > Number(row.max_amount)) amount = Number(row.max_amount);

  const currency = String(row.currency ?? "BDT");
  const fee = Number(row.fee_fixed ?? 0) + (amount * Number(row.fee_percentage ?? 0)) / 100;
  const total = amount + fee;
  const invoice = await generatePaymentInvoiceNumber();

  const created = await prisma.payments.create({
    data: {
      paymentable_type: "other",
      paymentable_id: 0,
      invoice_number: invoice,
      amount,
      paid_amount: 0,
      due_amount: total,
      discount_amount: 0,
      fine_amount: 0,
      tax_amount: 0,
      total_amount: total,
      payment_method: row.code,
      payment_status: "pending",
      payment_details: JSON.stringify({
        description: "Gateway diagnostics test payment",
        currency,
      }),
      metadata: JSON.stringify({ diagnostic: true }),
      created_by: user.id,
      updated_by: user.id,
      created_at: new Date(),
      updated_at: new Date(),
    },
  });

  const payment = parsePaymentRow(created as unknown as Record<string, unknown>);
  const returnUrl = new URL(`/payments/status/${payment.id}`, request.nextUrl.origin).toString();
  payment.payment_details = { ...payment.payment_details, currency, return_url: returnUrl, cancel_url: returnUrl };

  await prisma.payments.update({
    where: { id: payment.id },
    data: { payment_details: JSON.stringify(payment.payment_details), updated_at: new Date() },
  });

  try {
    const init = await initializePayment(payment, gateway, { return_url: returnUrl, cancel_url: returnUrl });
    return NextResponse.redirect(new URL(init.redirect_url ?? returnUrl, request.nextUrl.origin), 303);
  } catch {
    return fail("init_failed");
  }
}
