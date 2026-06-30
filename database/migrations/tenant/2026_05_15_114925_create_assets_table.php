<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Laptop, Phone, Vehicle, ID Card
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('asset_categories');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('asset_code', 30)->unique();    // "LMT-LP-001"
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('description')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_until')->nullable();
            $table->string('vendor')->nullable();
            $table->string('invoice_number')->nullable();
            $table->enum('condition', ['new', 'good', 'fair', 'damaged', 'retired'])->default('new');
            $table->enum('status', ['available', 'assigned', 'in_repair', 'lost', 'retired'])->default('available');
            $table->string('qr_token', 64)->nullable()->unique();
            $table->string('image')->nullable();
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('returned_at')->nullable();
            $table->enum('condition_at_assignment', ['new', 'good', 'fair', 'damaged'])->default('good');
            $table->enum('condition_at_return', ['new', 'good', 'fair', 'damaged'])->nullable();
            $table->text('assignment_notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->string('handover_document')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->enum('type', ['scheduled', 'repair', 'inspection', 'upgrade'])->default('scheduled');
            $table->text('description');
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};