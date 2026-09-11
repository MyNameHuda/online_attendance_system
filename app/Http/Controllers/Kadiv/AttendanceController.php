<?php

namespace App\Http\Controllers\Kadiv;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Daftar absensi anggota divisi KD yang sedang login.
     * Data auto-filter ke karyawan yang ada di division_id KD.
     * Admin/HRD yang iseng buka URL ini akan ditolak (lihat middleware role).
     */
    public function index(Request $request): View
    {
        $kd = $request->user();

        // Daftar karyawan di divisi KD (exclude admin & KD sendiri, kecuali KD juga absen)
        $employees = User::with('division')
            ->where('division_id', $kd->division_id)
            ->where('role', User::ROLE_KARYAWAN)
            ->orderBy('name')
            ->get();

        // Filter tanggal default: 14 hari terakhir
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : Carbon::today()->subDays(13);
        $endDate   = $request->query('end_date')   ? Carbon::parse($request->query('end_date'))   : Carbon::today();

        $query = Attendance::with(['user.division'])
            ->whereIn('user_id', $employees->pluck('id'))
            ->orderByDesc('date')
            ->orderByDesc('clock_in_time');

        $query->whereDate('date', '>=', $startDate->toDateString())
              ->whereDate('date', '<=', $endDate->toDateString());

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($statusFilter = $request->query('status')) {
            if ($statusFilter === 'belum_pulang') {
                $query->whereNotNull('clock_in_time')->whereNull('clock_out_time');
            } elseif ($statusFilter === 'belum_masuk') {
                $query->whereNull('clock_in_time');
            } elseif ($statusFilter === 'lengkap') {
                $query->whereNotNull('clock_in_time')->whereNotNull('clock_out_time');
            }
        }

        $attendances = $query->paginate(25)->withQueryString();

        // Ringkasan (clone query supaya tidak ikut pagination)
        $summary = [
            'karyawan'    => $employees->count(),
            'total'       => (clone $query)->count(),
            'hadir'       => (clone $query)->whereNotNull('clock_in_time')->count(),
            'belum_pulang'=> (clone $query)->whereNotNull('clock_in_time')->whereNull('clock_out_time')->count(),
            'belum_masuk' => (clone $query)->whereNull('clock_in_time')->count(),
        ];

        return view('kadiv.attendances.index', compact(
            'attendances', 'summary', 'employees', 'startDate', 'endDate', 'kd'
        ));
    }

    /**
     * Detail 1 attendance record. Hanya untuk attendance milik karyawan divisi KD yang login.
     * Cross-division access → 403.
     */
    public function show(Request $request, Attendance $attendance): View
    {
        $kd = $request->user();

        // HARD GUARD: attendance harus milik karyawan di divisi KD
        if ($attendance->user->division_id !== $kd->division_id) {
            abort(403, 'Absensi ini bukan dari anggota divisi Anda.');
        }

        $attendance->load(['user.division']);

        $schedule = Schedule::with('shift')
            ->where('user_id', $attendance->user_id)
            ->whereDate('date', $attendance->date)
            ->first();

        $office = OfficeLocation::getMain();
        $clockInDistance  = $office && $attendance->clock_in_lat  !== null ? $office->distanceFrom($attendance->clock_in_lat,  $attendance->clock_in_lng)  : null;
        $clockOutDistance = $office && $attendance->clock_out_lat !== null ? $office->distanceFrom($attendance->clock_out_lat, $attendance->clock_out_lng) : null;

        return view('kadiv.attendances.show', [
            'attendance'      => $attendance,
            'schedule'        => $schedule,
            'office'          => $office,
            'clockInDistance' => $clockInDistance,
            'clockOutDistance'=> $clockOutDistance,
            'kd'              => $kd,
        ]);
    }
}
