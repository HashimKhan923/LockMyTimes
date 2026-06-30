<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ QR Codes (one per location/office) ============ */
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('token', 64)->unique();      // unique random string baked into the QR image
            $table->string('label')->nullable();        // "Main Entrance", "2nd Floor"
            $table->boolean('is_active')->default(true);
            $table->boolean('require_selfie')->default(false);
            $table->boolean('rotate_token')->default(false);   // if true, token rotates daily
            $table->timestamp('last_rotated_at')->nullable();
            $table->integer('scan_count')->default(0);
            $table->timestamps();
        });

        /* ============ Attendance Records (clock-in / clock-out) ============ */
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();

            $table->date('work_date');                  // YYYY-MM-DD
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();

            // Geo data captured at clock-in / clock-out
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();
            $table->integer('clock_in_distance_meters')->nullable();  // distance from location centroid
            $table->integer('clock_out_distance_meters')->nullable();

            // Anti-spoof
            $table->string('clock_in_selfie')->nullable();
            $table->string('clock_out_selfie')->nullable();
            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->string('user_agent')->nullable();

            // Computed values
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->decimal('break_hours', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('regular_hours', 5, 2)->default(0);
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->boolean('is_early_out')->default(false);
            $table->integer('early_minutes')->default(0);

            // Status
            $table->enum('source', ['qr', 'web', 'mobile', 'manual', 'biometric'])->default('qr');
            $table->enum('status', ['present', 'absent', 'half_day', 'on_leave', 'holiday', 'weekend'])->default('present');
            $table->boolean('is_geofence_breach')->default(false);
            $table->boolean('is_manual_entry')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->index('work_date');
        });

        /* ============ Attendance Breaks ============ */
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->decimal('duration_minutes', 8, 2)->default(0);
            $table->enum('break_type', ['lunch', 'short_break', 'personal', 'other'])->default('short_break');
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('qr_codes');
    }
};