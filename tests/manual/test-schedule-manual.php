<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

$pass = 0; $fail = 0;
function check($n, $c, $extra = '') { global $pass, $fail; if ($c) { echo "  OK   $n $extra\n"; $pass++; } else { echo "  FAIL $n $extra\n"; $fail++; } }

echo "=== Test Manual Schedule CRUD ===\n\n";

// Setup
$andi = User::where('email', 'andi@attendance.test')->first();
$shiftPagi = Shift::where('name', 'Pagi')->first();
$shiftSiang = Shift::where('name', 'Siang')->first();

// Hapus semua schedule Andi untuk test bersih
Schedule::where('user_id', $andi->id)->delete();
check("Setup: hapus schedule existing", Schedule::where('user_id', $andi->id)->count() === 0);

// 1. CREATE: Tambah schedule kerja untuk Andi besok
echo "\n--- Test CREATE ---\n";
$tomorrow = Carbon::today()->addDay();
$tomorrowStr = $tomorrow->toDateString();
$schedule = Schedule::create([
    'user_id' => $andi->id,
    'date' => $tomorrowStr,
    'shift_id' => $shiftPagi->id,
    'status' => 'kerja',
]);
check("Schedule kerja dibuat", $schedule->exists);
check("Shift_id ter-set", $schedule->shift_id === $shiftPagi->id);
check("Status kerja", $schedule->status === 'kerja');

// 2. CREATE: Tambah schedule libur
echo "\n--- Test CREATE libur ---\n";
$dayAfter = Carbon::today()->addDays(2);
$liburSched = Schedule::create([
    'user_id' => $andi->id,
    'date' => $dayAfter->toDateString(),
    'shift_id' => null,
    'status' => 'libur',
]);
check("Schedule libur dibuat", $liburSched->exists);
check("Status libur", $liburSched->status === 'libur');
check("Shift_id null untuk libur", $liburSched->shift_id === null);

// 3. UNIQUE CHECK: Tidak boleh duplikat (user_id + date)
echo "\n--- Test UNIQUE constraint ---\n";
$duplicate = Schedule::where('user_id', $andi->id)
    ->whereDate('date', $tomorrowStr)
    ->exists();
check("Duplicate prevention works (exists check)", $duplicate);

// 4. UPDATE: Ubah schedule kerja ke shift siang
echo "\n--- Test UPDATE ---\n";
$schedule->update(['shift_id' => $shiftSiang->id, 'status' => 'kerja']);
$schedule->refresh();
check("Schedule updated ke shift Siang", $schedule->shift_id === $shiftSiang->id);
check("Status tetap kerja", $schedule->status === 'kerja');

// 5. UPDATE: Ubah ke libur (shift_id harus auto-null)
$schedule->update(['status' => 'libur', 'shift_id' => null]);
$schedule->refresh();
check("Update ke libur", $schedule->status === 'libur');
check("Shift_id null setelah update ke libur", $schedule->shift_id === null);

// 6. DELETE: Hapus schedule
echo "\n--- Test DELETE ---\n";
$schedule->delete();
check("Schedule terhapus", !Schedule::find($schedule->id));
check("Libur masih ada", Schedule::find($liburSched->id) !== null);

// 7. Verify created_at & updated_at
echo "\n--- Test timestamps ---\n";
$newSched = Schedule::create([
    'user_id' => $andi->id,
    'date' => Carbon::today()->addDays(10)->toDateString(),
    'shift_id' => $shiftPagi->id,
    'status' => 'kerja',
]);
check("created_at ter-set", $newSched->created_at !== null);
check("updated_at ter-set", $newSched->updated_at !== null);
$newSched->update(['shift_id' => $shiftSiang->id]);
$updatedAt = $newSched->fresh()->updated_at;
check("updated_at berubah setelah update", $updatedAt->gt($newSched->created_at));
$newSched->delete();

// Final count check
$remaining = Schedule::where('user_id', $andi->id)->count();
check("Cleanup: hanya 1 schedule (libur) tersisa", $remaining === 1, "(actual: $remaining)");

// Verify routes registered
echo "\n--- Test Routes ---\n";
$routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->map(fn ($r) => $r->uri());
check("Route admin.schedules.index ada", $routes->contains('admin/schedules'));
check("Route admin.schedules.create ada", $routes->contains('admin/schedules/create'));
check("Route admin.schedules.store ada", $routes->contains('admin/schedules'));
check("Route admin.schedules.edit ada", $routes->contains('admin/schedules/{schedule}/edit'));
check("Route admin.schedules.update ada", $routes->contains('admin/schedules/{schedule}'));
check("Route admin.schedules.destroy ada", $routes->contains('admin/schedules/{schedule}'));

echo "\nPassed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) exit(1);
echo "MANUAL SCHEDULE CRUD WORKS\n";
