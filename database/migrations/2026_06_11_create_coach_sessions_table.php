<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coach_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->date('session_date');
            $table->enum('role', ['primary', 'assistant'])->default('primary');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('checked_in_at')->useCurrent();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            // One check-in per coach per schedule per day
            $table->unique(['schedule_id', 'coach_id', 'session_date']);
            $table->index(['schedule_id', 'session_date']);
            $table->index(['coach_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_sessions');
    }
};
