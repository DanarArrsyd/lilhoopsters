<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children');
            $table->foreignId('enrollment_id')->constrained('enrollments');
            $table->foreignId('schedule_id')->constrained('schedules');
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->unsignedBigInteger('leave_request_id')->nullable(); // FK added after leave_requests
            $table->unsignedBigInteger('make_up_class_id')->nullable();  // FK added after make_up_classes
            $table->enum('status', ['present', 'no_show', 'sick', 'permit', 'make_up']);
            $table->enum('source', ['qr', 'manual', 'system'])->default('qr');
            $table->timestamp('attended_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('session_deducted')->default(true);
            $table->index(['child_id', 'attended_at']);
            $table->index(['schedule_id', 'attended_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
