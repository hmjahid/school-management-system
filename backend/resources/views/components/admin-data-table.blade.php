@props([
    'headers' => [],
    'paginator' => null,
    'emptyTitle' => __('No records found'),
    'emptyMessage' => __('There are no records to display yet.'),
    'emptyIcon' => 'inbox',
    'emptyCta' => null,
])

<div class="admin-card">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="bg-slate-50/80">
                    @foreach ($headers as $h)
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 {{ $h['class'] ?? '' }}">
                            {{ $h['label'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @if ($paginator && method_exists($paginator, 'total') && $paginator->total() === 0)
                    <tr>
                        <td colspan="{{ count($headers) }}" class="px-4 py-16">
                            <x-empty-state :title="$emptyTitle" :message="$emptyMessage" :icon="$emptyIcon" :cta="$emptyCta" />
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @if ($paginator && method_exists($paginator, 'hasPages') && $paginator->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $paginator->withQueryString()->links() }}
        </div>
    @endif
</div>
