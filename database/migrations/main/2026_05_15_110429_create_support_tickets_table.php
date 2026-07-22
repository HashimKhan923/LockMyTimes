<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Support Tickets — tenants SaaS owner support channel.
     */
    public function up(): void
    {
        Schema::connection('main')->create('support_tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('super_admins')->nullOnDelete();

            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'waiting_on_customer', 'resolved', 'closed'])->default('open');
            $table->enum('category', ['billing', 'technical', 'feature_request', 'bug', 'other'])->default('technical');

            $table->json('attachments')->nullable();

            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Replies / conversation thread
        Schema::connection('main')->create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->morphs('author');                                 // can be super_admin OR tenant user
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal_note')->default(false);     // staff-only notes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('support_ticket_replies');
        Schema::connection('main')->dropIfExists('support_tickets');
    }
};