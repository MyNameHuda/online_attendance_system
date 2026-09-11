@extends('layouts.app')
@section('title', 'Notifikasi')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Notifikasi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Semua pemberitahuan untuk Anda</p>
        </div>
        @if($notifications->where('read_at', null)->count() > 0)
            <form method="POST" action="{{ route('notifications.markAllRead') }}">
                @csrf
                <button class="inline-flex items-center gap-1.5 bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 rounded-lg text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Tandai semua dibaca
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-card border border-slate-200 overflow-hidden">
        @if($notifications->isEmpty())
            <div class="py-16 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                <p class="text-sm font-medium text-slate-700">Belum ada notifikasi</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($notifications as $n)
                    <li class="p-4 hover:bg-slate-50 {{ $n->isRead() ? '' : 'bg-brand-50/40' }}">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                @switch(true)
                                    @case(str_contains($n->type, 'approved')) bg-emerald-100 text-emerald-600
                                    @case(str_contains($n->type, 'rejected')) bg-red-100 text-red-600
                                    @case(str_contains($n->type, 'kadiv')) bg-amber-100 text-amber-600
                                    @default bg-brand-100 text-brand-600
                                @endswitch">
                                @if(str_contains($n->type, 'approved'))
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                @elseif(str_contains($n->type, 'rejected'))
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                @elseif(str_contains($n->type, 'kadiv'))
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm {{ $n->isRead() ? 'font-medium' : 'font-semibold' }} text-slate-900">{{ $n->title }}</p>
                                        <p class="text-sm text-slate-600 mt-1">{{ $n->message }}</p>
                                        <p class="text-xs text-slate-400 mt-1.5">{{ $n->created_at->diffForHumans() }} · {{ $n->created_at->format('d M Y H:i') }}</p>
                                    </div>
                                    @if(!$n->isRead())
                                        <span class="w-2 h-2 mt-1.5 rounded-full bg-brand-500 flex-shrink-0" title="Belum dibaca"></span>
                                    @endif
                                </div>
                                @if($n->action_url)
                                    <div class="mt-2 flex gap-2">
                                        <a href="{{ $n->action_url }}" class="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline font-medium">
                                            Lihat detail <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                        @if(!$n->isRead())
                                            <form method="POST" action="{{ route('notifications.read', $n) }}" class="inline">
                                                @csrf
                                                <button class="text-xs text-slate-500 hover:text-slate-700">Tandai dibaca</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="px-4 py-3 border-t border-slate-200">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
@endsection
