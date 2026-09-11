@extends('layouts.app')
@section('title', 'Laporan Bulanan')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Laporan Absensi Bulanan</h1>
            <p class="text-sm text-slate-500 mt-0.5">Rekap kehadiran per karyawan per bulan</p>
        </div>
        <a href="{{ route('admin.reports.export', ['month' => $month, 'division_id' => $divisionFilter]) }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
            Export CSV
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4 sm:p-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Bulan</label>
                <input type="month" name="month" value="{{ $month }}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Divisi</label>
                <select name="division_id" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">Semua divisi</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}" @selected($divisionFilter == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        </form>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl shadow-card border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Total Karyawan</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $report->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-card border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Total Hari Kerja</p>
            @php $totalKerja = $report->sum('kerja'); @endphp
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalKerja }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-card border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Total Hadir</p>
            @php $totalHadir = $report->sum('hadir'); @endphp
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $totalHadir }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-card border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Rata-rata Kehadiran</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $totalKerja > 0 ? round(($totalHadir / $totalKerja) * 100, 1) : 0 }}%</p>
        </div>
    </div>

    {{-- Per-karyawan summary --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Ringkasan per Karyawan</h2>
            <span class="text-xs text-slate-500">{{ $startDate->translatedFormat('d M') }} – {{ $endDate->translatedFormat('d F Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200 bg-slate-50/50">
                    <tr>
                        <th class="py-3 px-6 font-semibold">Karyawan</th>
                        <th class="py-3 px-3 font-semibold text-center">Hari Kerja</th>
                        <th class="py-3 px-3 font-semibold text-center">Libur</th>
                        <th class="py-3 px-3 font-semibold text-center">Hadir</th>
                        <th class="py-3 px-3 font-semibold text-center">Absen</th>
                        <th class="py-3 px-3 font-semibold text-center">Full Day</th>
                        <th class="py-3 px-6 font-semibold">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($report as $r)
                    <tr class="border-b last:border-0 hover:bg-slate-50/50">
                        <td class="py-3 px-6">
                            <p class="font-medium text-slate-900">{{ $r->user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $r->user->division->name }} · {{ $r->user->nik }}</p>
                        </td>
                        <td class="py-3 px-3 text-center font-mono">{{ $r->kerja }}</td>
                        <td class="py-3 px-3 text-center font-mono text-slate-500">{{ $r->libur }}</td>
                        <td class="py-3 px-3 text-center font-mono text-emerald-600 font-semibold">{{ $r->hadir }}</td>
                        <td class="py-3 px-3 text-center font-mono {{ $r->absen > 0 ? 'text-red-600 font-semibold' : 'text-slate-500' }}">{{ $r->absen }}</td>
                        <td class="py-3 px-3 text-center font-mono text-indigo-600">{{ $r->full_day }}</td>
                        <td class="py-3 px-6">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full
                                        @if($r->pct_kehadiran >= 90) bg-emerald-500
                                        @elseif($r->pct_kehadiran >= 75) bg-amber-500
                                        @else bg-red-500
                                        @endif" style="width: {{ min(100, $r->pct_kehadiran) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-slate-700 w-12 text-right">{{ $r->pct_kehadiran }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Calendar view per hari --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Detail Harian</h2>
            <p class="text-xs text-slate-500 mt-0.5">Lihat absensi per hari. Klik cell untuk lihat detail karyawan.</p>
        </div>
        <div class="overflow-x-auto -mx-6">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left p-3 sticky left-0 bg-slate-50 z-10 font-semibold text-slate-700 min-w-[180px]">Karyawan</th>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php $date = $startDate->copy()->addDays($d - 1); @endphp
                            <th class="p-1 border-r border-slate-200 text-center min-w-[36px]">
                                <div class="text-slate-500 text-[9px]">{{ $date->translatedFormat('D') }}</div>
                                <div class="font-semibold text-slate-700">{{ $d }}</div>
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                @foreach($dailyRows as $row)
                    <tr class="border-b last:border-0">
                        <td class="p-3 sticky left-0 bg-white z-10 font-medium">
                            <div>{{ $row['user']->name }}</div>
                            <div class="text-slate-500 text-[10px] font-normal">{{ $row['user']->division->name }}</div>
                        </td>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php $cell = $row['days'][$d]; @endphp
                            <td class="p-0.5 border-r border-slate-100 text-center">
                                @if($cell->is_holiday)
                                    <div class="text-emerald-600 font-bold" title="{{ \App\Models\Holiday::holidayName($cell->date->toDateString()) }}">★</div>
                                @elseif($cell->attendance && $cell->attendance->hasClockedIn())
                                    <div class="@if($cell->attendance->hasClockedOut()) bg-indigo-100 text-indigo-700 @else bg-emerald-100 text-emerald-700 @endif rounded text-[10px] font-bold" title="Masuk: {{ $cell->attendance->clock_in_time?->format('H:i') }} @if($cell->attendance->hasClockedOut()) Pulang: {{ $cell->attendance->clock_out_time?->format('H:i') }} @endif">
                                        {{ $cell->attendance->clock_in_time?->format('H:i') }}
                                    </div>
                                @elseif($cell->schedule && $cell->schedule->isLibur())
                                    <div class="text-amber-600 text-[10px]">L</div>
                                @elseif($cell->schedule)
                                    <div class="text-red-500 text-[10px] font-bold" title="Tidak hadir">✗</div>
                                @else
                                    <div class="text-slate-300">·</div>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-200 text-xs text-slate-600 flex flex-wrap gap-4">
            <span class="flex items-center gap-1.5"><span class="inline-block bg-emerald-100 text-emerald-700 rounded text-[10px] px-1 font-bold">08:03</span> Hadir (masuk saja)</span>
            <span class="flex items-center gap-1.5"><span class="inline-block bg-indigo-100 text-indigo-700 rounded text-[10px] px-1 font-bold">17:00</span> Full day</span>
            <span class="flex items-center gap-1.5"><span class="text-amber-600">L</span> Libur</span>
            <span class="flex items-center gap-1.5"><span class="text-emerald-600">★</span> Hari Libur Nasional</span>
            <span class="flex items-center gap-1.5"><span class="text-red-500">✗</span> Tidak Hadir</span>
        </div>
    </div>
</div>
@endsection
