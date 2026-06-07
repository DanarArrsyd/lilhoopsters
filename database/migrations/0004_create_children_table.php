<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('photo')->nullable();
            $table->string('school')->nullable();
            $table->text('medical_notes')->nullable();
            $table->uuid('qr_identifier')->unique();
            $table->enum('status', ['unregistered', 'pending', 'active', 'inactive'])->default('unregistered');
            $table->string('jersey_name')->nullable();
            $table->string('jersey_number')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
