@extends('layouts.app')
@section('title', $office ? 'Edit Lokasi Kantor' : 'Setup Lokasi Kantor')
@section('content')
<div class="max-w-4xl mx-auto space-y-4" x-data="mapPicker()" x-init="init()">
    <div>
        <a href="{{ route('admin.office-locations.index') }}" class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-6 sm:p-8">
        <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2 mb-1">
            <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </span>
            {{ $office ? 'Edit Lokasi Kantor' : 'Setup Lokasi Kantor' }}
        </h1>
        <p class="text-sm text-slate-500">Lokasi ini berlaku untuk semua divisi (kantor utama).</p>

        @if($errors->any())
            <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.office-locations.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lokasi</label>
                <input id="name" name="name" value="{{ old('name', $office?->name) }}" placeholder="Kantor Pusat Jakarta" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Lokasi di Peta</label>
                <p class="text-xs text-slate-500 mb-2">Klik di peta, atau drag marker untuk atur posisi yang tepat.</p>

                {{-- Search bar --}}
                <div class="flex gap-2 mb-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </span>
                        <input x-model="searchQuery" @keydown.enter.prevent="searchAddress()" type="text" placeholder="Cari alamat / tempat (contoh: Monas, Jakarta)"
                            class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <button type="button" @click="searchAddress()" :disabled="searching || !searchQuery" class="inline-flex items-center gap-1.5 bg-slate-700 hover:bg-slate-800 text-white px-3 py-2 rounded-lg text-sm font-medium disabled:opacity-50">
                        <svg x-show="!searching" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <svg x-show="searching" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Cari
                    </button>
                    <button type="button" @click="useMyLocation()" :disabled="locating" class="inline-flex items-center gap-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-3 py-2 rounded-lg text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Lokasi Saya
                    </button>
                </div>

                {{-- Map container --}}
                <div id="officeMap" class="w-full h-80 rounded-xl border-2 border-slate-200 z-0"></div>
                <p class="text-xs text-slate-500 mt-2" x-show="searchError" x-text="searchError" x-cloak></p>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="latitude" class="block text-sm font-medium text-slate-700 mb-1.5">Latitude <span class="text-red-500">*</span></label>
                    <input id="latitude" name="latitude" type="number" step="any" x-model="lat" @change="updateMarkerFromInput()" required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm font-mono">
                </div>
                <div>
                    <label for="longitude" class="block text-sm font-medium text-slate-700 mb-1.5">Longitude <span class="text-red-500">*</span></label>
                    <input id="longitude" name="longitude" type="number" step="any" x-model="lng" @change="updateMarkerFromInput()" required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm font-mono">
                </div>
                <div>
                    <label for="radius_meters" class="block text-sm font-medium text-slate-700 mb-1.5">Radius (m) <span class="text-red-500">*</span></label>
                    <input id="radius_meters" name="radius_meters" type="number" min="10" max="10000" x-model.number="radius" @input="updateCircle()" required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white px-5 py-2.5 rounded-lg font-semibold text-sm shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Simpan
                </button>
                <a href="{{ route('admin.office-locations.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium">Batal</a>
            </div>
        </form>
    </div>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
    /* Fix Leaflet default icon paths in Laravel */
    .leaflet-default-icon-path { background-image: url('https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png'); }
    .office-marker {
        background: #4f46e5;
        width: 28px;
        height: 28px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }
    .office-marker::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 10px;
        height: 10px;
        background: white;
        border-radius: 50%;
        transform: translate(-50%, -50%);
    }
</style>
<script>
function mapPicker() {
    return {
        lat: {{ old('latitude', $office?->latitude ?? '-6.175392') }},
        lng: {{ old('longitude', $office?->longitude ?? '106.827153') }},
        radius: {{ old('radius_meters', $office?->radius_meters ?? 200) }},
        map: null,
        marker: null,
        circle: null,
        searchQuery: '',
        searching: false,
        searchError: '',
        locating: false,

        init() {
            this.$nextTick(() => {
                this.map = L.map('officeMap').setView([this.lat, this.lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(this.map);

                const icon = L.divIcon({
                    className: '',
                    html: '<div class="office-marker"></div>',
                    iconSize: [28, 28],
                    iconAnchor: [14, 28],
                });
                this.marker = L.marker([this.lat, this.lng], { icon, draggable: true }).addTo(this.map);
                this.marker.on('dragend', () => {
                    const pos = this.marker.getLatLng();
                    this.lat = +pos.lat.toFixed(7);
                    this.lng = +pos.lng.toFixed(7);
                    this.updateCircle();
                });

                this.circle = L.circle([this.lat, this.lng], {
                    radius: this.radius,
                    color: '#4f46e5',
                    fillColor: '#4f46e5',
                    fillOpacity: 0.15,
                    weight: 2,
                }).addTo(this.map);

                this.map.on('click', (e) => {
                    this.marker.setLatLng(e.latlng);
                    this.lat = +e.latlng.lat.toFixed(7);
                    this.lng = +e.latlng.lng.toFixed(7);
                    this.updateCircle();
                });
            });
        },

        updateMarkerFromInput() {
            const lat = parseFloat(this.lat);
            const lng = parseFloat(this.lng);
            if (!isNaN(lat) && !isNaN(lng) && this.marker) {
                this.marker.setLatLng([lat, lng]);
                this.circle.setLatLng([lat, lng]);
                this.map.panTo([lat, lng]);
            }
        },

        updateCircle() {
            if (this.circle) {
                this.circle.setRadius(this.radius);
            }
        },

        async useMyLocation() {
            this.locating = true;
            this.searchError = '';
            if (!navigator.geolocation) {
                this.searchError = 'Browser tidak support GPS';
                this.locating = false;
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.lat = +lat.toFixed(7);
                    this.lng = +lng.toFixed(7);
                    this.marker.setLatLng([lat, lng]);
                    this.circle.setLatLng([lat, lng]);
                    this.map.setView([lat, lng], 17);
                    this.updateCircle();
                    this.locating = false;
                },
                (err) => {
                    this.searchError = 'Gagal GPS: ' + err.message;
                    this.locating = false;
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        },

        async searchAddress() {
            if (!this.searchQuery.trim()) return;
            this.searching = true;
            this.searchError = '';
            try {
                const resp = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(this.searchQuery)}`);
                const data = await resp.json();
                if (data.length === 0) {
                    this.searchError = 'Alamat tidak ditemukan. Coba kata kunci lain.';
                } else {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    this.lat = +lat.toFixed(7);
                    this.lng = +lng.toFixed(7);
                    this.marker.setLatLng([lat, lng]);
                    this.circle.setLatLng([lat, lng]);
                    this.map.setView([lat, lng], 17);
                    this.updateCircle();
                }
            } catch (e) {
                this.searchError = 'Gagal mencari: ' + e.message;
            }
            this.searching = false;
        },
    };
}
</script>
@endpush
@endsection
