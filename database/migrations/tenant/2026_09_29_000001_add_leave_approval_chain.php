<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('tenant')->statement(
            "ALTER TABLE leave_requests MODIFY status ENUM('pending','pending_final','approved','rejected','cancelled') DEFAULT 'pending'"
        );

        // Mirrors expense_approvals' shape — a per-level audit trail of who decided what.
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('approval_level');
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');

        DB::connection('tenant')->statement(
            "ALTER TABLE leave_requests MODIFY status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending'"
        );
    }
};
