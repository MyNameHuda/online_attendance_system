<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\OfficeLocation;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = Carbon::today();
        $office = OfficeLocation::getMain();

        $todaySchedule = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $history = Attendance::where('user_id', $user->id)
            ->orderByDesc('date')
            ->limit(14)
            ->get();

        return view('attendance.index', compact('user', 'today', 'office', 'todaySchedule', 'todayAttendance', 'history'));
    }

    public function clockIn(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->validate([
                'latitude'  => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'photo'     => ['required'],
            ]);

            $user = $request->user();
            $today = Carbon::today();

            // 1. Cek schedule hari ini
            $schedule = Schedule::with('shift')
                ->where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (! $schedule) {
                return $this->fail($request, 'Tidak ada jadwal untuk hari ini. Hubungi Admin.');
            }

            if ($schedule->isLibur()) {
                return $this->fail($request, 'Hari ini adalah hari libur. Tidak bisa melakukan absen.');
            }

            // 2. Cek geofencing (skip kalau demo_mode)
            $lat = (float) $request->latitude;
            $lng = (float) $request->longitude;
            $demoMode = $request->boolean('demo_mode');

            if (! $demoMode) {
                $office = OfficeLocation::getMain();
                if (! $office) {
                    return $this->fail($request, 'Lokasi kantor utama belum dikonfigurasi. Hubungi Admin.');
                }
                $distance = $office->distanceFrom($lat, $lng);
                if (! $office->isWithinRadius($lat, $lng)) {
                    return $this->fail($request, sprintf(
                        'Anda di luar jangkauan kantor (%.0f meter dari titik absen, maksimal %d meter). Centang "Demo Mode" untuk bypass.',
                        $distance,
                        $office->radius_meters
                    ));
                }
            }

            // 3. Cek double clock-in
            $existing = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();
            if ($existing && $existing->hasClockedIn()) {
                return $this->fail($request, 'Anda sudah melakukan absensi masuk hari ini.');
            }

            // 4. Simpan foto
            $photoPath = $this->savePhoto($request, $user->id, 'in');

            // 5. Upsert
            DB::transaction(function () use ($user, $today, $photoPath, $lat, $lng, $existing) {
                if ($existing) {
                    $existing->update([
                        'clock_in_time' => now(),
                        'clock_in_photo' => $photoPath,
                        'clock_in_lat' => $lat,
                        'clock_in_lng' => $lng,
                    ]);
                } else {
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $today,
                        'clock_in_time' => now(),
                        'clock_in_photo' => $photoPath,
                        'clock_in_lat' => $lat,
                        'clock_in_lng' => $lng,
                    ]);
                }
            });

            AuditLog::log('clock_in', $user->id, 'Attendance', $existing?->id ?? null, [
                'lat' => $lat, 'lng' => $lng, 'demo_mode' => $demoMode,
            ]);

            return $this->success($request, 'Absen masuk berhasil.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->fail($request, collect($e->errors())->flatten()->first());
        } catch (\Throwable $e) {
            return $this->fail($request, 'Server error: ' . $e->getMessage());
        }
    }

    public function clockOut(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $request->validate([
                'latitude'  => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'photo'     => ['required'],
            ]);

            $user = $request->user();
            $today = Carbon::today();

            $schedule = Schedule::where('user_id', $user->id)->whereDate('date', $today)->first();
            if (! $schedule || $schedule->isLibur()) {
                return $this->fail($request, 'Tidak bisa absen pulang di hari libur.');
            }

            $lat = (float) $request->latitude;
            $lng = (float) $request->longitude;
            $demoMode = $request->boolean('demo_mode');

            if (! $demoMode) {
                $office = OfficeLocation::getMain();
                if (! $office) {
                    return $this->fail($request, 'Lokasi kantor utama belum dikonfigurasi.');
                }
                if (! $office->isWithinRadius($lat, $lng)) {
                    return $this->fail($request, 'Anda di luar jangkauan kantor. Centang "Demo Mode" untuk bypass.');
                }
            }

            $att = Attendance::where('user_id', $user->id)->whereDate('date', $today)->first();

            if (! $att || ! $att->hasClockedIn()) {
                return $this->fail($request, 'Anda belum melakukan absen masuk hari ini.');
            }

            if ($att->hasClockedOut()) {
                return $this->fail($request, 'Anda sudah melakukan absensi pulang hari ini.');
            }

            $photoPath = $this->savePhoto($request, $user->id, 'out');

            $att->update([
                'clock_out_time' => now(),
                'clock_out_photo' => $photoPath,
                'clock_out_lat' => $lat,
                'clock_out_lng' => $lng,
            ]);

            AuditLog::log('clock_out', $user->id, 'Attendance', $att->id, [
                'lat' => $lat, 'lng' => $lng, 'demo_mode' => $demoMode,
            ]);

            return $this->success($request, 'Absen pulang berhasil.');
        } catch (\Illuminate\Validation\ValidationException $e) {
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->fail($request, collect($e->errors())->flatten()->first());
        } catch (\Throwable $e) {
            Log::error('ClockOut error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->fail($request, 'Server error: ' . $e->getMessage());
        }
    }

    private function savePhoto(Request $request, int $userId, string $kind): string
    {
        $file = $request->file('photo');
        // Jika dari Blob (via fetch + dataUrlToBlob), tipe bisa 'image/jpeg' atau tanpa ekstensi
        $ext = 'jpg';
        if ($file->getClientMimeType()) {
            $mimeParts = explode('/', $file->getClientMimeType());
            $ext = $mimeParts[1] ?? 'jpg';
        }
        $filename = "attendance/{$userId}/" . now()->format('Y-m-d_His') . "-{$kind}.{$ext}";
        return $file->storeAs("attendance/{$userId}", now()->format('Y-m-d_His') . "-{$kind}.{$ext}", 'public');
    }

    private function success(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    private function fail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return back()->withErrors(['general' => $message]);
    }
}
