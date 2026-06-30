<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Task Lists / Columns (Kanban columns within a project) ============ */
        Schema::create('task_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');                              // "Backlog", "To Do", "In Progress"
            $table->string('color', 7)->default('#6C7DF7');
            $table->enum('column_type', ['backlog', 'todo', 'in_progress', 'review', 'done', 'cancelled', 'custom'])->default('custom');
            $table->integer('sort_order')->default(0);
            $table->integer('wip_limit')->nullable();           // Work-in-progress limit
            $table->boolean('is_closed_status')->default(false); // tasks here count as "done"
            $table->timestamps();
        });

        /* ============ Tags / Labels ============ */
        Schema::create('task_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#6C7DF7');
            $table->timestamps();
        });

        /* ============ Tasks ============ */
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('task_list_id')->nullable()->constrained('task_lists')->nullOnDelete();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('task_code', 30)->unique();          // "TSK-001"
            $table->string('title');
            $table->longText('description')->nullable();
            $table->enum('type', ['task', 'bug', 'feature', 'epic', 'story', 'improvement', 'support'])->default('task');

            // Status & priority
            $table->enum('status', [
                'backlog',
                'todo',
                'in_progress',
                'in_review',
                'on_hold',
                'done',
                'cancelled',
            ])->default('todo');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->integer('progress')->default(0);            // 0–100

            // Dates
            $table->date('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();

            // Time
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('logged_hours', 8, 2)->default(0);
            $table->decimal('billable_hours', 8, 2)->default(0);

            // Billing
            $table->boolean('is_billable')->default(false);
            $table->decimal('hourly_rate', 10, 2)->nullable();

            // Other
            $table->integer('sort_order')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_rule')->nullable();         // e.g. {"freq":"weekly","day":"mon"}
            $table->json('custom_fields')->nullable();
            $table->integer('comments_count')->default(0);
            $table->integer('attachments_count')->default(0);
            $table->integer('subtasks_count')->default(0);
            $table->integer('completed_subtasks_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('due_date');
        });

        /* ============ Task Assignees (multiple per task) ============ */
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['task_id', 'employee_id']);
        });

        /* ============ Task Watchers (notification-only) ============ */
        Schema::create('task_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'employee_id']);
        });

        /* ============ Task Tag pivot ============ */
        Schema::create('task_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('task_tag_id')->constrained('task_tags')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'task_tag_id']);
        });

        /* ============ Task Dependencies ============ */
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->enum('type', ['blocks', 'blocked_by', 'relates_to', 'duplicates'])->default('blocked_by');
            $table->timestamps();
            $table->unique(['task_id', 'depends_on_task_id', 'type'], 'task_dep_unique');
        });

        /* ============ Task Checklists (mini sub-items inside a task) ============ */
        Schema::create('task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('item');
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        /* ============ Task Comments ============ */
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_comment_id')->nullable()->constrained('task_comments')->nullOnDelete();
            $table->longText('content');
            $table->json('mentions')->nullable();              // user IDs mentioned with @
            $table->json('attachments')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        /* ============ Task Attachments ============ */
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->string('thumbnail')->nullable();
            $table->timestamps();
        });

        /* ============ Task Activity Log ============ */
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');                           // "status_changed", "assigned", "commented"
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_checklists');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_tag_pivot');
        Schema::dropIfExists('task_watchers');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_tags');
        Schema::dropIfExists('task_lists');
    }
};