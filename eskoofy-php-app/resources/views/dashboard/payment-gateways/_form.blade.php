@include('dashboard.partials.form-errors')

<form method="post" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($method !== 'post')
        @method($method)
    @endif

    <x-card>
        <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('Gateway') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                <input type="text" name="name" required maxlength="255" value="{{ old('name', $gateway->name) }}" class="admin-input" placeholder="Google Pay">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Code') }}</label>
                <input type="text" name="code" required maxlength="50" value="{{ old('code', $gateway->code) }}" class="admin-input font-mono" placeholder="gpay">
                <p class="mt-1 text-xs text-slate-500">{{ __('Lowercase identifier used in URLs and payloads (letters, numbers, underscore).') }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Type') }}</label>
                <select name="type" class="admin-input" required>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $gateway->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Currency') }}</label>
                <input type="text" name="currency" maxlength="3" value="{{ old('currency', $gateway->currency) }}" class="admin-input uppercase" placeholder="USD">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Supported currencies') }}</label>
                <input type="text" name="supported_currencies" value="{{ old('supported_currencies', implode(', ', (array) ($gateway->supported_currencies ?? []))) }}" class="admin-input" placeholder="USD, EUR, GBP">
                <p class="mt-1 text-xs text-slate-500">{{ __('Comma-separated. Defaults to the single currency above.') }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Sort order') }}</label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $gateway->sort_order ?? 0) }}" class="admin-input">
            </div>
            <div class="flex flex-col justify-end gap-2 pb-1">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $gateway->is_active)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('Enabled (show to payers)') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_online" value="1" @checked(old('is_online', $gateway->is_online)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('Online (redirect/hosted checkout)') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="has_api" value="1" @checked(old('has_api', $gateway->has_api)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('Has API') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="test_mode" value="1" @checked(old('test_mode', $gateway->test_mode)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('Sandbox / test mode') }}
                </label>
            </div>
        </div>
    </x-card>

    <x-card>
        <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('Credentials & endpoints') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('API Key') }}</label>
                <input type="text" name="api_key" maxlength="255" value="{{ old('api_key', $gateway->api_key) }}" class="admin-input" autocomplete="off">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('API Secret') }}</label>
                <input type="password" name="api_secret" maxlength="255" value="{{ old('api_secret', $gateway->api_secret) }}" class="admin-input" autocomplete="off">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('API Username') }}</label>
                <input type="text" name="api_username" maxlength="255" value="{{ old('api_username', $gateway->api_username) }}" class="admin-input" autocomplete="off">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('API Password') }}</label>
                <input type="password" name="api_password" maxlength="255" value="{{ old('api_password', $gateway->api_password) }}" class="admin-input" autocomplete="off">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Sandbox URL') }}</label>
                <input type="url" name="sandbox_url" maxlength="255" value="{{ old('sandbox_url', $gateway->sandbox_url) }}" class="admin-input" placeholder="https://sandbox.example.com/checkout">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Live URL') }}</label>
                <input type="url" name="live_url" maxlength="255" value="{{ old('live_url', $gateway->live_url) }}" class="admin-input" placeholder="https://checkout.example.com">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Callback URL') }}</label>
                <input type="url" name="callback_url" maxlength="255" value="{{ old('callback_url', $gateway->callback_url) }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Webhook URL') }}</label>
                <input type="url" name="webhook_url" maxlength="255" value="{{ old('webhook_url', $gateway->webhook_url) }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Success URL') }}</label>
                <input type="url" name="success_url" maxlength="255" value="{{ old('success_url', $gateway->success_url) }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Cancel URL') }}</label>
                <input type="url" name="cancel_url" maxlength="255" value="{{ old('cancel_url', $gateway->cancel_url) }}" class="admin-input">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('IPN URL') }}</label>
                <input type="url" name="ipn_url" maxlength="255" value="{{ old('ipn_url', $gateway->ipn_url) }}" class="admin-input">
            </div>
        </div>
    </x-card>

    <x-card>
        <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('Fees & limits') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Fee percentage') }}</label>
                <input type="number" step="0.01" min="0" name="fee_percentage" value="{{ old('fee_percentage', $gateway->fee_percentage) }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Fee fixed') }}</label>
                <input type="number" step="0.01" min="0" name="fee_fixed" value="{{ old('fee_fixed', $gateway->fee_fixed) }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Minimum amount') }}</label>
                <input type="number" step="0.01" min="0" name="min_amount" value="{{ old('min_amount', $gateway->min_amount) }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Maximum amount') }}</label>
                <input type="number" step="0.01" min="0" name="max_amount" value="{{ old('max_amount', $gateway->max_amount) }}" class="admin-input">
            </div>
        </div>
    </x-card>

    <x-card>
        <h3 class="mb-4 text-sm font-semibold text-slate-900">{{ __('Presentation') }}</h3>
        <div class="grid gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Logo path') }}</label>
                <input type="text" name="logo" maxlength="255" value="{{ old('logo', $gateway->logo) }}" class="admin-input" placeholder="gateways/gpay.png">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                <textarea name="description" rows="2" class="admin-input">{{ old('description', $gateway->description) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Instructions') }}</label>
                <textarea name="instructions" rows="3" class="admin-input">{{ old('instructions', $gateway->instructions) }}</textarea>
            </div>
        </div>
    </x-card>

    <x-card>
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Advanced settings') }}</h3>
        <p class="mt-1 text-xs text-slate-500">{{ __('Key/value options for the config-driven adapter, e.g. checkout_url_template, verify_url, verify_success_value, refund_url, signature_header.') }}</p>
        <div class="mt-4 space-y-2" data-extra-attributes>
            @php($extraAttributes = old('extra_keys') !== null
                ? array_combine((array) old('extra_keys'), array_pad((array) old('extra_values'), count((array) old('extra_keys')), ''))
                : (array) ($gateway->extra_attributes ?? []))
            @foreach($extraAttributes as $key => $value)
                <div class="flex gap-2">
                    <input type="text" name="extra_keys[]" value="{{ $key }}" class="admin-input font-mono" placeholder="key">
                    <input type="text" name="extra_values[]" value="{{ $value }}" class="admin-input" placeholder="value">
                    <button type="button" data-remove-row class="rounded-lg px-3 text-slate-400 hover:text-red-600" aria-label="{{ __('Remove') }}">&times;</button>
                </div>
            @endforeach
        </div>
        <button type="button" data-add-row class="mt-3 text-sm font-semibold text-brand-700 hover:underline">{{ __('+ Add option') }}</button>
    </x-card>

    <div class="flex justify-end gap-2">
        <x-button :href="route('dashboard.payment-gateways.index')" variant="ghost">{{ __('Cancel') }}</x-button>
        <x-button type="submit">{{ $submitLabel }}</x-button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const container = document.querySelector('[data-extra-attributes]');
    const addButton = document.querySelector('[data-add-row]');
    if (!container || !addButton) return;

    const row = () => {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex gap-2';
        wrapper.innerHTML = '<input type="text" name="extra_keys[]" class="admin-input font-mono" placeholder="key">'
            + '<input type="text" name="extra_values[]" class="admin-input" placeholder="value">'
            + '<button type="button" data-remove-row class="rounded-lg px-3 text-slate-400 hover:text-red-600" aria-label="{{ __('Remove') }}">&times;</button>';
        return wrapper;
    };

    addButton.addEventListener('click', () => container.appendChild(row()));
    container.addEventListener('click', (event) => {
        if (event.target.matches('[data-remove-row]')) {
            event.target.closest('.flex').remove();
        }
    });
})();
</script>
@endpush
