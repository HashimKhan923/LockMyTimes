<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('overtime_allowed')->default(false)->after('employment_mode');
            $table->decimal('overtime_rate_multiplier', 4, 2)->nullable()->after('overtime_allowed');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('overtime_started_at')->nullable()->after('clock_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['overtime_allowed', 'overtime_rate_multiplier']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('overtime_started_at');
        });
    }
};
