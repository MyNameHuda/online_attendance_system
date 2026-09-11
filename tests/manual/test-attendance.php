<?php
/**
 * Test fitur absen end-to-end via direct controller call.
 * Lebih reliable dari HTTP test (gak keurus CSRF).
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\AttendanceController;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

$pass = 0;
$fail = 0;

function check($name, $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name $extra\n"; $pass++; }
    else       { echo "  FAIL $name $extra\n"; $fail++; }
}

function makeFakeJpeg() {
    $tmp = tempnam(sys_get_temp_dir(), 'absen_') . '.jpg';
    $jpg = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AVoP/2Q==');
    file_put_contents($tmp, $jpg);
    return $tmp;
}

function buildRequest(User $user, float $lat, float $lng, bool $demo = false): Request {
    $tmp = makeFakeJpeg();
    $uploaded = new \Illuminate\Http\UploadedFile($tmp, 'absen.jpg', 'image/jpeg', null, true);
    $req = Request::create('/attendance/clock-in', 'POST', [
        'latitude' => (string)$lat,
        'longitude' => (string)$lng,
        'demo_mode' => $demo ? '1' : '0',
    ], [], ['photo' => $uploaded]);
    $req->setUserResolver(fn () => $user);
    // Mark as AJAX to get JSON response
    $req->headers->set('Accept', 'application/json');
    $req->headers->set('X-Requested-With', 'XMLHttpRequest');
    return $req;
}

echo "=== TEST ABSENSI ===\n\n";

// 1. Set up: Andi login
$andi = User::where('email', 'andi@attendance.test')->first();
check("Andi ada", $andi !== null);
// Pastikan Andi punya schedule kerja untuk hari ini (reset kalau test sebelumnya mengubahnya via tukar libur)
$andiScheduleToday = Schedule::where('user_id', $andi->id)->whereDate('date', today())->first();
if (!$andiScheduleToday || $andiScheduleToday->status !== 'kerja') {
    Schedule::where('user_id', $andi->id)->whereDate('date', today())->update([
        'status' => 'kerja', 'shift_id' => 1,
    ]);
}
check("Andi punya schedule kerja hari ini",
    Schedule::where('user_id', $andi->id)->whereDate('date', today())->where('status', 'kerja')->exists()
);
$office = OfficeLocation::getMain();
check("Office location ada (main)", $office !== null, "({$office->latitude}, {$office->longitude}, r={$office->radius_meters}m)");

// 2. Hapus attendance lama kalau ada
Attendance::where('user_id', $andi->id)->whereDate('date', today())->delete();

// 3. TEST: clockIn di luar radius (no demo) — harus 422
echo "\n--- Clock-in di Surabaya (no demo) ---\n";
$controller = new AttendanceController();
$req = buildRequest($andi, -7.25, 112.768, false);
$resp = $controller->clockIn($req);
check("Response JSON 422", $resp->status() === 422, "(status={$resp->status()})");
$data = json_decode($resp->getContent(), true);
$msg = $data['message'] ?? '-';
check("Message sebut 'luar jangkauan'",
    str_contains(strtolower($msg), 'jangkauan'),
    "($msg)"
);
check("Tidak ada attendance dibuat",
    !Attendance::where('user_id', $andi->id)->whereDate('date', today())->exists()
);

// 4. TEST: clockIn di Surabaya (demo mode) — harus 200
echo "\n--- Clock-in di Surabaya (DEMO MODE) ---\n";
$req = buildRequest($andi, -7.25, 112.768, true);
$resp = $controller->clockIn($req);
check("Response JSON 200", $resp->status() === 200, "(status={$resp->status()})");
$data = json_decode($resp->getContent(), true);
check("Response success=true", ($data['success'] ?? false) === true);

$att = Attendance::where('user_id', $andi->id)->whereDate('date', today())->first();
check("Attendance dibuat di DB", $att !== null);
check("clock_in_time ter-set", $att && $att->clock_in_time !== null);
check("clock_in_photo ter-set", $att && $att->clock_in_photo !== null);
check("Lokasi Surabaya tersimpan",
    $att && abs((float)$att->clock_in_lat - (-7.25)) < 0.001,
    "(lat: {$att->clock_in_lat})"
);
check("File foto ada di storage", $att && Storage::disk('public')->exists($att->clock_in_photo), "(path: {$att->clock_in_photo})");

// 5. TEST: double clock-in — harus 422
echo "\n--- Double clock-in ---\n";
$req = buildRequest($andi, -7.25, 112.768, true);
$resp = $controller->clockIn($req);
check("Response JSON 422", $resp->status() === 422, "(status={$resp->status()})");
$data = json_decode($resp->getContent(), true);
$msg = $data['message'] ?? '-';
check("Message sebut 'sudah'", str_contains(strtolower($msg), 'sudah'), "($msg)");

// 6. TEST: clock-out
echo "\n--- Clock-out (demo mode) ---\n";
$controller2 = new AttendanceController();
$tmp = makeFakeJpeg();
$uploaded = new \Illuminate\Http\UploadedFile($tmp, 'absen-out.jpg', 'image/jpeg', null, true);
$req = Request::create('/attendance/clock-out', 'POST', [
    'latitude' => '-7.25',
    'longitude' => '112.768',
    'demo_mode' => '1',
], [], ['photo' => $uploaded]);
$req->setUserResolver(fn () => $andi);
$req->headers->set('Accept', 'application/json');
$resp = $controller2->clockOut($req);
check("Clock-out 200", $resp->status() === 200, "(status={$resp->status()})");
$att->refresh();
check("clock_out_time ter-set", $att->clock_out_time !== null);
check("clock_out_photo ter-set", $att->clock_out_photo !== null);

// 7. TEST: double clock-out
echo "\n--- Double clock-out ---\n";
$tmp = makeFakeJpeg();
$uploaded = new \Illuminate\Http\UploadedFile($tmp, 'absen-out2.jpg', 'image/jpeg', null, true);
$req = Request::create('/attendance/clock-out', 'POST', [
    'latitude' => '-7.25',
    'longitude' => '112.768',
    'demo_mode' => '1',
], [], ['photo' => $uploaded]);
$req->setUserResolver(fn () => $andi);
$req->headers->set('Accept', 'application/json');
$resp = $controller2->clockOut($req);
check("Double clock-out 422", $resp->status() === 422, "(status={$resp->status()})");

// 8. TEST: absen di hari libur (weekend) — harus 422
echo "\n--- Absen di hari libur ---\n";
$liburDate = Schedule::where('user_id', $andi->id)
    ->where('status', 'libur')
    ->whereDate('date', '>=', today())
    ->orderBy('date')
    ->first();
if ($liburDate) {
    // Set jadwal Andi hari ini jadi libur (simulasi weekend), test clock-in harus ditolak,
    // lalu kembalikan ke kerja agar test lain berikutnya tidak terpengaruh.
    $backupStatus = Schedule::where('user_id', $andi->id)->whereDate('date', today())->value('status');
    $backupShift  = Schedule::where('user_id', $andi->id)->whereDate('date', today())->value('shift_id');
    Schedule::where('user_id', $andi->id)->whereDate('date', today())->update(['status' => 'libur', 'shift_id' => null]);
    // Hapus attendance hari ini dulu agar bisa test
    Attendance::where('user_id', $andi->id)->whereDate('date', today())->delete();

    $tmp = makeFakeJpeg();
    $uploaded = new \Illuminate\Http\UploadedFile($tmp, 'absen.jpg', 'image/jpeg', null, true);
    $req = Request::create('/attendance/clock-in', 'POST', [
        'latitude' => '-7.25',
        'longitude' => '112.768',
        'demo_mode' => '1',
    ], [], ['photo' => $uploaded]);
    $req->setUserResolver(fn () => $andi);
    $req->headers->set('Accept', 'application/json');
    $resp = $controller2->clockIn($req);
    check("Clock-in di hari libur -> 422", $resp->status() === 422, "(status={$resp->status()})");
    $data = json_decode($resp->getContent(), true);
    $msg = $data['message'] ?? '-';
    check("Message sebut 'libur'", str_contains(strtolower($msg), 'libur'), "($msg)");

    // Restore jadwal Andi hari ini
    Schedule::where('user_id', $andi->id)->whereDate('date', today())->update(['status' => $backupStatus, 'shift_id' => $backupShift]);
}

// 9. Cleanup
Attendance::where('user_id', $andi->id)->whereDate('date', today())->delete();

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) exit(1);
echo "ALL ATTENDANCE TESTS PASSED\n";
