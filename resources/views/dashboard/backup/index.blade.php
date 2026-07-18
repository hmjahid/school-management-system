@extends('layouts.dashboard')

@section('title', __('Backups') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Backups')" :description="__('Create and restore file + SQLite backups.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Backups')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <form method="post" action="{{ route('dashboard.backup.create') }}">
                @csrf
                <x-button type="submit">{{ __('Create backup now') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if(session('backup_output'))
        <x-card :title="__('Last backup output')" class="mb-6">
            <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ session('backup_output') }}</pre>
        </x-card>
    @endif

    <x-card :padding="false">
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
                @forelse($files as $f)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $f['name'] }}</td>
                        <td class="px-4 py-3">{{ number_format($f['size'] / 1024, 1) }} KB</td>
                        <td class="px-4 py-3 text-slate-500">{{ \Carbon\Carbon::createFromTimestamp($f['modified'])->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-button :href="route('dashboard.backup.download', ['file' => $f['name']])" variant="ghost" size="sm">{{ __('Download') }}</x-button>
                            @can('restore_database')
                                <form method="post" action="{{ route('dashboard.backup.restore', ['file' => $f['name']]) }}" class="inline" onsubmit="return confirm('{{ __('Restore this backup? Existing files will be overwritten.') }}')">
                                    @csrf
                                    <button class="text-xs font-semibold text-amber-700 hover:underline" type="submit">{{ __('Restore') }}</button>
                                </form>
                            @endcan
                            <form method="post" action="{{ route('dashboard.backup.destroy', ['file' => $f['name']]) }}" class="inline" onsubmit="return confirm('{{ __('Delete this backup file?') }}')">
                                @csrf @method('delete')
                                <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No backups yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
@endsection