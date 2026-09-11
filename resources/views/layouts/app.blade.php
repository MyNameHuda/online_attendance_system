<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — @yield('title', 'Dashboard')</title>

    {{-- Inter font (Professional/Corporate pairing) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Tailwind via CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50:  '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                    },
                    boxShadow: {
                        'soft': '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
                        'card': '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                        'card-hover': '0 4px 12px -2px rgb(15 23 42 / 0.08), 0 2px 4px -1px rgb(15 23 42 / 0.04)',
                    },
                },
            },
        }
    </script>

    {{-- Alpine.js --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        html { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        body { font-feature-settings: 'cv11', 'ss01', 'ss03'; -webkit-font-smoothing: antialiased; }

        /* Global focus ring */
        *:focus-visible {
            outline: 2px solid #4f46e5;
            outline-offset: 2px;
            border-radius: 0.25rem;
        }
        button:focus-visible, a:focus-visible {
            outline-offset: 1px;
        }

        /* Smooth transitions */
        a, button, input, select, textarea {
            transition: background-color 150ms ease, color 150ms ease, border-color 150ms ease, box-shadow 150ms ease, transform 150ms ease;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Reduce motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col antialiased">
@auth
    <nav class="bg-white/90 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-lg text-slate-900 hover:text-brand-600">
                        <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span class="hidden sm:inline">Attendance</span>
                    </a>
                    <div class="hidden md:flex gap-1 text-sm font-medium">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg flex items-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('dashboard') ? '!text-brand-700 !bg-brand-50' : '' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                            Dashboard
                        </a>
                        <a href="{{ route('attendance.index') }}" class="px-3 py-2 rounded-lg flex items-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('attendance.*') ? '!text-brand-700 !bg-brand-50' : '' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Absensi
                        </a>
                        @if(auth()->user()->isKaryawan())
                            <a href="{{ route('shift-swaps.index') }}" class="px-3 py-2 rounded-lg flex items-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('shift-swaps.*') ? '!text-brand-700 !bg-brand-50' : '' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                Tukar Shift
                            </a>
                            <a href="{{ route('day-off-swaps.index') }}" class="px-3 py-2 rounded-lg flex items-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('day-off-swaps.*') ? '!text-brand-700 !bg-brand-50' : '' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                Tukar Libur
                            </a>
                        @endif
                        @if(auth()->user()->isKadiv())
                            <a href="{{ route('kadiv.attendances.index') }}" class="px-3 py-2 rounded-lg flex items-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('kadiv.attendances.*') ? '!text-brand-700 !bg-brand-50' : '' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.856-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                Absensi Divisi
                            </a>
                            <a href="{{ route('kadiv.approvals.index') }}" class="px-3 py-2 rounded-lg flex items-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('kadiv.approvals.*') ? '!text-brand-700 !bg-brand-50' : '' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                Approvals
                            </a>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <div x-data="{ open: false }" class="relative" @click.away="open = false">
                                <button @click="open = !open" class="px-3 py-2 rounded-lg flex items-center gap-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('admin.*') ? '!text-brand-700 !bg-brand-50' : '' }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    Admin
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-card-hover border border-slate-200 py-1.5 text-sm">
                                    <a href="{{ route('admin.employees.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                        Karyawan
                                    </a>
                                    <a href="{{ route('admin.divisions.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        Divisi
                                    </a>
                                    <a href="{{ route('admin.shifts.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Shift
                                    </a>
                                    <a href="{{ route('admin.schedules.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        Jadwal
                                    </a>
                                    <a href="{{ route('admin.attendances.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        Daftar Absensi
                                    </a>
                                    <a href="{{ route('admin.office-locations.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        Lokasi Kantor
                                    </a>
                                    <a href="{{ route('admin.holidays.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        Hari Libur
                                    </a>
                                    <a href="{{ route('admin.reports.monthly') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                        Laporan Bulanan
                                    </a>
                                    <a href="{{ route('admin.swap-requests.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        Timeline Pengajuan
                                    </a>
                                    <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                        Audit Log
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    {{-- Notification bell --}}
                    @php $unreadCount = \App\Models\Notification::unreadCountFor(auth()->id()); @endphp
                    <div x-data="{ open: false }" class="relative" @click.away="open = false">
                        <button @click="open = !open" class="relative text-slate-500 hover:text-slate-700 p-2 rounded-lg hover:bg-slate-100" title="Notifikasi">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            @if($unreadCount > 0)
                                <span class="absolute top-0.5 right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                            @endif
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-card-hover border border-slate-200 z-50 max-h-96 overflow-y-auto">
                            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                                <p class="font-semibold text-sm text-slate-900">Notifikasi</p>
                                @if($unreadCount > 0)
                                    <form method="POST" action="{{ route('notifications.markAllRead') }}" class="inline">
                                        @csrf
                                        <button class="text-xs text-brand-600 hover:underline">Tandai semua dibaca</button>
                                    </form>
                                @endif
                            </div>
                            @php
                                $recentNotifs = \App\Models\Notification::where('user_id', auth()->id())
                                    ->orderByDesc('created_at')
                                    ->limit(8)
                                    ->get();
                            @endphp
                            @if($recentNotifs->isEmpty())
                                <p class="px-4 py-6 text-center text-sm text-slate-500">Tidak ada notifikasi</p>
                            @else
                                <div class="divide-y divide-slate-100">
                                    @foreach($recentNotifs as $n)
                                        <a href="{{ $n->action_url ?: '#' }}" class="block px-4 py-3 hover:bg-slate-50 {{ $n->isRead() ? '' : 'bg-brand-50/50' }}">
                                            <p class="text-sm font-medium text-slate-900 {{ $n->isRead() ? '' : 'font-semibold' }}">{{ $n->title }}</p>
                                            <p class="text-xs text-slate-600 mt-0.5">{{ Str::limit($n->message, 80) }}</p>
                                            <p class="text-[10px] text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ route('notifications.index') }}" class="block px-4 py-2.5 text-center text-sm font-medium text-brand-600 hover:bg-slate-50 border-t border-slate-100">Lihat semua</a>
                            @endif
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 text-slate-600">
                        <span class="font-medium">{{ auth()->user()->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            @if(auth()->user()->isAdmin()) bg-amber-100 text-amber-800
                            @elseif(auth()->user()->isKadiv()) bg-purple-100 text-purple-800
                            @else bg-slate-100 text-slate-700
                            @endif
                        ">{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-slate-500 hover:text-red-600 p-2 rounded-lg hover:bg-red-50" title="Logout">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div class="md:hidden border-t border-slate-200 px-2 py-2 overflow-x-auto">
            <div class="flex gap-1 text-xs font-medium">
                <a href="{{ route('dashboard') }}" class="px-2.5 py-1.5 rounded-md text-slate-600 {{ request()->routeIs('dashboard') ? '!text-brand-700 !bg-brand-50' : '' }}">Dashboard</a>
                <a href="{{ route('attendance.index') }}" class="px-2.5 py-1.5 rounded-md text-slate-600 {{ request()->routeIs('attendance.*') ? '!text-brand-700 !bg-brand-50' : '' }}">Absensi</a>
                @if(auth()->user()->isKaryawan())
                    <a href="{{ route('shift-swaps.index') }}" class="px-2.5 py-1.5 rounded-md text-slate-600 {{ request()->routeIs('shift-swaps.*') ? '!text-brand-700 !bg-brand-50' : '' }}">Shift</a>
                    <a href="{{ route('day-off-swaps.index') }}" class="px-2.5 py-1.5 rounded-md text-slate-600 {{ request()->routeIs('day-off-swaps.*') ? '!text-brand-700 !bg-brand-50' : '' }}">Libur</a>
                @endif
                @if(auth()->user()->isKadiv())
                    <a href="{{ route('kadiv.attendances.index') }}" class="px-2.5 py-1.5 rounded-md text-slate-600 {{ request()->routeIs('kadiv.*') ? '!text-brand-700 !bg-brand-50' : '' }}">Absensi Divisi</a>
                @endif
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.employees.index') }}" class="px-2.5 py-1.5 rounded-md text-slate-600 {{ request()->routeIs('admin.*') ? '!text-brand-700 !bg-brand-50' : '' }}">Admin</a>
                @endif
            </div>
        </div>
    </nav>
@endauth

<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition.opacity x-init="setTimeout(() => show = false, 5000)"
            class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-soft">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="flex-1">{{ session('success') }}</span>
            <button @click="show = false" class="text-emerald-600 hover:text-emerald-800">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif
    @if($errors->any() && !request()->routeIs(['attendance.*', 'shift-swaps.*', 'day-off-swaps.*']))
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-3 shadow-soft">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <ul class="list-disc pl-5 flex-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="text-center text-xs text-slate-400 py-6 mt-auto">
    <p>Online Attendance System &middot; Studi Kasus Web Developer</p>
</footer>
</body>
</html>
