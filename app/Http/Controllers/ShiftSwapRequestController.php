<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftSwapRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $swaps = ShiftSwapRequest::with(['requester', 'targetEmployee', 'requesterShift', 'targetShift', 'approver'])
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                  ->orWhere('target_employee_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('shift-swaps.index', compact('swaps'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        // Rekan satu divisi (exclude diri sendiri)
        $candidates = User::where('division_id', $user->division_id)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        $preselectedDate = old('date', $request->query('date'));
        $preselectedTarget = old('target_employee_id', $request->query('target'));

        return view('shift-swaps.create', compact('user', 'candidates', 'preselectedDate', 'preselectedTarget'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'target_employee_id' => ['required', 'exists:users,id', 'different:requester_id'],
            'date'               => ['required', 'date', 'after_or_equal:today'],
            'reason'             => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $data['requester_id'] = $user->id;

        // Validasi: target satu divisi
        $target = User::findOrFail($data['target_employee_id']);
        if ($target->division_id !== $user->division_id) {
            return back()->withErrors(['target_employee_id' => 'Target harus dari divisi yang sama dengan Anda.'])
                ->withInput();
        }

        // Ambil schedule keduanya di tanggal tsb
        $date = Carbon::parse($data['date'])->toDateString();
        $mySchedule = Schedule::with('shift')->where('user_id', $user->id)->whereDate('date', $date)->first();
        $targetSchedule = Schedule::with('shift')->where('user_id', $target->id)->whereDate('date', $date)->first();

        if (! $mySchedule || $mySchedule->isLibur()) {
            return back()->withErrors(['date' => 'Anda tidak memiliki jadwal kerja di tanggal tersebut.'])->withInput();
        }
        if (! $targetSchedule || $targetSchedule->isLibur()) {
            return back()->withErrors(['target_employee_id' => "{$target->name} libur di tanggal tersebut, tidak bisa ditukar."])
                ->withInput();
        }

        if (! $mySchedule->shift_id || ! $targetSchedule->shift_id) {
            return back()->withErrors(['date' => 'Salah satu jadwal belum memiliki shift.'])->withInput();
        }

        // Cek tidak ada request aktif di tanggal yang sama
        $existsActive = ShiftSwapRequest::whereIn('status', [
            ShiftSwapRequest::STATUS_PENDING_TARGET,
            ShiftSwapRequest::STATUS_PENDING_KADIV,
        ])->where('date', $date)
          ->where(function ($q) use ($user, $target) {
              $q->where('requester_id', $user->id)
                ->orWhere('target_employee_id', $user->id)
                ->orWhere('requester_id', $target->id)
                ->orWhere('target_employee_id', $target->id);
          })->exists();
        if ($existsActive) {
            return back()->withErrors(['date' => 'Sudah ada pengajuan aktif di tanggal tersebut.'])->withInput();
        }

        ShiftSwapRequest::create([
            'requester_id'       => $user->id,
            'target_employee_id' => $target->id,
            'date'               => $date,
            'requester_shift_id' => $mySchedule->shift_id,
            'target_shift_id'    => $targetSchedule->shift_id,
            'reason'             => $data['reason'],
            'status'             => ShiftSwapRequest::STATUS_PENDING_TARGET,
        ]);

        // Audit
        AuditLog::log('shift_swap_created', $user->id, 'ShiftSwapRequest', null, [
            'target' => $target->name,
            'date' => $date,
        ]);

        // Notify target employee
        NotificationService::send(
            $target->id,
            'shift_swap.new',
            'Pengajuan Tukar Shift Baru',
            "{$user->name} mengajak Anda tukar shift pada tanggal " . Carbon::parse($date)->translatedFormat('d F Y') . ". Silakan cek dan berikan respons.",
            route('shift-swaps.index'),
            ['requester_name' => $user->name, 'date' => $date]
        );

        return redirect()->route('shift-swaps.index')->with('success', 'Pengajuan tukar shift berhasil dibuat. Menunggu ACC dari ' . $target->name . '.');
    }

    public function show(ShiftSwapRequest $shiftSwap): View
    {
        $shiftSwap->load(['requester', 'targetEmployee', 'requesterShift', 'targetShift', 'approver']);
        $user = auth()->user();
        return view('shift-swaps.show', compact('shiftSwap', 'user'));
    }

    public function cancel(ShiftSwapRequest $shiftSwap): RedirectResponse
    {
        $user = auth()->user();
        if ($shiftSwap->requester_id !== $user->id) {
            abort(403);
        }
        if ($shiftSwap->status !== ShiftSwapRequest::STATUS_PENDING_TARGET) {
            return back()->withErrors(['general' => 'Pengajuan tidak bisa dibatalkan pada tahap ini.']);
        }
        $shiftSwap->update(['status' => ShiftSwapRequest::STATUS_CANCELLED]);
        return redirect()->route('shift-swaps.index')->with('success', 'Pengajuan dibatalkan.');
    }

    /**
     * Budi (target) merespons pengajuan.
     */
    public function targetRespond(Request $request, ShiftSwapRequest $shiftSwap): RedirectResponse
    {
        $user = auth()->user();
        if ($shiftSwap->target_employee_id !== $user->id) {
            abort(403);
        }
        if ($shiftSwap->status !== ShiftSwapRequest::STATUS_PENDING_TARGET) {
            return back()->withErrors(['general' => 'Pengajuan ini tidak menunggu ACC Anda.']);
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['decision'] === 'accept') {
            // Target ACC → masuk tahap approval KD. Tidak auto-swap.
            $shiftSwap->update([
                'status' => ShiftSwapRequest::STATUS_PENDING_KADIV,
                'target_responded_at' => now(),
                'target_response_note' => $data['note'] ?? null,
            ]);

            AuditLog::log('shift_swap_target_accepted', $user->id, 'ShiftSwapRequest', $shiftSwap->id);

            // Notify semua KD di divisi requester
            $kadivs = User::where('role', User::ROLE_KADIV)
                ->where('division_id', $shiftSwap->requester->division_id)
                ->get();
            foreach ($kadivs as $kd) {
                NotificationService::send(
                    $kd->id,
                    'shift_swap.kadiv_review',
                    'Pengajuan Tukar Shift Perlu Approval',
                    "{$shiftSwap->requester->name} mengajukan tukar shift dengan {$shiftSwap->targetEmployee->name} untuk tanggal " . $shiftSwap->date->translatedFormat('d F Y') . ". Target sudah ACC.",
                    route('kadiv.approvals.show', ['swapType' => 'shift', 'id' => $shiftSwap->id]),
                    ['requester_name' => $shiftSwap->requester->name, 'date' => $shiftSwap->date->toDateString()]
                );
            }

            // Notify requester bahwa target sudah ACC
            NotificationService::send(
                $shiftSwap->requester_id,
                'shift_swap.target_accepted',
                'Target ACC Pengajuan',
                "{$user->name} ACC pengajuan tukar shift Anda. Sekarang menunggu approval Kepala Divisi.",
                route('shift-swaps.show', $shiftSwap)
            );

            return redirect()->route('shift-swaps.index')->with('success', 'Anda ACC pengajuan. Sekarang menunggu approval Kepala Divisi.');
        } else {
            $shiftSwap->update([
                'status' => ShiftSwapRequest::STATUS_REJECTED,
                'target_responded_at' => now(),
                'target_response_note' => $data['note'] ?? null,
            ]);

            AuditLog::log('shift_swap_target_rejected', $user->id, 'ShiftSwapRequest', $shiftSwap->id);

            // Notify requester
            NotificationService::send(
                $shiftSwap->requester_id,
                'shift_swap.rejected',
                'Pengajuan Tukar Shift Ditolak',
                "{$user->name} menolak pengajuan tukar shift Anda untuk tanggal " . $shiftSwap->date->translatedFormat('d F Y') . ".",
                route('shift-swaps.index')
            );

            return redirect()->route('shift-swaps.index')->with('success', 'Pengajuan ditolak.');
        }
    }
}
