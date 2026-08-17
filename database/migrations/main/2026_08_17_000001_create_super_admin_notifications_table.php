<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super admin bell notifications — one row per recipient super admin (same pattern as
     * NotificationService::notifyAdmins() on the tenant side), since a shared/broadcast row
     * couldn't track per-recipient read state independently.
     */
    public function up(): void
    {
        Schema::connection('main')->create('super_admin_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('super_admin_id')->constrained('super_admins')->cascadeOnDelete();

            $table->string('type')->default('general');
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['super_admin_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('super_admin_notifications');
    }
};
