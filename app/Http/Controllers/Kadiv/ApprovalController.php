<?php

namespace App\Http\Controllers\Kadiv;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DayOffSwapRequest;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * Daftar pengajuan yang menunggu approval KD.
     * Filter otomatis: hanya karyawan dari divisi KD yang login.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Pending shift swaps (dari karyawan satu divisi)
        $pendingShiftSwaps = ShiftSwapRequest::with(['requester', 'targetEmployee', 'requesterShift', 'targetShift'])
            ->where('status', ShiftSwapRequest::STATUS_PENDING_KADIV)
            ->whereHas('requester', fn ($q) => $q->where('division_id', $user->division_id))
            ->orderBy('created_at')
            ->get();

        // Pending day-off swaps
        $pendingDayOffSwaps = DayOffSwapRequest::where('status', DayOffSwapRequest::STATUS_PENDING)
            ->whereHas('requester', fn ($q) => $q->where('division_id', $user->division_id))
            ->orderBy('created_at')
            ->get();

        return view('kadiv.approvals.index', compact('pendingShiftSwaps', 'pendingDayOffSwaps'));
    }

    /**
     * Detail satu pengajuan (shift atau day-off).
     */
    public function show(Request $request, string $swapType, int $id): View
    {
        $user = $request->user();
        $swap = $this->resolveSwap($swapType, $id);

        // Pastikan requester dari divisi KD ini
        $requester = ($swap instanceof ShiftSwapRequest) ? $swap->requester : $swap->requester;
        if ($requester->division_id !== $user->division_id) {
            abort(403, 'Pengajuan ini bukan dari divisi Anda.');
        }

        return view('kadiv.approvals.show', compact('swap', 'swapType'));
    }

    /**
     * Approve / reject pengajuan.
     * Untuk shift swap: kalau approve → auto-apply schedule swap.
     */
    public function decide(Request $request, string $swapType, int $id): RedirectResponse
    {
        $user = $request->user();
        $swap = $this->resolveSwap($swapType, $id);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        // Verify division
        $requester = ($swap instanceof ShiftSwapRequest) ? $swap->requester : $swap->requester;
        if ($requester->division_id !== $user->division_id) {
            abort(403, 'Pengajuan ini bukan dari divisi Anda.');
        }

        // Verify status masih pending
        $expectedStatus = ($swap instanceof ShiftSwapRequest)
            ? ShiftSwapRequest::STATUS_PENDING_KADIV
            : DayOffSwapRequest::STATUS_PENDING;

        if ($swap->status !== $expectedStatus) {
            return back()->withErrors(['general' => 'Pengajuan ini sudah diproses.']);
        }

        if ($data['decision'] === 'reject') {
            $swap->update([
                'status' => ($swap instanceof ShiftSwapRequest)
                    ? ShiftSwapRequest::STATUS_REJECTED
                    : DayOffSwapRequest::STATUS_REJECTED,
                'approver_id' => $user->id,
                'approver_decided_at' => now(),
                'approver_response_note' => $data['note'] ?? null,
            ]);

            $swapTypeLabel = ($swap instanceof ShiftSwapRequest) ? 'shift' : 'libur';
            AuditLog::log("{$swapTypeLabel}_swap_rejected", $user->id, get_class($swap), $swap->id, [
                'note' => $data['note'] ?? null,
            ]);

            // Notify requester
            NotificationService::send(
                $swap->requester_id,
                "{$swapTypeLabel}_swap.rejected",
                "Tukar {$swapTypeLabel} Ditolak",
                "Pengajuan Anda ditolak oleh Kepala Divisi. " . ($data['note'] ? "Catatan: {$data['note']}" : ''),
                ($swap instanceof ShiftSwapRequest) ? route('shift-swaps.show', $swap) : route('day-off-swaps.show', $swap),
            );

            return redirect()->route('kadiv.approvals.index')->with('success', 'Pengajuan ditolak.');
        }

        // APPROVE flow
        DB::transaction(function () use ($swap, $data, $user) {
            if ($swap instanceof ShiftSwapRequest) {
                // Auto-swap: tukar shift_id antara requester dan target
                Schedule::where('user_id', $swap->requester_id)
                    ->whereDate('date', $swap->date)
                    ->update(['shift_id' => $swap->target_shift_id]);

                Schedule::where('user_id', $swap->target_employee_id)
                    ->whereDate('date', $swap->date)
                    ->update(['shift_id' => $swap->requester_shift_id]);
            } else {
                // DayOffSwapRequest: tukar status kerja/libur
                Schedule::where('user_id', $swap->requester_id)
                    ->whereDate('date', $swap->old_off_date)
                    ->update(['status' => Schedule::STATUS_KERJA, 'shift_id' => null]);

                Schedule::where('user_id', $swap->requester_id)
                    ->whereDate('date', $swap->new_off_date)
                    ->update(['status' => Schedule::STATUS_LIBUR, 'shift_id' => null]);
            }

            $swap->update([
                'status' => ($swap instanceof ShiftSwapRequest)
                    ? ShiftSwapRequest::STATUS_APPROVED
                    : DayOffSwapRequest::STATUS_APPROVED,
                'approver_id' => $user->id,
                'approver_decided_at' => now(),
                'approver_response_note' => $data['note'] ?? null,
            ]);
        });

        $swapTypeLabel = ($swap instanceof ShiftSwapRequest) ? 'shift' : 'libur';
        AuditLog::log("{$swapTypeLabel}_swap_approved", $user->id, get_class($swap), $swap->id, [
            'note' => $data['note'] ?? null,
        ]);

        // Notify requester
        NotificationService::send(
            $swap->requester_id,
            "{$swapTypeLabel}_swap.approved",
            "Tukar {$swapTypeLabel} Disetujui",
            "Pengajuan Anda disetujui oleh Kepala Divisi. " . ($data['note'] ? "Catatan: {$data['note']}" : ''),
            ($swap instanceof ShiftSwapRequest) ? route('shift-swaps.show', $swap) : route('day-off-swaps.show', $swap),
        );

        // Untuk shift swap: notif juga ke target
        if ($swap instanceof ShiftSwapRequest) {
            NotificationService::send(
                $swap->target_employee_id,
                'shift_swap.approved',
                'Tukar Shift Disetujui',
                "KD telah menyetujui tukar shift Anda dengan {$swap->requester->name}. Jadwal sudah diupdate.",
                route('shift-swaps.show', $swap),
            );
        }

        return redirect()->route('kadiv.approvals.index')->with('success', 'Pengajuan disetujui, jadwal telah diupdate.');
    }

    /**
     * Resolve swap (shift atau day-off) by type + id.
     */
    private function resolveSwap(string $swapType, int $id)
    {
        if ($swapType === 'shift') {
            return ShiftSwapRequest::findOrFail($id);
        } elseif ($swapType === 'dayoff') {
            return DayOffSwapRequest::findOrFail($id);
        }
        abort(404, 'Unknown swap type');
    }
}
