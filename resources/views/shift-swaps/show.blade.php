@extends('layouts.app')
@section('title', 'Detail Tukar Shift')
@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    <div>
        <a href="{{ route('shift-swaps.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <div class="flex justify-between items-start mb-5">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Detail Tukar Shift</h1>
                <p class="text-sm text-slate-500 mt-0.5">{{ $shiftSwap->date->translatedFormat('l, d F Y') }}</p>
            </div>
            @include('partials._status-badge', ['status' => $shiftSwap->status, 'size' => 'lg', 'longLabel' => true])
        </div>

        <div class="grid sm:grid-cols-2 gap-4 mb-5">
            <div class="p-4 bg-slate-50 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Pemohon</p>
                        <p class="font-semibold text-slate-900 text-sm">{{ $shiftSwap->requester->name }}</p>
                    </div>
                </div>
                <div class="mt-2 px-2.5 py-1 rounded bg-white text-xs inline-flex items-center gap-1.5">
                    <span class="font-semibold">{{ $shiftSwap->requesterShift->name }}</span>
                    <span class="text-slate-400">{{ substr($shiftSwap->requesterShift->start_time,0,5) }}–{{ substr($shiftSwap->requesterShift->end_time,0,5) }}</span>
                </div>
            </div>
            <div class="p-4 bg-slate-50 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Target</p>
                        <p class="font-semibold text-slate-900 text-sm">{{ $shiftSwap->targetEmployee->name }}</p>
                    </div>
                </div>
                <div class="mt-2 px-2.5 py-1 rounded bg-white text-xs inline-flex items-center gap-1.5">
                    <span class="font-semibold">{{ $shiftSwap->targetShift->name }}</span>
                    <span class="text-slate-400">{{ substr($shiftSwap->targetShift->start_time,0,5) }}–{{ substr($shiftSwap->targetShift->end_time,0,5) }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-3 text-sm border-t border-slate-200 pt-5">
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Alasan</p>
                <p class="text-slate-700">{{ $shiftSwap->reason }}</p>
            </div>
            @if($shiftSwap->target_responded_at)
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Respons Target</p>
                    <p class="text-slate-700">{{ $shiftSwap->target_response_note ?? '(tanpa catatan)' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $shiftSwap->target_responded_at->format('d M Y H:i') }}</p>
                </div>
            @endif
            @if($shiftSwap->approver)
                <div>
                    <p class="text-xs text-slate-500 mb-0.5">Diproses oleh</p>
                    <p class="text-slate-700">{{ $shiftSwap->approver->name }} <span class="text-xs text-slate-400">· {{ $shiftSwap->approver_decided_at->format('d M Y H:i') }}</span></p>
                    @if($shiftSwap->approver_response_note)
                        <p class="text-slate-600 text-xs mt-1 italic">"{{ $shiftSwap->approver_response_note }}"</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Actions --}}
        @if($shiftSwap->isPendingTarget() && $shiftSwap->requester_id === $user->id)
            <div class="mt-5 pt-5 border-t border-slate-200">
                <form method="POST" action="{{ route('shift-swaps.cancel', $shiftSwap) }}">
                    @csrf
                    <button class="text-red-600 text-sm hover:underline inline-flex items-center gap-1" onclick="return confirm('Yakin batalkan?')">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v2H8V4a1 1 0 011-1z" /></svg>
                        Batalkan pengajuan
                    </button>
                </form>
            </div>
        @endif

        @if($shiftSwap->isPendingTarget() && $shiftSwap->target_employee_id === $user->id)
            <form method="POST" action="{{ route('shift-swaps.targetRespond', $shiftSwap) }}" class="mt-5 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                @csrf
                <p class="text-sm font-semibold text-blue-900 mb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Anda diminta merespons pengajuan ini
                </p>
                <textarea name="note" rows="2" placeholder="Catatan (opsional)" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                <div class="flex gap-2">
                    <button name="decision" value="accept" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Terima
                    </button>
                    <button name="decision" value="reject" class="inline-flex items-center gap-1.5 bg-white border border-red-300 text-red-700 hover:bg-red-50 px-4 py-2 rounded-lg text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        Tolak
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
