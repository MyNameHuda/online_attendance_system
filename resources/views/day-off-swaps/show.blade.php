@extends('layouts.app')
@section('title', 'Detail Tukar Libur')
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div>
        <a href="{{ route('day-off-swaps.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <div class="flex justify-between items-start mb-5">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Detail Tukar Hari Libur</h1>
                <p class="text-sm text-slate-500 mt-0.5">Permintaan tukar hari libur</p>
            </div>
            @include('partials._status-badge', ['status' => $dayOffSwap->status, 'size' => 'lg', 'longLabel' => true])
        </div>

        <div class="grid sm:grid-cols-2 gap-3 mb-5">
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                <p class="text-xs text-amber-700 font-medium mb-1">Libur Lama</p>
                <p class="font-bold text-slate-900">{{ $dayOffSwap->old_off_date->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                <p class="text-xs text-emerald-700 font-medium mb-1">Libur Baru</p>
                <p class="font-bold text-slate-900">{{ $dayOffSwap->new_off_date->translatedFormat('l, d F Y') }}</p>
            </div>
        </div>

        <div class="space-y-3 text-sm border-t border-slate-200 pt-5">
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Alasan</p>
                <p class="text-slate-700">{{ $dayOffSwap->reason }}</p>
            </div>
            @if($dayOffSwap->approver)
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Diproses oleh</p>
                    <p class="text-slate-700">{{ $dayOffSwap->approver->name }} <span class="text-xs text-slate-400">· {{ $dayOffSwap->approver_decided_at->format('d M Y H:i') }}</span></p>
                    @if($dayOffSwap->approver_response_note)
                        <p class="text-slate-600 text-xs mt-1 italic">"{{ $dayOffSwap->approver_response_note }}"</p>
                    @endif
                </div>
            @endif
        </div>

        @if($dayOffSwap->status === 'pending')
            <div class="mt-5 pt-5 border-t border-slate-200">
                <form method="POST" action="{{ route('day-off-swaps.cancel', $dayOffSwap) }}">
                    @csrf
                    <button class="text-red-600 text-sm hover:underline inline-flex items-center gap-1" onclick="return confirm('Yakin batalkan?')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v2H8V4a1 1 0 011-1z" /></svg>
                        Batalkan pengajuan
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
