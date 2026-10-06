import { prisma } from "@/lib/prisma";
import { isGatewayConfigured, resolveGatewayAdapter, type GatewayRow } from "@/lib/payments/gateways";

const ERROR_MESSAGES: Record<string, string> = {
  unavailable: "That gateway is unavailable or inactive.",
  not_configured: "That gateway is not configured yet — add its credentials first.",
  init_failed: "The test payment could not be started. Check the gateway settings.",
};

function YesNo({ value }: { value: boolean }) {
  return (
    <span
      className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
        value ? "bg-emerald-100 text-emerald-800" : "bg-slate-100 text-slate-600"
      }`}
    >
      {value ? "Yes" : "No"}
    </span>
  );
}

/**
 * Admin diagnostics table on the payment-gateways screen: one row per gateway
 * with its resolved adapter/configured state and a "Run test payment" action
 * that starts a zero-cost payment through that gateway's flow
 * (`test_gateway` lands on the local sandbox page — no money moves).
 *
 * Server component rendered by the dashboard catch-all on
 * `/dashboard/payment-gateways`.
 */
export async function GatewayDiagnostics({ error }: { error?: string }) {
  const rows = await prisma.payment_gateways
    .findMany({ where: { deleted_at: null }, orderBy: [{ sort_order: "asc" }, { name: "asc" }] })
    .catch(() => []);

  return (
    <section className="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex flex-wrap items-baseline justify-between gap-2">
        <h2 className="text-base font-semibold text-slate-900">Gateway diagnostics</h2>
        <p className="text-xs text-slate-500">
          “Run test payment” starts a free test payment — for <span className="font-mono">test_gateway</span> nothing is ever charged.
        </p>
      </div>

      {error && ERROR_MESSAGES[error] ? (
        <p className="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900" role="alert">
          {ERROR_MESSAGES[error]}
        </p>
      ) : null}

      <div className="mt-4 overflow-x-auto">
        <table className="min-w-full divide-y divide-slate-200 text-sm">
          <thead className="bg-slate-50">
            <tr>
              <th className="px-3 py-2 text-left font-semibold text-slate-700">Code</th>
              <th className="px-3 py-2 text-left font-semibold text-slate-700">Label</th>
              <th className="px-3 py-2 text-left font-semibold text-slate-700">Active</th>
              <th className="px-3 py-2 text-left font-semibold text-slate-700">Test mode</th>
              <th className="px-3 py-2 text-left font-semibold text-slate-700">Configured</th>
              <th className="px-3 py-2 text-left font-semibold text-slate-700">Adapter</th>
              <th className="px-3 py-2 text-left font-semibold text-slate-700">Action</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((row) => {
              const gateway = row as unknown as GatewayRow;
              return (
                <tr key={row.id}>
                  <td className="px-3 py-2 font-mono text-xs text-slate-900">{row.code}</td>
                  <td className="px-3 py-2 text-slate-900">{row.name}</td>
                  <td className="px-3 py-2">
                    <YesNo value={row.is_active} />
                  </td>
                  <td className="px-3 py-2">
                    <YesNo value={row.test_mode} />
                  </td>
                  <td className="px-3 py-2">
                    <YesNo value={isGatewayConfigured(gateway)} />
                  </td>
                  <td className="px-3 py-2 font-mono text-xs text-slate-700">{resolveGatewayAdapter(gateway)}</td>
                  <td className="px-3 py-2">
                    <a
                      href={`/dashboard/payment-gateways/test-payment?gateway=${encodeURIComponent(row.code)}`}
                      className="text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline"
                    >
                      Run test payment
                    </a>
                  </td>
                </tr>
              );
            })}
            {rows.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-3 py-4 text-center text-sm text-slate-500">
                  No gateways seeded yet — run <span className="font-mono">npm run db:seed</span>.
                </td>
              </tr>
            ) : null}
          </tbody>
        </table>
      </div>
    </section>
  );
}
