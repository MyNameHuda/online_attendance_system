<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))
            : Carbon::today();

        $endDate = $startDate->copy()->addDays(13);

        $employees = User::with(['division', 'schedules' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->with('shift');
        }])->orderBy('name')->get();

        $dates = [];
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            $dates[] = $d->copy();
        }

        return view('admin.schedules.index', compact('employees', 'dates', 'startDate', 'endDate'));
    }

    public function create(Request $request): View
    {
        $employees = User::with('division')->where('role', 'karyawan')->orderBy('name')->get();
        $shifts = Shift::orderBy('start_time')->get();
        $preselectedDate = old('date', $request->query('date'));
        $preselectedUsers = old('user_ids', $request->query('user_ids', []));
        if (! is_array($preselectedUsers)) {
            $preselectedUsers = [$preselectedUsers];
        }
        $preselectedUser = $preselectedUsers[0] ?? null;
        return view('admin.schedules.create', compact('employees', 'shifts', 'preselectedDate', 'preselectedUser', 'preselectedUsers'));
    }

    /**
     * Simpan jadwal untuk satu tanggal + satu shift, tapi bisa untuk BANYAK karyawan sekaligus.
     * Loop per user_id. Skip kalau (user, date) sudah ada jadwal.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'date'       => ['required', 'date'],
            'shift_id'   => ['nullable', 'exists:shifts,id'],
            'status'     => ['required', 'in:kerja,libur'],
        ]);

        // Validasi tambahan: kerja → shift wajib
        if ($data['status'] === 'libur') {
            $data['shift_id'] = null;
        } elseif (empty($data['shift_id'])) {
            return back()->withErrors(['shift_id' => 'Shift wajib dipilih saat status Kerja.'])->withInput();
        }

        // Skip karyawan yang sudah ada jadwal di tanggal tsb
        $existingPairs = Schedule::whereIn('user_id', $data['user_ids'])
            ->whereDate('date', $data['date'])
            ->pluck('user_id')
            ->all();
        $existingPairs = array_flip($existingPairs);

        $created = 0;
        $skipped = [];
        DB::transaction(function () use ($data, $existingPairs, &$created, &$skipped) {
            foreach ($data['user_ids'] as $uid) {
                if (isset($existingPairs[$uid])) {
                    $skipped[] = $uid;
                    continue;
                }
                Schedule::create([
                    'user_id'  => $uid,
                    'date'     => $data['date'],
                    'shift_id' => $data['shift_id'],
                    'status'   => $data['status'],
                ]);
                $created++;
            }
        });

        // Flash summary
        $msg = sprintf('Jadwal berhasil ditambahkan untuk %d karyawan.', $created);
        if (count($skipped) > 0) {
            $names = User::whereIn('id', $skipped)->pluck('name')->all();
            $msg .= ' Lewati ' . count($skipped) . ' (sudah ada jadwal): ' . implode(', ', $names) . '.';
        }

        return redirect()->route('admin.schedules.index', ['start_date' => Carbon::parse($data['date'])->subDays(7)->toDateString()])
            ->with('success', $msg);
    }

    public function edit(Schedule $schedule): View
    {
        $employees = User::orderBy('name')->get();
        $shifts = Shift::orderBy('start_time')->get();
        return view('admin.schedules.edit', compact('schedule', 'employees', 'shifts'));
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'date'    => ['required', 'date'],
            'shift_id'=> ['nullable', 'exists:shifts,id'],
            'status'  => ['required', 'in:kerja,libur'],
        ]);

        if ($data['status'] === 'libur') {
            $data['shift_id'] = null;
        }

        // Cek unique (kecuali untuk record ini sendiri)
        $conflict = Schedule::where('user_id', $data['user_id'])
            ->whereDate('date', $data['date'])
            ->where('id', '!=', $schedule->id)
            ->exists();
        if ($conflict) {
            return back()->withErrors(['date' => 'Karyawan ini sudah punya jadwal lain di tanggal tersebut.'])->withInput();
        }

        $schedule->update($data);
        return redirect()->route('admin.schedules.index', ['start_date' => Carbon::parse($data['date'])->subDays(7)->toDateString()])
            ->with('success', 'Jadwal berhasil diupdate.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $schedule->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
