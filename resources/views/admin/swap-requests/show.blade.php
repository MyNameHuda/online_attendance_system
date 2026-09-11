@extends('layouts.app')
@section('title', 'Detail Pengajuan')
@section('content')
@php
    use App\Models\ShiftSwapRequest;
    use App\Models\DayOffSwapRequest;
    $isShift = $swap instanceof ShiftSwapRequest;
    $typeLabel = $isShift ? 'Tukar Shift' : 'Tukar Libur';
@endphp

<div class="space-y-4">
    {{-- Breadcrumb --}}
    <div class="text-sm text-slate-500">
        <a href="{{ route('admin.swap-requests.index') }}" class="hover:text-brand-600">← Timeline Pengajuan</a>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-5">
        <div class="flex items-start justify-between flex-wrap gap-3">
            <div>
                <div class="text-xs uppercase font-bold text-slate-400 tracking-wide">{{ $typeLabel }} #{{ $swap->id }}</div>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $swap->requester->name }}</h1>
                <div class="text-sm text-slate-500 mt-0.5">
                    {{ $swap->requester->division?->name ?? '—' }} · Diajukan {{ $swap->created_at->format('Y-m-d H:i') }}
                </div>
            </div>
            <div>
                @include('partials._status-badge', ['status' => $swap->status, 'size' => 'md'])
            </div>
        </div>

        {{-- Detail rows --}}
        <dl class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            @if($isShift)
                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase">Tanggal</dt>
                    <dd class="text-slate-800 font-medium">{{ $swap->date->format('Y-m-d (l, d M Y)') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase">Target</dt>
                    <dd class="text-slate-800 font-medium">{{ $swap->targetEmployee?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase">Shift Awal</dt>
                    <dd class="text-slate-800">
                        Requester: <strong>{{ $swap->requesterShift?->name ?? '—' }}</strong>
                        · Target: <strong>{{ $swap->targetShift?->name ?? '—' }}</strong>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase">Alasan Pengajuan</dt>
                    <dd class="text-slate-700 italic">"{{ $swap->reason }}"</dd>
                </div>
            @else
                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase">Libur Lama</dt>
                    <dd class="text-slate-800 font-medium">{{ $swap->old_off_date->format('Y-m-d (l, d M Y)') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 uppercase">Libur Baru</dt>
                    <dd class="text-slate-800 font-medium">{{ $swap->new_off_date->format('Y-m-d (l, d M Y)') }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500 uppercase">Alasan Pengajuan</dt>
                    <dd class="text-slate-700 italic">"{{ $swap->reason }}"</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-5">
        <h2 class="font-bold text-slate-800 mb-4">Timeline</h2>
        <ol class="relative border-l-2 border-slate-200 pl-6 space-y-5">
            {{-- 1. Diajukan --}}
            <li class="relative">
                <span class="absolute -left-[34px] top-0 w-5 h-5 bg-slate-400 rounded-full border-4 border-white"></span>
                <div class="text-xs text-slate-500 font-semibold uppercase">Diajukan</div>
                <div class="text-sm text-slate-800 mt-0.5">
                    <strong>{{ $swap->requester->name }}</strong> mengajukan tukar {{ $isShift ? 'shift' : 'libur' }}.
                </div>
                <div class="text-xs text-slate-400 mt-0.5">{{ $swap->created_at->format('Y-m-d H:i:s') }}</div>
            </li>

            {{-- 2. Target respond (shift only) --}}
            @if($isShift)
                <li class="relative">
                    @if($swap->target_responded_at)
                        <span class="absolute -left-[34px] top-0 w-5 h-5 bg-blue-500 rounded-full border-4 border-white"></span>
                        <div class="text-xs text-blue-600 font-semibold uppercase">Target Merespons</div>
                        <div class="text-sm text-slate-800 mt-0.5">
                            <strong>{{ $swap->targetEmployee?->name ?? '—' }}</strong>
                            {{ $swap->status === \App\Models\ShiftSwapRequest::STATUS_CANCELLED ? 'menolak' : 'menerima' }}
                            pengajuan.
                            @if($swap->target_response_note)
                                <div class="mt-1 italic text-slate-600">"{{ $swap->target_response_note }}"</div>
                            @endif
                        </div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $swap->target_responded_at->format('Y-m-d H:i:s') }}</div>
                    @else
                        <span class="absolute -left-[34px] top-0 w-5 h-5 bg-slate-200 rounded-full border-4 border-white"></span>
                        <div class="text-xs text-slate-400 font-semibold uppercase">Menunggu Target</div>
                        <div class="text-sm text-slate-500 mt-0.5">
                            Pengajuan belum direspons oleh <strong>{{ $swap->targetEmployee?->name ?? 'target' }}</strong>.
                        </div>
                    @endif
                </li>
            @endif

            {{-- 3. KD approval --}}
            <li class="relative">
                @if($swap->approver_decided_at)
                    <span class="absolute -left-[34px] top-0 w-5 h-5
                        @if($swap->status === \App\Models\ShiftSwapRequest::STATUS_APPROVED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_APPROVED)
                            bg-emerald-500
                        @elseif($swap->status === \App\Models\ShiftSwapRequest::STATUS_REJECTED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_REJECTED)
                            bg-red-500
                        @else
                            bg-slate-400
                        @endif
                        rounded-full border-4 border-white"></span>
                    <div class="text-xs font-semibold uppercase
                        @if($swap->status === \App\Models\ShiftSwapRequest::STATUS_APPROVED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_APPROVED)
                            text-emerald-600
                        @elseif($swap->status === \App\Models\ShiftSwapRequest::STATUS_REJECTED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_REJECTED)
                            text-red-600
                        @else
                            text-slate-500
                        @endif">
                        @if($swap->status === \App\Models\ShiftSwapRequest::STATUS_APPROVED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_APPROVED)
                            Disetujui Kepala Divisi
                        @elseif($swap->status === \App\Models\ShiftSwapRequest::STATUS_REJECTED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_REJECTED)
                            Ditolak Kepala Divisi
                        @else
                            Diputuskan
                        @endif
                    </div>
                    <div class="text-sm text-slate-800 mt-0.5">
                        <strong>{{ $swap->approver?->name ?? '—' }}</strong>
                        @if($swap->status === \App\Models\ShiftSwapRequest::STATUS_APPROVED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_APPROVED)
                            @if($isShift)
                                menyetujui tukar shift. Jadwal telah diupdate otomatis.
                            @else
                                menyetujui tukar libur. Jadwal telah diupdate otomatis.
                            @endif
                        @elseif($swap->status === \App\Models\ShiftSwapRequest::STATUS_REJECTED || $swap->status === \App\Models\DayOffSwapRequest::STATUS_REJECTED)
                            menolak pengajuan.
                        @else
                            memutuskan pengajuan.
                        @endif
                        @if($swap->approver_response_note)
                            <div class="mt-1 italic text-slate-600">"{{ $swap->approver_response_note }}"</div>
                        @endif
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ $swap->approver_decided_at->format('Y-m-d H:i:s') }}</div>
                @else
                    <span class="absolute -left-[34px] top-0 w-5 h-5 bg-slate-200 rounded-full border-4 border-white"></span>
                    <div class="text-xs text-slate-400 font-semibold uppercase">Menunggu Kepala Divisi</div>
                    <div class="text-sm text-slate-500 mt-0.5">
                        @if($swap->status === \App\Models\ShiftSwapRequest::STATUS_PENDING_TARGET)
                            Pengajuan masih menunggu konfirmasi target.
                        @elseif($swap->status === \App\Models\ShiftSwapRequest::STATUS_PENDING_KADIV)
                            Target sudah ACC, menunggu Kepala Divisi <strong>{{ $swap->requester->division?->name ?? 'divisi' }}</strong> approve.
                        @else
                            Menunggu keputusan Kepala Divisi.
                        @endif
                    </div>
                @endif
            </li>
        </ol>
    </div>

    {{-- Schedule impact (jika sudah approved) --}}
    @if($isShift && $swap->status === \App\Models\ShiftSwapRequest::STATUS_APPROVED)
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5">
            <h2 class="font-bold text-emerald-800 mb-2">Dampak Jadwal</h2>
            <p class="text-sm text-emerald-700">
                Pada tanggal <strong>{{ $swap->date->format('Y-m-d') }}</strong>,
                shift <strong>{{ $swap->requester->name }}</strong> menjadi
                <strong>{{ $swap->targetShift?->name ?? '—' }}</strong>,
                dan shift <strong>{{ $swap->targetEmployee?->name ?? '—' }}</strong> menjadi
                <strong>{{ $swap->requesterShift?->name ?? '—' }}</strong>.
            </p>
        </div>
    @endif

    <div class="text-center pt-2">
        <a href="{{ route('admin.swap-requests.index') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">← Kembali ke timeline</a>
    </div>
</div>
@endsection
