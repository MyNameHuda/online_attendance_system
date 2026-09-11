@extends('layouts.app')
@section('title', 'Karyawan')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Karyawan</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manajemen data karyawan & akses</p>
        </div>
        <a href="{{ route('admin.employees.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Karyawan
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4 sm:p-6">
        <form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input name="q" value="{{ $q }}" placeholder="Cari nama, NIK, atau email..." class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <select name="division_id" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Semua divisi</option>
                @foreach($divisions as $d)
                    <option value="{{ $d->id }}" @selected($divisionFilter == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
            <button class="inline-flex items-center gap-1.5 bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 2h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 4H19a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V4z" /></svg>
                Filter
            </button>
        </form>

        @if($employees->isEmpty())
            <div class="py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                <p class="text-sm font-medium text-slate-700">Tidak ada karyawan ditemukan</p>
            </div>
        @else
            <div class="overflow-x-auto -mx-4 sm:-mx-6">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200 bg-slate-50/50">
                        <tr>
                            <th class="py-3 px-6 font-semibold">NIK</th>
                            <th class="py-3 px-3 font-semibold">Nama</th>
                            <th class="py-3 px-3 font-semibold">Divisi</th>
                            <th class="py-3 px-3 font-semibold">Jabatan</th>
                            <th class="py-3 px-3 font-semibold">Role</th>
                            <th class="py-3 px-6 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $e)
                            <tr class="border-b last:border-0 hover:bg-slate-50/50">
                                <td class="py-3 px-6 font-mono text-xs">{{ $e->nik }}</td>
                                <td class="py-3 px-3">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $e->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $e->email }}</p>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-slate-700">{{ $e->division->name }}</td>
                                <td class="py-3 px-3 text-slate-700">{{ $e->position ?? '—' }}</td>
                                <td class="py-3 px-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                        @if($e->isAdmin()) bg-amber-100 text-amber-800
                                        @elseif($e->isKadiv()) bg-purple-100 text-purple-800
                                        @else bg-slate-100 text-slate-700
                                        @endif">{{ ucfirst(str_replace('_', ' ', $e->role)) }}</span>
                                </td>
                                <td class="py-3 px-6 text-right text-xs space-x-3">
                                    <a href="{{ route('admin.employees.edit', $e) }}" class="text-brand-600 hover:text-brand-700 font-medium">Edit</a>
                                    <form method="POST" action="{{ route('admin.employees.destroy', $e) }}" class="inline" onsubmit="return confirm('Hapus {{ $e->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:text-red-700 font-medium">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $employees->links() }}</div>
        @endif
    </div>
</div>
@endsection
