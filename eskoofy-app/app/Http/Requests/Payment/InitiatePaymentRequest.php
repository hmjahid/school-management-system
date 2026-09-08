<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\ApiFormRequest;

class InitiatePaymentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string', 'exists:payment_gateways,code'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'paymentable_type' => ['required', 'string', 'in:admission,tuition,exam,library,transport,hostel,other'],
            'paymentable_id' => ['required', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'return_url' => ['nullable', 'url'],
            'cancel_url' => ['nullable', 'url'],
        ];
    }
}
