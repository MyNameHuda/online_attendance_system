@extends('layouts.app')
@section('title', 'Hari Libur')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Hari Libur</h1>
            <p class="text-sm text-slate-500 mt-0.5">Daftar hari libur nasional & cuti bersama. Tanggal ini otomatis ditandai libur di semua jadwal.</p>
        </div>
        <a href="{{ route('admin.holidays.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Hari Libur
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
        @if($holidays->isEmpty())
            <div class="py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <p class="text-sm font-medium text-slate-700">Belum ada hari libur</p>
                <p class="text-xs text-slate-500 mt-1">Tambah hari libur nasional seperti 17 Agustus, Lebaran, dll</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200 bg-slate-50/50">
                        <tr>
                            <th class="py-3 px-6 font-semibold">Tanggal</th>
                            <th class="py-3 px-3 font-semibold">Nama</th>
                            <th class="py-3 px-3 font-semibold">Jenis</th>
                            <th class="py-3 px-6 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($holidays as $h)
                        <tr class="border-b last:border-0 hover:bg-slate-50/50">
                            <td class="py-3 px-6 font-medium text-slate-900">{{ $h->date->translatedFormat('l, d F Y') }}</td>
                            <td class="py-3 px-3 text-slate-700">{{ $h->name }}</td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $h->is_national ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $h->is_national ? 'Nasional' : 'Custom' }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-right text-xs space-x-3">
                                <a href="{{ route('admin.holidays.edit', $h) }}" class="text-brand-600 hover:text-brand-700 font-medium">Edit</a>
                                <form method="POST" action="{{ route('admin.holidays.destroy', $h) }}" class="inline" onsubmit="return confirm('Hapus hari libur {{ $h->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-700 font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-slate-200">{{ $holidays->links() }}</div>
        @endif
    </div>
</div>
@endsection
