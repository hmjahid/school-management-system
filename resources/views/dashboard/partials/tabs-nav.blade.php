@props(['tabs' => [], 'active' => null])

@php
    $activeTab = $active ?? array_key_first($tabs);
@endphp

<nav class="mb-6 flex flex-wrap gap-1 border-b border-gray-200">
    @foreach ($tabs as $id => $label)
        <button
            type="button"
            data-tab-btn="{{ $id }}"
            class="mb-[-1px] rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-medium transition-colors {{ $activeTab === $id ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
        >
            {{ $label }}
        </button>
    @endforeach
</nav>

<script>
    document.querySelectorAll('[data-tab-btn]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-tab-btn');
            document.querySelectorAll('[data-tab-btn]').forEach(function (b) {
                var on = b.getAttribute('data-tab-btn') === id;
                b.classList.toggle('border-blue-600', on);
                b.classList.toggle('text-blue-600', on);
                b.classList.toggle('border-transparent', !on);
                b.classList.toggle('text-gray-500', !on);
            });
            document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== id);
            });
        });
    });
</script>