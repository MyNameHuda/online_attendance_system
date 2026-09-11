@extends('layouts.app')
@section('title', 'Absensi')
@section('content')
<div x-data="attendanceApp()" x-init="init()" class="space-y-4">

    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span><strong>Error:</strong> {{ $errors->first() }}</span>
        </div>
    @endif
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Today's status --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Absensi Hari Ini
            <span class="text-sm font-normal text-slate-500">— {{ $today->translatedFormat('l, d F Y') }}</span>
        </h2>

        @if(!$todaySchedule)
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm flex items-start gap-2">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <span>Tidak ada jadwal untuk hari ini. Hubungi Admin.</span>
            </div>
        @elseif($todaySchedule->isLibur())
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                <div>
                    <p class="font-semibold">Hari ini libur</p>
                    <p class="text-xs mt-0.5">Tidak bisa melakukan absen.</p>
                </div>
            </div>
        @else
            <div class="grid sm:grid-cols-2 gap-3 mb-5 text-sm">
                <div class="p-3.5 bg-slate-50 rounded-xl">
                    <p class="text-xs text-slate-500 mb-1">Shift</p>
                    <p class="font-semibold text-slate-900">{{ $todaySchedule->shift->name }} <span class="text-slate-500 font-normal">({{ substr($todaySchedule->shift->start_time,0,5) }} – {{ substr($todaySchedule->shift->end_time,0,5) }})</span></p>
                </div>
                <div class="p-3.5 bg-slate-50 rounded-xl">
                    <p class="text-xs text-slate-500 mb-1">Status</p>
                    <p class="font-semibold text-slate-900">
                        @if(!$todayAttendance || !$todayAttendance->hasClockedIn())
                            <span class="text-slate-500 font-normal">Belum absen masuk</span>
                        @elseif(!$todayAttendance->hasClockedOut())
                            <span class="text-emerald-600">Masuk {{ $todayAttendance->clock_in_time->format('H:i') }} <span class="text-slate-400 font-normal">· belum pulang</span></span>
                        @else
                            <span class="text-indigo-600">Selesai <span class="text-slate-500 font-normal">({{ $todayAttendance->clock_in_time->format('H:i') }} – {{ $todayAttendance->clock_out_time->format('H:i') }})</span></span>
                        @endif
                    </p>
                </div>
            </div>

            @if($office)
                <p class="text-xs text-slate-500 mb-4 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Lokasi absen: <strong>{{ $office->name ?? 'Kantor' }}</strong> ({{ $office->latitude }}, {{ $office->longitude }}, radius {{ $office->radius_meters }}m)
                </p>
            @endif

            {{-- Step-by-step wizard --}}
            <div class="border border-slate-200 rounded-xl p-5 bg-gradient-to-br from-slate-50 to-white">

                {{-- Step 1: Photo --}}
                <div class="mb-5 pb-5 border-b border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-slate-900 flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition" :class="photoDataUrl ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'">1</span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Ambil Foto
                        </p>
                        <span x-show="photoDataUrl" class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Foto siap
                        </span>
                    </div>

                    {{-- State 1: Belum ada foto sama sekali (idle) --}}
                    <div x-show="!photoDataUrl && !cameraActive" class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center bg-slate-50/50">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-700">Pilih cara ambil foto</p>
                        <p class="text-xs text-slate-500 mb-4">Pastikan wajah terlihat jelas di frame</p>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <button type="button" @click="startCamera()" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                Buka Kamera
                            </button>
                            <label class="inline-flex items-center gap-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                Upload File
                                <input type="file" accept="image/*" @change="onFileSelect($event)" class="hidden">
                            </label>
                        </div>
                    </div>

                    {{-- State 2: Kamera aktif (live preview) --}}
                    <div x-show="cameraActive && !photoDataUrl" class="relative bg-slate-900 rounded-xl overflow-hidden">
                        {{-- Status bar --}}
                        <div class="absolute top-0 left-0 right-0 z-10 flex items-center justify-between p-3 bg-gradient-to-b from-black/60 to-transparent">
                            <div class="flex items-center gap-2 text-white text-xs">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                                <span class="font-medium">Kamera Aktif</span>
                            </div>
                            <button type="button" @click="stopCamera()" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10" title="Tutup kamera">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        {{-- Video preview --}}
                        <div class="relative aspect-[4/3] bg-slate-900">
                            <video x-ref="video" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover transform scale-x-[-1]"></video>
                            <canvas x-ref="canvas" class="hidden"></canvas>

                            {{-- Flash effect on capture --}}
                            <div x-show="false" class="absolute inset-0 bg-white opacity-0" :class="{ 'animate-pulse opacity-100': _flash }"></div>

                            {{-- Frame guide (oval) --}}
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="w-44 h-56 rounded-[50%] border-2 border-white/60 ring-4 ring-black/10"></div>
                            </div>

                            {{-- Camera switch button --}}
                            <button type="button" @click="switchCamera()" x-show="hasMultipleCameras" class="absolute bottom-3 right-3 z-10 w-10 h-10 rounded-full bg-white/90 hover:bg-white text-slate-800 flex items-center justify-center shadow-lg cursor-pointer" title="Putar kamera">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>
                        </div>

                        {{-- Shutter controls --}}
                        <div class="bg-slate-800 p-4 flex items-center justify-center gap-4">
                            <div class="w-10"></div>
                            <button type="button" @click="capturePhoto()" class="group relative w-16 h-16 rounded-full bg-white hover:bg-slate-100 active:scale-95 transition shadow-xl ring-4 ring-slate-700 flex items-center justify-center cursor-pointer" title="Ambil foto">
                                <span class="absolute inset-2 rounded-full border-2 border-slate-800 group-active:inset-3 transition-all"></span>
                            </button>
                            <button type="button" @click="stopCamera()" class="w-10 h-10 rounded-full bg-slate-700 hover:bg-slate-600 text-white flex items-center justify-center cursor-pointer" title="Batal">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        {{-- Tip --}}
                        <p class="bg-slate-700 text-slate-300 text-xs text-center py-2">Posisikan wajah di dalam frame, lalu tekan tombol shutter</p>
                    </div>

                    {{-- State 3: Foto sudah diambil (preview + actions) --}}
                    <div x-show="photoDataUrl" class="space-y-3">
                        <div class="relative rounded-xl overflow-hidden border-2 border-emerald-300 bg-slate-900">
                            <img :src="photoDataUrl" class="w-full h-auto max-h-72 object-contain">
                            <div class="absolute top-2 left-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500 text-white text-xs font-semibold shadow-lg">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Foto Terpilih
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="retakePhoto()" class="inline-flex items-center gap-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Ambil Ulang
                            </button>
                            <label class="inline-flex items-center gap-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                Upload Lain
                                <input type="file" accept="image/*" @change="onFileSelect($event)" class="hidden">
                            </label>
                        </div>
                    </div>

                    {{-- Camera error --}}
                    <div x-show="cameraError" x-cloak class="mt-2 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-xs flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span x-text="cameraError"></span>
                    </div>
                </div>

                {{-- Step 2: GPS --}}
                <div class="mb-5 pb-5 border-b border-slate-200">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-slate-900 flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition" :class="coords ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'">2</span>
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Ambil Lokasi GPS
                        </p>
                        <span x-show="coords" class="inline-flex items-center gap-1 text-xs font-medium" :class="withinRadius ? 'text-emerald-600' : 'text-red-600'">
                            <template x-if="withinRadius === true">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Dalam jangkauan
                                </span>
                            </template>
                            <template x-if="withinRadius === false">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Di luar jangkauan
                                </span>
                            </template>
                        </span>
                    </div>
                    <button type="button" @click="getLocation()" class="bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Ambil Lokasi Saya
                    </button>
                    <div class="mt-3 text-sm" x-show="coords">
                        <p class="text-xs text-slate-500">Lat: <span class="font-mono" x-text="coords?.lat?.toFixed(6)"></span>, Lng: <span class="font-mono" x-text="coords?.lng?.toFixed(6)"></span></p>
                        <p class="text-xs text-slate-500 mt-1" x-show="distance !== null">
                            Jarak ke titik kantor: <strong class="text-slate-900" x-text="Math.round(distance)"></strong>m
                            <span class="text-slate-400">(radius: <span x-text="office?.radius"></span>m)</span>
                        </p>
                    </div>
                    <div x-show="gpsError" class="mt-2 text-xs text-red-600" x-text="gpsError"></div>
                </div>

                {{-- Step 3: Demo mode toggle --}}
                <div class="mb-5 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                    <label class="flex items-start gap-2 text-xs text-amber-900 cursor-pointer">
                        <input type="checkbox" x-model="demoMode" class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                        <span>
                            <strong>Demo Mode</strong> — bypass geofence (abaikan cek jarak ke kantor). Centang ini kalau lagi testing di tempat yang jauh dari lokasi kantor. <strong>JANGAN</strong> dipakai di produksi.
                        </span>
                    </label>
                </div>

                {{-- Step 4: Submit --}}
                <div>
                    <p class="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition" :class="canSubmit ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'">3</span>
                        Submit Absen
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @if(!$todayAttendance || !$todayAttendance->hasClockedIn())
                            <button type="button" @click="submit('{{ route('attendance.clockIn') }}')"
                                :disabled="!canSubmit || submitting"
                                class="bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2.5 rounded-lg font-semibold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer flex items-center gap-2 shadow-sm">
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                                <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span x-text="submitting ? 'Memproses...' : 'Absen Masuk'"></span>
                            </button>
                        @endif
                        @if($todayAttendance && $todayAttendance->hasClockedIn() && !$todayAttendance->hasClockedOut())
                            <button type="button" @click="submit('{{ route('attendance.clockOut') }}')"
                                :disabled="!canSubmit || submitting"
                                class="bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white px-5 py-2.5 rounded-lg font-semibold disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer flex items-center gap-2 shadow-sm">
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                <span x-text="submitting ? 'Memproses...' : 'Absen Pulang'"></span>
                            </button>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-3 flex items-center gap-1.5" x-show="!canSubmit && (photoDataUrl || coords)">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <span x-show="!photoDataUrl">Ambil foto dulu</span>
                        <span x-show="photoDataUrl && !coords">Ambil lokasi dulu</span>
                        <span x-show="photoDataUrl && coords && !demoMode && withinRadius === false">Di luar jangkauan — enable Demo Mode untuk bypass</span>
                    </p>
                </div>
            </div>
        @endif
    </div>

    {{-- History --}}
    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
            Riwayat 14 Hari Terakhir
        </h2>
        @if($history->isEmpty())
            <div class="py-8 text-center">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <p class="text-sm text-slate-500">Belum ada riwayat absensi.</p>
            </div>
        @else
            <div class="overflow-x-auto -mx-6">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-6 font-semibold">Tanggal</th>
                            <th class="py-3 px-3 font-semibold">Masuk</th>
                            <th class="py-3 px-3 font-semibold">Pulang</th>
                            <th class="py-3 px-6 font-semibold text-right">Foto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $h)
                            <tr class="border-b last:border-0 hover:bg-slate-50/50">
                                <td class="py-3 px-6 text-slate-700">{{ $h->date->translatedFormat('D, d M') }}</td>
                                <td class="py-3 px-3 font-mono text-emerald-600">{{ $h->clock_in_time?->format('H:i') ?? '—' }}</td>
                                <td class="py-3 px-3 font-mono text-indigo-600">{{ $h->clock_out_time?->format('H:i') ?? '—' }}</td>
                                <td class="py-3 px-6 text-right">
                                    <div class="inline-flex gap-1.5 justify-end">
                                        @if($h->clock_in_photo)
                                            <a href="{{ asset('storage/' . $h->clock_in_photo) }}" target="_blank" title="Lihat foto masuk"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14" /></svg>
                                                Masuk
                                            </a>
                                        @endif
                                        @if($h->clock_out_photo)
                                            <a href="{{ asset('storage/' . $h->clock_out_photo) }}" target="_blank" title="Lihat foto pulang"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                                                Pulang
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@push('head')
<script>
function attendanceApp() {
    return {
        photoDataUrl: null,
        cameraActive: false,
        cameraError: null,
        hasMultipleCameras: false,
        currentFacingMode: 'user',
        _stream: null,
        coords: null,
        distance: null,
        withinRadius: null,
        gpsError: null,
        demoMode: false,
        submitting: false,
        office: @json($office ? ['lat' => (float)$office->latitude, 'lng' => (float)$office->longitude, 'radius' => $office->radius_meters] : null),
        csrf: '{{ csrf_token() }}',

        init() {
            this.detectCameras();
        },

        async detectCameras() {
            try {
                if (!navigator.mediaDevices?.enumerateDevices) return;
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoInputs = devices.filter(d => d.kind === 'videoinput');
                this.hasMultipleCameras = videoInputs.length > 1;
            } catch (e) { /* ignore */ }
        },

        async startCamera() {
            this.cameraError = null;
            try {
                if (!navigator.mediaDevices?.getUserMedia) {
                    this.cameraError = 'Browser tidak mendukung akses kamera. Gunakan upload file.';
                    return;
                }
                if (this._stream) this.stopCamera();
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this.currentFacingMode, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false,
                });
                this._stream = stream;
                this.$refs.video.srcObject = stream;
                this.cameraActive = true;
                // detect again (labels need permission to be exposed)
                this.detectCameras();
            } catch (e) {
                if (e.name === 'NotAllowedError') {
                    this.cameraError = 'Akses kamera ditolak. Klik ikon kunci/kamera di address bar browser untuk mengizinkan, lalu coba lagi. Atau gunakan upload file.';
                } else if (e.name === 'NotFoundError') {
                    this.cameraError = 'Tidak ada kamera terdeteksi di perangkat ini. Gunakan upload file.';
                } else {
                    this.cameraError = 'Gagal akses kamera: ' + e.message;
                }
            }
        },

        stopCamera() {
            if (this._stream) {
                this._stream.getTracks().forEach(t => t.stop());
                this._stream = null;
            }
            if (this.$refs.video) this.$refs.video.srcObject = null;
            this.cameraActive = false;
        },

        async switchCamera() {
            this.currentFacingMode = this.currentFacingMode === 'user' ? 'environment' : 'user';
            await this.startCamera();
        },

        async capturePhoto() {
            if (!this._stream || !this.$refs.video.videoWidth) {
                this.cameraError = 'Kamera belum siap. Tunggu sebentar lalu coba lagi.';
                return;
            }
            // Flash effect
            this._flash = true;
            setTimeout(() => this._flash = false, 200);

            const canvas = this.$refs.canvas;
            canvas.width = this.$refs.video.videoWidth;
            canvas.height = this.$refs.video.videoHeight;
            const ctx = canvas.getContext('2d');
            // Un-mirror if front camera (so captured photo matches preview without mirror)
            if (this.currentFacingMode === 'user') {
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(this.$refs.video, 0, 0);
            this.photoDataUrl = canvas.toDataURL('image/jpeg', 0.85);
            this.stopCamera();
        },

        retakePhoto() {
            this.photoDataUrl = null;
            this.startCamera();
        },

        onFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                this.photoDataUrl = ev.target.result;
                this.stopCamera();
            };
            reader.readAsDataURL(file);
        },

        getLocation() {
            this.gpsError = null;
            if (!navigator.geolocation) {
                this.gpsError = 'Browser tidak support GPS';
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    if (this.office) {
                        this.distance = haversine(this.coords.lat, this.coords.lng, this.office.lat, this.office.lng);
                        this.withinRadius = this.distance <= this.office.radius;
                    }
                },
                (err) => {
                    this.gpsError = 'GPS error: ' + err.message + ' — Mohon izinkan akses lokasi di browser.';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        get canSubmit() {
            if (!this.photoDataUrl || !this.coords) return false;
            if (this.demoMode) return true;
            return this.withinRadius === true;
        },

        dataUrlToBlob(dataUrl) {
            const arr = dataUrl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while(n--) u8arr[n] = bstr.charCodeAt(n);
            return new Blob([u8arr], { type: mime });
        },

        async submit(url) {
            if (!this.canSubmit || this.submitting) return;
            this.submitting = true;
            try {
                const formData = new FormData();
                formData.append('_token', this.csrf);
                formData.append('latitude', this.coords.lat);
                formData.append('longitude', this.coords.lng);
                formData.append('photo', this.dataUrlToBlob(this.photoDataUrl), 'absen.jpg');
                if (this.demoMode) formData.append('demo_mode', '1');

                const resp = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const data = await resp.json().catch(() => ({}));

                if (resp.ok) {
                    window.location.reload();
                } else {
                    alert('Gagal: ' + (data.message || data.error || resp.statusText));
                    this.submitting = false;
                }
            } catch (e) {
                alert('Error: ' + e.message);
                this.submitting = false;
            }
        },
    };
}

function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = (x) => x * Math.PI / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng/2)**2;
    return 2 * R * Math.asin(Math.sqrt(a));
}
</script>
@endpush
@endsection
