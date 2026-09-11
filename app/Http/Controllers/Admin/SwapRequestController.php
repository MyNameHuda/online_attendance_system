<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DayOffSwapRequest;
use App\Models\Division;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SwapRequestController extends Controller
{
    /**
     * Timeline lengkap pengajuan tukar shift & tukar libur — admin/HRD.
     * Menampilkan semua status: pending, approved, rejected, cancelled.
     */
    public function index(Request $request): View
    {
        $type = $request->query('type', 'all'); // all|shift|dayoff

        // Filter tanggal (berdasarkan tanggal relevan per tipe)
        $startDate = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : null;
        $endDate   = $request->query('end_date')   ? Carbon::parse($request->query('end_date'))   : null;

        // Filter status (comma-separated untuk multi-select: "pending_target,approved")
        $statusFilter = $request->query('status');
        $statusList   = $statusFilter ? explode(',', $statusFilter) : [];

        // Filter divisi / karyawan
        $divisionId = $request->query('division_id');
        $userId     = $request->query('user_id');

        // ==== Shift swaps ====
        $shiftQuery = ShiftSwapRequest::with([
            'requester.division', 'targetEmployee.division',
            'requesterShift', 'targetShift', 'approver',
        ])->orderByDesc('created_at');

        $this->applyCommonFilters($shiftQuery, 'date', $startDate, $endDate, $divisionId, $userId);
        if (!empty($statusList)) {
            $shiftQuery->whereIn('status', $statusList);
        }
        $shiftSwaps = $shiftQuery->paginate(15)->withQueryString();

        // ==== Day-off swaps ====
        $dayoffQuery = DayOffSwapRequest::with([
            'requester.division', 'approver',
        ])->orderByDesc('created_at');

        // Untuk dayoff, "date" yang difilter = old_off_date (tanggal libur yang akan ditukar)
        $this->applyCommonFilters($dayoffQuery, 'old_off_date', $startDate, $endDate, $divisionId, $userId);
        if (!empty($statusList)) {
            $dayoffQuery->whereIn('status', $statusList);
        }
        $dayOffSwaps = $dayoffQuery->paginate(15)->withQueryString();

        // ==== Ringkasan semua status ====
        $summary = [
            'shift_total'      => ShiftSwapRequest::count(),
            'shift_pending'    => ShiftSwapRequest::whereIn('status', [
                ShiftSwapRequest::STATUS_PENDING_TARGET,
                ShiftSwapRequest::STATUS_PENDING_KADIV,
            ])->count(),
            'shift_approved'   => ShiftSwapRequest::where('status', ShiftSwapRequest::STATUS_APPROVED)->count(),
            'shift_rejected'   => ShiftSwapRequest::where('status', ShiftSwapRequest::STATUS_REJECTED)->count(),
            'dayoff_total'     => DayOffSwapRequest::count(),
            'dayoff_pending'   => DayOffSwapRequest::where('status', DayOffSwapRequest::STATUS_PENDING)->count(),
            'dayoff_approved'  => DayOffSwapRequest::where('status', DayOffSwapRequest::STATUS_APPROVED)->count(),
            'dayoff_rejected'  => DayOffSwapRequest::where('status', DayOffSwapRequest::STATUS_REJECTED)->count(),
        ];

        $divisions = Division::orderBy('name')->get();
        $employees = User::with('division')->orderBy('name')->get();

        return view('admin.swap-requests.index', compact(
            'shiftSwaps', 'dayOffSwaps', 'summary',
            'divisions', 'employees', 'startDate', 'endDate',
            'type', 'statusFilter',
        ));
    }

    /**
     * Detail 1 pengajuan (shift atau dayoff) + timeline kejadian.
     */
    public function show(Request $request, string $swapType, int $id): View
    {
        $swap = $this->resolveSwap($swapType, $id);
        $swap->load(['requester.division', 'approver']);

        if ($swap instanceof ShiftSwapRequest) {
            $swap->load(['targetEmployee.division', 'requesterShift', 'targetShift']);
        }

        return view('admin.swap-requests.show', compact('swap', 'swapType'));
    }

    /**
     * Apply filter bersama (date range, division, user) ke query.
     */
    private function applyCommonFilters($query, string $dateColumn, ?Carbon $startDate, ?Carbon $endDate, $divisionId, $userId): void
    {
        if ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate->toDateString());
        }
        if ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate->toDateString());
        }
        if ($divisionId) {
            $query->whereHas('requester', fn ($q) => $q->where('division_id', $divisionId));
        }
        if ($userId) {
            $query->where('requester_id', $userId);
        }
    }

    /**
     * Resolve swap (shift atau dayoff) by type + id.
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
