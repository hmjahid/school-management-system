<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\Payment\GatewayAdapterFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardPaymentGatewayController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSettings($request);

        $rows = PaymentGateway::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $diagnostics = $rows->map(fn (PaymentGateway $row) => [
            'gateway' => $row,
            'adapter' => class_basename(GatewayAdapterFactory::make($row->code)),
        ]);

        return view('dashboard.payment-gateways.index', [
            'rows' => $rows,
            'diagnostics' => $diagnostics,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeSettings($request);

        return view('dashboard.payment-gateways.create', [
            'gateway' => new PaymentGateway(['currency' => 'USD', 'type' => PaymentGateway::TYPE_ONLINE_PAYMENT]),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSettings($request);

        $gateway = PaymentGateway::create($this->payload($request));

        return redirect()
            ->route('dashboard.payment-gateways.index')
            ->with('status', __('Payment gateway created: :name', ['name' => $gateway->name]));
    }

    public function edit(Request $request, PaymentGateway $paymentGateway): View
    {
        $this->authorizeSettings($request);

        return view('dashboard.payment-gateways.edit', [
            'gateway' => $paymentGateway,
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, PaymentGateway $paymentGateway): RedirectResponse
    {
        $this->authorizeSettings($request);

        $paymentGateway->update($this->payload($request, $paymentGateway));

        return redirect()
            ->route('dashboard.payment-gateways.index')
            ->with('status', __('Payment gateway updated: :name', ['name' => $paymentGateway->name]));
    }

    public function destroy(Request $request, PaymentGateway $paymentGateway): RedirectResponse
    {
        $this->authorizeSettings($request);

        $paymentGateway->delete();

        return redirect()
            ->route('dashboard.payment-gateways.index')
            ->with('status', __('Payment gateway deleted.'));
    }

    /**
     * @return array<string, string>
     */
    private function types(): array
    {
        return [
            PaymentGateway::TYPE_ONLINE_PAYMENT => __('Online payment'),
            PaymentGateway::TYPE_MOBILE_FINANCIAL_SERVICE => __('Mobile financial service'),
            PaymentGateway::TYPE_BANK => __('Bank'),
            PaymentGateway::TYPE_OTHER => __('Other'),
        ];
    }

    /**
     * Validate and normalise the submitted gateway payload.
     *
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?PaymentGateway $gateway = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('payment_gateways', 'code')->ignore($gateway?->id),
            ],
            'type' => ['required', 'string', 'in:bank,mobile_financial_service,online_payment,other'],
            'is_active' => ['nullable', 'boolean'],
            'is_online' => ['nullable', 'boolean'],
            'has_api' => ['nullable', 'boolean'],
            'test_mode' => ['nullable', 'boolean'],
            'sandbox_url' => ['nullable', 'url', 'max:255'],
            'live_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
            'api_username' => ['nullable', 'string', 'max:255'],
            'api_password' => ['nullable', 'string', 'max:255'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'webhook_url' => ['nullable', 'url', 'max:255'],
            'success_url' => ['nullable', 'url', 'max:255'],
            'cancel_url' => ['nullable', 'url', 'max:255'],
            'ipn_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'currency' => ['nullable', 'string', 'max:3'],
            'fee_percentage' => ['nullable', 'numeric', 'min:0'],
            'fee_fixed' => ['nullable', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'supported_currencies' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code'] = strtolower($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_online'] = $request->boolean('is_online');
        $data['has_api'] = $request->boolean('has_api');
        $data['test_mode'] = $request->boolean('test_mode');

        $currencies = array_values(array_filter(array_map(
            fn ($value) => strtoupper(trim($value)),
            preg_split('/[\s,]+/', (string) $request->input('supported_currencies', '')) ?: []
        )));
        if ($currencies === []) {
            $currencies = $data['currency'] ? [(string) $data['currency']] : [];
        }
        $data['supported_currencies'] = $currencies;

        $data['extra_attributes'] = $this->extraAttributes($request);

        foreach (['api_key', 'api_secret', 'api_username', 'api_password'] as $secret) {
            if ($gateway && ($data[$secret] ?? '') === '' && $gateway->{$secret}) {
                unset($data[$secret]);
            }
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function extraAttributes(Request $request): array
    {
        $keys = (array) $request->input('extra_keys', []);
        $values = (array) $request->input('extra_values', []);
        $attributes = [];

        foreach ($keys as $index => $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            $attributes[$key] = (string) ($values[$index] ?? '');
        }

        return $attributes;
    }

    private function authorizeSettings(Request $request): void
    {
        abort_unless($request->user()?->can('manage_school_settings'), 403);
    }
}
