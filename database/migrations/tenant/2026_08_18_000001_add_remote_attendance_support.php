<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // 'location_id' remains the employee's primary location. This just tells the rest of
            // the app how to treat it: onsite employees are geofenced against it as before; remote
            // employees skip geofencing entirely (a location may still be on file for reporting);
            // hybrid employees get the relaxed remote treatment while keeping location context.
            $table->enum('employment_mode', ['onsite', 'remote', 'hybrid'])->default('onsite')->after('location_id');
        });

        /* ============ Multiple assigned locations per employee ============ */
        Schema::create('employee_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['employee_id', 'location_id']);
        });

        // Backfill: every employee who already has a primary location_id gets a matching pivot
        // row, so nothing about today's single-location behavior regresses.
        $existingAssignments = DB::connection('tenant')->table('employees')
            ->whereNotNull('location_id')
            ->select('id', 'location_id')
            ->orderBy('id')
            ->get();

        foreach ($existingAssignments as $employee) {
            DB::connection('tenant')->table('employee_locations')->insert([
                'employee_id' => $employee->id,
                'location_id' => $employee->location_id,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_remote_clockin')->default(false)->after('is_geofence_breach');
            $table->string('clock_in_city')->nullable()->after('clock_in_ip');
            $table->string('clock_in_country', 2)->nullable()->after('clock_in_city');
            $table->string('clock_in_timezone')->nullable()->after('clock_in_country');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['is_remote_clockin', 'clock_in_city', 'clock_in_country', 'clock_in_timezone']);
        });

        Schema::dropIfExists('employee_locations');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('employment_mode');
        });
    }
};
