@extends('layouts.app')
@section('title', 'Jadwal Kerja')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Jadwal Kerja</h1>
            <p class="text-sm text-slate-500 mt-0.5">Klik cell untuk edit. Tambah jadwal per karyawan & tanggal.</p>
        </div>
        <a href="{{ route('admin.schedules.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Tambah Jadwal
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-4 sm:p-6">
        @if($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first() }}</div>
        @endif
        @if(session('success'))
            <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
        @endif

        <form method="GET" class="mb-4 flex flex-wrap gap-2 items-center text-sm">
            <label class="text-slate-600 font-medium">Mulai:</label>
            <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
            <button class="inline-flex items-center gap-1.5 bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg font-medium">Tampilkan</button>
            <span class="text-xs text-slate-500 ml-auto">{{ $startDate->translatedFormat('d M') }} – {{ $endDate->translatedFormat('d M Y') }} (14 hari)</span>
        </form>

        <div class="overflow-x-auto -mx-4 sm:-mx-6">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left p-3 border-r border-slate-200 sticky left-0 bg-slate-50 z-10 font-semibold text-slate-700 min-w-[180px]">Karyawan</th>
                        @foreach($dates as $d)
                            <th class="p-1.5 border-r border-slate-200 text-center min-w-[68px]">
                                <div class="text-slate-500 text-[10px]">{{ $d->translatedFormat('D') }}</div>
                                <div class="font-semibold text-slate-700">{{ $d->format('d/m') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($employees as $e)
                    <tr class="border-b last:border-0">
                        <td class="p-3 border-r border-slate-200 sticky left-0 bg-white z-10 font-medium">
                            <div>{{ $e->name }}</div>
                            <div class="text-slate-500 text-[10px] font-normal">{{ $e->division->name }}</div>
                            <a href="{{ route('admin.schedules.create', ['user_id' => $e->id, 'date' => $startDate->toDateString()]) }}" class="inline-flex items-center gap-0.5 text-[10px] text-brand-600 hover:underline mt-1">
                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Tambah jadwal
                            </a>
                        </td>
                        @foreach($dates as $d)
                            @php
                                $sch = $e->schedules->firstWhere('date', $d->toDateString());
                            @endphp
                            <td class="p-1 border-r border-slate-100 text-center group">
                                @if($sch)
                                    <a href="{{ route('admin.schedules.edit', $sch) }}" class="block p-1.5 rounded hover:bg-brand-50 transition" title="Klik untuk edit · {{ $d->format('d M Y') }}">
                                        @if($sch->isLibur())
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">
                                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                                                L
                                            </span>
                                        @else
                                            <span class="inline-block">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-800">{{ $sch->shift?->name ?? '?' }}</span>
                                                @if($sch->shift)
                                                    <div class="text-[9px] text-slate-400 mt-0.5">{{ substr($sch->shift->start_time,0,5) }}</div>
                                                @endif
                                            </span>
                                        @endif
                                    </a>
                                @else
                                    <a href="{{ route('admin.schedules.create', ['user_id' => $e->id, 'date' => $d->toDateString()]) }}" class="block p-1.5 rounded text-slate-300 hover:bg-slate-50 hover:text-brand-500 transition" title="Belum ada jadwal · klik untuk tambah">
                                        <svg class="w-4 h-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    </a>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 p-3 bg-slate-50 rounded-lg text-xs text-slate-600 flex items-start gap-2">
            <svg class="w-4 h-4 text-slate-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>
                <p><strong>L</strong> = Libur. Klik cell untuk edit jadwal. Klik <span class="inline-flex items-center text-slate-400"><svg class="w-3 h-3 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg></span> di cell kosong untuk tambah.</p>
            </div>
        </div>
    </div>
</div>
@endsection
