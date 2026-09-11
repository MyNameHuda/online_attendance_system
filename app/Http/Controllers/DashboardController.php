<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\ShiftSwapRequest;
use App\Models\DayOffSwapRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today();

        // Jadwal hari ini
        $todaySchedule = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Absensi hari ini
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Jadwal berikutnya (7 hari ke depan)
        $upcomingSchedules = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->limit(7)
            ->get();

        // Riwayat absensi 7 hari terakhir
        $attendanceHistory = Attendance::where('user_id', $user->id)
            ->orderByDesc('date')
            ->limit(7)
            ->get();

        // Tukar shift status (semua pengajuan yang saya terlibat)
        $myShiftSwaps = ShiftSwapRequest::with(['requester', 'targetEmployee', 'requesterShift', 'targetShift'])
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                  ->orWhere('target_employee_id', $user->id);
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // Tukar libur status
        $myDayOffSwaps = DayOffSwapRequest::where('requester_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'user',
            'today',
            'todaySchedule',
            'todayAttendance',
            'upcomingSchedules',
            'attendanceHistory',
            'myShiftSwaps',
            'myDayOffSwaps',
        ));
    }
}
