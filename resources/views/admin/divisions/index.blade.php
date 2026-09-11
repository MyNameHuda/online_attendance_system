@extends('layouts.app')
@section('title', 'Divisi')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Divisi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Organisasi divisi perusahaan</p>
        </div>
        <a href="{{ route('admin.divisions.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Divisi
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
        @if($errors->any())<div class="m-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first() }}</div>@endif
        @if($divisions->isEmpty())
            <div class="py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                <p class="text-sm font-medium text-slate-700">Belum ada divisi</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200 bg-slate-50/50">
                        <tr>
                            <th class="py-3 px-6 font-semibold">Nama Divisi</th>
                            <th class="py-3 px-3 font-semibold">Jumlah Karyawan</th>
                            <th class="py-3 px-6 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($divisions as $d)
                        <tr class="border-b last:border-0 hover:bg-slate-50/50">
                            <td class="py-3 px-6 font-semibold text-slate-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                                {{ $d->name }}
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center gap-1.5 text-sm text-slate-700">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    {{ $d->employees_count }}
                                </span>
                            </td>
                            <td class="py-3 px-6 text-right text-xs space-x-3">
                                <a href="{{ route('admin.divisions.edit', $d) }}" class="text-brand-600 hover:text-brand-700 font-medium">Edit</a>
                                <form method="POST" action="{{ route('admin.divisions.destroy', $d) }}" class="inline" onsubmit="return confirm('Hapus divisi {{ $d->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-700 font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
