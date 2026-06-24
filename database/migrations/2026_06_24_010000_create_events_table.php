<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            // Scope: null = applies to all locations / all programs.
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
        });

        // Records which enrollments had their expiry frozen for an event, and
        // by how many days — so the freeze can be reversed cleanly.
        Schema::create('event_enrollment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->unsignedSmallInteger('days_added');
            $table->timestamps();

            $table->unique(['event_id', 'enrollment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_enrollment');
        Schema::dropIfExists('events');
    }
};
