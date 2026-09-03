<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's standard password-reset-token table — never existed in this
     * project (the default Laravel scaffold ships one, but this app's own
     * users/tenants migrations replaced it without carrying this table
     * forward). Required for the forgot/reset password flow (admin web,
     * employee web, mobile API) added alongside this migration.
     */
    public function up(): void
    {
        // Some tenants provisioned before this project's own migrations
        // replaced Laravel's default scaffold still have this table sitting
        // in their DB from that original stub, with no tracked migration —
        // don't fail on those, just skip creating it again.
        if (Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
