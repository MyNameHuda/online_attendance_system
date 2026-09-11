@extends('layouts.app')
@section('title', 'Detail Absensi Anggota')
@section('content')
<div class="max-w-5xl mx-auto space-y-4">
    <div>
        <a href="{{ route('kadiv.attendances.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Absensi Divisi
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 mb-2">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Divisi {{ $kd->division?->name }}
                </div>
                <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </span>
                    Detail Absensi
                </h1>
                <p class="text-sm text-slate-500 mt-1">{{ $attendance->date->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-slate-900">{{ $attendance->user->name }}</div>
                <div class="text-sm text-slate-500">{{ $attendance->user->division?->name ?? '-' }} · {{ ucfirst($attendance->user->role) }}</div>
                @if($schedule)
                    <div class="mt-1 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $schedule->isKerja() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        Shift: {{ $schedule->shift?->name ?? 'Libur' }}{{ $schedule->shift ? ' (' . substr($schedule->shift->start_time,0,5) . '–' . substr($schedule->shift->end_time,0,5) . ')' : '' }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Clock-In + Clock-Out side by side --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- CLOCK IN --}}
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-emerald-50/50">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                        </span>
                        Clock In
                    </h2>
                    @if($attendance->hasClockedIn())
                        <div class="text-right">
                            <div class="text-xl font-bold text-emerald-700">{{ $attendance->clock_in_time->format('H:i') }}</div>
                            <div class="text-xs text-slate-500">{{ $attendance->clock_in_time->format('d M Y') }}</div>
                        </div>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Belum absen</span>
                    @endif
                </div>
            </div>
            <div class="p-5 space-y-4">
                @if($attendance->hasClockedIn())
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Foto Selfie</label>
                        <a href="{{ asset('storage/' . $attendance->clock_in_photo) }}" target="_blank" class="block">
                            <img src="{{ asset('storage/' . $attendance->clock_in_photo) }}" alt="Foto clock-in {{ $attendance->user->name }}"
                                 class="w-full h-64 object-cover rounded-xl border border-slate-200 hover:opacity-95 transition">
                        </a>
                        <p class="text-xs text-slate-500 mt-1">Klik foto untuk lihat ukuran penuh.</p>
                    </div>

                    @if($attendance->clock_in_lat !== null && $attendance->clock_in_lng !== null)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide">Lokasi Absen</label>
                                @if($clockInDistance !== null && $office)
                                    @if($clockInDistance <= $office->radius_meters)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            Dalam radius ({{ number_format($clockInDistance) }} m)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            Luar radius ({{ number_format($clockInDistance) }} m)
                                        </span>
                                    @endif
                                @endif
                            </div>
                            <div class="text-xs text-slate-600 font-mono mb-2">
                                {{ number_format((float)$attendance->clock_in_lat, 7) }}, {{ number_format((float)$attendance->clock_in_lng, 7) }}
                            </div>
                            <div id="map-clock-in" class="w-full h-64 rounded-xl border border-slate-200 z-0"></div>
                        </div>
                    @else
                        <div class="text-xs text-slate-500 italic">Tidak ada data lokasi (kemungkinan absen dengan Demo Mode).</div>
                    @endif
                @else
                    <div class="py-10 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-sm">Anggota belum clock-in pada tanggal ini.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- CLOCK OUT --}}
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-indigo-50/50">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </span>
                        Clock Out
                    </h2>
                    @if($attendance->hasClockedOut())
                        <div class="text-right">
                            <div class="text-xl font-bold text-indigo-700">{{ $attendance->clock_out_time->format('H:i') }}</div>
                            <div class="text-xs text-slate-500">{{ $attendance->clock_out_time->format('d M Y') }}</div>
                        </div>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Belum absen</span>
                    @endif
                </div>
            </div>
            <div class="p-5 space-y-4">
                @if($attendance->hasClockedOut())
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">Foto Selfie</label>
                        <a href="{{ asset('storage/' . $attendance->clock_out_photo) }}" target="_blank" class="block">
                            <img src="{{ asset('storage/' . $attendance->clock_out_photo) }}" alt="Foto clock-out {{ $attendance->user->name }}"
                                 class="w-full h-64 object-cover rounded-xl border border-slate-200 hover:opacity-95 transition">
                        </a>
                        <p class="text-xs text-slate-500 mt-1">Klik foto untuk lihat ukuran penuh.</p>
                    </div>

                    @if($attendance->clock_out_lat !== null && $attendance->clock_out_lng !== null)
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide">Lokasi Absen</label>
                                @if($clockOutDistance !== null && $office)
                                    @if($clockOutDistance <= $office->radius_meters)
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            Dalam radius ({{ number_format($clockOutDistance) }} m)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            Luar radius ({{ number_format($clockOutDistance) }} m)
                                        </span>
                                    @endif
                                @endif
                            </div>
                            <div class="text-xs text-slate-600 font-mono mb-2">
                                {{ number_format((float)$attendance->clock_out_lat, 7) }}, {{ number_format((float)$attendance->clock_out_lng, 7) }}
                            </div>
                            <div id="map-clock-out" class="w-full h-64 rounded-xl border border-slate-200 z-0"></div>
                        </div>
                    @else
                        <div class="text-xs text-slate-500 italic">Tidak ada data lokasi (kemungkinan absen dengan Demo Mode).</div>
                    @endif
                @else
                    <div class="py-10 text-center text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-sm">Anggota belum clock-out pada tanggal ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
    .leaflet-default-icon-path { background-image: url('https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png'); }
    .attendance-marker {
        background: #10b981;
        width: 22px; height: 22px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
    .attendance-marker.out {
        background: #6366f1;
    }
    .office-marker {
        background: #4f46e5;
        width: 26px; height: 26px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
</style>
@endpush

@push('scripts')
@if($office)
<script>
    const officeLat = {{ (float) $office->latitude }};
    const officeLng = {{ (float) $office->longitude }};
    const officeName = @json($office->name);
    const officeRadius = {{ (int) $office->radius_meters }};
</script>
@endif

@if($attendance->hasClockedIn() && $attendance->clock_in_lat !== null)
<script>
    (function () {
        const lat = {{ (float) $attendance->clock_in_lat }};
        const lng = {{ (float) $attendance->clock_in_lng }};
        const map = L.map('map-clock-in').setView([lat, lng], 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        const m = L.divIcon({ className: '', html: '<div class="attendance-marker"></div>', iconSize: [22, 22], iconAnchor: [11, 22] });
        L.marker([lat, lng], { icon: m }).addTo(map).bindPopup(`<b>Clock-In</b><br>{{ $attendance->user->name }}<br>{{ $attendance->clock_in_time->format('H:i') }}`).openPopup();
        @if($office)
        const oIcon = L.divIcon({ className: '', html: '<div class="office-marker"></div>', iconSize: [26, 26], iconAnchor: [13, 13] });
        L.marker([officeLat, officeLng], { icon: oIcon }).addTo(map).bindPopup(`<b>${officeName}</b><br>(kantor utama)`);
        L.circle([officeLat, officeLng], { radius: officeRadius, color: '#4f46e5', fillColor: '#4f46e5', fillOpacity: 0.08, weight: 1 }).addTo(map);
        const bounds = L.latLngBounds([[lat, lng], [officeLat, officeLng]]);
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 17 });
        @endif
    })();
</script>
@endif

@if($attendance->hasClockedOut() && $attendance->clock_out_lat !== null)
<script>
    (function () {
        const lat = {{ (float) $attendance->clock_out_lat }};
        const lng = {{ (float) $attendance->clock_out_lng }};
        const map = L.map('map-clock-out').setView([lat, lng], 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        const m = L.divIcon({ className: '', html: '<div class="attendance-marker out"></div>', iconSize: [22, 22], iconAnchor: [11, 22] });
        L.marker([lat, lng], { icon: m }).addTo(map).bindPopup(`<b>Clock-Out</b><br>{{ $attendance->user->name }}<br>{{ $attendance->clock_out_time->format('H:i') }}`).openPopup();
        @if($office)
        const oIcon = L.divIcon({ className: '', html: '<div class="office-marker"></div>', iconSize: [26, 26], iconAnchor: [13, 13] });
        L.marker([officeLat, officeLng], { icon: oIcon }).addTo(map).bindPopup(`<b>${officeName}</b><br>(kantor utama)`);
        L.circle([officeLat, officeLng], { radius: officeRadius, color: '#4f46e5', fillColor: '#4f46e5', fillOpacity: 0.08, weight: 1 }).addTo(map);
        const bounds = L.latLngBounds([[lat, lng], [officeLat, officeLng]]);
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 17 });
        @endif
    })();
</script>
@endif
@endpush
@endsection
