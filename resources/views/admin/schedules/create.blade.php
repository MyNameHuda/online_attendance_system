@extends('layouts.app')
@section('title', 'Tambah Jadwal')
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <div>
        <a href="{{ route('admin.schedules.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2 mb-1">
            <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </span>
            Tambah Jadwal Kerja
        </h1>
        <p class="text-sm text-slate-500">Pilih satu tanggal + shift, bisa untuk banyak karyawan sekaligus.</p>

        @if($errors->any())
            <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.schedules.store') }}" class="mt-6 space-y-5"
              x-data="{ status: '{{ old('status', 'kerja') }}', division: '', selected: @json($preselectedUsers) }">
            @csrf

            {{-- Karyawan (multi-select) --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-slate-700">Karyawan <span class="text-red-500">*</span></label>
                    <div class="flex gap-3 text-xs">
                        <button type="button" @click="selected = Array.from(document.querySelectorAll('.emp-cb:not([disabled])')).map(c => parseInt(c.value))" class="font-medium text-brand-600 hover:text-brand-800">Pilih Semua</button>
                        <span class="text-slate-300">|</span>
                        <button type="button" @click="selected = []" class="font-medium text-slate-500 hover:text-slate-700">Kosongkan</button>
                    </div>
                </div>

                {{-- Filter divisi --}}
                <select x-model="division" class="w-full mb-2 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                    <option value="">— Semua Divisi —</option>
                    @foreach($employees->pluck('division.name')->unique()->filter()->sort() as $divName)
                        <option value="{{ $divName }}">{{ $divName }}</option>
                    @endforeach
                </select>

                <div class="border border-slate-300 rounded-lg p-3 max-h-72 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($employees as $e)
                        <label class="emp-row flex items-center gap-2 px-2 py-1.5 rounded hover:bg-slate-50 text-sm cursor-pointer"
                               data-division="{{ $e->division->name }}"
                               x-show="division === '' || division === '{{ $e->division->name }}'">
                            <input type="checkbox" name="user_ids[]" value="{{ $e->id }}"
                                   :checked="selected.includes({{ $e->id }})"
                                   @change="selected = selected.includes({{ $e->id }}) ? selected.filter(x => x !== {{ $e->id }}) : [...selected, {{ $e->id }}]"
                                   {{-- legacy support: kalau user_id single dari old() --}}
                                   @if(in_array($e->id, $preselectedUsers)) checked @endif
                                   class="emp-cb rounded text-brand-600 focus:ring-brand-500">
                            <span class="flex-1 truncate">{{ $e->name }}</span>
                            <span class="text-xs text-slate-500">{{ $e->division->name }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-slate-500 mt-1">Terpilih: <span x-text="selected.length" class="font-semibold text-slate-700"></span> karyawan.</p>
                @error('user_ids')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tanggal --}}
            <div>
                <label for="date" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                <input id="date" name="date" type="date" value="{{ old('date', $preselectedDate) }}" required
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Status <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition" :class="status === 'kerja' ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="status" value="kerja" x-model="status" class="text-brand-600 focus:ring-brand-500" checked>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">Hari Kerja</p>
                            <p class="text-xs text-slate-500">Karyawan masuk kerja</p>
                        </div>
                    </label>
                    <label class="relative flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition" :class="status === 'libur' ? 'border-amber-500 bg-amber-50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="status" value="libur" x-model="status" class="text-amber-600 focus:ring-amber-500">
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">Hari Libur</p>
                            <p class="text-xs text-slate-500">Off / cuti</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Shift (conditional) --}}
            <div x-show="status === 'kerja'" x-cloak>
                <label for="shift_id" class="block text-sm font-medium text-slate-700 mb-1.5">Shift <span class="text-red-500">*</span></label>
                <select id="shift_id" name="shift_id" :required="status === 'kerja'"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                    <option value="">— Pilih shift —</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" @selected(old('shift_id') == $s->id)>
                            {{ $s->name }} ({{ substr($s->start_time,0,5) }}–{{ substr($s->end_time,0,5) }})
                        </option>
                    @endforeach
                </select>
                @error('shift_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Simpan untuk <span x-text="selected.length"></span> Karyawan
                </button>
                <a href="{{ route('admin.schedules.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Batal</a>
            </div>
        </form>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>
@endsection
