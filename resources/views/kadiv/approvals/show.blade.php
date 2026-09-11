@extends('layouts.app')
@section('title', 'Review Pengajuan')
@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    <div>
        <a href="{{ route('kadiv.approvals.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke daftar approval
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <h1 class="text-xl font-bold text-slate-900 mb-1">Review Pengajuan</h1>
        <p class="text-sm text-slate-500">Pastikan semua data benar sebelum approve / reject.</p>

        @if($swapType === 'shift')
            {{-- Detail Shift Swap --}}
            <div class="mt-6 space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <p class="text-xs text-slate-500 mb-1">Pemohon</p>
                        <p class="font-semibold text-slate-900">{{ $swap->requester->name }}</p>
                        <p class="text-xs text-slate-500">{{ $swap->requester->division?->name ?? '-' }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <p class="text-xs text-slate-500 mb-1">Target Karyawan</p>
                        <p class="font-semibold text-slate-900">{{ $swap->targetEmployee->name }}</p>
                        <p class="text-xs text-slate-500">{{ $swap->targetEmployee->division?->name ?? '-' }}</p>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Tanggal</p>
                    <p class="text-slate-900 font-medium">{{ $swap->date->translatedFormat('l, d F Y') }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Pertukaran Shift</p>
                    <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl">
                        <div class="flex-1">
                            <p class="text-xs text-slate-500">Shift saat ini (Pemohon)</p>
                            <p class="font-semibold text-slate-900">{{ $swap->requesterShift?->name ?? '—' }}</p>
                            @if($swap->requesterShift)
                                <p class="text-xs text-slate-500">{{ substr($swap->requesterShift->start_time,0,5) }}–{{ substr($swap->requesterShift->end_time,0,5) }}</p>
                            @endif
                        </div>
                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        <div class="flex-1">
                            <p class="text-xs text-slate-500">Akan menjadi</p>
                            <p class="font-semibold text-slate-900">{{ $swap->targetShift?->name ?? '—' }}</p>
                            @if($swap->targetShift)
                                <p class="text-xs text-slate-500">{{ substr($swap->targetShift->start_time,0,5) }}–{{ substr($swap->targetShift->end_time,0,5) }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                @if($swap->reason)
                    <div>
                        <p class="text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Alasan dari Pemohon</p>
                        <p class="text-slate-900 italic p-3 bg-slate-50 rounded-lg">"{{ $swap->reason }}"</p>
                    </div>
                @endif

                @if($swap->target_responded_at)
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                        Target ({{ $swap->targetEmployee->name }}) sudah ACC pada {{ $swap->target_responded_at->translatedFormat('d M Y H:i') }}.
                        @if($swap->target_response_note)
                            <p class="mt-1 italic">"{{ $swap->target_response_note }}"</p>
                        @endif
                    </div>
                @endif
            </div>
        @else
            {{-- Detail Day-Off Swap --}}
            <div class="mt-6 space-y-4">
                <div class="p-4 bg-slate-50 rounded-xl">
                    <p class="text-xs text-slate-500 mb-1">Pemohon</p>
                    <p class="font-semibold text-slate-900">{{ $swap->requester->name }}</p>
                    <p class="text-xs text-slate-500">{{ $swap->requester->division?->name ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Pertukaran Hari Libur</p>
                    <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl">
                        <div class="flex-1">
                            <p class="text-xs text-slate-500">Libur lama (saat ini libur)</p>
                            <p class="font-semibold text-slate-900">{{ \Carbon\Carbon::parse($swap->old_off_date)->translatedFormat('l, d F Y') }}</p>
                        </div>
                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        <div class="flex-1">
                            <p class="text-xs text-slate-500">Akan menjadi libur</p>
                            <p class="font-semibold text-slate-900">{{ \Carbon\Carbon::parse($swap->new_off_date)->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>
                </div>

                @if($swap->reason)
                    <div>
                        <p class="text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Alasan dari Pemohon</p>
                        <p class="text-slate-900 italic p-3 bg-slate-50 rounded-lg">"{{ $swap->reason }}"</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Form decide --}}
        <form method="POST" action="{{ route('kadiv.approvals.decide', ['swapType' => $swapType, 'id' => $swap->id]) }}" class="mt-6 pt-6 border-t border-slate-200">
            @csrf
            <label for="note" class="block text-sm font-medium text-slate-700 mb-1.5">Catatan (opsional)</label>
            <textarea id="note" name="note" rows="2" maxlength="500"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                placeholder="Misalnya: 'Setuju, tolong swap selesai sebelum shift malam.'"></textarea>

            <div class="mt-4 flex flex-wrap gap-2 justify-end">
                <button type="submit" name="decision" value="reject"
                    class="inline-flex items-center gap-2 bg-white hover:bg-red-50 text-red-700 border border-red-200 px-4 py-2 rounded-lg font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    Tolak
                </button>
                <button type="submit" name="decision" value="approve"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2 rounded-lg font-semibold text-sm shadow-sm"
                    onclick="return confirm('Setujui pengajuan ini? Jadwal akan otomatis diupdate.')">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Setujui
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
