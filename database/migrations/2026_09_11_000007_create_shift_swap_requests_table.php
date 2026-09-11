<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_employee_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('requester_shift_id')->constrained('shifts')->restrictOnDelete();
            $table->foreignId('target_shift_id')->constrained('shifts')->restrictOnDelete();
            $table->text('reason');
            $table->text('target_response_note')->nullable();
            $table->text('approver_response_note')->nullable();
            $table->enum('status', ['pending_target', 'pending_kadiv', 'approved', 'rejected', 'cancelled'])
                  ->default('pending_target');
            $table->dateTime('target_responded_at')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approver_decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_swap_requests');
    }
};
