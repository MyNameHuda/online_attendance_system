@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">

    {{-- Greeting --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Selamat datang, {{ explode(' ', $user->name)[0] }} 👋</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $today->translatedFormat('l, d F Y') }}</p>
    </div>

    {{-- Hero: Today's status --}}
    @if($todaySchedule)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-indigo-800 text-white p-6 sm:p-8 shadow-lg">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>

            <div class="relative grid sm:grid-cols-2 gap-6 items-center">
                <div>
                    <p class="text-xs uppercase tracking-wider text-brand-100 font-semibold mb-2">Shift Hari Ini</p>
                    <h2 class="text-3xl font-bold mb-3">
                        {{ $todaySchedule->shift?->name ?? 'Libur' }}
                        @if($todaySchedule->shift)
                            <span class="text-brand-200 text-xl font-medium ml-2">{{ substr($todaySchedule->shift->start_time,0,5) }} – {{ substr($todaySchedule->shift->end_time,0,5) }}</span>
                        @endif
                    </h2>

                    @if($todayAttendance)
                        <div class="flex gap-6 text-sm">
                            <div>
                                <p class="text-brand-200 text-xs">Masuk</p>
                                <p class="font-semibold text-lg">{{ $todayAttendance->clock_in_time?->format('H:i') ?? '—' }}</p>
                            </div>
                            <div class="w-px bg-white/20"></div>
                            <div>
                                <p class="text-brand-200 text-xs">Pulang</p>
                                <p class="font-semibold text-lg">{{ $todayAttendance->clock_out_time?->format('H:i') ?? '—' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-brand-100 text-sm">Belum absen hari ini</p>
                    @endif
                </div>

                <div class="flex flex-col items-start sm:items-end gap-3">
                    @if($todaySchedule->isLibur())
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-400/20 text-amber-100 text-sm font-medium border border-amber-400/30">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                            Hari Libur
                        </span>
                    @elseif($todayAttendance && $todayAttendance->hasClockedOut())
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-400/20 text-emerald-100 text-sm font-medium border border-emerald-400/30">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Selesai
                        </span>
                    @elseif($todayAttendance && $todayAttendance->hasClockedIn())
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-400/20 text-emerald-100 text-sm font-medium border border-emerald-400/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                            Sedang Bekerja
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-400/20 text-amber-100 text-sm font-medium border border-amber-400/30">
                            <span class="w-2 h-2 rounded-full bg-amber-300"></span>
                            Belum Absen
                        </span>
                    @endif

                    <a href="{{ route('attendance.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-white text-brand-700 font-semibold text-sm hover:bg-brand-50 shadow-sm hover:shadow transition cursor-pointer">
                        @if(!$todayAttendance || !$todayAttendance->hasClockedIn())
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                            Absen Masuk
                        @elseif(!$todayAttendance->hasClockedOut())
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Absen Pulang
                        @else
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            Lihat Absensi
                        @endif
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-6 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <div>
                <p class="font-semibold text-amber-900">Tidak ada jadwal hari ini</p>
                <p class="text-sm text-amber-700 mt-1">Hubungi Admin untuk setup jadwal Anda.</p>
            </div>
        </div>
    @endif

    {{-- Quick actions (hanya untuk karyawan) --}}
    @if($user->isKaryawan())
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('shift-swaps.create') }}" class="rounded-xl bg-white border border-slate-200 p-4 hover:border-brand-300 hover:shadow-card transition group">
            <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center mb-2 group-hover:bg-brand-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
            </div>
            <p class="text-sm font-semibold text-slate-900">Tukar Shift</p>
            <p class="text-xs text-slate-500">Ajukan pertukaran</p>
        </a>
        <a href="{{ route('day-off-swaps.create') }}" class="rounded-xl bg-white border border-slate-200 p-4 hover:border-brand-300 hover:shadow-card transition group">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2 group-hover:bg-emerald-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-slate-900">Tukar Libur</p>
            <p class="text-xs text-slate-500">Pindah hari libur</p>
        </a>
        <a href="{{ route('attendance.index') }}" class="rounded-xl bg-white border border-slate-200 p-4 hover:border-brand-300 hover:shadow-card transition group">
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-2 group-hover:bg-purple-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
            </div>
            <p class="text-sm font-semibold text-slate-900">Riwayat</p>
            <p class="text-xs text-slate-500">Lihat absensi</p>
        </a>
        <a href="{{ route('shift-swaps.index') }}" class="rounded-xl bg-white border border-slate-200 p-4 hover:border-brand-300 hover:shadow-card transition group">
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mb-2 group-hover:bg-amber-100">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
            <p class="text-sm font-semibold text-slate-900">Status</p>
            <p class="text-xs text-slate-500">Pengajuan saya</p>
        </a>
    </div>
    @endif

    {{-- Two-column section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Upcoming schedules --}}
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Jadwal Berikutnya
                </h2>
            </div>
            @if($upcomingSchedules->isEmpty())
                <p class="text-sm text-slate-500">Belum ada jadwal.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($upcomingSchedules as $sch)
                        <li class="py-2.5 flex justify-between items-center text-sm">
                            <span class="text-slate-700">{{ $sch->date->translatedFormat('D, d M') }}</span>
                            <span>
                                @if($sch->isLibur())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                                        Libur
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="text-slate-900 font-medium">{{ $sch->shift?->name ?? '—' }}</span>
                                        <span class="text-slate-400 text-xs">{{ $sch->shift ? substr($sch->shift->start_time,0,5) : '' }}</span>
                                    </span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Attendance history --}}
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6">
            <h2 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Riwayat Absensi
            </h2>
            @if($attendanceHistory->isEmpty())
                <p class="text-sm text-slate-500">Belum ada riwayat.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($attendanceHistory as $att)
                        <li class="py-2.5 flex justify-between items-center text-sm">
                            <span class="text-slate-700">{{ $att->date->format('d M') }}</span>
                            <span class="font-mono text-xs text-slate-500">
                                <span class="text-emerald-600">{{ $att->clock_in_time?->format('H:i') ?? '—' }}</span>
                                <span class="text-slate-300 mx-1">→</span>
                                <span class="text-indigo-600">{{ $att->clock_out_time?->format('H:i') ?? '—' }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Swap status (hanya karyawan) --}}
        @if($user->isKaryawan())
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 lg:col-span-2">
            <h2 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                Status Pengajuan
            </h2>
            @if($myShiftSwaps->isEmpty() && $myDayOffSwaps->isEmpty())
                <p class="text-sm text-slate-500">Belum ada pengajuan.</p>
            @else
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-2">Tukar Shift</h3>
                        @if($myShiftSwaps->isEmpty())
                            <p class="text-sm text-slate-400">Belum ada.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach($myShiftSwaps as $s)
                                    <li>
                                        <a href="{{ route('shift-swaps.show', $s) }}" class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-brand-300 hover:bg-brand-50/30 transition group">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ $s->date->format('d M Y') }} &middot;
                                                    <span class="text-slate-500 font-normal">
                                                        {{ $s->requester_id === $user->id ? 'ke' : 'dari' }}
                                                        {{ $s->requester_id === $user->id ? $s->targetEmployee->name : $s->requester->name }}
                                                    </span>
                                                </p>
                                            </div>
                                            <span class="shrink-0">
                                                @include('partials._status-badge', ['status' => $s->status, 'size' => 'sm'])
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-xs uppercase tracking-wider font-semibold text-slate-500 mb-2">Tukar Libur</h3>
                        @if($myDayOffSwaps->isEmpty())
                            <p class="text-sm text-slate-400">Belum ada.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach($myDayOffSwaps as $s)
                                    <li>
                                        <a href="{{ route('day-off-swaps.show', $s) }}" class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 hover:border-brand-300 hover:bg-brand-50/30 transition">
                                            <p class="text-sm font-medium text-slate-900">
                                                {{ $s->old_off_date->format('d M') }} → {{ $s->new_off_date->format('d M') }}
                                            </p>
                                            <span class="shrink-0">
                                                @include('partials._status-badge', ['status' => $s->status, 'size' => 'sm'])
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
