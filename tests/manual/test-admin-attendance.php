<?php
/**
 * Test Admin/HRD attendance list + detail views.
 * Covers: filter (date, division, employee, status), detail with photo + Leaflet map.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\AttendanceController;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

$pass = 0; $fail = 0;
function check($name, $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name $extra\n"; $pass++; }
    else       { echo "  FAIL $name $extra\n"; $fail++; }
}
View::share('errors', new \Illuminate\Support\ViewErrorBag());

echo "=== ADMIN ATTENDANCE VIEW TEST ===\n\n";

// Setup: bikin attendance untuk Andi dengan foto dummy (full clock-in + clock-out)
$andi = User::where('email', 'andi@attendance.test')->first();
$today = Carbon::today()->toDateString();

// Hapus attendance existing untuk Andi hari ini agar bisa bikin fresh.
// Test lain (test-business-logic.php) mungkin sudah create attendance tanpa clock-out.
Attendance::where('user_id', $andi->id)->whereDate('date', $today)->delete();

$inPhoto  = 'attendance/' . $andi->id . '/test-in.jpg';
$outPhoto = 'attendance/' . $andi->id . '/test-out.jpg';
// Create dummy jpeg files in real storage
$jpg = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AVoP/2Q==');
Storage::disk('public')->put($inPhoto, $jpg);
Storage::disk('public')->put($outPhoto, $jpg);

$existingAtt = Attendance::create([
    'user_id'         => $andi->id,
    'date'            => $today,
    'clock_in_time'   => now()->subHours(4),
    'clock_in_photo'  => $inPhoto,
    'clock_in_lat'    => -6.175392,
    'clock_in_lng'    => 106.827153,
    'clock_out_time'  => now()->subHours(1),
    'clock_out_photo' => $outPhoto,
    'clock_out_lat'   => -6.175500,
    'clock_out_lng'   => 106.827200,
]);
echo "Created test attendance #{$existingAtt->id}\n";

$controller = new AttendanceController();

// 1. index() renders without error
echo "--- index() ---\n";
$resp = $controller->index(Request::create('/admin/attendances', 'GET'));
check("Response is View", $resp instanceof \Illuminate\View\View);
$content = $resp->render();
check("Index has filter form (start_date)", str_contains($content, 'name="start_date"'));
check("Index has division filter", str_contains($content, 'name="division_id"'));
check("Index has employee filter", str_contains($content, 'name="user_id"'));
check("Index has status filter", str_contains($content, 'name="status"'));
check("Index menampilkan nama Andi", str_contains($content, $andi->name));
check("Index ada link Detail ke show", str_contains($content, route('admin.attendances.show', $existingAtt)));
check("Index menampilkan summary card 'Total Record'", str_contains($content, 'Total Record'));
check("Index menampilkan summary 'Sudah Clock-In'", str_contains($content, 'Sudah Clock-In'));

// 2. Filter by user_id
echo "\n--- Filter by user ---\n";
$resp2 = $controller->index(Request::create('/admin/attendances?user_id=' . $andi->id, 'GET'));
$content2 = $resp2->render();
check("Filtered index mengandung Andi", str_contains($content2, $andi->name));

// 3. Filter by date range kosong (kemarin)
echo "\n--- Filter date range (kosong) ---\n";
$yesterday = Carbon::yesterday()->toDateString();
$resp3 = $controller->index(Request::create("/admin/attendances?start_date=$yesterday&end_date=$yesterday", 'GET'));
$content3 = $resp3->render();
check("Empty range menampilkan empty state", str_contains($content3, 'Tidak ada data absensi'));

// 4. show() renders attendance detail
echo "\n--- show() ---\n";
$resp4 = $controller->show($existingAtt);
check("Show response is View", $resp4 instanceof \Illuminate\View\View);
$content4 = $resp4->render();
check("Show menampilkan nama Andi", str_contains($content4, $andi->name));
check("Show menampilkan tanggal", str_contains($content4, $existingAtt->date->translatedFormat('d F Y')));
check("Show punya section Clock In", str_contains($content4, 'Clock In'));
check("Show punya section Clock Out", str_contains($content4, 'Clock Out'));
check("Show punya Leaflet CSS", str_contains($content4, 'leaflet@1.9.4/dist/leaflet.css'));
check("Show punya Leaflet JS", str_contains($content4, 'leaflet@1.9.4/dist/leaflet.js'));
check("Show memuat container map-clock-in", str_contains($content4, 'id="map-clock-in"'));
check("Show memuat container map-clock-out", str_contains($content4, 'id="map-clock-out"'));
check("Show memuat marker attendance (green)", str_contains($content4, 'attendance-marker'));
check("Show memuat marker office", str_contains($content4, 'office-marker'));
// @push('scripts') content hanya di-emit saat dibungkus layout. Cek via source view file.
$showViewSource = file_get_contents(__DIR__ . '/../../resources/views/admin/attendances/show.blade.php');
$hasTile = str_contains($showViewSource, 'tile.openstreetmap.org') || str_contains($showViewSource, 'openstreetmap.org');
check("Show view source memuat tile layer OpenStreetMap", $hasTile);
check("Show menampilkan koordinat clock-in", str_contains($content4, '-6.1753920'));
check("Show menampilkan koordinat clock-out", str_contains($content4, '-6.1755000'));
check("Show badge 'Dalam radius'", str_contains($content4, 'Dalam radius'));
check("Show punya img src untuk foto in", str_contains($content4, 'storage/' . $existingAtt->clock_in_photo));
check("Show punya img src untuk foto out", str_contains($content4, 'storage/' . $existingAtt->clock_out_photo));

// 5. Show dengan attendance tanpa clock-out (hanya in)
echo "\n--- show() attendance partial ---\n";
$inOnly = Attendance::create([
    'user_id'         => $andi->id,
    'date'            => Carbon::today()->subDays(1)->toDateString(),
    'clock_in_time'   => now()->subHours(5),
    'clock_in_photo'  => 'attendance/test/in2.jpg',
    'clock_in_lat'    => -6.175392,
    'clock_in_lng'    => 106.827153,
    'clock_out_time'  => null,
    'clock_out_photo' => null,
    'clock_out_lat'   => null,
    'clock_out_lng'   => null,
]);
$resp5 = $controller->show($inOnly);
$content5 = $resp5->render();
check("Partial show masih render", str_contains($content5, 'Clock In'));
check("Partial show menampilkan 'Belum clock-out'", str_contains($content5, 'belum clock-out') || str_contains($content5, 'Belum absen'));
$inOnly->delete();

// 6. Office distance computed correctly
echo "\n--- Distance computation ---\n";
$office = OfficeLocation::getMain();
$distance = $office->distanceFrom(-6.175392, 106.827153);
check("Distance ~0m untuk titik di kantor", $distance < 5, "($distance m)");

// 7. Cleanup: hapus attendance test (atau biarkan kalau pakai existing)
echo "\n--- Cleanup ---\n";
$existingAtt->delete();
"Test attendance removed.\n";

// 8. Nav link present
echo "\n--- Nav link ---\n";
$layout = file_get_contents(__DIR__ . '/../../resources/views/layouts/app.blade.php');
check("Layout punya link Daftar Absensi", str_contains($layout, 'admin.attendances.index'));
check("Layout punya link text 'Daftar Absensi'", str_contains($layout, 'Daftar Absensi'));

echo "\n=== RINGKASAN ===\n";
echo "Passed: $pass / " . ($pass + $fail) . "\n";
if ($fail > 0) { echo "Failed: $fail\n"; exit(1); }
echo "ALL ADMIN ATTENDANCE TESTS PASS\n";
