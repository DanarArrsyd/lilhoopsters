<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->unsignedBigInteger('enrollment_id')->nullable(); // FK added after enrollments table
            $table->string('transaction_code')->unique();
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'paid', 'rejected', 'expired'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_proof')->nullable();
            $table->text('payment_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
