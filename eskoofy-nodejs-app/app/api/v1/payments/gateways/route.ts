import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { isGatewayConfigured, supportedCurrencies, type GatewayRow } from "@/lib/payments/gateways";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

/** Human label for a gateway's type, mirroring `PaymentGateway::getTypeLabelAttribute()`. */
function typeLabel(code: string, type: string): string {
  if (["cash", "cheque", "bank_transfer"].includes(code)) return "Offline";
  if (type === "mobile_money") return "Mobile money";
  if (type === "bank") return "Bank";
  return "Online";
}

/** GET /api/v1/payments/gateways — active gateways, mirrors `PaymentController::gateways`. */
export async function GET() {
  const rows = await prisma.payment_gateways.findMany({
    where: { is_active: true },
    orderBy: [{ sort_order: "asc" }, { name: "asc" }],
  });

  const data = rows.map((row) => {
    const gateway = row as unknown as GatewayRow & Record<string, unknown>;
    return {
      id: row.id,
      name: row.name,
      code: row.code,
      type: row.type,
      type_label: typeLabel(row.code, row.type),
      is_online: Boolean(row.is_online),
      logo_url: typeof row.logo === "string" && row.logo !== "" ? `/storage/${row.logo}` : null,
      description: row.description,
      fee_percentage: Number(row.fee_percentage ?? 0),
      fee_fixed: Number(row.fee_fixed ?? 0),
      min_amount: row.min_amount == null ? null : Number(row.min_amount),
      max_amount: row.max_amount == null ? null : Number(row.max_amount),
      currency: row.currency,
      supported_currencies: supportedCurrencies(gateway),
      is_configured: isGatewayConfigured(gateway),
    };
  });

  return NextResponse.json({ success: true, data });
}
