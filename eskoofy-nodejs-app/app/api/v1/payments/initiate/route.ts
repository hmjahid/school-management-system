import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { unauthorized } from "@/lib/api-response";
import { supportedCurrencies, isGatewayConfigured } from "@/lib/payments/gateways";
import { generatePaymentInvoiceNumber, initializePayment, parsePaymentRow, type PaymentRecord } from "@/lib/payments/service";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

const PAYMENTABLE_TYPES = ["admission", "tuition", "exam", "library", "transport", "hostel", "other"];

function bad(message: string) {
  return NextResponse.json({ success: false, message }, { status: 400 });
}

/** POST /api/v1/payments/initiate — mirrors `PaymentController::initiate`. */
export async function POST(request: NextRequest) {
  const user = await currentUser();
  if (!user) return unauthorized();

  const body = (await request.json().catch(() => null)) as Record<string, unknown> | null;
  if (!body) return NextResponse.json({ success: false, message: "A JSON body is required." }, { status: 422 });

  const gatewayCode = String(body.gateway ?? "");
  const amount = Number(body.amount);
  const currency = String(body.currency ?? "");
  const paymentableType = String(body.paymentable_type ?? "");
  const paymentableId = Number(body.paymentable_id);

  if (!gatewayCode) return bad("The selected payment gateway is currently unavailable.");
  if (!Number.isFinite(amount) || amount < 1) return NextResponse.json({ success: false, message: "The amount must be at least 1." }, { status: 422 });
  if (currency.length !== 3) return NextResponse.json({ success: false, message: "The currency must be 3 characters." }, { status: 422 });
  if (!PAYMENTABLE_TYPES.includes(paymentableType)) return NextResponse.json({ success: false, message: "The paymentable type is invalid." }, { status: 422 });
  if (!Number.isFinite(paymentableId)) return NextResponse.json({ success: false, message: "The paymentable id is required." }, { status: 422 });

  const gatewayRow = await prisma.payment_gateways.findUnique({ where: { code: gatewayCode } });
  if (!gatewayRow) return bad("The selected payment gateway is currently unavailable.");
  if (!gatewayRow.is_active) return bad("The selected payment gateway is currently unavailable.");

  const gateway = gatewayRow as unknown as Parameters<typeof initializePayment>[1];
  if (gatewayRow.is_online && !isGatewayConfigured(gateway)) {
    return bad("The selected payment gateway is not properly configured.");
  }

  if (gatewayRow.min_amount !== null && amount < Number(gatewayRow.min_amount)) {
    return bad(`Minimum payment amount is ${gatewayRow.currency} ${Number(gatewayRow.min_amount)} for ${gatewayRow.name}.`);
  }
  if (gatewayRow.max_amount !== null && amount > Number(gatewayRow.max_amount)) {
    return bad(`Maximum payment amount is ${gatewayRow.currency} ${Number(gatewayRow.max_amount)} for ${gatewayRow.name}.`);
  }
  if (!supportedCurrencies(gateway).includes(currency)) {
    return bad(`The selected currency is not supported by ${gatewayRow.name}.`);
  }

  const fee = Number(gatewayRow.fee_fixed ?? 0) + (amount * Number(gatewayRow.fee_percentage ?? 0)) / 100;
  const total = amount + fee;
  const description = body.description == null ? null : String(body.description);
  const metadata = body.metadata && typeof body.metadata === "object" ? (body.metadata as Record<string, unknown>) : null;
  const returnUrl = body.return_url == null ? null : String(body.return_url);
  const cancelUrl = body.cancel_url == null ? null : String(body.cancel_url);
  const invoice = await generatePaymentInvoiceNumber();

  try {
    const created = await prisma.payments.create({
      data: {
        paymentable_type: paymentableType,
        paymentable_id: paymentableId,
        invoice_number: invoice,
        amount,
        paid_amount: 0,
        due_amount: total,
        discount_amount: 0,
        fine_amount: 0,
        tax_amount: 0,
        total_amount: total,
        payment_method: gatewayRow.code,
        payment_status: "pending",
        payment_details: JSON.stringify({
          description,
          metadata,
          currency,
          fee_percentage: Number(gatewayRow.fee_percentage ?? 0),
          fee_fixed: Number(gatewayRow.fee_fixed ?? 0),
          fee_amount: fee,
          return_url: returnUrl,
          cancel_url: cancelUrl,
        }),
        metadata: metadata ? JSON.stringify(metadata) : null,
        notes: description,
        created_by: user.id,
        updated_by: user.id,
        created_at: new Date(),
        updated_at: new Date(),
      },
    });

    const payment: PaymentRecord = parsePaymentRow(created as unknown as Record<string, unknown>);
    const result = await initializePayment(payment, gateway, { return_url: returnUrl ?? undefined, cancel_url: cancelUrl ?? undefined });

    const updated = await prisma.payments.update({
      where: { id: payment.id },
      data: {
        payment_details: JSON.stringify({ ...payment.payment_details, init_response: result }),
        updated_at: new Date(),
      },
    });

    return NextResponse.json({
      success: true,
      message: "Payment initiated successfully",
      data: { payment: parsePaymentRow(updated as unknown as Record<string, unknown>), gateway: result },
    });
  } catch (error) {
    console.error("Payment initiation failed:", error);
    return NextResponse.json(
      {
        success: false,
        message: "Failed to initiate payment. Please try again or contact support.",
        error: process.env.NODE_ENV !== "production" ? String(error instanceof Error ? error.message : error) : null,
      },
      { status: 500 },
    );
  }
}
