<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function monthly(Request $request)
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $divisionFilter = $request->query('division_id');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $employees = User::with('division')
            ->when($divisionFilter, fn ($q) => $q->where('division_id', $divisionFilter))
            ->orderBy('name')
            ->get();

        // Hitung summary per karyawan
        $report = $employees->map(function ($emp) use ($startDate, $endDate, $daysInMonth) {
            $schedules = Schedule::where('user_id', $emp->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();
            $attendances = Attendance::where('user_id', $emp->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->keyBy(fn ($a) => $a->date->toDateString());

            $totalKerja = $schedules->where('status', 'kerja')->count();
            $totalLibur = $schedules->where('status', 'libur')->count();
            $totalHadir = $attendances->filter(fn ($a) => $a->hasClockedIn())->count();
            $totalLembur = $attendances->filter(fn ($a) => $a->hasClockedIn() && $a->hasClockedOut())->count();
            $totalHari = $startDate->daysInMonth;

            return (object) [
                'user' => $emp,
                'total_hari' => $totalHari,
                'kerja' => $totalKerja,
                'libur' => $totalLibur,
                'hadir' => $totalHadir,
                'absen' => max(0, $totalKerja - $totalHadir),
                'full_day' => $totalLembur, // hadir masuk + pulang
                'pct_kehadiran' => $totalKerja > 0 ? round(($totalHadir / $totalKerja) * 100, 1) : 0,
            ];
        });

        // Rekap per hari (calendar view)
        $dailyRows = [];
        foreach ($employees as $emp) {
            $row = ['user' => $emp, 'days' => []];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = $startDate->copy()->addDays($d - 1);
                $att = Attendance::where('user_id', $emp->id)->whereDate('date', $date)->first();
                $sch = Schedule::where('user_id', $emp->id)->whereDate('date', $date)->first();
                $isHoliday = Holiday::isHoliday($date->toDateString());
                $row['days'][$d] = (object) [
                    'date' => $date,
                    'attendance' => $att,
                    'schedule' => $sch,
                    'is_holiday' => $isHoliday,
                ];
            }
            $dailyRows[] = $row;
        }

        $divisions = \App\Models\Division::orderBy('name')->get();
        return view('admin.reports.monthly', compact('report', 'dailyRows', 'startDate', 'endDate', 'daysInMonth', 'month', 'divisionFilter', 'divisions'));
    }

    public function export(Request $request): StreamedResponse
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $divisionFilter = $request->query('division_id');

        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $filename = "attendance-{$month}.csv";

        return response()->streamDownload(function () use ($startDate, $endDate, $divisionFilter) {
            $out = fopen('php://output', 'w');
            // BOM biar Excel detect UTF-8
            fwrite($out, "\xEF\xBB\xBF");

            // Header
            fputcsv($out, ['NIK', 'Nama', 'Divisi', 'Jabatan', 'Role', 'Hari Kerja', 'Hari Libur', 'Hadir', 'Absen', 'Full Day', 'Persentase Kehadiran (%)']);

            $employees = User::with('division')
                ->when($divisionFilter, fn ($q) => $q->where('division_id', $divisionFilter))
                ->orderBy('name')
                ->get();

            foreach ($employees as $emp) {
                $schedules = Schedule::where('user_id', $emp->id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get();
                $attendances = Attendance::where('user_id', $emp->id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get();

                $totalKerja = $schedules->where('status', 'kerja')->count();
                $totalLibur = $schedules->where('status', 'libur')->count();
                $totalHadir = $attendances->filter(fn ($a) => $a->hasClockedIn())->count();
                $totalLembur = $attendances->filter(fn ($a) => $a->hasClockedIn() && $a->hasClockedOut())->count();
                $pct = $totalKerja > 0 ? round(($totalHadir / $totalKerja) * 100, 1) : 0;

                fputcsv($out, [
                    $emp->nik,
                    $emp->name,
                    $emp->division->name,
                    $emp->position ?? '-',
                    ucfirst(str_replace('_', ' ', $emp->role)),
                    $totalKerja,
                    $totalLibur,
                    $totalHadir,
                    max(0, $totalKerja - $totalHadir),
                    $totalLembur,
                    $pct,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
