@extends('layouts.app')
@section('title', 'Absensi Divisi')
@section('content')
<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Absensi Divisi {{ $kd->division?->name }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">
            Monitor kehadiran {{ $summary['karyawan'] }} anggota divisi Anda. Klik baris untuk lihat foto & lokasi detail.
        </p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4">
            <div class="text-2xl font-bold text-slate-800">{{ $summary['karyawan'] }}</div>
            <div class="text-xs text-slate-500 font-medium mt-0.5">Anggota</div>
        </div>
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4">
            <div class="text-2xl font-bold text-slate-800">{{ $summary['total'] }}</div>
            <div class="text-xs text-slate-500 font-medium mt-0.5">Record</div>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4">
            <div class="text-2xl font-bold text-emerald-700">{{ $summary['hadir'] }}</div>
            <div class="text-xs text-emerald-600 font-medium mt-0.5">Sudah Clock-In</div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
            <div class="text-2xl font-bold text-amber-700">{{ $summary['belum_pulang'] }}</div>
            <div class="text-xs text-amber-600 font-medium mt-0.5">Belum Clock-Out</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <div class="text-2xl font-bold text-red-700">{{ $summary['belum_masuk'] }}</div>
            <div class="text-xs text-red-600 font-medium mt-0.5">Belum Clock-In</div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Dari</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Sampai</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Anggota</label>
                <select name="user_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">— Semua anggota —</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected(request('user_id') == $e->id)>{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">— Semua —</option>
                    <option value="lengkap" @selected(request('status') === 'lengkap')>Lengkap (clock in + out)</option>
                    <option value="belum_pulang" @selected(request('status') === 'belum_pulang')>Belum Clock-Out</option>
                    <option value="belum_masuk" @selected(request('status') === 'belum_masuk')>Belum Clock-In</option>
                </select>
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button class="inline-flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter
                </button>
                <a href="{{ route('kadiv.attendances.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
        @if($attendances->isEmpty())
            <div class="py-16 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <p class="text-sm font-medium text-slate-700">Tidak ada data absensi untuk divisi Anda</p>
                <p class="text-xs text-slate-500 mt-1">Coba ubah filter atau rentang tanggal.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200 bg-slate-50/50">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Tanggal</th>
                            <th class="py-3 px-3 font-semibold">Anggota</th>
                            <th class="py-3 px-3 font-semibold">Clock In</th>
                            <th class="py-3 px-3 font-semibold">Clock Out</th>
                            <th class="py-3 px-3 font-semibold">Lokasi</th>
                            <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($attendances as $a)
                            @php
                                $hasIn  = $a->hasClockedIn();
                                $hasOut = $a->hasClockedOut();
                                $lat = $a->clock_in_lat ?? $a->clock_out_lat;
                                $lng = $a->clock_in_lng ?? $a->clock_out_lng;
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ $a->date->translatedFormat('d M Y') }}</div>
                                    <div class="text-xs text-slate-500">{{ $a->date->translatedFormat('l') }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-medium text-slate-800">{{ $a->user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $a->user->division?->name ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    @if($hasIn)
                                        <div class="font-semibold text-emerald-700">{{ $a->clock_in_time->format('H:i') }}</div>
                                        @if($a->clock_in_photo)
                                            <div class="text-xs text-slate-500 inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                Foto
                                            </div>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Belum</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    @if($hasOut)
                                        <div class="font-semibold text-indigo-700">{{ $a->clock_out_time->format('H:i') }}</div>
                                        @if($a->clock_out_photo)
                                            <div class="text-xs text-slate-500 inline-flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                Foto
                                            </div>
                                        @endif
                                    @elseif($hasIn)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Belum</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    @if($lat !== null && $lng !== null)
                                        <div class="text-xs text-slate-600 font-mono">{{ number_format((float)$lat, 5) }}, {{ number_format((float)$lng, 5) }}</div>
                                        <div class="text-xs text-slate-500">📍 {{ $hasIn && $hasOut ? 'In & Out' : ($hasIn ? 'Clock-In' : 'Clock-Out') }}</div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('kadiv.attendances.show', $a) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold">
                                        Detail
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
