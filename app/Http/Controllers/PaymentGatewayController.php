<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewGateways', Payment::class);

        $gateways = PaymentGateway::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->active())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($gateways);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manageGateways', Payment::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:payment_gateways,code'],
            'type' => ['required', 'string', 'in:bank,mobile_financial_service,online_payment,other'],
            'is_active' => ['sometimes', 'boolean'],
            'is_online' => ['sometimes', 'boolean'],
            'has_api' => ['sometimes', 'boolean'],
            'test_mode' => ['sometimes', 'boolean'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'fee_percentage' => ['sometimes', 'numeric', 'min:0'],
            'fee_fixed' => ['sometimes', 'numeric', 'min:0'],
            'min_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_amount' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $gateway = PaymentGateway::create($validated);

        return $this->created($gateway->getConfig(), 'Payment gateway created');
    }

    public function show(PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('viewGateways', Payment::class);

        return $this->success($paymentGateway->getConfig());
    }

    public function update(Request $request, PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('manageGateways', Payment::class);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:payment_gateways,code,'.$paymentGateway->id],
            'type' => ['sometimes', 'string', 'in:bank,mobile_financial_service,online_payment,other'],
            'is_active' => ['sometimes', 'boolean'],
            'is_online' => ['sometimes', 'boolean'],
            'has_api' => ['sometimes', 'boolean'],
            'test_mode' => ['sometimes', 'boolean'],
            'api_key' => ['nullable', 'string'],
            'api_secret' => ['nullable', 'string'],
            'callback_url' => ['nullable', 'url'],
            'webhook_url' => ['nullable', 'url'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'fee_percentage' => ['sometimes', 'numeric', 'min:0'],
            'fee_fixed' => ['sometimes', 'numeric', 'min:0'],
            'min_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_amount' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $paymentGateway->update($validated);

        return $this->success($paymentGateway->fresh()->getConfig(), 'Payment gateway updated');
    }

    public function destroy(PaymentGateway $paymentGateway): JsonResponse
    {
        $this->authorize('manageGateways', Payment::class);

        $paymentGateway->delete();

        return $this->success(null, 'Payment gateway deleted');
    }
}
