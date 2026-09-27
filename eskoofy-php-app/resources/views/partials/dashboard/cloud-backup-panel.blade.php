{{--
    Cloud backup panel — rendered inside the Backups page (Local / Cloud tabs).
    Byte-identical with eskoofy-php-app so both products render the same UI.
--}}

@if (! $isConfigured)
    <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        {{ __('This provider has no complete credential set yet, so uploads are skipped. Fill in the required fields below.') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 ps-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    {{-- ------------------------------------------------------------ provider --}}
    <x-card :title="__('Provider & schedule')">
        <form method="post" action="{{ route('dashboard.cloud-backup.update') }}" class="space-y-4">
            @csrf

            <div>
                <label for="cloud-provider" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Provider') }}</label>
                <select id="cloud-provider" name="provider" class="w-full rounded-lg border-slate-300 text-sm" data-provider-select>
                    @foreach ($providers as $key => $provider)
                        <option value="{{ $key }}" @selected($settings->provider === $key)>{{ $provider['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="cloud-folder" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Remote folder') }}</label>
                <input id="cloud-folder" name="folder" type="text" value="{{ old('folder', $settings->folder) }}"
                       class="w-full rounded-lg border-slate-300 text-sm" autocomplete="off">
                <p class="mt-1 text-xs text-slate-500">{{ __('Backups are uploaded into this folder on the provider.') }}</p>
            </div>

            {{-- Credential inputs are rendered from config/backup.php, so a new
                 provider only needs a config entry — never a Blade change. --}}
            @foreach ($providers as $key => $provider)
                <div data-provider-panel="{{ $key }}" @unless($settings->provider === $key) hidden @endunless>
                    @if (empty($provider['fields']))
                        <p class="text-sm text-slate-500">{{ __('This provider needs no credentials.') }}</p>
                    @endif

                    @foreach ($provider['fields'] as $field => $label)
                        <div class="mt-3">
                            <label for="cloud-{{ $key }}-{{ $field }}" class="mb-1 block text-sm font-medium text-slate-700">
                                {{ __($label) }}
                                @if (in_array($field, $provider['required'] ?? [], true))
                                    <span class="text-red-600">*</span>
                                @endif
                                @if (! empty($configured[$field] ?? false))
                                    <span class="ml-1 text-xs font-normal text-emerald-700">{{ __('saved') }}</span>
                                @endif
                            </label>
                            <input id="cloud-{{ $key }}-{{ $field }}" name="credentials[{{ $field }}]" type="password"
                                   value="" placeholder="{{ ! empty($configured[$field] ?? false) ? __('•••••••• (unchanged)') : '' }}"
                                   class="w-full rounded-lg border-slate-300 text-sm" autocomplete="new-password">
                        </div>
                    @endforeach

                    @if (! empty($provider['alternatives']))
                        <p class="mt-3 text-xs text-slate-500">{{ __('Any one of these credential sets is enough:') }}</p>
                        <ul class="mt-1 list-disc space-y-0.5 ps-5 text-xs text-slate-500">
                            @foreach ($provider['alternatives'] as $alternative)
                                <li>{{ __($alternative['label']) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="cloud-interval" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Interval (minutes)') }}</label>
                    <input id="cloud-interval" name="interval_minutes" type="number"
                           min="{{ $bounds['min_interval'] }}" max="{{ $bounds['max_interval'] }}"
                           value="{{ old('interval_minutes', $settings->interval_minutes) }}"
                           class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label for="cloud-keep" class="mb-1 block text-sm font-medium text-slate-700">{{ __('Keep newest files') }}</label>
                    <input id="cloud-keep" name="keep" type="number"
                           min="{{ $bounds['min_keep'] }}" max="{{ $bounds['max_keep'] }}"
                           value="{{ old('keep', $settings->keep) }}"
                           class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input name="is_enabled" type="checkbox" value="1" @checked($settings->is_enabled)>
                {{ __('Cloud backup enabled') }}
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input name="auto_enabled" type="checkbox" value="1" @checked($settings->auto_enabled)>
                {{ __('Upload automatically on a schedule') }}
            </label>

            <div class="flex flex-wrap gap-2 pt-2">
                <x-button type="submit">{{ __('Save settings') }}</x-button>
            </div>
        </form>

        <form method="post" action="{{ route('dashboard.cloud-backup.test') }}" class="mt-3 border-t border-slate-200 pt-3">
            @csrf
            <input type="hidden" name="provider" value="{{ $settings->provider }}">
            <x-button type="submit" variant="secondary" size="sm">{{ __('Test saved settings') }}</x-button>
        </form>

        <form method="post" action="{{ route('dashboard.cloud-backup.run') }}" class="mt-3">
            @csrf
            <x-button type="submit" variant="primary" size="sm" :disabled="! $isConfigured">{{ __('Back up now') }}</x-button>
        </form>
    </x-card>

    {{-- -------------------------------------------------------------- status --}}
    <div class="space-y-6">
        <x-card :title="__('Status')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Provider') }}</dt>
                    <dd class="font-medium">{{ $providers[$settings->provider]['label'] ?? $settings->provider }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Credentials') }}</dt>
                    <dd class="font-medium {{ $isConfigured ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $isConfigured ? __('complete') : __('incomplete') }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Last run') }}</dt>
                    <dd class="font-medium">{{ $settings->last_run_at?->format('Y-m-d H:i') ?? __('Never') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Last result') }}</dt>
                    <dd class="font-medium">{{ $settings->last_status ?? __('—') }}</dd>
                </div>
            </dl>

            @if ($settings->last_error)
                <p class="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-800">{{ $settings->last_error }}</p>
            @endif

            @if (session('cloudResult'))
                <p class="mt-3 rounded-lg p-3 text-xs {{ session('cloudResult')['ok'] ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900' }}">
                    {{ session('cloudResult')['message'] }}
                </p>
            @endif
        </x-card>

        <x-card :title="__('Recent runs')" :padding="false">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-2">{{ __('When') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2">{{ __('File') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('Size') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($runs as $run)
                        <tr>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ $run->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 text-xs">
                                <span class="{{ $run->status === 'success' ? 'text-emerald-700' : ($run->status === 'failed' ? 'text-red-700' : 'text-slate-500') }}">
                                    {{ $run->status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $run->file_name }}</td>
                            <td class="px-4 py-2 text-right text-xs">{{ $run->size ? number_format($run->size / 1024, 1).' KB' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8"><x-empty-state :title="__('No runs yet')" :message="__('The first automatic or manual run will appear here.')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</div>

{{-- --------------------------------------------------------- remote files --}}
<x-card :title="__('Remote backups')" class="mt-6" :padding="false">
    @if ($remoteNotice)
        <p class="border-b border-slate-200 p-4 text-sm text-amber-800">{{ $remoteNotice }}</p>
    @endif

    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
                <th class="px-4 py-3">{{ __('File') }}</th>
                <th class="px-4 py-3">{{ __('Size') }}</th>
                <th class="px-4 py-3">{{ __('Modified') }}</th>
                <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($remote as $file)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">{{ $file['name'] ?? $file['id'] }}</td>
                    <td class="px-4 py-3">{{ number_format(($file['size'] ?? 0) / 1024, 1) }} KB</td>
                    <td class="px-4 py-3 text-slate-500">{{ isset($file['modified']) ? \Carbon\Carbon::createFromTimestamp((int) $file['modified'])->format('Y-m-d H:i') : '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @can('restore_database')
                            <form method="post" action="{{ route('dashboard.cloud-backup.restore', ['file' => $file['id']]) }}" class="inline" data-confirm="{{ __('Restore this remote backup? The current database and uploaded files will be overwritten.') }}">
                                @csrf
                                <button class="text-xs font-semibold text-amber-700 hover:underline" type="submit">{{ __('Restore') }}</button>
                            </form>
                        @endcan
                        <form method="post" action="{{ route('dashboard.cloud-backup.destroy', ['file' => $file['id']]) }}" class="inline" data-confirm="{{ __('Delete this remote backup?') }}">
                            @csrf @method('delete')
                            <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-12"><x-empty-state :title="__('No remote backups')" :message="__('Run a backup to upload the first file.')" /></td></tr>
            @endforelse
        </tbody>
    </table>
</x-card>

@push('scripts')
    <script>
        // Only the selected provider's credential inputs are submitted, so a
        // half-filled form for another provider cannot overwrite the stored
        // secrets of the one that is actually in use.
        //
        // Hiding a panel is not enough on its own: a `hidden` element is still
        // a successful form control, so its values are posted. The inputs are
        // disabled as well, which is what actually keeps them out of the
        // request body. Disabled controls keep their value in the DOM, so
        // switching back and forth does not clear a half-typed secret.
        (function () {
            var select = document.querySelector('[data-provider-select]');
            if (!select) return;

            function sync() {
                document.querySelectorAll('[data-provider-panel]').forEach(function (panel) {
                    var active = panel.getAttribute('data-provider-panel') === select.value;
                    panel.hidden = !active;
                    panel.querySelectorAll('input, select, textarea').forEach(function (field) {
                        field.disabled = !active;
                    });
                });
            }

            select.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush