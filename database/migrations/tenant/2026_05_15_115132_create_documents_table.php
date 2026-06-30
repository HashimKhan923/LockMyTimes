<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#6C7DF7');
            $table->string('icon')->nullable();
            $table->enum('visibility', ['public', 'private', 'role_based'])->default('private');
            $table->json('allowed_roles')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->string('version', 10)->default('1.0');
            $table->foreignId('parent_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->enum('category', ['policy', 'contract', 'template', 'form', 'handbook', 'other'])->default('other');
            $table->enum('visibility', ['public', 'private', 'role_based', 'employee_specific'])->default('private');
            $table->json('allowed_roles')->nullable();
            $table->json('allowed_employees')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('requires_acknowledgment')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->integer('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        /* ============ Document Acknowledgments / Signatures ============ */
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('action', ['acknowledged', 'signed', 'declined'])->default('acknowledged');
            $table->longText('signature_data')->nullable();   // base64 canvas image
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_folders');
    }
};