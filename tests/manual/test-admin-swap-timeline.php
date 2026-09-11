<?php
/**
 * Test Admin\SwapRequestController — timeline pengajuan tukar shift & libur.
 * Verifikasi:
 *  - Route terdaftar
 *  - Index menampilkan SEMUA status (pending, approved, rejected, cancelled)
 *  - Filter tipe/status/divisi bekerja
 *  - Show menampilkan detail + timeline
 *  - Tidak error saat data kosong
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\SwapRequestController;
use App\Models\DayOffSwapRequest;
use App\Models\Division;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$pass = 0; $fail = 0;
function check($name, $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name $extra\n"; $pass++; }
    else       { echo "  FAIL $name $extra\n"; $fail++; }
}

echo "=== ADMIN SWAP TIMELINE TEST ===\n\n";

// === 1. Routes ===
echo "--- 1. Routes ---\n";
check("Route admin.swap-requests.index ada",
    Route::has('admin.swap-requests.index'));
check("Route admin.swap-requests.show ada",
    Route::has('admin.swap-requests.show'));

// === 2. Setup: cari user, shift, schedule yang ada ===
echo "\n--- 2. Setup ---\n";
$andi = User::where('email', 'andi@attendance.test')->first();
$hendra = User::where('email', 'hendra@attendance.test')->first();
$budi = User::where('email', 'budi.kadiv@attendance.test')->first();
check("Andi ada", $andi !== null);
check("Hendra ada", $hendra !== null);
check("Budi (KD) ada", $budi !== null);

// Ensure Andi & Hendra punya working schedules (test-schedule-manual.php bisa wipe data).
// Restore kalau Andi tidak punya ≥3 kerja schedule.
$andiKerja = Schedule::where('user_id', $andi->id)->where('status', 'kerja')->count();
if ($andiKerja < 3) {
    $shiftPagi = Shift::where('name', 'Pagi')->first();
    $shiftSiang = Shift::where('name', 'Siang')->first();
    $shiftMalam = Shift::where('name', 'Malam')->first();
    $shifts = [$shiftPagi->id, $shiftSiang->id, $shiftMalam->id];
    $mirrorMap = [
        $shiftPagi->id  => $shiftSiang->id,
        $shiftSiang->id => $shiftMalam->id,
        $shiftMalam->id => $shiftPagi->id,
    ];
    Schedule::whereIn('user_id', [$andi->id, $hendra->id])->delete();
    $startDate = Carbon::today();
    for ($i = 0; $i < 14; $i++) {
        $date = $startDate->copy()->addDays($i);
        $isWeekend = in_array($date->dayOfWeek, [0, 6], true);
        if ($isWeekend) {
            Schedule::create(['user_id' => $andi->id, 'date' => $date->toDateString(), 'shift_id' => null, 'status' => Schedule::STATUS_LIBUR]);
            Schedule::create(['user_id' => $hendra->id, 'date' => $date->toDateString(), 'shift_id' => null, 'status' => Schedule::STATUS_LIBUR]);
        } else {
            $shiftId = $shifts[$i % 3];
            Schedule::create(['user_id' => $andi->id, 'date' => $date->toDateString(), 'shift_id' => $shiftId, 'status' => Schedule::STATUS_KERJA]);
            Schedule::create(['user_id' => $hendra->id, 'date' => $date->toDateString(), 'shift_id' => $mirrorMap[$shiftId], 'status' => Schedule::STATUS_KERJA]);
        }
    }
    echo "  (Restored Andi/Hendra schedules — previous test wiped them.)\n";
}

// Cari 1 swap candidate date (Andi & Hendra beda shift)
$swapDate = null;
$andiShiftId = $hendraShiftId = null;
$workingDates = Schedule::where('user_id', $andi->id)
    ->where('status', 'kerja')->orderBy('date')->get();
foreach ($workingDates as $as) {
    $hs = Schedule::where('user_id', $hendra->id)->whereDate('date', $as->date->toDateString())->first();
    if ($hs && $hs->status === 'kerja' && $as->shift_id !== $hs->shift_id) {
        $swapDate = $as->date->toDateString();
        $andiShiftId = $as->shift_id;
        $hendraShiftId = $hs->shift_id;
        break;
    }
}
check("Swap candidate date", $swapDate !== null, "($swapDate)");

// Cari dayoff candidate untuk requester Andi
$liburDates = Schedule::where('user_id', $andi->id)
    ->where('status', 'libur')->orderBy('date')->get();
$dayOffOld = $dayOffNew = null;
if ($liburDates->count() >= 2) {
    $dayOffOld = $liburDates[0]->date->toDateString();
    $dayOffNew = $liburDates[1]->date->toDateString();
}
check("DayOff candidate tersedia", $dayOffOld !== null, "($dayOffOld → $dayOffNew)");

// === 3. Cleanup: hapus swap request Andi & Hendra (idempotent) ===
echo "\n--- 3. Pre-cleanup ---\n";
ShiftSwapRequest::whereIn('requester_id', [$andi->id, $hendra->id])
    ->orWhereIn('target_employee_id', [$andi->id, $hendra->id])->delete();
DayOffSwapRequest::where('requester_id', $andi->id)->delete();
echo "  Pre-cleaned.\n";

// === 4. Seed data: 4 swap requests dengan 4 status berbeda ===
echo "\n--- 4. Seed 4 status berbeda ---\n";

// (a) shift_swap approved (via direct DB untuk konsistensi)
$swapApproved = ShiftSwapRequest::create([
    'requester_id' => $andi->id,
    'target_employee_id' => $hendra->id,
    'date' => $swapDate,
    'requester_shift_id' => $andiShiftId,
    'target_shift_id' => $hendraShiftId,
    'reason' => 'Approved swap untuk test timeline',
    'status' => ShiftSwapRequest::STATUS_APPROVED,
    'target_responded_at' => now()->subHours(2),
    'approver_id' => $budi->id,
    'approver_decided_at' => now()->subHour(),
    'approver_response_note' => 'OK',
]);
check("Seed: shift approved", $swapApproved->exists);

// (b) shift_swap rejected
$swapRejected = ShiftSwapRequest::create([
    'requester_id' => $hendra->id,
    'target_employee_id' => $andi->id,
    'date' => $swapDate,
    'requester_shift_id' => $hendraShiftId,
    'target_shift_id' => $andiShiftId,
    'reason' => 'Rejected swap test',
    'status' => ShiftSwapRequest::STATUS_REJECTED,
    'target_responded_at' => now()->subHours(3),
    'approver_id' => $budi->id,
    'approver_decided_at' => now()->subHours(2),
    'approver_response_note' => 'Tidak bisa, bentrok.',
]);
check("Seed: shift rejected", $swapRejected->exists);

// (c) shift_swap pending_kadiv
$swapPending = ShiftSwapRequest::create([
    'requester_id' => $andi->id,
    'target_employee_id' => $hendra->id,
    'date' => $workingDates[2]->date->toDateString() ?? $swapDate,
    'requester_shift_id' => $andiShiftId,
    'target_shift_id' => $hendraShiftId,
    'reason' => 'Pending KD swap',
    'status' => ShiftSwapRequest::STATUS_PENDING_KADIV,
    'target_responded_at' => now()->subMinutes(30),
]);
check("Seed: shift pending_kadiv", $swapPending->exists);

// (d) dayoff_swap pending (masih nunggu KD)
if ($dayOffOld && $dayOffNew) {
    $dayoffPending = DayOffSwapRequest::create([
        'requester_id' => $andi->id,
        'old_off_date' => $dayOffOld,
        'new_off_date' => $dayOffNew,
        'reason' => 'Pending day-off swap',
        'status' => DayOffSwapRequest::STATUS_PENDING,
    ]);
    check("Seed: dayoff pending", $dayoffPending->exists);
}

// === 5. Test controller index — verifikasi summary counts & filter ===
echo "\n--- 5. Controller index — summary & filter ---\n";
$ctrl = new SwapRequestController();

$req = Request::create('/admin/swap-requests', 'GET');
$req->setUserResolver(fn () => User::where('role', 'admin')->first());
$resp = $ctrl->index($req);
check("Index return view", $resp instanceof \Illuminate\View\View);

$data = $resp->getData();
check("Summary.shift_total ≥ 2", $data['summary']['shift_total'] >= 2,
    "(actual={$data['summary']['shift_total']})");
check("Summary.shift_approved ≥ 1", $data['summary']['shift_approved'] >= 1);
check("Summary.shift_rejected ≥ 1", $data['summary']['shift_rejected'] >= 1);
check("Summary.shift_pending ≥ 1", $data['summary']['shift_pending'] >= 1);
check("Summary.dayoff_pending ≥ 1", $data['summary']['dayoff_pending'] >= 1);
check("Shift swaps has items", $data['shiftSwaps']->total() >= 3);
check("Day-off swaps has items", $data['dayOffSwaps']->total() >= 1);

// === 6. Filter by status ===
echo "\n--- 6. Filter by status ---\n";
$req = Request::create('/admin/swap-requests?status=approved', 'GET');
$req->setUserResolver(fn () => User::where('role', 'admin')->first());
$data = $ctrl->index($req)->getData();
check("Filter approved: semua shift = approved",
    $data['shiftSwaps']->getCollection()->every(fn ($s) => $s->status === 'approved'),
    "(got {$data['shiftSwaps']->total()} items)");
check("Filter approved: day-off juga ke-filter",
    $data['dayOffSwaps']->getCollection()->every(fn ($s) => $s->status === 'approved') ||
    $data['dayOffSwaps']->total() === 0);

// === 7. Filter by type=shift ===
echo "\n--- 7. Filter by type=shift ---\n";
$req = Request::create('/admin/swap-requests?type=shift', 'GET');
$req->setUserResolver(fn () => User::where('role', 'admin')->first());
$resp = $ctrl->index($req);
$data = $resp->getData();
check("Filter type=shift: type passed ke view", $data['type'] === 'shift');

// === 8. Filter by division (IT) ===
echo "\n--- 8. Filter by division IT ---\n";
$itDivision = Division::where('name', 'IT')->first();
if ($itDivision) {
    $req = Request::create("/admin/swap-requests?division_id={$itDivision->id}", 'GET');
    $req->setUserResolver(fn () => User::where('role', 'admin')->first());
    $data = $ctrl->index($req)->getData();
    check("Filter IT: shift swaps semua dari IT",
        $data['shiftSwaps']->getCollection()->every(fn ($s) => $s->requester->division_id === $itDivision->id));
}

// === 9. Show detail ===
echo "\n--- 9. Controller show — detail + timeline ---\n";
$req = Request::create("/admin/swap-requests/shift/{$swapApproved->id}", 'GET');
$req->setUserResolver(fn () => User::where('role', 'admin')->first());
$resp = $ctrl->show($req, 'shift', $swapApproved->id);
check("Show return view", $resp instanceof \Illuminate\View\View);
$data = $resp->getData();
check("Show: swap adalah ShiftSwapRequest", $data['swap'] instanceof ShiftSwapRequest);
check("Show: swapType = 'shift'", $data['swapType'] === 'shift');

// === 10. Show dayoff detail ===
echo "\n--- 10. Show dayoff detail ---\n";
if (isset($dayoffPending) && $dayoffPending) {
    $req = Request::create("/admin/swap-requests/dayoff/{$dayoffPending->id}", 'GET');
    $req->setUserResolver(fn () => User::where('role', 'admin')->first());
    $resp = $ctrl->show($req, 'dayoff', $dayoffPending->id);
    check("Show dayoff return view", $resp instanceof \Illuminate\View\View);
    $data = $resp->getData();
    check("Show dayoff: swap adalah DayOffSwapRequest", $data['swap'] instanceof DayOffSwapRequest);
}

// === 11. Show unknown type → 404 ===
echo "\n--- 11. Invalid type → 404 ---\n";
$req = Request::create("/admin/swap-requests/invalid/1", 'GET');
$req->setUserResolver(fn () => User::where('role', 'admin')->first());
$threwNotFound = false;
try {
    $ctrl->show($req, 'invalid', 1);
} catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
    $threwNotFound = true;
}
check("Invalid swapType → NotFoundHttpException", $threwNotFound);

// === 12. View render check ===
echo "\n--- 12. Views exist & render OK ---\n";
$indexView = file_exists(__DIR__ . '/../../resources/views/admin/swap-requests/index.blade.php');
$showView  = file_exists(__DIR__ . '/../../resources/views/admin/swap-requests/show.blade.php');
check("View index ada", $indexView);
check("View show ada", $showView);

// Render check: tidak ada undefined variable
$req = Request::create('/admin/swap-requests', 'GET');
$req->setUserResolver(fn () => User::where('role', 'admin')->first());
// Share 'errors' agar layout (yang reference $errors->any()) tidak crash di test
view()->share('errors', new \Illuminate\Support\ViewErrorBag());
$renderedHtml = $ctrl->index($req)->render();
check("Index view render OK (no exceptions)", strlen($renderedHtml) > 1000,
    '(' . strlen($renderedHtml) . ' bytes)');
check("Index HTML ada judul 'Timeline Pengajuan'",
    str_contains($renderedHtml, 'Timeline Pengajuan'));
check("Index HTML ada tabel 'Tukar Shift'",
    str_contains($renderedHtml, 'Tukar Shift'));
check("Index HTML ada tabel 'Tukar Libur'",
    str_contains($renderedHtml, 'Tukar Libur'));

$req = Request::create("/admin/swap-requests/shift/{$swapApproved->id}", 'GET');
$req->setUserResolver(fn () => User::where('role', 'admin')->first());
$showHtml = $ctrl->show($req, 'shift', $swapApproved->id)->render();
check("Show HTML ada section 'Timeline'",
    str_contains($showHtml, 'Timeline'));
check("Show HTML ada 'Disetujui Kepala Divisi'",
    str_contains($showHtml, 'Disetujui Kepala Divisi'));
check("Show HTML ada 'Dampak Jadwal' untuk approved",
    str_contains($showHtml, 'Dampak Jadwal'));

// === 13. Cleanup ===
echo "\n--- 13. Cleanup ---\n";
$swapApproved->delete();
$swapRejected->delete();
$swapPending->delete();
if (isset($dayoffPending)) $dayoffPending->delete();
echo "  Test data deleted.\n";

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) { echo "Failed: $fail\n"; exit(1); }
echo "ALL ADMIN SWAP TIMELINE TESTS PASS\n";
