<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code', 30)->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['in_person', 'online', 'hybrid', 'self_paced'])->default('online');
            $table->enum('category', ['onboarding', 'compliance', 'technical', 'soft_skills', 'leadership', 'safety', 'other'])->default('other');
            $table->string('provider')->nullable();
            $table->string('instructor')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->string('online_url')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->integer('duration_hours')->default(0);
            $table->integer('max_participants')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('issues_certificate')->default(false);
            $table->integer('certificate_valid_months')->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('materials')->nullable();         // links to PDFs, videos
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamp('enrolled_at');
            $table->enum('status', ['enrolled', 'in_progress', 'completed', 'failed', 'dropped'])->default('enrolled');
            $table->integer('progress')->default(0);     // 0–100
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('certificate_path')->nullable();
            $table->date('certificate_expiry')->nullable();
            $table->text('feedback')->nullable();
            $table->integer('rating')->nullable();        // 1–5
            $table->timestamps();
            $table->unique(['training_id', 'employee_id']);
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');                       // "AWS Certified Solutions Architect"
            $table->string('issuer');
            $table->string('credential_id')->nullable();
            $table->string('credential_url')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('certificate_file')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('trainings');
    }
};