<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('date_format', 5)->default('DMY')->after('notification_preferences');
            $table->string('time_format', 2)->default('12')->after('date_format');
            $table->string('theme', 10)->default('system')->after('time_format');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->json('privacy_settings')->nullable()->after('custom_fields');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['date_format', 'time_format', 'theme']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('privacy_settings');
        });
    }
};
