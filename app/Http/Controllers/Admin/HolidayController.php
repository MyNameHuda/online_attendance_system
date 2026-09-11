<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::orderBy('date', 'desc')->paginate(20);
        return view('admin.holidays.index', compact('holidays'));
    }

    public function create(): View
    {
        return view('admin.holidays.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:255'],
            'is_national' => ['nullable', 'boolean'],
        ]);
        $data['is_national'] = $request->boolean('is_national');

        $holiday = Holiday::create($data);

        // Auto-mark all employees' schedule on that date as libur
        $affected = Schedule::whereDate('date', $holiday->date)
            ->update(['status' => Schedule::STATUS_LIBUR, 'shift_id' => null]);

        // Notify all employees
        $users = User::all();
        foreach ($users as $u) {
            NotificationService::send(
                $u->id,
                'holiday.new',
                'Hari Libur Baru',
                "{$holiday->date->translatedFormat('l, d F Y')} adalah hari libur: {$holiday->name}. Jadwal kerja Anda otomatis di-update.",
                route('dashboard')
            );
        }

        return redirect()->route('admin.holidays.index')
            ->with('success', "Hari libur \"{$holiday->name}\" berhasil ditambahkan. {$affected} jadwal kerja di-update jadi libur, notifikasi terkirim ke semua karyawan.");
    }

    public function edit(Holiday $holiday): View
    {
        return view('admin.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'unique:holidays,date,' . $holiday->id],
            'name' => ['required', 'string', 'max:255'],
            'is_national' => ['nullable', 'boolean'],
        ]);
        $data['is_national'] = $request->boolean('is_national');
        $holiday->update($data);

        return redirect()->route('admin.holidays.index')->with('success', 'Hari libur berhasil diupdate.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();
        return redirect()->route('admin.holidays.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}
