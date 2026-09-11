@extends('layouts.app')
@section('title', 'Edit Jadwal')
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
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </span>
            Edit Jadwal
        </h1>
        <p class="text-sm text-slate-500">Ubah jadwal untuk karyawan di tanggal tertentu.</p>

        @if($errors->any())
            <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}" class="mt-6 space-y-5" x-data="{ status: '{{ old('status', $schedule->status) }}' }">
            @csrf
            @method('PUT')
            <div>
                <label for="user_id" class="block text-sm font-medium text-slate-700 mb-1.5">Karyawan <span class="text-red-500">*</span></label>
                <select id="user_id" name="user_id" required
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                    <option value="">— Pilih karyawan —</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(old('user_id', $schedule->user_id) == $e->id)>
                            {{ $e->name }} ({{ $e->division->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="date" class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                <input id="date" name="date" type="date" value="{{ old('date', $schedule->date->toDateString()) }}" required
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Status <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition" :class="status === 'kerja' ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="status" value="kerja" x-model="status" class="text-brand-600 focus:ring-brand-500" {{ $schedule->status === 'kerja' ? 'checked' : '' }}>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">Hari Kerja</p>
                            <p class="text-xs text-slate-500">Karyawan masuk kerja</p>
                        </div>
                    </label>
                    <label class="relative flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition" :class="status === 'libur' ? 'border-amber-500 bg-amber-50' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="status" value="libur" x-model="status" class="text-amber-600 focus:ring-amber-500" {{ $schedule->status === 'libur' ? 'checked' : '' }}>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">Hari Libur</p>
                            <p class="text-xs text-slate-500">Off / cuti</p>
                        </div>
                    </label>
                </div>
            </div>
            <div x-show="status === 'kerja'" x-cloak>
                <label for="shift_id" class="block text-sm font-medium text-slate-700 mb-1.5">Shift <span class="text-red-500">*</span></label>
                <select id="shift_id" name="shift_id" :required="status === 'kerja'"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                    <option value="">— Pilih shift —</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" @selected(old('shift_id', $schedule->shift_id) == $s->id)>
                            {{ $s->name }} ({{ substr($s->start_time,0,5) }}–{{ substr($s->end_time,0,5) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-200 mt-6">
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Update
                </button>
                <a href="{{ route('admin.schedules.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Batal</a>
                <form method="POST" action="{{ route('admin.schedules.destroy', $schedule) }}" class="ml-auto" onsubmit="return confirm('Hapus jadwal ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 px-3 py-2 rounded-lg text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a1 1 0 011 1v2H8V4a1 1 0 011-1z" /></svg>
                        Hapus Jadwal
                    </button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection
