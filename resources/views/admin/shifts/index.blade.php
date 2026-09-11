@extends('layouts.app')
@section('title', 'Shift')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Shift</h1>
            <p class="text-sm text-slate-500 mt-0.5">Daftar shift kerja (jam masuk & keluar)</p>
        </div>
        <a href="{{ route('admin.shifts.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Shift
        </a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($shifts as $s)
            <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-5 hover:shadow-card-hover transition group">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">{{ $s->name }}</p>
                        <p class="text-2xl font-bold text-slate-900 mt-1">{{ substr($s->start_time,0,5) }}</p>
                        <p class="text-sm text-slate-500">s/d {{ substr($s->end_time,0,5) }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <div class="flex gap-2 pt-3 border-t border-slate-100">
                    <a href="{{ route('admin.shifts.edit', $s) }}" class="flex-1 text-center px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-medium">Edit</a>
                    <form method="POST" action="{{ route('admin.shifts.destroy', $s) }}" class="flex-1" onsubmit="return confirm('Hapus shift {{ $s->name }}?')">
                        @csrf @method('DELETE')
                        <button class="w-full px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl shadow-card border border-slate-200 p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <p class="text-sm font-medium text-slate-700">Belum ada shift</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
