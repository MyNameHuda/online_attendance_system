@extends('layouts.app')
@section('title', 'Tukar Shift')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Tukar Shift</h1>
            <p class="text-sm text-slate-500 mt-0.5">Pengajuan tukar shift dengan rekan satu divisi</p>
        </div>
        <a href="{{ route('shift-swaps.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Buat Pengajuan
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
        @if($swaps->isEmpty())
            <div class="py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                <p class="text-sm font-medium text-slate-700">Belum ada pengajuan</p>
                <p class="text-xs text-slate-500 mt-1">Buat pengajuan tukar shift pertama Anda</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200 bg-slate-50/50">
                        <tr>
                            <th class="py-3 px-6 font-semibold">Tanggal</th>
                            <th class="py-3 px-3 font-semibold">Pemohon</th>
                            <th class="py-3 px-3 font-semibold">Target</th>
                            <th class="py-3 px-3 font-semibold">Pertukaran</th>
                            <th class="py-3 px-3 font-semibold">Status</th>
                            <th class="py-3 px-6 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($swaps as $s)
                            <tr class="border-b last:border-0 hover:bg-slate-50/50">
                                <td class="py-3 px-6 text-slate-900 font-medium">{{ $s->date->format('d M Y') }}</td>
                                <td class="py-3 px-3 text-slate-700">{{ $s->requester->name }}</td>
                                <td class="py-3 px-3 text-slate-700">{{ $s->targetEmployee->name }}</td>
                                <td class="py-3 px-3 text-xs">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">{{ $s->requesterShift->name }}</span>
                                        <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">{{ $s->targetShift->name }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    @include('partials._status-badge', ['status' => $s->status, 'size' => 'sm'])
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <a href="{{ route('shift-swaps.show', $s) }}" class="inline-flex items-center gap-1 text-brand-600 hover:text-brand-700 text-xs font-medium">Detail <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-slate-200">{{ $swaps->links() }}</div>
        @endif
    </div>
</div>
@endsection
