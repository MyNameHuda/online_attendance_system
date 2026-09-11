@extends('layouts.app')
@section('title', 'Timeline Pengajuan Tukar')
@section('content')
@php
    use App\Models\ShiftSwapRequest;
    use App\Models\DayOffSwapRequest;
@endphp
<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Timeline Pengajuan</h1>
            <p class="text-sm text-slate-500 mt-0.5">Semua pengajuan tukar shift & tukar libur dari seluruh karyawan — pending, approved, rejected, cancelled.</p>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4">
            <div class="text-xs uppercase font-bold text-slate-500 tracking-wide">Tukar Shift</div>
            <div class="text-2xl font-bold text-slate-800 mt-1">{{ $summary['shift_total'] }}</div>
            <div class="text-xs text-slate-500 mt-1">Total pengajuan</div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <div class="text-xs uppercase font-bold text-amber-600 tracking-wide">Pending</div>
            <div class="text-2xl font-bold text-amber-700 mt-1">
                {{ $summary['shift_pending'] + $summary['dayoff_pending'] }}
            </div>
            <div class="text-xs text-amber-600 mt-1">
                Shift: {{ $summary['shift_pending'] }} · Libur: {{ $summary['dayoff_pending'] }}
            </div>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4">
            <div class="text-xs uppercase font-bold text-emerald-600 tracking-wide">Disetujui</div>
            <div class="text-2xl font-bold text-emerald-700 mt-1">
                {{ $summary['shift_approved'] + $summary['dayoff_approved'] }}
            </div>
            <div class="text-xs text-emerald-600 mt-1">
                Shift: {{ $summary['shift_approved'] }} · Libur: {{ $summary['dayoff_approved'] }}
            </div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <div class="text-xs uppercase font-bold text-red-600 tracking-wide">Ditolak</div>
            <div class="text-2xl font-bold text-red-700 mt-1">
                {{ $summary['shift_rejected'] + $summary['dayoff_rejected'] }}
            </div>
            <div class="text-xs text-red-600 mt-1">
                Shift: {{ $summary['shift_rejected'] }} · Libur: {{ $summary['dayoff_rejected'] }}
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 text-sm">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tipe</label>
                <select name="type" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="all" @selected($type === 'all' || !$type)>— Semua —</option>
                    <option value="shift" @selected($type === 'shift')>Tukar Shift</option>
                    <option value="dayoff" @selected($type === 'dayoff')>Tukar Libur</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Dari</label>
                <input type="date" name="start_date" value="{{ $startDate?->toDateString() }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Sampai</label>
                <input type="date" name="end_date" value="{{ $endDate?->toDateString() }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Divisi</label>
                <select name="division_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">— Semua —</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}" @selected(request('division_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Karyawan</label>
                <select name="user_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">— Semua —</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(request('user_id') == $e->id)>{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">— Semua —</option>
                    <option value="pending_target,pending_kadiv,pending" @selected($statusFilter === 'pending_target,pending_kadiv,pending')>Pending (semua)</option>
                    <option value="approved" @selected($statusFilter === 'approved')>Disetujui</option>
                    <option value="rejected" @selected($statusFilter === 'rejected')>Ditolak</option>
                    <option value="cancelled" @selected($statusFilter === 'cancelled')>Dibatalkan</option>
                </select>
            </div>
            <div class="md:col-span-6 flex gap-2">
                <button class="inline-flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter
                </button>
                <a href="{{ route('admin.swap-requests.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tukar Shift --}}
    @if($type !== 'dayoff')
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-bold text-slate-800">Tukar Shift</h2>
                <span class="text-xs text-slate-500">{{ $shiftSwaps->total() }} pengajuan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Requester</th>
                            <th class="px-4 py-2 text-left font-semibold">Target</th>
                            <th class="px-4 py-2 text-left font-semibold">Tanggal</th>
                            <th class="px-4 py-2 text-left font-semibold">Shift</th>
                            <th class="px-4 py-2 text-left font-semibold">Status</th>
                            <th class="px-4 py-2 text-left font-semibold">Approver</th>
                            <th class="px-4 py-2 text-left font-semibold">Diajukan</th>
                            <th class="px-4 py-2 text-left font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($shiftSwaps as $s)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-slate-800">{{ $s->requester->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $s->requester->division?->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-2 text-slate-700">{{ $s->targetEmployee?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ $s->date->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-xs">
                                    <div>{{ $s->requesterShift?->name ?? '—' }} → {{ $s->targetShift?->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-2">
                                    @include('partials._status-badge', ['status' => $s->status, 'size' => 'sm'])
                                </td>
                                <td class="px-4 py-2 text-xs text-slate-600">
                                    {{ $s->approver?->name ?? '—' }}
                                    @if($s->approver_decided_at)
                                        <div class="text-slate-400">{{ $s->approver_decided_at->format('Y-m-d H:i') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-slate-500">{{ $s->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('admin.swap-requests.show', ['swapType' => 'shift', 'id' => $s->id]) }}"
                                       class="text-brand-600 hover:text-brand-700 font-semibold text-xs">Detail →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-slate-400">Belum ada pengajuan tukar shift.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $shiftSwaps->links() }}
            </div>
        </div>
    @endif

    {{-- Tukar Libur --}}
    @if($type !== 'shift')
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-bold text-slate-800">Tukar Libur</h2>
                <span class="text-xs text-slate-500">{{ $dayOffSwaps->total() }} pengajuan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Requester</th>
                            <th class="px-4 py-2 text-left font-semibold">Libur Lama</th>
                            <th class="px-4 py-2 text-left font-semibold">Libur Baru</th>
                            <th class="px-4 py-2 text-left font-semibold">Status</th>
                            <th class="px-4 py-2 text-left font-semibold">Approver</th>
                            <th class="px-4 py-2 text-left font-semibold">Diajukan</th>
                            <th class="px-4 py-2 text-left font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($dayOffSwaps as $s)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-slate-800">{{ $s->requester->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $s->requester->division?->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-2 text-slate-700">{{ $s->old_off_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 text-slate-700">{{ $s->new_off_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">
                                    @include('partials._status-badge', ['status' => $s->status, 'size' => 'sm'])
                                </td>
                                <td class="px-4 py-2 text-xs text-slate-600">
                                    {{ $s->approver?->name ?? '—' }}
                                    @if($s->approver_decided_at)
                                        <div class="text-slate-400">{{ $s->approver_decided_at->format('Y-m-d H:i') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-slate-500">{{ $s->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('admin.swap-requests.show', ['swapType' => 'dayoff', 'id' => $s->id]) }}"
                                       class="text-brand-600 hover:text-brand-700 font-semibold text-xs">Detail →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">Belum ada pengajuan tukar libur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $dayOffSwaps->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
