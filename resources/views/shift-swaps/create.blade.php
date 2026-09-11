@extends('layouts.app')
@section('title', 'Buat Tukar Shift')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('shift-swaps.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
            </span>
            Ajukan Tukar Shift
        </h1>
        <p class="text-sm text-slate-500 mt-1">Pilih tanggal dan rekan satu divisi yang akan diajak bertukar shift.</p>

        @if($errors->any())
            <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('shift-swaps.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="date" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                <input id="date" type="date" name="date" value="{{ old('date', $preselectedDate) }}" min="{{ date('Y-m-d') }}" required
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
            </div>
            <div>
                <label for="target_employee_id" class="block text-sm font-medium text-slate-700 mb-1.5">Karyawan Tujuan <span class="text-red-500">*</span></label>
                <select id="target_employee_id" name="target_employee_id" required
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                    <option value="">— Pilih rekan satu divisi —</option>
                    @foreach($candidates as $c)
                        <option value="{{ $c->id }}" @selected(old('target_employee_id', $preselectedTarget) == $c->id)>
                            {{ $c->name }} ({{ $c->nik }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Hanya rekan dari divisi yang sama ({{ auth()->user()->division->name }})
                </p>
            </div>
            <div>
                <label for="reason" class="block text-sm font-medium text-slate-700 mb-1.5">Alasan <span class="text-red-500">*</span></label>
                <textarea id="reason" name="reason" rows="3" required minlength="3" maxlength="500"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">{{ old('reason') }}</textarea>
            </div>

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                <p class="text-xs font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Alur approval
                </p>
                <ol class="space-y-1.5 text-xs text-slate-600">
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">1</span>
                        <span>Anda buat pengajuan → status: <em>Pending Target</em></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">2</span>
                        <span>Target harus ACC dulu → status: <em>Pending KD</em></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center flex-shrink-0 text-[10px] font-bold">3</span>
                        <span>Kepala Divisi approve → jadwal otomatis ditukar</span>
                    </li>
                </ol>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Kirim Pengajuan
                </button>
                <a href="{{ route('shift-swaps.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
