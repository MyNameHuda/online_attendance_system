<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(): View
    {
        $shifts = Shift::orderBy('start_time')->get();
        return view('admin.shifts.index', compact('shifts'));
    }

    public function create(): View
    {
        return view('admin.shifts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'different:start_time'],
        ]);
        Shift::create($data);
        return redirect()->route('admin.shifts.index')->with('success', 'Shift berhasil ditambahkan.');
    }

    public function edit(Shift $shift): View
    {
        return view('admin.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'different:start_time'],
        ]);
        $shift->update($data);
        return redirect()->route('admin.shifts.index')->with('success', 'Shift berhasil diupdate.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        if ($shift->schedules()->exists()) {
            return back()->withErrors(['general' => 'Shift masih dipakai jadwal, tidak bisa dihapus.']);
        }
        $shift->delete();
        return redirect()->route('admin.shifts.index')->with('success', 'Shift berhasil dihapus.');
    }
}
