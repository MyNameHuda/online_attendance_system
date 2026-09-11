<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Cleanup existing data — keep only 1 main office (or none if fresh)
        $first = DB::table('office_locations')->orderBy('id')->first();
        if ($first) {
            DB::table('office_locations')->where('id', '!=', $first->id)->delete();
        }

        // Step 2: For SQLite, we recreate the table without foreign key + nullable division_id + add is_main
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            Schema::drop('office_locations');
            Schema::create('office_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('division_id')->nullable();
                $table->string('name')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->unsignedInteger('radius_meters')->default(200);
                $table->boolean('is_main')->default(false);
                $table->timestamps();
            });
            // Re-insert data (if any existed before)
            if ($first) {
                DB::table('office_locations')->insert([
                    'id' => $first->id,
                    'division_id' => null,
                    'name' => $first->name,
                    'latitude' => $first->latitude,
                    'longitude' => $first->longitude,
                    'radius_meters' => $first->radius_meters ?? 200,
                    'is_main' => true,
                    'created_at' => $first->created_at ?? now(),
                    'updated_at' => now(),
                ]);
                $maxId = DB::table('office_locations')->max('id');
                if ($maxId) {
                    DB::statement("UPDATE sqlite_sequence SET seq = ? WHERE name = 'office_locations'", [$maxId]);
                }
            }
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        // Best-effort rollback
        Schema::table('office_locations', function (Blueprint $table) {
            $table->dropColumn('is_main');
        });
    }
};
