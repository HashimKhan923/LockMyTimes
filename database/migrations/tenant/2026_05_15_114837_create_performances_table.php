<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Goals / OKRs ============ */
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('parent_goal_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['okr', 'smart', 'kpi', 'personal'])->default('smart');
            $table->enum('category', ['individual', 'team', 'department', 'company'])->default('individual');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('progress')->default(0);       // 0–100
            $table->enum('status', ['not_started', 'in_progress', 'at_risk', 'completed', 'cancelled'])->default('not_started');
            $table->decimal('weight', 5, 2)->default(1);
            $table->json('key_results')->nullable();
            $table->timestamps();
        });

        /* ============ Performance Review Cycles ============ */
        Schema::create('review_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // "2025 Annual Review"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['annual', 'semi_annual', 'quarterly', 'monthly', 'project_based', 'probation'])->default('annual');
            $table->enum('status', ['draft', 'open', 'in_progress', 'closed'])->default('draft');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        /* ============ Performance Reviews ============ */
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_cycle_id')->constrained('review_cycles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('review_type', ['self', 'manager', 'peer', 'subordinate', '360'])->default('manager');
            $table->enum('status', ['pending', 'in_progress', 'submitted', 'acknowledged', 'completed'])->default('pending');
            $table->decimal('overall_rating', 3, 2)->nullable();
            $table->json('competency_ratings')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('manager_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        /* ============ Review Responses (Q&A) ============ */
        Schema::create('review_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('performance_reviews')->cascadeOnDelete();
            $table->string('question');
            $table->enum('question_type', ['text', 'rating', 'multiple_choice', 'yes_no'])->default('text');
            $table->text('answer')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        /* ============ 1-on-1 Meetings ============ */
        Schema::create('one_on_ones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            $table->json('agenda')->nullable();
            $table->text('notes')->nullable();
            $table->json('action_items')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'missed'])->default('scheduled');
            $table->timestamps();
        });

        /* ============ Kudos / Peer Recognition ============ */
        Schema::create('kudos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('to_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('badge')->nullable();         // "Team Player", "Innovation", etc.
            $table->text('message');
            $table->boolean('is_public')->default(true);
            $table->integer('reactions_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kudos');
        Schema::dropIfExists('one_on_ones');
        Schema::dropIfExists('review_responses');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('review_cycles');
        Schema::dropIfExists('goals');
    }
};