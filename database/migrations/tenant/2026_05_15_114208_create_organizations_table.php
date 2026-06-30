<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ============ Departments ============ */
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('manager_id')->nullable();      // FK to employees (added later)
            $table->string('color', 7)->default('#6C7DF7');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /* ============ Positions / Job Titles ============ */
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->decimal('min_salary', 12, 2)->nullable();
            $table->decimal('max_salary', 12, 2)->nullable();
            $table->enum('level', ['entry', 'junior', 'mid', 'senior', 'lead', 'manager', 'director', 'executive'])->default('mid');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /* ============ Work Locations ============ */
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // "HQ - New York"
            $table->string('code', 20)->nullable()->unique();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 50)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('US');
            $table->string('timezone')->default('America/New_York');

            // Geofence
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('geofence_radius_meters')->default(100);   // attendance must be within this radius

            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_headquarters')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};