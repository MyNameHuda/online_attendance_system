<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\OfficeLocation;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // === Shifts ===
        $shiftPagi = Shift::create(['name' => 'Pagi',  'start_time' => '08:00', 'end_time' => '16:00']);
        $shiftSiang = Shift::create(['name' => 'Siang', 'start_time' => '14:00', 'end_time' => '22:00']);
        $shiftMalam = Shift::create(['name' => 'Malam', 'start_time' => '22:00', 'end_time' => '06:00']);
        $shifts = [$shiftPagi->id, $shiftSiang->id, $shiftMalam->id];

        // === Divisions ===
        $it = Division::create(['name' => 'IT']);
        $finance = Division::create(['name' => 'Finance']);
        $hrd = Division::create(['name' => 'HRD']);

        // === Single Main Office Location (dipakai semua divisi untuk geofencing) ===
        OfficeLocation::create([
            'division_id' => null,
            'name' => 'Kantor Pusat Jakarta',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'radius_meters' => 200,
            'is_main' => true,
        ]);

        // === Users ===
        $admin = User::create([
            'nik' => 'ADM001',
            'name' => 'Admin HRD',
            'email' => 'admin@attendance.test',
            'password' => Hash::make('password123'),
            'division_id' => $hrd->id,
            'position' => 'HR Manager',
            'role' => User::ROLE_ADMIN,
        ]);

        // IT Division
        $itKadiv = User::create([
            'nik' => 'ITK01',
            'name' => 'Budi Santoso',
            'email' => 'budi.kadiv@attendance.test',
            'password' => Hash::make('password123'),
            'division_id' => $it->id,
            'position' => 'IT Manager',
            'role' => User::ROLE_KADIV,
        ]);
        $itEmployees = [];
        foreach ([
            ['NIK' => 'IT001', 'name' => 'Andi Wijaya',     'email' => 'andi@attendance.test',   'position' => 'Backend Developer'],
            ['NIK' => 'IT002', 'name' => 'Citra Lestari',   'email' => 'citra@attendance.test',  'position' => 'Frontend Developer'],
            ['NIK' => 'IT003', 'name' => 'Dedi Kurniawan',  'email' => 'dedi@attendance.test',   'position' => 'DevOps'],
            ['NIK' => 'IT004', 'name' => 'Hendra Gunawan',  'email' => 'hendra@attendance.test', 'position' => 'QA Engineer'],
        ] as $u) {
            $itEmployees[] = User::create([
                'nik' => $u['NIK'],
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make('password123'),
                'division_id' => $it->id,
                'position' => $u['position'],
                'role' => User::ROLE_KARYAWAN,
            ]);
        }

        // Finance Division
        $finKadiv = User::create([
            'nik' => 'FNK01',
            'name' => 'Siti Aminah',
            'email' => 'siti.kadiv@attendance.test',
            'password' => Hash::make('password123'),
            'division_id' => $finance->id,
            'position' => 'Finance Manager',
            'role' => User::ROLE_KADIV,
        ]);
        $finEmployees = [];
        foreach ([
            ['NIK' => 'FN001', 'name' => 'Eko Prabowo',     'email' => 'eko@attendance.test',    'position' => 'Accountant'],
            ['NIK' => 'FN002', 'name' => 'Fitri Handayani', 'email' => 'fitri@attendance.test',  'position' => 'Finance Staff'],
        ] as $u) {
            $finEmployees[] = User::create([
                'nik' => $u['NIK'],
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make('password123'),
                'division_id' => $finance->id,
                'position' => $u['position'],
                'role' => User::ROLE_KARYAWAN,
            ]);
        }

        // HRD Division (no KD karyawan extra, KD = HR Manager兼任)
        $hrdEmployees = [];
        foreach ([
            ['NIK' => 'HR001', 'name' => 'Gita Permata', 'email' => 'gita@attendance.test', 'position' => 'HR Staff'],
        ] as $u) {
            $hrdEmployees[] = User::create([
                'nik' => $u['NIK'],
                'name' => $u['name'],
                'email' => $u['email'],
                'password' => Hash::make('password123'),
                'division_id' => $hrd->id,
                'position' => $u['position'],
                'role' => User::ROLE_KARYAWAN,
            ]);
        }

        // === Schedules: 14 hari ke depan, mulai hari ini ===
        // Pola: 5 hari kerja, 2 hari libur, berulang.
        $allUsers = array_merge(
            [$itKadiv, $finKadiv, $admin],
            $itEmployees,
            $finEmployees,
            $hrdEmployees
        );

        $startDate = Carbon::today();
        $schedulesCreated = 0;

        // Hendra (IT004) punya pola shift MIRROR Andi: kalau Andi Pagi → Hendra Siang, dll.
        // Tujuannya: Andi & Hendra selalu beda shift di setiap hari kerja, sehingga bisa saling tukar.
        $andi = collect($itEmployees)->firstWhere('email', 'andi@attendance.test');
        $hendra = collect($itEmployees)->firstWhere('email', 'hendra@attendance.test');
        $mirrorMap = [
            $shiftPagi->id  => $shiftSiang->id,
            $shiftSiang->id => $shiftMalam->id,
            $shiftMalam->id => $shiftPagi->id,
        ];

        foreach ($allUsers as $user) {
            for ($i = 0; $i < 14; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dayOfWeek = $date->dayOfWeek; // 0=Sun, 6=Sat
                $isWeekend = in_array($dayOfWeek, [0, 6], true);

                if ($isWeekend) {
                    Schedule::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'shift_id' => null,
                        'status' => Schedule::STATUS_LIBUR,
                    ]);
                } else {
                    // Hendra → mirror Andi
                    if ($hendra && $user->id === $hendra->id) {
                        $andiShiftId = Schedule::where('user_id', $andi->id)
                            ->whereDate('date', $date->toDateString())
                            ->value('shift_id');
                        $shiftId = $mirrorMap[$andiShiftId] ?? $shifts[$i % 3];
                    } else {
                        // Rotasi shift default: hari 1&4=Pagi, 2&5=Siang, 3=Malam
                        $shiftIdx = $i % 3;
                        $shiftId = $shifts[$shiftIdx];
                    }
                    Schedule::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'shift_id' => $shiftId,
                        'status' => Schedule::STATUS_KERJA,
                    ]);
                }
                $schedulesCreated++;
            }
        }

        $this->command->info("Seeded " . count($allUsers) . " users, 3 divisions, 3 shifts, {$schedulesCreated} schedules.");
        $this->command->info("Default password: password123");
        $this->command->info("Hendra's schedule mirrors Andi's (eligible for shift swap testing).");
    }
}
