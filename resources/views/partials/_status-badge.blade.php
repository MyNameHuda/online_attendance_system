@php
    /**
     * Reusable status badge untuk ShiftSwapRequest + DayOffSwapRequest.
     *
     * Usage:
     *   @include('partials._status-badge', ['status' => $swap->status])
     *   @include('partials._status-badge', ['status' => 'approved', 'size' => 'lg'])
     *
     * Variants:
     *   pending_target — amber,  clock icon       "Menunggu ACC target"
     *   pending_kadiv  — blue,   shield icon     "Menunggu approval KD"
     *   approved       — emerald, check icon     "Disetujui"
     *   rejected       — red,    x icon          "Ditolak"
     *   cancelled      — slate,  dash icon       "Dibatalkan"
     */
    $status    = $status ?? 'pending_target';
    $size      = $size ?? 'md'; // sm | md | lg
    $longLabel = $longLabel ?? false;

    $map = [
        'pending_target' => [
            'label' => 'Menunggu ACC target',
            'short' => 'Pending target',
            'bg'    => 'bg-amber-100',
            'text'  => 'text-amber-700',
            'ring'  => 'ring-amber-200',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'pending_kadiv' => [
            'label' => 'Menunggu approval KD',
            'short' => 'Pending KD',
            'bg'    => 'bg-blue-100',
            'text'  => 'text-blue-700',
            'ring'  => 'ring-blue-200',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
        ],
        // DayOffSwapRequest pakai status 'pending' (langsung ke KD, tanpa 2-step)
        'pending' => [
            'label' => 'Menunggu approval KD',
            'short' => 'Pending',
            'bg'    => 'bg-amber-100',
            'text'  => 'text-amber-700',
            'ring'  => 'ring-amber-200',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'approved' => [
            'label' => 'Disetujui',
            'short' => 'Approved',
            'bg'    => 'bg-emerald-100',
            'text'  => 'text-emerald-700',
            'ring'  => 'ring-emerald-200',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />',
        ],
        'rejected' => [
            'label' => 'Ditolak',
            'short' => 'Rejected',
            'bg'    => 'bg-red-100',
            'text'  => 'text-red-700',
            'ring'  => 'ring-red-200',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />',
        ],
        'cancelled' => [
            'label' => 'Dibatalkan',
            'short' => 'Cancelled',
            'bg'    => 'bg-slate-200',
            'text'  => 'text-slate-700',
            'ring'  => 'ring-slate-300',
            'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />',
        ],
    ];

    // Alias untuk konsistensi
    if ($status === 'pending_target') $map['pending'] = $map['pending_target'];
    $cfg = $map[$status] ?? $map['pending_target'];

    $sizeCfg = [
        'sm' => ['wrap' => 'gap-1.5', 'circle' => 'w-6 h-6',  'icon' => 'w-3 h-3',  'text' => 'text-xs'],
        'md' => ['wrap' => 'gap-2',   'circle' => 'w-8 h-8',  'icon' => 'w-4 h-4',  'text' => 'text-sm'],
        'lg' => ['wrap' => 'gap-2.5', 'circle' => 'w-10 h-10', 'icon' => 'w-5 h-5',  'text' => 'text-base font-semibold'],
    ][$size];

    $displayLabel = $longLabel ? $cfg['label'] : $cfg['short'];
@endphp

<span class="inline-flex items-center {{ $sizeCfg['wrap'] }} {{ $cfg['text'] }}">
    <span class="inline-flex items-center justify-center rounded-full {{ $cfg['bg'] }} {{ $sizeCfg['circle'] }} ring-1 {{ $cfg['ring'] }} shadow-sm">
        <svg class="{{ $sizeCfg['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
            {!! $cfg['icon'] !!}
        </svg>
    </span>
    <span class="font-semibold {{ $sizeCfg['text'] }}">{{ $displayLabel }}</span>
</span>
