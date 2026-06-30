<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('audience', ['all', 'department', 'location', 'role', 'specific'])->default('all');
            $table->json('audience_filter')->nullable();
            $table->string('banner_image')->nullable();
            $table->boolean('requires_acknowledgment')->default(false);
            $table->boolean('show_on_login')->default(false);
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->integer('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            $table->unique(['announcement_id', 'user_id']);
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('question');
            $table->text('description')->nullable();
            $table->enum('type', ['single_choice', 'multiple_choice', 'rating'])->default('single_choice');
            $table->json('options');                       // ["Option A", "Option B", ...]
            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('selected_options');
            $table->timestamps();
            $table->unique(['poll_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};