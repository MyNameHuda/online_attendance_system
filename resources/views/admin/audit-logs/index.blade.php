@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Audit Log</h1>
            <p class="text-sm text-slate-500 mt-0.5">Log aktivitas sistem: login, absen, swap, approval, dll</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4 sm:p-6">
        <form method="GET" class="mb-4 flex flex-wrap gap-2">
            <select name="action" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                <option value="">Semua action</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" @selected(request('action') == $a)>{{ $a }}</option>
                @endforeach
            </select>
            <button class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        </form>

        @if($logs->isEmpty())
            <p class="text-center text-sm text-slate-500 py-8">Belum ada log.</p>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200 bg-slate-50/50">
                        <tr>
                            <th class="py-3 px-6 font-semibold">Waktu</th>
                            <th class="py-3 px-3 font-semibold">User</th>
                            <th class="py-3 px-3 font-semibold">Action</th>
                            <th class="py-3 px-3 font-semibold">Resource</th>
                            <th class="py-3 px-3 font-semibold">IP</th>
                            <th class="py-3 px-6 font-semibold">Metadata</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr class="border-b last:border-0 hover:bg-slate-50/50">
                            <td class="py-3 px-6 font-mono text-xs text-slate-600">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="py-3 px-3">{{ $log->user?->name ?? '<system>' }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700 font-mono">{{ $log->action }}</span>
                            </td>
                            <td class="py-3 px-3 text-xs text-slate-500">
                                @if($log->resource_type){{ class_basename($log->resource_type) }} #{{ $log->resource_id }}@endif
                            </td>
                            <td class="py-3 px-3 font-mono text-xs text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                            <td class="py-3 px-6">
                                @if($log->metadata)
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-slate-500 hover:text-slate-700">lihat</summary>
                                        <pre class="mt-1 p-2 bg-slate-50 rounded text-[10px] overflow-x-auto">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 px-6">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
