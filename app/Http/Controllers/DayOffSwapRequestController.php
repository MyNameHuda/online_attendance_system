<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DayOffSwapRequest;
use App\Models\Schedule;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DayOffSwapRequestController extends Controller
{
    public function index(Request $request): View
    {
        $swaps = DayOffSwapRequest::with(['requester', 'approver'])
            ->where('requester_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('day-off-swaps.index', compact('swaps'));
    }

    public function create(): View
    {
        $user = auth()->user();
        // Ambil jadwal libur yang akan datang (max 30 hari ke depan)
        $liburDates = Schedule::where('user_id', $user->id)
            ->where('status', Schedule::STATUS_LIBUR)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->limit(30)
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());

        // Ambil jadwal kerja yang akan datang (untuk new_off_date)
        $kerjaDates = Schedule::where('user_id', $user->id)
            ->where('status', Schedule::STATUS_KERJA)
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->limit(30)
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString());

        return view('day-off-swaps.create', compact('liburDates', 'kerjaDates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'old_off_date' => ['required', 'date', 'after_or_equal:today'],
            'new_off_date' => ['required', 'date', 'after_or_equal:today', 'different:old_off_date'],
            'reason'       => ['required', 'string', 'min:3', 'max:500'],
        ]);

        // Validasi old_off_date harus libur
        $oldSchedule = Schedule::where('user_id', $user->id)
            ->whereDate('date', $data['old_off_date'])
            ->first();
        if (! $oldSchedule || $oldSchedule->isLibur() === false) {
            return back()->withErrors(['old_off_date' => 'Tanggal lama harus hari libur Anda.'])->withInput();
        }

        // Validasi new_off_date harus kerja
        $newSchedule = Schedule::where('user_id', $user->id)
            ->whereDate('date', $data['new_off_date'])
            ->first();
        if (! $newSchedule || $newSchedule->isKerja() === false) {
            return back()->withErrors(['new_off_date' => 'Tanggal baru harus hari kerja Anda.'])->withInput();
        }

        // Buat pengajuan dengan status pending. Menunggu approval Kepala Divisi.
        $swap = DayOffSwapRequest::create([
            'requester_id' => $user->id,
            'old_off_date' => $data['old_off_date'],
            'new_off_date' => $data['new_off_date'],
            'reason'       => $data['reason'],
            'status'       => DayOffSwapRequest::STATUS_PENDING,
        ]);

        AuditLog::log('dayoff_swap_created', $user->id, 'DayOffSwapRequest', $swap->id);

        // Notify KD di divisi user
        $kadivs = User::where('role', User::ROLE_KADIV)
            ->where('division_id', $user->division_id)
            ->get();
        foreach ($kadivs as $kd) {
            NotificationService::send(
                $kd->id,
                'dayoff_swap.new',
                'Pengajuan Tukar Hari Libur',
                "{$user->name} mengajukan tukar hari libur dari " . Carbon::parse($data['old_off_date'])->translatedFormat('d F') . " ke " . Carbon::parse($data['new_off_date'])->translatedFormat('d F') . ".",
                route('kadiv.approvals.show', ['swapType' => 'dayoff', 'id' => $swap->id]),
            );
        }

        return redirect()->route('day-off-swaps.index')->with('success', 'Pengajuan tukar hari libur berhasil dibuat. Menunggu approval Kepala Divisi.');
    }

    public function show(DayOffSwapRequest $dayOffSwap): View
    {
        $dayOffSwap->load(['requester', 'approver']);
        return view('day-off-swaps.show', compact('dayOffSwap'));
    }

    public function cancel(DayOffSwapRequest $dayOffSwap): RedirectResponse
    {
        $user = auth()->user();
        if ($dayOffSwap->requester_id !== $user->id) {
            abort(403);
        }
        if ($dayOffSwap->status !== DayOffSwapRequest::STATUS_PENDING) {
            return back()->withErrors(['general' => 'Tidak bisa dibatalkan.']);
        }
        $dayOffSwap->update(['status' => DayOffSwapRequest::STATUS_CANCELLED]);
        return redirect()->route('day-off-swaps.index')->with('success', 'Pengajuan dibatalkan.');
    }
}
