@extends('layouts.app')
@section('title', 'Buat Tukar Libur')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('day-off-swaps.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <span class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </span>
            Ajukan Tukar Hari Libur
        </h1>
        <p class="text-sm text-slate-500 mt-1">Pilih hari libur Anda dan tanggal kerja baru yang akan dijadikan libur.</p>

        @if($errors->any())
            <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('day-off-swaps.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="old_off_date" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal Libur Lama <span class="text-red-500">*</span></label>
                <select id="old_off_date" name="old_off_date" required
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                    <option value="">— Pilih hari libur Anda —</option>
                    @foreach($liburDates as $d)
                        <option value="{{ $d }}" @selected(old('old_off_date') == $d)>{{ \Carbon\Carbon::parse($d)->translatedFormat('l, d F Y') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="new_off_date" class="block text-sm font-medium text-slate-700 mb-1.5">Menjadi Libur Baru (tanggal kerja) <span class="text-red-500">*</span></label>
                <select id="new_off_date" name="new_off_date" required
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                    <option value="">— Pilih hari kerja Anda —</option>
                    @foreach($kerjaDates as $d)
                        <option value="{{ $d }}" @selected(old('new_off_date') == $d)>{{ \Carbon\Carbon::parse($d)->translatedFormat('l, d F Y') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="reason" class="block text-sm font-medium text-slate-700 mb-1.5">Alasan <span class="text-red-500">*</span></label>
                <textarea id="reason" name="reason" rows="3" required minlength="3" maxlength="500"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">{{ old('reason') }}</textarea>
            </div>

            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs text-slate-600 flex items-start gap-2">
                <svg class="w-4 h-4 text-slate-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>Setelah disetujui Kepala Divisi, tanggal lama jadi kerja dan tanggal baru jadi libur.</span>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Kirim
                </button>
                <a href="{{ route('day-off-swaps.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
