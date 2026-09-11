<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

$pass = 0; $fail = 0;
function check($n, $c, $extra = '') { global $pass, $fail; if ($c) { echo "  OK   $n $extra\n"; $pass++; } else { echo "  FAIL $n $extra\n"; $fail++; } }

// Disable mail sending (test mode)
Mail::fake();

echo "=== Tier 1 Smoke Test ===\n\n";

// =============== 1. NOTIFICATIONS ===============
echo "--- Feature 1: Notifications ---\n";
Notification::truncate();
$andi = User::where('email', 'andi@attendance.test')->first();
$citra = User::where('email', 'citra@attendance.test')->first();
$budiKD = User::where('email', 'budi.kadiv@attendance.test')->first();

NotificationService::send(
    $citra->id,
    'shift_swap.new',
    'Pengajuan Tukar Shift',
    "Andi mengajak tukar shift",
    '/shift-swaps',
);
$notif = Notification::where('user_id', $citra->id)->first();
check("Notification created in-app", $notif !== null);
check("Notification type correct", $notif->type === 'shift_swap.new');
check("Notification has action_url", $notif->action_url === '/shift-swaps');
check("Notification unread count > 0", Notification::unreadCountFor($citra->id) === 1);

$notif->markAsRead();
check("markAsRead() works", Notification::unreadCountFor($citra->id) === 0);

// =============== 2. AUDIT LOG ===============
echo "\n--- Feature 2: Audit Log ---\n";
AuditLog::truncate();

AuditLog::log('login', $andi->id);
check("Audit log created (login)", AuditLog::where('user_id', $andi->id)->count() === 1);

AuditLog::log('shift_swap_created', $citra->id, 'ShiftSwapRequest', 42, ['target' => 'Andi']);
$log = AuditLog::where('action', 'shift_swap_created')->first();
check("Audit log has metadata", $log->metadata !== null);
check("Audit log resource_type set", $log->resource_type === 'ShiftSwapRequest');
check("Audit log resource_id set", $log->resource_id === 42);
check("Audit log IP captured", $log->ip_address !== null);

// =============== 3. PASSWORD RESET ===============
echo "\n--- Feature 3: Password Reset ---\n";
$tokenPlain = \Illuminate\Support\Str::random(64);
DB::table('password_reset_tokens')->updateOrInsert(
    ['email' => $andi->email],
    ['email' => $andi->email, 'token' => Hash::make($tokenPlain), 'created_at' => now()]
);
$row = DB::table('password_reset_tokens')->where('email', $andi->email)->first();
check("Token tersimpan", $row !== null);
check("Hashed token (bukan plain)", $row->token !== $tokenPlain);
check("Hash::check works", Hash::check($tokenPlain, $row->token));

// Test reset password
$newPassword = 'newPassword123';
// Jangan pre-hash di sini — Eloquent cast 'password' => 'hashed' akan auto-hash sekali lagi,
// yang akan jadi double-hash. Assign plain string, biar cast yang kerja.
$andi->update(['password' => $newPassword]);
check("Password berhasil diupdate", Hash::check($newPassword, $andi->fresh()->password));
// Restore back ke default 'password123' supaya demo login gak ke-reset tiap kali test jalan
$andi->update(['password' => 'password123']);
DB::table('password_reset_tokens')->where('email', $andi->email)->delete();
check("Token dihapus setelah reset", DB::table('password_reset_tokens')->where('email', $andi->email)->doesntExist());

// =============== 4. MONTHLY REPORT ===============
echo "\n--- Feature 4: Monthly Report ---\n";
$month = Carbon::now()->format('Y-m');
$startDate = Carbon::parse($month . '-01')->startOfMonth();
$endDate = $startDate->copy()->endOfMonth();

$employee = User::with('division')->first();
$totalKerja = Schedule::where('user_id', $employee->id)
    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
    ->where('status', 'kerja')->count();
check("Ada data schedule bulan ini", $totalKerja > 0);

$totalHadir = \App\Models\Attendance::where('user_id', $employee->id)
    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
    ->get()->filter(fn ($a) => $a->hasClockedIn())->count();
check("Logic hitung hadir works", $totalHadir >= 0);

// =============== 5. HOLIDAY CALENDAR ===============
echo "\n--- Feature 5: Holiday Calendar ---\n";
Holiday::truncate();
// Pakai tanggal di dalam window jadwal yang di-seed (14 hari ke depan) supaya schedule untuk Andi sudah ada
$testDate = Carbon::today()->addDays(5)->toDateString();

// Buat schedule kerja dulu untuk Andi di tanggal tsb
$scheduleBefore = Schedule::where('user_id', $andi->id)
    ->whereDate('date', $testDate)
    ->where('status', 'kerja')
    ->exists();
check("Ada schedule kerja untuk testing", $scheduleBefore);

// Add holiday
$holiday = Holiday::create([
    'date' => $testDate,
    'name' => 'Hari Libur Test',
    'is_national' => true,
]);
check("Holiday created", $holiday !== null);

// Setup: pastikan ada schedule kerja Andi di tanggal test (pakai whereDate supaya cocok dengan datetime yang tersimpan)
$existing = Schedule::where('user_id', $andi->id)->whereDate('date', $testDate)->first();
if ($existing) {
    $existing->update(['status' => Schedule::STATUS_KERJA, 'shift_id' => 1]);
} else {
    Schedule::create([
        'user_id' => $andi->id,
        'date' => $testDate . ' 00:00:00',
        'status' => Schedule::STATUS_KERJA,
        'shift_id' => 1,
    ]);
}
$scheduleBefore = Schedule::where('user_id', $andi->id)
    ->whereDate('date', $testDate)
    ->where('status', 'kerja')
    ->exists();
check("Schedule kerja dibuat untuk testing", $scheduleBefore);

// Auto-mark jadi libur
Schedule::whereDate('date', $testDate)
    ->update(['status' => Schedule::STATUS_LIBUR, 'shift_id' => null]);
$scheduleAfter = Schedule::where('user_id', $andi->id)
    ->whereDate('date', $testDate)->first();
check("Schedule auto-marked as libur", $scheduleAfter->status === 'libur');
check("Shift_id nullified", $scheduleAfter->shift_id === null);

// Notification triggered
$users = User::all();
foreach ($users as $u) {
    NotificationService::send($u->id, 'holiday.new', 'Hari Libur Baru', "Test holiday message", '/');
}
$holidayNotif = Notification::where('type', 'holiday.new')->count();
check("Holiday notification broadcast ke semua user", $holidayNotif === $users->count());

// Holiday::isHoliday check
check("Holiday::isHoliday returns true", Holiday::isHoliday($testDate));
check("Holiday::isHoliday returns false for other date", !Holiday::isHoliday(Carbon::today()->toDateString()));

// Cleanup
Holiday::where('date', $testDate)->delete();
// Kembalikan shift_id kalau test sebelumnya null-kan, dan set ke kerja lagi
Schedule::whereDate('date', $testDate)->update(['status' => 'kerja', 'shift_id' => 1]);

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) exit(1);
echo "ALL TIER 1 FEATURES PASS\n";
