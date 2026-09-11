@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<div class="min-h-[80vh] flex items-center justify-center -mt-8">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-500/20 mb-4">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Password Baru</h1>
            <p class="text-sm text-slate-500 mt-1">Buat password baru untuk akun Anda</p>
        </div>

        <div class="bg-white rounded-2xl shadow-card border border-slate-200 p-8">
            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password Baru</label>
                    <input id="password" type="password" name="password" required minlength="6"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                        placeholder="Minimal 6 karakter">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-brand-600 to-brand-700 hover:from-brand-700 hover:to-brand-800 text-white py-2.5 rounded-lg font-semibold shadow-sm">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
