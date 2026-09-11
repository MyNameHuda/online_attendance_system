<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\OfficeLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Daftar absensi semua karyawan, dengan filter (tanggal, divisi, karyawan, status).
     */
    public function index(Request $request): View
    {
        $query = Attendance::with(['user.division', 'user.schedules' => function ($q) {
            // ambil shift dari schedule di tanggal yang sama
        }])->orderByDesc('date')->orderByDesc('clock_in_time');

        // Filter tanggal
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : Carbon::today()->subDays(13);
        $endDate   = $request->query('end_date')   ? Carbon::parse($request->query('end_date'))   : Carbon::today();
        $query->whereDate('date', '>=', $startDate->toDateString())
              ->whereDate('date', '<=', $endDate->toDateString());

        // Filter karyawan / divisi
        if ($divisionId = $request->query('division_id')) {
            $query->whereHas('user', fn ($q) => $q->where('division_id', $divisionId));
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        // Filter status (hadir = clock_in_time ada, lengkap = ada clock_out, telat = perlu join shift)
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

        // Ringkasan
        $summary = [
            'total'        => (clone $query)->count(),
            'hadir'        => (clone $query)->whereNotNull('clock_in_time')->count(),
            'belum_pulang' => (clone $query)->whereNotNull('clock_in_time')->whereNull('clock_out_time')->count(),
            'belum_masuk'  => (clone $query)->whereNull('clock_in_time')->count(),
        ];

        $divisions = Division::orderBy('name')->get();
        $employees = User::with('division')->where('role', 'karyawan')->orderBy('name')->get();

        return view('admin.attendances.index', compact(
            'attendances', 'summary', 'divisions', 'employees', 'startDate', 'endDate'
        ));
    }

    /**
     * Detail 1 attendance record: foto clock-in/out + peta lokasi.
     */
    public function show(Attendance $attendance): View
    {
        $attendance->load(['user.division']);

        // Ambil shift dari schedule di tanggal yang sama (kalau ada)
        $schedule = \App\Models\Schedule::with('shift')
            ->where('user_id', $attendance->user_id)
            ->whereDate('date', $attendance->date)
            ->first();

        $office = OfficeLocation::getMain();
        $clockInDistance  = $office && $attendance->clock_in_lat  !== null ? $office->distanceFrom($attendance->clock_in_lat,  $attendance->clock_in_lng)  : null;
        $clockOutDistance = $office && $attendance->clock_out_lat !== null ? $office->distanceFrom($attendance->clock_out_lat, $attendance->clock_out_lng) : null;

        return view('admin.attendances.show', [
            'attendance'      => $attendance,
            'schedule'        => $schedule,
            'office'          => $office,
            'clockInDistance' => $clockInDistance,
            'clockOutDistance'=> $clockOutDistance,
        ]);
    }
}
