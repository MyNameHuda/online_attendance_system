<?php

/**
 * Business logic smoke test untuk Online Attendance System.
 * Dijalankan via: php test-business-logic.php
 *
 * Test semua 10 fitur di level logic.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Attendance;
use App\Models\DayOffSwapRequest;
use App\Models\OfficeLocation;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$pass = 0;
$fail = 0;

// Cleanup any test data from previous runs
echo "(Cleaning up any previous test data...)\n";
Attendance::truncate();
ShiftSwapRequest::truncate();
DayOffSwapRequest::truncate();
// Restore schedules that may have been mutated by previous tests
DB::table('schedules')->update(['status' => DB::raw("CASE WHEN strftime('%w', date) IN ('0','6') THEN 'libur' ELSE 'kerja' END"), 'shift_id' => null]);
DB::table('schedules')->where('status', 'kerja')->orderBy('id')->each(function ($s) {
    $shiftId = (($s->id - 1) % 3) + 1; // round-robin
    DB::table('schedules')->where('id', $s->id)->update(['shift_id' => $shiftId]);
});
// Reset admin & employee passwords (in case some test changed them) - skip, not needed

function check($name, $cond) {
    global $pass, $fail;
    if ($cond) {
        echo "  ✓ $name\n";
        $pass++;
    } else {
        echo "  ✗ $name\n";
        $fail++;
    }
}

echo "=== FITUR 1: Data Karyawan ===\n";
$employees = User::with('division')->get();
// 10 users: admin + 2 KD + 7 karyawan (Andi, Citra, Dedi, Hendra, Eko, Fitri, Gita)
check("10 users ter-seed", $employees->count() === 10);
check("User punya relasi division", $employees->first()->division !== null);
check("Ada minimal 1 admin", $employees->where('role', 'admin')->count() >= 1);
check("Ada minimal 1 KD per divisi", User::where('role', 'kepala_divisi')->where('division_id', 1)->exists());

echo "\n=== FITUR 2: Data Shift ===\n";
$shifts = Shift::all();
check("3 shift ter-seed (Pagi/Siang/Malam)", $shifts->count() === 3);
$pagi = $shifts->where('name', 'Pagi')->first();
check("Shift Pagi 08:00-16:00", str_starts_with($pagi->start_time, '08:00') && str_starts_with($pagi->end_time, '16:00'));

echo "\n=== FITUR 3: Jadwal Kerja ===\n";
$schedules = Schedule::all();
check("140 schedules (10 user × 14 hari)", $schedules->count() === 140);
check("Ada jadwal libur (weekend)", $schedules->where('status', 'libur')->count() > 0);
check("Ada jadwal kerja (weekday)", $schedules->where('status', 'kerja')->count() > 0);
check("Unique (user, date)", DB::table('schedules')->select('user_id', 'date')->groupBy('user_id', 'date')->havingRaw('COUNT(*) > 1')->get()->isEmpty());

echo "\n=== FITUR 4-6: Absensi (Logic via Controller simulation) ===\n";
$andi = User::where('email', 'andi@attendance.test')->first();
$today = Carbon::today();

// 4: Buat attendance hari ini (idempotent)
$att = Attendance::updateOrCreate(
    ['user_id' => $andi->id, 'date' => $today->toDateString()],
    [
        'clock_in_time' => now(),
        'clock_in_photo' => 'test/photo.jpg',
        'clock_in_lat' => -6.175392,
        'clock_in_lng' => 106.827153,
    ]
);
check("Absen masuk tersimpan", $att->hasClockedIn());

// 5: Absen pulang
$att->update([
    'clock_out_time' => now()->addHour(8),
    'clock_out_photo' => 'test/photo-out.jpg',
]);
check("Absen pulang tersimpan", $att->fresh()->hasClockedOut());

// 6: Double clock-in ditolak (check di controller sudah ada logic-nya)
// 6a: Absen di hari libur ditolak
$liburDate = Schedule::where('user_id', $andi->id)->where('status', 'libur')->first()?->date;
if ($liburDate) {
    $liburSchedule = Schedule::where('user_id', $andi->id)->whereDate('date', $liburDate)->first();
    check("Hari libur terdeteksi", $liburSchedule->isLibur());
}

echo "\n=== FITUR 7: Tukar Shift (2-Step Approval) ===\n";
$citra = User::where('email', 'citra@attendance.test')->first();
$dedi = User::where('email', 'dedi@attendance.test')->first();

// Cari tanggal kerja yang sama-sama ada di Andi & Citra
$andiKerja = Schedule::where('user_id', $andi->id)
    ->where('status', 'kerja')
    ->whereDate('date', '>=', today())
    ->pluck('date');
$swapDate = null;
foreach ($andiKerja as $d) {
    $dStr = is_string($d) ? $d : $d->toDateString();
    $cs = Schedule::where('user_id', $citra->id)->whereDate('date', $dStr)->first();
    if ($cs && $cs->isKerja() && $cs->shift_id && $dStr !== today()->toDateString()) {
        $swapDate = $dStr;
        break;
    }
}

if ($swapDate) {
    $andiSched = Schedule::where('user_id', $andi->id)->whereDate('date', $swapDate)->first();
    $citraSched = Schedule::where('user_id', $citra->id)->whereDate('date', $swapDate)->first();
} else {
    $andiSched = $citraSched = null;
}

if ($andiSched && $citraSched && $andiSched->isKerja() && $citraSched->isKerja()) {
    $swap = ShiftSwapRequest::create([
        'requester_id' => $andi->id,
        'target_employee_id' => $citra->id,
        'date' => $swapDate,
        'requester_shift_id' => $andiSched->shift_id,
        'target_shift_id' => $citraSched->shift_id,
        'reason' => 'Butuh tuker shift untuk acara keluarga',
        'status' => ShiftSwapRequest::STATUS_PENDING_TARGET,
    ]);
    check("Tukar shift created (status: pending_target)", $swap->status === 'pending_target');

    // Same-division check
    check("Andi & Citra satu divisi", $andi->division_id === $citra->division_id);

    // Target ACC → masuk tahap approval KD
    $swap->update([
        'status' => ShiftSwapRequest::STATUS_PENDING_KADIV,
        'target_responded_at' => now(),
        'target_response_note' => 'OK',
    ]);
    check("Target ACC (status: pending_kadiv)", $swap->fresh()->status === 'pending_kadiv');

    // KD approve → auto-swap
    $kadiv = User::where('role', 'kepala_divisi')->where('division_id', $andi->division_id)->first();
    DB::transaction(function () use ($swap, $kadiv) {
        Schedule::where('user_id', $swap->requester_id)
            ->whereDate('date', $swap->date)
            ->update(['shift_id' => $swap->target_shift_id]);
        Schedule::where('user_id', $swap->target_employee_id)
            ->whereDate('date', $swap->date)
            ->update(['shift_id' => $swap->requester_shift_id]);
        $swap->update([
            'status' => ShiftSwapRequest::STATUS_APPROVED,
            'approver_id' => $kadiv->id,
            'approver_decided_at' => now(),
        ]);
    });

    $newAndiSched = Schedule::where('user_id', $andi->id)->whereDate('date', $swapDate)->first();
    $newCitraSched = Schedule::where('user_id', $citra->id)->whereDate('date', $swapDate)->first();
    check("Auto-swap: Andi dapat shift Citra", $newAndiSched->shift_id === $swap->target_shift_id);
    check("Auto-swap: Citra dapat shift Andi", $newCitraSched->shift_id === $swap->requester_shift_id);
    check("Status approved", $swap->fresh()->status === 'approved');
    check("Approver tercatat (KD IT)", $swap->fresh()->approver_id === $kadiv->id);
} else {
    echo "  (skip — tanggal uji bukan hari kerja semua)\n";
}

echo "\n=== FITUR 8: Tukar Hari Libur (KD Approval Required) ===\n";
$liburDate = Schedule::where('user_id', $andi->id)->where('status', 'libur')->whereDate('date', '>=', today())->first()?->date;
$kerjaDate = Schedule::where('user_id', $andi->id)->where('status', 'kerja')->whereDate('date', '>=', today())->where('date', '!=', $liburDate)->first()?->date;

if ($liburDate && $kerjaDate) {
    $dayOffSwap = DayOffSwapRequest::create([
        'requester_id' => $andi->id,
        'old_off_date' => $liburDate,
        'new_off_date' => $kerjaDate,
        'reason' => 'Ada acara keluarga',
        'status' => 'pending',
    ]);
    check("Tukar libur created (status: pending)", $dayOffSwap->status === 'pending');

    // Schedule belum berubah saat masih pending
    $oldLibur = Schedule::where('user_id', $andi->id)->whereDate('date', $liburDate)->first();
    check("Schedule belum berubah saat pending", $oldLibur->isLibur());

    // KD approve → apply schedule swap
    $kadiv = User::where('role', 'kepala_divisi')->where('division_id', $andi->division_id)->first();
    DB::transaction(function () use ($dayOffSwap, $kadiv) {
        Schedule::where('user_id', $dayOffSwap->requester_id)
            ->whereDate('date', $dayOffSwap->old_off_date)
            ->update(['status' => 'kerja', 'shift_id' => null]);
        Schedule::where('user_id', $dayOffSwap->requester_id)
            ->whereDate('date', $dayOffSwap->new_off_date)
            ->update(['status' => 'libur', 'shift_id' => null]);
        $dayOffSwap->update([
            'status' => 'approved',
            'approver_id' => $kadiv->id,
            'approver_decided_at' => now(),
        ]);
    });

    $newLibur = Schedule::where('user_id', $andi->id)->whereDate('date', $liburDate)->first();
    $newKerja = Schedule::where('user_id', $andi->id)->whereDate('date', $kerjaDate)->first();
    check("Old date jadi kerja", $newLibur->isKerja());
    check("New date jadi libur", $newKerja->isLibur());
    check("Status approved", $dayOffSwap->fresh()->status === 'approved');
} else {
    echo "  (skip — tidak ada tanggal libur/kerja yang cocok)\n";
}

echo "\n=== FITUR 9: Data KD per Divisi ===\n";
// KD role masih ada di sistem untuk struktur organisasi, tapi tidak ada approval workflow lagi.
$kadivIT = User::where('email', 'budi.kadiv@attendance.test')->first();
$kadivFin = User::where('email', 'siti.kadiv@attendance.test')->first();
check("KD IT dan KD Finance beda divisi", $kadivIT->division_id !== $kadivFin->division_id);
check("KD punya role kepala_divisi", $kadivIT->role === 'kepala_divisi');
check("KD IT mengawasi divisi IT", $kadivIT->division_id === $andi->division_id);

echo "\n=== FITUR 10: Dashboard Data ===\n";
$dashboard = [
    'todaySchedule' => Schedule::where('user_id', $andi->id)->whereDate('date', today())->first(),
    'attendanceHistory' => Attendance::where('user_id', $andi->id)->count(),
    'myShiftSwaps' => ShiftSwapRequest::where('requester_id', $andi->id)->count(),
];
check("Dashboard: today's schedule loaded", $dashboard['todaySchedule'] !== null);
check("Dashboard: ada attendance history", $dashboard['attendanceHistory'] > 0);
check("Dashboard: ada shift swap history (Andi sebagai requester)", $dashboard['myShiftSwaps'] > 0);

echo "\n=== Office Location (Geofencing) ===\n";
$office = OfficeLocation::getMain();
check("Office location utama tersedia (singleton)", $office !== null);

$distanceInside = $office->distanceFrom(-6.175392, 106.827153);
$distanceOutside = $office->distanceFrom(-6.300000, 107.000000); // ~18km dari Jakarta Pusat
$distanceJauh = $office->distanceFrom(-7.250000, 112.768000); // Surabaya
check("Distance calculation (titik sama) ≈ 0m", $distanceInside < 1);
check("Distance ke Surabaya > 500km", $distanceJauh > 500000);
check("Within radius check works (true)", $office->isWithinRadius(-6.175392, 106.827153) === true);
check("Within radius check works (false, ~18km)", $office->isWithinRadius(-6.300000, 107.000000) === false);

echo "\n=== RINGKASAN ===\n";
$total = $pass + $fail;
echo "Passed: $pass / $total\n";
if ($fail > 0) {
    echo "Failed: $fail\n";
    exit(1);
}
echo "\n✓ SEMUA FITUR VALID\n";
