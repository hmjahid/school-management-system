@props(['action' => '', 'publishable' => true])

<form method="post" action="{{ $action }}" id="bulk-bar" class="hidden items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3" onsubmit="return window.handleBulkSubmit(this)">
    @csrf
    <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
        <span id="bulk-count" class="font-semibold text-slate-900">0</span>
        <span>{{ __('selected') }}</span>
    </span>
    <select name="action" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm">
        @if ($publishable)
            <option value="publish">{{ __('Publish') }}</option>
            <option value="unpublish">{{ __('Unpublish') }}</option>
        @endif
        <option value="delete" class="text-red-600">{{ __('Delete') }}</option>
    </select>
    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Apply') }}</button>
    <div id="bulk-ids"></div>
</form>

<script>
    window.handleBulkSubmit = function (form) {
        var action = form.querySelector('select[name="action"]').value;
        if (action === 'delete' && ! window.confirm('Delete the selected items? This cannot be undone.')) {
            return false;
        }
        return true;
    };

    document.addEventListener('DOMContentLoaded', function () {
        var table = document.querySelector('[data-bulk-table]');
        var bar = document.getElementById('bulk-bar');
        if (! table || ! bar) {
            return;
        }
        var checks = Array.prototype.slice.call(table.querySelectorAll('.bulk-check'));
        var count = document.getElementById('bulk-count');
        var all = table.querySelector('.bulk-all');
        var ids = document.getElementById('bulk-ids');

        function sync() {
            var selected = checks.filter(function (c) { return c.checked; });
            count.textContent = selected.length;
            if (ids) {
                ids.querySelectorAll('input').forEach(function (i) { i.remove(); });
                selected.forEach(function (c) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'ids[]';
                    h.value = c.value;
                    ids.appendChild(h);
                });
            }
            bar.classList.toggle('hidden', selected.length === 0);
            bar.classList.toggle('flex', selected.length > 0);
            if (all) {
                all.checked = checks.length > 0 && selected.length === checks.length;
            }
        }
        checks.forEach(function (c) { c.addEventListener('change', sync); });
        if (all) {
            all.addEventListener('change', function () {
                checks.forEach(function (c) { c.checked = all.checked; });
                sync();
            });
        }
    });
</script>