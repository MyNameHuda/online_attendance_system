<?php
/**
 * Test KD attendance view: scoped to KD's division, 403 cross-division.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Kadiv\AttendanceController;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

$pass = 0; $fail = 0;
function check($name, $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name $extra\n"; $pass++; }
    else       { echo "  FAIL $name $extra\n"; $fail++; }
}
\View::share('errors', new \Illuminate\Support\ViewErrorBag());

echo "=== KADIV ATTENDANCE TEST ===\n\n";

// Setup
$itDiv = Division::where('name', 'IT')->first();
$finDiv = Division::where('name', 'Finance')->first();
$itKadiv = User::where('email', 'budi.kadiv@attendance.test')->first(); // KD IT
$finKadiv = User::where('email', 'siti.kadiv@attendance.test')->first(); // KD Finance
$andi = User::where('email', 'andi@attendance.test')->first(); // Karyawan IT
$hendra = User::where('email', 'hendra@attendance.test')->first(); // Karyawan IT
$eko = User::where('email', 'eko@attendance.test')->first(); // Karyawan Finance

echo "KD IT: $itKadiv->name (div_id=$itKadiv->division_id)\n";
echo "KD Finance: $finKadiv->name (div_id=$finKadiv->division_id)\n";
echo "IT employees: Andi, Hendra | Finance: Eko\n\n";

// Buat attendance untuk Andi IT (divisi KD IT)
$existingAndi = Attendance::where('user_id', $andi->id)
    ->whereDate('date', Carbon::today()->toDateString())
    ->first();
if (!$existingAndi) {
    Storage::disk('public')->put('attendance/test/in.jpg', 'jpg-fake');
    Storage::disk('public')->put('attendance/test/out.jpg', 'jpg-fake');
    $existingAndi = Attendance::create([
        'user_id'        => $andi->id,
        'date'           => Carbon::today()->toDateString(),
        'clock_in_time'  => now()->subHours(3),
        'clock_in_photo' => 'attendance/test/in.jpg',
        'clock_in_lat'   => -6.175392,
        'clock_in_lng'   => 106.827153,
        'clock_out_time' => now()->subHours(1),
        'clock_out_photo'=> 'attendance/test/out.jpg',
        'clock_out_lat'  => -6.175500,
        'clock_out_lng'  => 106.827200,
    ]);
}
$itAttendanceId = $existingAndi->id;

// 1. Test: index() as KD IT — bisa lihat attendance Andi & Hendra, TIDAK bisa lihat Eko
echo "--- 1. KD IT: index view ---\n";
$ctrl = new AttendanceController();
$req = Request::create('/kadiv/attendances', 'GET');
$req->setUserResolver(fn () => $itKadiv);
$resp = $ctrl->index($req);
check("Response is View", $resp instanceof \Illuminate\View\View);
$content = $resp->render();
check("Header menyebut 'Divisi IT'", str_contains($content, 'Divisi IT'));
check("Index menampilkan Andi", str_contains($content, $andi->name));
check("Index menampilkan Hendra", str_contains($content, $hendra->name));
check("Index TIDAK menampilkan Eko (bukan divisi IT)", !str_contains($content, $eko->name));
check("Ada link Detail ke show", str_contains($content, route('kadiv.attendances.show', $existingAndi)));

// 2. Test: KD IT show attendance Andi — boleh
echo "\n--- 2. KD IT: show attendance milik divisi sendiri (Andi) ---\n";
$req2 = Request::create("/kadiv/attendances/$itAttendanceId", 'GET');
$req2->setUserResolver(fn () => $itKadiv);
$resp2 = $ctrl->show($req2, $existingAndi);
check("Response is View", $resp2 instanceof \Illuminate\View\View);
$content2 = $resp2->render();
check("Show menampilkan Andi", str_contains($content2, $andi->name));
check("Show menampilkan divisi badge IT", str_contains($content2, 'Divisi IT'));

// 3. Test: KD IT mencoba show attendance Eko (cross-division) — harus 403
echo "\n--- 3. KD IT: show attendance Eko (cross-division, harus 403) ---\n";
// Eko belum punya attendance, buat dummy
$ekoAtt = Attendance::where('user_id', $eko->id)
    ->whereDate('date', Carbon::today()->subDays(2)->toDateString())
    ->first();
if (!$ekoAtt) {
    Storage::disk('public')->put('attendance/test/eko.jpg', 'jpg-fake');
    $ekoAtt = Attendance::create([
        'user_id'        => $eko->id,
        'date'           => Carbon::today()->subDays(2)->toDateString(),
        'clock_in_time'  => now()->subHours(4),
        'clock_in_photo' => 'attendance/test/eko.jpg',
        'clock_in_lat'   => -6.175392,
        'clock_in_lng'   => 106.827153,
        'clock_out_time' => null,
        'clock_out_photo'=> null,
        'clock_out_lat'  => null,
        'clock_out_lng'  => null,
    ]);
}
$req3 = Request::create("/kadiv/attendances/{$ekoAtt->id}", 'GET');
$req3->setUserResolver(fn () => $itKadiv);

$threw403 = false;
try {
    $ctrl->show($req3, $ekoAtt);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    $threw403 = $e->getStatusCode() === 403;
    check("Cross-division ditolak dengan 403", $threw403);
}
check("Show Eko sebagai KD IT → throw HttpException 403", $threw403);

// 4. Test: KD Finance mencoba show attendance Andi IT — harus 403
echo "\n--- 4. KD Finance: show attendance Andi IT (cross-division, harus 403) ---\n";
$req4 = Request::create("/kadiv/attendances/$itAttendanceId", 'GET');
$req4->setUserResolver(fn () => $finKadiv);

$threw403b = false;
try {
    $ctrl->show($req4, $existingAndi);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    $threw403b = $e->getStatusCode() === 403;
    check("Cross-division ditolak dengan 403", $threw403b);
}
check("Show Andi IT sebagai KD Finance → throw HttpException 403", $threw403b);

// 5. Test: Filter status pada index
echo "\n--- 5. KD IT: filter status ---\n";
$req5 = Request::create('/kadiv/attendances?status=lengkap', 'GET');
$req5->setUserResolver(fn () => $itKadiv);
$resp5 = $ctrl->index($req5);
check("Filter status=lengkap → View", $resp5 instanceof \Illuminate\View\View);

// 6. Test: Filter user_id pada index
echo "\n--- 6. KD IT: filter user_id=Andi ---\n";
$req6 = Request::create("/kadiv/attendances?user_id={$andi->id}", 'GET');
$req6->setUserResolver(fn () => $itKadiv);
$resp6 = $ctrl->index($req6);
$content6 = $resp6->render();
check("Filter Andi → hanya menampilkan Andi", str_contains($content6, $andi->name));
// Verify Hendra TIDAK ada di tabel (dropdown masih menampilkan semua anggota divisi = OK)
// Cek apakah di body ada Hendra di section table, bukan di dropdown filter
$tableSection = explode('<tbody', $content6)[1] ?? '';
$tableSection = explode('</tbody>', $tableSection)[0] ?? '';
check("Filter Andi → Hendra TIDAK ada di <tbody>",
    !str_contains($tableSection, $hendra->name),
    "(Hendra ada di dropdown filter saja — itu OK)");

// 7. Test: Nav link 'Absensi Divisi' muncul untuk KD
echo "\n--- 7. Nav link ---\n";
$layout = file_get_contents(__DIR__ . '/../../resources/views/layouts/app.blade.php');
check("Layout punya link 'Absensi Divisi'", str_contains($layout, 'Absensi Divisi'));
check("Layout punya link kadiv.attendances.index", str_contains($layout, 'kadiv.attendances.index'));
check("Nav Absensi Divisi dibungkus @if(isKadiv())", str_contains($layout, "@if(auth()->user()->isKadiv())\n                            <a href=\"{{ route('kadiv.attendances.index') }}"));

// 8. Test: Route terdaftar
echo "\n--- 8. Routes ---\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$hasIndex = false; $hasShow = false;
foreach ($routes as $route) {
    if ($route->getName() === 'kadiv.attendances.index') $hasIndex = true;
    if ($route->getName() === 'kadiv.attendances.show') $hasShow = true;
}
check("Route kadiv.attendances.index terdaftar", $hasIndex);
check("Route kadiv.attendances.show terdaftar", $hasShow);

// 9. Test: Hanya KD yang bisa akses (admin ditolak oleh middleware)
echo "\n--- 9. Role middleware ---\n";
$admin = User::where('email', 'admin@attendance.test')->first();
$reqAdmin = Request::create('/kadiv/attendances', 'GET');
$reqAdmin->setUserResolver(fn () => $admin);
$mw = new \App\Http\Middleware\RoleMiddleware();
$threwAdmin = false;
try {
    $mw->handle($reqAdmin, fn () => null, 'kepala_divisi');
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) $threwAdmin = true;
}
check("Admin ditolak akses /kadiv/attendances (403)", $threwAdmin);

// Karyawan ditolak juga
$reqKaryawan = Request::create('/kadiv/attendances', 'GET');
$reqKaryawan->setUserResolver(fn () => $andi);
$threwKar = false;
try {
    $mw->handle($reqKaryawan, fn () => null, 'kepala_divisi');
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) $threwKar = true;
}
check("Karyawan ditolak akses /kadiv/attendances (403)", $threwKar);

// 10. Cleanup
echo "\n--- 10. Cleanup ---\n";
$ekoAtt->delete();
$existingAndi->delete();
echo "  Test data cleaned.\n";

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) { echo "Failed: $fail\n"; exit(1); }
echo "ALL KADIV ATTENDANCE TESTS PASS\n";
