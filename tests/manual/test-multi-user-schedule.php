<?php
/**
 * Test multi-user schedule creation via existing admin.schedules.create form.
 * Covers: many employees in one submission, conflict skip, validation.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\ScheduleController;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

$pass = 0; $fail = 0;
function check($name, $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name $extra\n"; $pass++; }
    else       { echo "  FAIL $name $extra\n"; $fail++; }
}
View::share('errors', new \Illuminate\Support\ViewErrorBag());

echo "=== MULTI-USER SCHEDULE TEST ===\n\n";

// Setup: IT employees
$it = \App\Models\Division::where('name', 'IT')->first();
$employees = User::where('role', 'karyawan')->where('division_id', $it->id)->orderBy('id')->get();
$shifts = Shift::orderBy('start_time')->get();
$shiftPagi = $shifts->firstWhere('name', 'Pagi') ?? $shifts->first();

// Pre-clean: hapus schedules di masa depan yang akan dipakai test
$testDate = Carbon::today()->addDays(40)->toDateString();
Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $testDate)
    ->delete();

$baseCount = Schedule::count();
echo "Baseline: $baseCount | Test date: $testDate | Employees: " . $employees->count() . "\n\n";

$controller = new ScheduleController();

// 1. create() view renders dengan multi-select
echo "--- create() view ---\n";
$resp = $controller->create(Request::create('/admin/schedules/create', 'GET'));
check("View instance", $resp instanceof \Illuminate\View\View);
$content = $resp->render();
check("Form punya user_ids[] checkbox", str_contains($content, 'name="user_ids[]"'));
check("Ada tombol Pilih Semua", str_contains($content, 'Pilih Semua'));
check("Ada tombol Kosongkan", str_contains($content, 'Kosongkan'));

// 2. store() multi-user: harus bikin N schedules
echo "\n--- store() multi-user ---\n";
$req = Request::create('/admin/schedules', 'POST', [
    'user_ids'  => $employees->pluck('id')->all(),
    'date'      => $testDate,
    'shift_id'  => $shiftPagi->id,
    'status'    => 'kerja',
]);
$resp = $controller->store($req);
check("Response redirect 302", $resp->getStatusCode() === 302, "({$resp->getStatusCode()})");
$flash = session('success') ?? '';
check("Flash sebut 'untuk " . $employees->count() . " karyawan'", str_contains($flash, $employees->count() . ' karyawan'), "('$flash')");
$newSchedules = Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $testDate)
    ->count();
check("Schedule count bertambah = " . $employees->count(), $newSchedules === $employees->count(), "(got $newSchedules)");
// Verify semua punya shift_id dan status=kerja
$semuanyaBenar = Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $testDate)
    ->where('status', 'kerja')
    ->where('shift_id', $shiftPagi->id)
    ->count();
check("Semua punya shift_id + status kerja", $semuanyaBenar === $employees->count(), "($semuanyaBenar/{$employees->count()})");

// 3. store() dengan duplikat (1 user sudah ada jadwal) — harus skip user itu
echo "\n--- store() skip karyawan yang sudah ada jadwal ---\n";
// Bersihkan schedules step 1 di testDate, lalu pre-seed 1 schedule manual untuk karyawan pertama
Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $testDate)
    ->delete();
Schedule::create([
    'user_id' => $employees->first()->id,
    'date'    => $testDate,
    'shift_id' => $shiftPagi->id,
    'status'  => 'libur',
]);
$countBefore = Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $testDate)
    ->count();
$req2 = Request::create('/admin/schedules', 'POST', [
    'user_ids' => $employees->pluck('id')->all(),
    'date'     => $testDate,
    'shift_id' => $shiftPagi->id,
    'status'   => 'kerja',
]);
$resp = $controller->store($req2);
$countAfter = Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $testDate)
    ->count();
$flash = session('success') ?? '';
$expectedAdded = $employees->count() - 1; // skip 1
check("Schedule count naik $expectedAdded (skip 1 yg duplikat)", ($countAfter - $countBefore) === $expectedAdded, "(+$($countAfter-$countBefore))");
check("Flash sebut 'Lewati 1'", str_contains($flash, 'Lewati 1'), "('$flash')");
check("Flash sebut nama karyawan yg di-skip", str_contains($flash, $employees->first()->name), "('$flash')");
// Verify: existing schedule untuk karyawan pertama TIDAK di-overwrite (masih libur)
$firstUserSchedule = Schedule::where('user_id', $employees->first()->id)
    ->whereDate('date', $testDate)->first();
check("Schedule existing (karyawan pertama) TIDAK ditimpa", $firstUserSchedule->status === 'libur', "(status={$firstUserSchedule->status})");

// 4. Validation: kosongkan user_ids harus gagal
echo "\n--- Validation ---\n";
$validationError = false;
try {
    $badReq = Request::create('/admin/schedules', 'POST', [
        'user_ids' => [],
        'date'     => $testDate,
        'shift_id' => $shiftPagi->id,
        'status'   => 'kerja',
    ]);
    $controller->store($badReq);
} catch (\Illuminate\Validation\ValidationException $e) {
    $validationError = true;
}
check("user_ids kosong ditolak", $validationError);

// 5. Validation: status=kerja tanpa shift_id harus gagal
$req5 = Request::create('/admin/schedules', 'POST', [
    'user_ids' => [$employees->first()->id],
    'date'     => Carbon::today()->addDays(50)->toDateString(),
    'shift_id' => null,
    'status'   => 'kerja',
]);
$resp5 = $controller->store($req5);
$errors = session('errors');
check("Kerja tanpa shift_id → redirect back", $resp5->getStatusCode() === 302, "({$resp5->getStatusCode()})");
check("Error 'Shift wajib' ada di session",
    $errors && $errors->has('shift_id') && str_contains($errors->first('shift_id'), 'Shift wajib'),
    "(errors=" . ($errors ? json_encode($errors->all()) : 'null') . ")"
);
// Verify tidak ada schedule yang dibuat
$noNewSchedule = !Schedule::where('user_id', $employees->first()->id)
    ->whereDate('date', Carbon::today()->addDays(50)->toDateString())
    ->exists();
check("Tidak ada schedule yg terbuat karena shift_id kosong", $noNewSchedule);

// 6. Status=libur dengan shift_id harus auto-nullify
echo "\n--- status=libur auto-nullify shift ---\n";
$liburDate = Carbon::today()->addDays(45)->toDateString();
Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $liburDate)
    ->delete();
$req3 = Request::create('/admin/schedules', 'POST', [
    'user_ids' => $employees->pluck('id')->all(),
    'date'     => $liburDate,
    'shift_id' => $shiftPagi->id, // akan di-nullify
    'status'   => 'libur',
]);
$controller->store($req3);
$liburRows = Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $liburDate)
    ->get();
check("Semua libur + shift_id null",
    $liburRows->count() === $employees->count() && $liburRows->every(fn ($r) => $r->status === 'libur' && $r->shift_id === null)
);

// Cleanup
Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $testDate)
    ->delete();
Schedule::whereIn('user_id', $employees->pluck('id'))
    ->whereDate('date', $liburDate)
    ->delete();

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) { echo "Failed: $fail\n"; exit(1); }
echo "ALL MULTI-USER SCHEDULE TESTS PASS\n";
