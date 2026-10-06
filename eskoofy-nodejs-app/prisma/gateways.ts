/**
 * `payment_gateways` seed rows for the Node variant.
 *
 * Mirrors `eskoofy-laravel-app/database/seeders/PaymentGatewaySeeder.php` for
 * the rows this product owns, plus Task 1/2 of
 * `docs/prompts/payment-sandbox-bd-gateways-branding-tweaks-prompt.md`:
 *
 *   - `test_gateway` — active, zero credentials, no external host: checkout
 *     redirects to the local sandbox page (`/payments/sandbox/{id}`).
 *   - the 8 extended BD hosted gateways — **inactive**, `test_mode`, empty
 *     credentials and draft sandbox URLs (marked DRAFT — verify each URL
 *     against the gateway's official docs before enabling), with the
 *     GenericHosted contract stored in `extra_attributes`.
 *
 * Existing gateway rows (bkash/rocket/nagad/uddoktapay/int) are NOT seeded
 * here — `prisma/seed.ts` upserts create-only so their flags/defaults never
 * change.
 */
import { TEST_GATEWAY_CODE, TEST_GATEWAY_LABEL } from "../lib/payments/gateways";

export interface PaymentGatewaySeed {
  name: string;
  code: string;
  type: string;
  is_active: boolean;
  is_online: boolean;
  has_api: boolean;
  test_mode: boolean;
  sandbox_url?: string | null;
  live_url?: string | null;
  description: string;
  instructions: string;
  currency: string;
  supported_currencies: string;
  sort_order: number;
  extra_attributes?: Record<string, unknown>;
}

/**
 * GenericHosted hosted-checkout contract (`extra_attributes`), per the app's
 * `GenericHostedGatewayAdapter`. `verify_url` and `checkout_url_template` are
 * intentionally left for the admin to fill from the official docs — the
 * adapter falls back to the sandbox URL + query string until then.
 */
const HOSTED_CONTRACT: Record<string, unknown> = {
  checkout_method: "GET",
  verify_success_path: "status",
  verify_success_value: "COMPLETED",
  signature_header: "X-Webhook-Signature",
};

export const TEST_GATEWAY_SEED: PaymentGatewaySeed = {
  name: TEST_GATEWAY_LABEL,
  code: TEST_GATEWAY_CODE,
  type: "online_payment",
  is_active: true,
  is_online: true,
  has_api: false,
  test_mode: true,
  sandbox_url: null,
  live_url: null,
  description: "Free test gateway — never charges real money; redirects to the local sandbox page to simulate success, failure or cancel.",
  instructions: "This is a free test payment: no money moves. Pick an outcome on the sandbox page to simulate the gateway response.",
  currency: "BDT",
  supported_currencies: JSON.stringify(["BDT"]),
  sort_order: 99,
};

/** The 8 extended Bangladeshi hosted gateways (Task 2) — disabled until an admin opts in. */
export const BD_GATEWAY_SEEDS: PaymentGatewaySeed[] = [
  {
    name: "ShurjoPay",
    code: "shurjopay",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against https://shurjopay.io docs before use.
    sandbox_url: "https://sandbox.shurjopay.io/",
    // DRAFT live URL — verify before use.
    live_url: "https://payment.shurjohub.com/",
    description: "ShurjoPay — checkout redirect (IPN/verify)",
    instructions: "You will be redirected to the ShurjoPay hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 40,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
  {
    name: "PortWallet",
    code: "portwallet",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against official docs before use.
    sandbox_url: "https://sandbox.portwallet.com/cloud-payment/",
    live_url: null,
    description: "PortWallet — hosted checkout + verify",
    instructions: "You will be redirected to the PortWallet hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 41,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
  {
    name: "Cellfin",
    code: "cellfin",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against official docs before use.
    sandbox_url: "https://sandbox.cellfin.io",
    live_url: null,
    description: "Cellfin — bank/wallet aggregator (EBL/DBBL style)",
    instructions: "You will be redirected to the Cellfin hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 42,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
  {
    name: "Purse",
    code: "purse",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against official docs before use.
    sandbox_url: "https://sandbox.purse.com.bd",
    live_url: null,
    description: "Purse — mobile financial services aggregator",
    instructions: "You will be redirected to the Purse hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 43,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
  {
    name: "Cashby",
    code: "cashby",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against official docs before use.
    sandbox_url: "https://sandbox.cashby.com.bd",
    live_url: null,
    description: "Cashby — merchant payout + checkout",
    instructions: "You will be redirected to the Cashby hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 44,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
  {
    name: "UPay",
    code: "upay",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against official docs before use.
    sandbox_url: "https://sandbox.upay.ltd",
    live_url: null,
    description: "UPay — Trust Axiata Pay",
    instructions: "You will be redirected to the UPay hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 45,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
  {
    name: "MyCash",
    code: "mycash",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against official docs before use.
    sandbox_url: "https://sandbox.mycash.com.bd",
    live_url: null,
    description: "MyCash — MFS aggregator",
    instructions: "You will be redirected to the MyCash hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 46,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
  {
    name: "Payer",
    code: "payer",
    type: "online_payment",
    is_active: false,
    is_online: true,
    has_api: true,
    test_mode: true,
    // DRAFT sandbox URL — verify against official docs before use.
    sandbox_url: "https://sandbox.payer.com.bd",
    live_url: null,
    description: "Payer — merchant gateway",
    instructions: "You will be redirected to the Payer hosted checkout. Enable it only after filling the sandbox credentials and verify settings.",
    currency: "BDT",
    supported_currencies: JSON.stringify(["BDT"]),
    sort_order: 47,
    extra_attributes: { ...HOSTED_CONTRACT },
  },
];

export const PAYMENT_GATEWAY_SEEDS: PaymentGatewaySeed[] = [TEST_GATEWAY_SEED, ...BD_GATEWAY_SEEDS];
