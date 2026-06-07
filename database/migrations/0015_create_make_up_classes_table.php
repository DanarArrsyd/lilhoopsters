<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('make_up_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('leave_request_id')->constrained('leave_requests');
            $table->foreignId('target_schedule_id')->constrained('schedules');
            $table->date('target_date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Add FK from attendances.make_up_class_id -> make_up_classes.id
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('make_up_class_id')->references('id')->on('make_up_classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['make_up_class_id']);
        });
        Schema::dropIfExists('make_up_classes');
    }
};
