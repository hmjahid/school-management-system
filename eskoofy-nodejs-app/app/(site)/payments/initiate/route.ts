import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { currentUser } from "@/lib/auth";
import { gatewayConfig, isGatewayConfigured, type GatewayRow } from "@/lib/payments/gateways";
import {
  generateFeePaymentInvoiceNumber,
  generatePaymentInvoiceNumber,
  initializePayment,
  parsePaymentRow,
  type PaymentRecord,
} from "@/lib/payments/service";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

async function studentIdsForUser(user: { id: number; role: string }): Promise<number[]> {
  if (user.role === "student") {
    const row = await prisma.students.findFirst({ where: { user_id: user.id, deleted_at: null }, select: { id: true } });
    return row ? [row.id] : [];
  }
  if (user.role === "parent") {
    const rows = await prisma.guardian_student.findMany({
      where: { guardians: { user_id: user.id } },
      select: { student_id: true },
    });
    return rows.map((r) => r.student_id);
  }
  return [];
}

/**
 * POST /payments/initiate — the public payments form. Mirrors
 * `PaymentsWebController::initiate()`: create the fee-payment + payment rows in
 * one transaction, initialize the gateway, then redirect to checkout (or the
 * status page when the gateway is offline).
 */
export async function POST(request: NextRequest) {
  const user = await currentUser();
  if (!user) return NextResponse.redirect(new URL("/login?redirect=/payments", request.nextUrl.origin), 303);

  const studentIds = await studentIdsForUser(user);
  if (!["student", "parent"].includes(user.role) || studentIds.length === 0) {
    return new NextResponse("Forbidden", { status: 403 });
  }

  const form = await request.formData();
  const studentId = Number(form.get("student_id"));
  const feeId = Number(form.get("fee_id"));
  const gatewayCode = String(form.get("gateway") ?? "");
  const amount = Number(form.get("amount"));

  if (!studentIds.includes(studentId)) return new NextResponse("Forbidden", { status: 403 });
  if (!Number.isFinite(feeId) || !Number.isFinite(amount) || amount < 1) {
    return NextResponse.redirect(new URL("/payments", request.nextUrl.origin), 303);
  }

  const [gatewayRow, fee] = await Promise.all([
    prisma.payment_gateways.findFirst({ where: { code: gatewayCode, is_active: true } }),
    prisma.fees.findFirst({ where: { id: feeId } }),
  ]);
  if (!gatewayRow || !fee) return NextResponse.redirect(new URL("/payments", request.nextUrl.origin), 303);

  const gateway = gatewayRow as unknown as GatewayRow;
  if (gatewayRow.is_online && !isGatewayConfigured(gateway)) {
    return NextResponse.redirect(new URL("/payments", request.nextUrl.origin), 303);
  }

  const statusPath = (id: number) => new URL(`/payments/status/${id}`, request.nextUrl.origin).toString();
  const [feeInvoice, paymentInvoice] = await Promise.all([generateFeePaymentInvoiceNumber(), generatePaymentInvoiceNumber()]);

  const payment = await prisma.$transaction(async (tx) => {
    const feePayment = await tx.fee_payments.create({
      data: {
        invoice_number: feeInvoice,
        student_id: studentId,
        fee_id: feeId,
        amount,
        paid_amount: 0,
        discount_amount: 0,
        fine_amount: 0,
        balance: amount,
        payment_date: new Date(),
        payment_method: "online_payment",
        status: "pending",
        metadata: JSON.stringify({ gateway: gatewayRow.code }),
        created_by: user.id,
        created_at: new Date(),
        updated_at: new Date(),
      },
    });

    const row = await tx.payments.create({
      data: {
        paymentable_type: "tuition",
        paymentable_id: feePayment.id,
        invoice_number: paymentInvoice,
        amount,
        paid_amount: 0,
        due_amount: amount,
        discount_amount: 0,
        fine_amount: 0,
        tax_amount: 0,
        total_amount: amount,
        payment_method: gatewayRow.code,
        payment_status: "pending",
        payment_details: JSON.stringify({
          description: `Fee payment: ${fee.name}`,
          currency: gatewayConfig(gateway).currency,
          return_url: statusPath(0),
          cancel_url: statusPath(0),
        }),
        metadata: JSON.stringify({ fee_payment_id: feePayment.id, student_id: studentId, fee_id: feeId }),
        created_by: user.id,
        updated_by: user.id,
        created_at: new Date(),
        updated_at: new Date(),
      },
    });

    return row;
  });

  const record: PaymentRecord = parsePaymentRow(payment as unknown as Record<string, unknown>);
  const returnUrl = statusPath(record.id);
  record.payment_details = { ...record.payment_details, return_url: returnUrl, cancel_url: returnUrl };

  await prisma.payments.update({
    where: { id: record.id },
    data: { payment_details: JSON.stringify(record.payment_details), updated_at: new Date() },
  });

  try {
    const init = await initializePayment(record, gateway, { return_url: returnUrl, cancel_url: returnUrl });
    return NextResponse.redirect(new URL(init.redirect_url ?? returnUrl, request.nextUrl.origin), 303);
  } catch {
    return NextResponse.redirect(new URL(returnUrl, request.nextUrl.origin), 303);
  }
}
