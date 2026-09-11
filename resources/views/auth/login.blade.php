@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="min-h-[80vh] flex items-center justify-center -mt-8">
    <div class="w-full max-w-md">
        {{-- Brand mark --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-500/20 mb-4">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Online Attendance</h1>
            <p class="text-sm text-slate-500 mt-1">Sistem absensi karyawan</p>
        </div>

        {{-- Login card --}}
        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-8">
            <h2 class="text-lg font-semibold text-slate-900 mb-6">Masuk ke akun Anda</h2>

            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm flex items-start gap-2">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                        </span>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm placeholder:text-slate-400"
                            placeholder="nama@perusahaan.com">
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input id="password" type="password" name="password" required
                            class="w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm placeholder:text-slate-400"
                            placeholder="••••••••">
                    </div>
                </div>
                <label class="flex items-center text-sm text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="ml-2">Ingat saya</span>
                </label>
                <button type="submit" class="w-full bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white py-2.5 rounded-lg font-semibold shadow-sm hover:shadow-md focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 cursor-pointer">
                    Masuk
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('password.request') }}" class="text-sm text-brand-600 hover:underline font-medium">Lupa password?</a>
            </div>
        </div>

        {{-- Demo accounts --}}
        <details class="mt-6 bg-white rounded-2xl shadow-soft border border-slate-200 overflow-hidden group">
            <summary class="px-6 py-4 cursor-pointer flex items-center justify-between text-sm font-medium text-slate-700 hover:bg-slate-50">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Akun demo untuk testing
                </span>
                <svg class="w-4 h-4 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </summary>
            <div class="px-6 pb-5 text-xs text-slate-600 space-y-2 border-t border-slate-100 pt-4">
                <p class="text-slate-500">Password untuk semua akun: <code class="px-1.5 py-0.5 bg-slate-100 rounded font-mono">password123</code></p>
                <ul class="space-y-1.5">
                    <li class="flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-400"></span>
                        <strong class="font-semibold">Admin / HRD:</strong>
                        <code class="px-1.5 py-0.5 bg-slate-100 rounded font-mono">admin@attendance.test</code>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-purple-400"></span>
                        <strong class="font-semibold">KD IT:</strong>
                        <code class="px-1.5 py-0.5 bg-slate-100 rounded font-mono">budi.kadiv@attendance.test</code>
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-slate-400"></span>
                        <strong class="font-semibold">Karyawan IT:</strong>
                        <code class="px-1.5 py-0.5 bg-slate-100 rounded font-mono">andi@attendance.test</code>
                    </li>
                </ul>
            </div>
        </details>
    </div>
</div>
@endsection
