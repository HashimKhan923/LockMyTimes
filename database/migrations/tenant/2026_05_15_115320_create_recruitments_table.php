<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->longText('requirements')->nullable();
            $table->longText('benefits')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'temporary'])->default('full_time');
            $table->enum('work_mode', ['on_site', 'remote', 'hybrid'])->default('on_site');
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'lead', 'executive'])->default('mid');
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->boolean('show_salary')->default(false);
            $table->integer('openings')->default(1);
            $table->date('posted_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->enum('status', ['draft', 'published', 'paused', 'closed', 'filled'])->default('draft');
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);
            $table->foreignId('hiring_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->json('custom_questions')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('cover_letter_path')->nullable();
            $table->text('cover_letter_text')->nullable();
            $table->json('custom_answers')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->date('available_from')->nullable();
            $table->string('source')->nullable();          // LinkedIn, referral, etc.
            $table->foreignId('referred_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('stage', ['applied', 'screening', 'interview', 'assessment', 'offer', 'hired', 'rejected', 'withdrawn'])->default('applied');
            $table->integer('rating')->nullable();      // 1–5
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_stage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('from_stage')->nullable();
            $table->string('to_stage');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['phone', 'video', 'in_person', 'technical', 'panel', 'culture'])->default('video');
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('meeting_url')->nullable();
            $table->string('location')->nullable();
            $table->json('interviewer_ids')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show', 'rescheduled'])->default('scheduled');
            $table->integer('rating')->nullable();
            $table->text('feedback')->nullable();
            $table->enum('recommendation', ['strong_yes', 'yes', 'maybe', 'no', 'strong_no'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('candidate_stage_history');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_postings');
    }
};