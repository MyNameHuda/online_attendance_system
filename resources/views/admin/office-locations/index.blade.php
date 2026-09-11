@extends('layouts.app')
@section('title', 'Lokasi Kantor')
@section('content')
<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Lokasi Kantor Utama</h1>
        <p class="text-sm text-slate-500 mt-0.5">Satu titik geofence yang dipakai oleh semua divisi</p>
    </div>

    @if($office)
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
            <div class="p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $office->name ?? 'Kantor Pusat' }}</h2>
                            <span class="inline-flex items-center gap-1 text-xs text-amber-700 font-medium">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                Kantor Utama
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('admin.office-locations.edit') }}" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Edit
                    </a>
                </div>

                <div class="grid sm:grid-cols-3 gap-3">
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Latitude
                        </p>
                        <p class="font-mono text-sm font-semibold text-slate-900">{{ $office->latitude }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Longitude
                        </p>
                        <p class="font-mono text-sm font-semibold text-slate-900">{{ $office->longitude }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            Radius
                        </p>
                        <p class="font-mono text-sm font-semibold text-slate-900">{{ $office->radius_meters }}m</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
            <p class="text-sm font-medium text-slate-700 mb-1">Lokasi kantor utama belum di-set</p>
            <p class="text-xs text-slate-500 mb-4">Sistem perlu 1 titik koordinat untuk validasi GPS absensi</p>
            <a href="{{ route('admin.office-locations.edit') }}" class="inline-flex items-center gap-2 bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Setup Lokasi
            </a>
        </div>
    @endif

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3 text-sm text-blue-800">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <div>
            <p class="font-semibold mb-1">Tentang Geofencing</p>
            <p class="text-xs">Lokasi ini digunakan untuk validasi GPS saat absensi. Berlaku untuk <strong>semua karyawan di semua divisi</strong>. Jika karyawan berada di luar radius yang ditentukan, absensi akan ditolak (kecuali mode demo diaktifkan). Default: Jakarta Pusat (Monas) dengan radius 200m.</p>
        </div>
    </div>
</div>
@endsection
