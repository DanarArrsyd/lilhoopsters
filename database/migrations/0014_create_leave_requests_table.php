<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules');
            $table->date('leave_date');
            $table->enum('type', ['sick', 'permit']);
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'auto_approved'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('auto_approve_at')->useCurrent(); // created_at + 72h, always overridden on insert
            $table->timestamps();
        });

        // Add FK from attendances.leave_request_id -> leave_requests.id
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['leave_request_id']);
        });
        Schema::dropIfExists('leave_requests');
    }
};
