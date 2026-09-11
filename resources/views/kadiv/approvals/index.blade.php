@extends('layouts.app')
@section('title', 'Approval Pengajuan')
@section('content')
<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Approval Pengajuan</h1>
        <p class="text-sm text-slate-500 mt-0.5">
            Pengajuan tukar shift & tukar hari libur yang menunggu approval Anda (dari divisi Anda).
        </p>
    </div>

    {{-- Tukar Shift --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                </span>
                Tukar Shift
            </h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">
                {{ count($pendingShiftSwaps) }} pending
            </span>
        </div>
        <div class="p-5">
            @if($pendingShiftSwaps->isEmpty())
                <p class="text-sm text-slate-500 text-center py-6">Tidak ada pengajuan tukar shift yang pending.</p>
            @else
                <ul class="space-y-3">
                    @foreach($pendingShiftSwaps as $s)
                        <li class="p-4 border border-slate-200 rounded-xl hover:border-amber-300 hover:bg-amber-50/30 transition">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="font-semibold text-slate-900">{{ $s->requester->name }}</span>
                                        <span class="text-slate-400">→</span>
                                        <span class="font-semibold text-slate-900">{{ $s->targetEmployee->name }}</span>
                                    </div>
                                    <div class="text-sm text-slate-600">
                                        <span class="font-medium">{{ $s->date->translatedFormat('l, d F Y') }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500 flex items-center gap-3">
                                        <span>{{ $s->requesterShift?->name ?? '—' }}</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                        <span>{{ $s->targetShift?->name ?? '—' }}</span>
                                    </div>
                                    @if($s->reason)
                                        <p class="mt-2 text-sm text-slate-700 italic">"{{ $s->reason }}"</p>
                                    @endif
                                </div>
                                <a href="{{ route('kadiv.approvals.show', ['swapType' => 'shift', 'id' => $s->id]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold shrink-0">
                                    Review
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Tukar Libur --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </span>
                Tukar Hari Libur
            </h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">
                {{ count($pendingDayOffSwaps) }} pending
            </span>
        </div>
        <div class="p-5">
            @if($pendingDayOffSwaps->isEmpty())
                <p class="text-sm text-slate-500 text-center py-6">Tidak ada pengajuan tukar hari libur yang pending.</p>
            @else
                <ul class="space-y-3">
                    @foreach($pendingDayOffSwaps as $s)
                        <li class="p-4 border border-slate-200 rounded-xl hover:border-emerald-300 hover:bg-emerald-50/30 transition">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-slate-900 mb-1.5">{{ $s->requester->name }}</div>
                                    <div class="text-sm text-slate-600 flex items-center gap-2">
                                        <span class="font-medium">{{ \Carbon\Carbon::parse($s->old_off_date)->translatedFormat('d M Y') }}</span>
                                        <span class="text-slate-400">(libur lama)</span>
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                        <span class="font-medium">{{ \Carbon\Carbon::parse($s->new_off_date)->translatedFormat('d M Y') }}</span>
                                        <span class="text-slate-400">(jadi libur)</span>
                                    </div>
                                    @if($s->reason)
                                        <p class="mt-2 text-sm text-slate-700 italic">"{{ $s->reason }}"</p>
                                    @endif
                                </div>
                                <a href="{{ route('kadiv.approvals.show', ['swapType' => 'dayoff', 'id' => $s->id]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold shrink-0">
                                    Review
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
