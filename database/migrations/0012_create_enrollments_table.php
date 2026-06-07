<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->enum('type', ['registration', 'program']);
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->foreignId('package_id')->constrained('packages');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->text('member_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('started_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedSmallInteger('remaining_sessions')->nullable();
            $table->unsignedSmallInteger('total_sessions')->nullable();
            $table->timestamps();
        });

        // Now add the FK from transactions.enrollment_id -> enrollments.id
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
        });
        Schema::dropIfExists('enrollments');
    }
};
