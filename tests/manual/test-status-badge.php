<?php
/**
 * Test reusable status badge partial.
 * Verify all 5 status variants render correctly with proper colors, icons, labels.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pass = 0; $fail = 0;
function check($name, $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name $extra\n"; $pass++; }
    else       { echo "  FAIL $name $extra\n"; $fail++; }
}
\View::share('errors', new \Illuminate\Support\ViewErrorBag());

echo "=== STATUS BADGE TEST ===\n\n";

$statuses = [
    'pending_target' => ['label' => 'Pending target',  'bg' => 'bg-amber-100',    'text' => 'text-amber-700'],
    'pending_kadiv'  => ['label' => 'Pending KD',      'bg' => 'bg-blue-100',     'text' => 'text-blue-700'],
    'pending'        => ['label' => 'Pending',         'bg' => 'bg-amber-100',    'text' => 'text-amber-700'],
    'approved'       => ['label' => 'Approved',        'bg' => 'bg-emerald-100',  'text' => 'text-emerald-700'],
    'rejected'       => ['label' => 'Rejected',        'bg' => 'bg-red-100',      'text' => 'text-red-700'],
    'cancelled'      => ['label' => 'Cancelled',       'bg' => 'bg-slate-200',    'text' => 'text-slate-700'],
];

foreach ($statuses as $status => $exp) {
    echo "--- Status: $status ---\n";
    $content = view('partials._status-badge', ['status' => $status, 'size' => 'md'])->render();
    check("Label '{$exp['label']}' muncul", str_contains($content, $exp['label']));
    check("Color bg '{$exp['bg']}' applied", str_contains($content, $exp['bg']));
    check("Color text '{$exp['text']}' applied", str_contains($content, $exp['text']));
    check("Punya rounded-full circle", str_contains($content, 'rounded-full'));
    check("Punya SVG icon", str_contains($content, '<svg'));
    check("Punya ring shadow", str_contains($content, 'ring-1') && str_contains($content, 'shadow-sm'));
}

// Size variants
echo "\n--- Size variants ---\n";
$smContent = view('partials._status-badge', ['status' => 'approved', 'size' => 'sm'])->render();
check("Size sm: circle w-6 h-6", str_contains($smContent, 'w-6 h-6'));
check("Size sm: icon w-3 h-3", str_contains($smContent, 'w-3 h-3'));
check("Size sm: text-xs", str_contains($smContent, 'text-xs'));

$mdContent = view('partials._status-badge', ['status' => 'approved', 'size' => 'md'])->render();
check("Size md: circle w-8 h-8", str_contains($mdContent, 'w-8 h-8'));
check("Size md: icon w-4 h-4", str_contains($mdContent, 'w-4 h-4'));

$lgContent = view('partials._status-badge', ['status' => 'approved', 'size' => 'lg'])->render();
check("Size lg: circle w-10 h-10", str_contains($lgContent, 'w-10 h-10'));
check("Size lg: icon w-5 h-5", str_contains($lgContent, 'w-5 h-5'));

// Long label (untuk halaman detail)
echo "\n--- Long label (lg + longLabel) ---\n";
$longContent = view('partials._status-badge', ['status' => 'pending_target', 'size' => 'lg', 'longLabel' => true])->render();
check("Long label 'Menunggu ACC target'", str_contains($longContent, 'Menunggu ACC target'));

// Unknown status → fallback ke pending_target styling
echo "\n--- Fallback ---\n";
$fallback = view('partials._status-badge', ['status' => 'unknown_status'])->render();
check("Unknown status fallback ke Pending target label", str_contains($fallback, 'Pending target'));

// HTML well-formedness — pastikan tidak ada double-spaces atau malformed
echo "\n--- HTML well-formed ---\n";
$content = view('partials._status-badge', ['status' => 'approved', 'size' => 'md'])->render();
check("Ada opening span", substr_count($content, '<span') >= 2); // outer + inner circle
check("Ada closing span", substr_count($content, '</span>') >= 2);
check("Ada svg close", str_contains($content, '</svg>'));

// Icon variety — setiap status punya icon berbeda (kecuali 'pending' & 'pending_target' yang by design sama-sama "waiting")
echo "\n--- Icon variety ---\n";
$iconSignatures = [];
foreach ($statuses as $status => $_) {
    $c = view('partials._status-badge', ['status' => $status, 'size' => 'md'])->render();
    preg_match('/<path[^>]+d="([^"]+)"/', $c, $m);
    $iconSignatures[$status] = $m[1] ?? '';
}
// pending & pending_target adalah alias dengan icon sama (by design). Status lain punya icon unik.
$distinctStatuses = ['pending_target', 'pending_kadiv', 'approved', 'rejected', 'cancelled']; // 5
$uniqueIcons = count(array_unique(array_intersect_key($iconSignatures, array_flip($distinctStatuses))));
check("5 status punya icon unik ($uniqueIcons unique)", $uniqueIcons === 5);
// Pending (day-off swap) reuse icon dari pending_target (waiting)
check("pending & pending_target pakai icon sama (alias)",
    $iconSignatures['pending'] === $iconSignatures['pending_target']);

// Integration: ensure the partial is wired into ALL views that show status
echo "\n--- Integration check ---\n";
$views = [
    'shift-swaps/index.blade.php'   => "shift-swaps/index pakai partial",
    'shift-swaps/show.blade.php'    => "shift-swaps/show pakai partial",
    'day-off-swaps/index.blade.php' => "day-off-swaps/index pakai partial",
    'day-off-swaps/show.blade.php'  => "day-off-swaps/show pakai partial",
    'dashboard.blade.php'           => "dashboard pakai partial",
];
foreach ($views as $path => $label) {
    $src = file_get_contents(__DIR__ . '/../../resources/views/' . $path);
    check($label, str_contains($src, "@include('partials._status-badge'"));
}

// Negative: pastikan TIDAK ada lagi inline switch yang lama di SEMUA view
echo "\n--- Old inline patterns removed ---\n";
$oldPatterns = [
    "@case('pending_target') bg-amber-100 text-amber-800",
    "@case('pending_kadiv') bg-blue-100 text-blue-800",
    "@case('pending') bg-amber-100 text-amber-800",
];
$hasOldInline = false;
foreach (array_keys($views) as $path) {
    $src = file_get_contents(__DIR__ . '/../../resources/views/' . $path);
    foreach ($oldPatterns as $p) {
        if (str_contains($src, $p)) {
            $hasOldInline = true;
            echo "  FOUND in $path: $p\n";
        }
    }
}
check("Tidak ada inline @switch status lama di semua view", !$hasOldInline);

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) { echo "Failed: $fail\n"; exit(1); }
echo "ALL STATUS BADGE TESTS PASS\n";
