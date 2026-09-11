<?php
/**
 * Test (1) login Andi & Hendra via Auth::attempt, (2) shift swap Andi <-> Hendra end-to-end.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ShiftSwapRequestController;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

$pass = 0; $fail = 0;
function check($name, $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name $extra\n"; $pass++; }
    else       { echo "  FAIL $name $extra\n"; $fail++; }
}

echo "=== LOGIN + SHIFT SWAP TEST (Andi & Hendra) ===\n\n";

// 1. Setup: Andi & Hendra ada, password 'password123'
echo "--- 1. Login verification ---\n";
$andi = User::where('email', 'andi@attendance.test')->first();
$hendra = User::where('email', 'hendra@attendance.test')->first();

check("Andi exists", $andi !== null, "(id={$andi?->id})");
check("Hendra exists", $hendra !== null, "(id={$hendra?->id})");
check("Andi role = karyawan", $andi?->role === 'karyawan');
check("Hendra role = karyawan", $hendra?->role === 'karyawan');
check("Andi & Hendra satu divisi (IT)", $andi?->division_id === $hendra?->division_id);

// Login attempts
\Illuminate\Support\Facades\Auth::logout();
$andiLogin = \Illuminate\Support\Facades\Auth::attempt(['email' => 'andi@attendance.test', 'password' => 'password123']);
check("Andi login OK", $andiLogin === true);
\Illuminate\Support\Facades\Auth::logout();
$hendraLogin = \Illuminate\Support\Facades\Auth::attempt(['email' => 'hendra@attendance.test', 'password' => 'password123']);
check("Hendra login OK", $hendraLogin === true);
\Illuminate\Support\Facades\Auth::logout();

// Wrong password harus gagal
$wrong = \Illuminate\Support\Facades\Auth::attempt(['email' => 'andi@attendance.test', 'password' => 'salah']);
check("Andi wrong password ditolak", $wrong === false);

// 2. Schedule verification: Andi & Hendra beda shift di hari kerja
echo "\n--- 2. Schedule mirror check ---\n";
$workingDates = Schedule::where('user_id', $andi->id)
    ->where('status', 'kerja')
    ->orderBy('date')
    ->get(['date', 'shift_id']);

$diffShiftDays = 0;
foreach ($workingDates as $as) {
    $hs = Schedule::where('user_id', $hendra->id)->whereDate('date', $as->date->toDateString())->first();
    if ($hs && $hs->status === 'kerja' && $as->shift_id !== $hs->shift_id) {
        $diffShiftDays++;
    }
}
check("Andi & Hendra beda shift di $diffShiftDays hari kerja", $diffShiftDays >= 3, "($diffShiftDays/{$workingDates->count()} hari kerja)");

// Pilih satu tanggal swap candidate
$swapDate = null;
foreach ($workingDates as $as) {
    $hs = Schedule::where('user_id', $hendra->id)->whereDate('date', $as->date->toDateString())->first();
    if ($hs && $hs->status === 'kerja' && $as->shift_id !== $hs->shift_id) {
        $swapDate = $as->date->toDateString();
        $andiShiftId = $as->shift_id;
        $hendraShiftId = $hs->shift_id;
        break;
    }
}
check("Swap candidate date dipilih", $swapDate !== null, "($swapDate)");
$andiShiftName = Shift::find($andiShiftId)?->name;
$hendraShiftName = Shift::find($hendraShiftId)?->name;
echo "  → Andi={$andiShiftName} (shift_id=$andiShiftId), Hendra={$hendraShiftName} (shift_id=$hendraShiftId)\n";

// 3. Cleanup: hapus swap request aktif sebelumnya
ShiftSwapRequest::whereIn('requester_id', [$andi->id, $hendra->id])
    ->orWhereIn('target_employee_id', [$andi->id, $hendra->id])
    ->delete();

// 4. Andi create swap request ke Hendra
echo "\n--- 3. Andi create swap request ---\n";
$ctrl = new ShiftSwapRequestController();
$req = Request::create('/shift-swaps', 'POST', [
    'target_employee_id' => $hendra->id,
    'date'               => $swapDate,
    'reason'             => 'Test swap dengan Hendra untuk coverage QA.',
]);
$req->setUserResolver(fn () => $andi);
$resp = $ctrl->store($req);
check("Response 302", $resp->getStatusCode() === 302, "({$resp->getStatusCode()})");

$swap = ShiftSwapRequest::where('requester_id', $andi->id)
    ->where('target_employee_id', $hendra->id)
    ->whereDate('date', $swapDate)
    ->first();
check("Swap dibuat", $swap !== null);
check("Status: pending_target", $swap?->status === 'pending_target');
check("requester_shift_id = Andi shift", $swap?->requester_shift_id === $andiShiftId);
check("target_shift_id = Hendra shift", $swap?->target_shift_id === $hendraShiftId);

// 5. Hendra ACC — sekarang auto-approved & auto-swap (tidak ada tahap KD lagi)
echo "\n--- 4. Hendra ACC (auto-approve + auto-swap) ---\n";
$req2 = Request::create("/shift-swaps/{$swap->id}/target-respond", 'POST', [
    'decision' => 'accept',
    'note'     => 'OK bisa, saya swap.',
]);
$req2->setUserResolver(fn () => $hendra);
// targetRespond pakai auth()->user() (bukan $request->user()), jadi set manual
\Auth::setUser($hendra);
$resp2 = $ctrl->targetRespond($req2, $swap);
check("Response 302", $resp2->getStatusCode() === 302);
$swap->refresh();
check("Status: approved (langsung, skip KD)", $swap->status === 'approved');
check("target_responded_at ter-set", $swap->target_responded_at !== null);
check("approver_id = Hendra (auto)", $swap->approver_id === $hendra->id);
check("approver_decided_at ter-set", $swap->approver_decided_at !== null);

// 6. Verify schedule swap: Andi dapat shift Hendra, Hendra dapat shift Andi
echo "\n--- 6. Schedule swap verification ---\n";
$andiSchedAfter = Schedule::where('user_id', $andi->id)->whereDate('date', $swapDate)->first();
$hendraSchedAfter = Schedule::where('user_id', $hendra->id)->whereDate('date', $swapDate)->first();
check("Andi sekarang punya shift Hendra ({$hendraShiftName})",
    $andiSchedAfter?->shift_id === $hendraShiftId, "(got shift_id={$andiSchedAfter?->shift_id})");
check("Hendra sekarang punya shift Andi ({$andiShiftName})",
    $hendraSchedAfter?->shift_id === $andiShiftId, "(got shift_id={$hendraSchedAfter?->shift_id})");

// 8. Cleanup
echo "\n--- 7. Cleanup ---\n";
// Restore schedules
Schedule::where('user_id', $andi->id)->whereDate('date', $swapDate)
    ->update(['shift_id' => $andiShiftId]);
Schedule::where('user_id', $hendra->id)->whereDate('date', $swapDate)
    ->update(['shift_id' => $hendraShiftId]);
$swap->delete();
echo "  Schedules & swap request restored/cleaned.\n";

// 9. Negative test: Andi cannot swap with Eko (beda divisi: IT vs Finance)
echo "\n--- 8. Negative: beda divisi ditolak ---\n";
$eko = User::where('email', 'eko@attendance.test')->first();
$workingDateAndi = Schedule::where('user_id', $andi->id)
    ->where('status', 'kerja')->whereDate('date', '>=', today())->orderBy('date')->first();
if ($workingDateAndi && $eko) {
    $reqNeg = Request::create('/shift-swaps', 'POST', [
        'target_employee_id' => $eko->id,
        'date'               => $workingDateAndi->date->toDateString(),
        'reason'             => 'test beda divisi',
    ]);
    $reqNeg->setUserResolver(fn () => $andi);
    $respNeg = $ctrl->store($reqNeg);
    check("Beda divisi → 302 (redirect back)", $respNeg->getStatusCode() === 302);
    $errors = session('errors');
    check("Ada error 'Target harus dari divisi yang sama'",
        $errors && collect($errors->all())->contains(fn ($e) => str_contains($e, 'divisi yang sama')));
    // Verify no swap was created
    $noSwap = !ShiftSwapRequest::where('requester_id', $andi->id)
        ->where('target_employee_id', $eko->id)->exists();
    check("Tidak ada swap dibuat untuk beda divisi", $noSwap);
}

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) { echo "Failed: $fail\n"; exit(1); }
echo "ALL LOGIN + SHIFT SWAP TESTS PASS\n";
