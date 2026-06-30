<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Shifts (templates) ============ */
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Morning", "Night", "Custom"
            $table->string('code', 20)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_minutes')->default(0);
            $table->boolean('crosses_midnight')->default(false);
            $table->decimal('total_hours', 5, 2);
            $table->string('color', 7)->default('#6C7DF7');
            $table->integer('late_grace_minutes')->default(10);
            $table->integer('early_out_grace_minutes')->default(10);
            $table->json('working_days')->nullable();        // ["mon","tue","wed","thu","fri"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ============ Shift Assignments ============ */
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();             // null = ongoing
            $table->boolean('is_default')->default(false);
            $table->json('overrides')->nullable();            // date-specific overrides
            $table->timestamps();

            $table->index(['employee_id', 'start_date']);
        });

        /* ============ Shift Swap Requests ============ */
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('requestee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('requester_shift_date');
            $table->date('requestee_shift_date');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'approved_by_manager', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_swap_requests');
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shifts');
    }
};