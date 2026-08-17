<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            // Null = applies tenant-wide (today's behavior, fully backward compatible). Set =
            // this holiday only applies to employees assigned to that location — needed for a
            // genuinely multi-country/multi-office workforce where not every office observes
            // the same public holidays.
            $table->foreignId('location_id')->nullable()->after('state')->constrained('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
