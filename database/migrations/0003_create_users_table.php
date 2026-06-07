<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable(); // null for Google OAuth users
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->text('address')->nullable();
            $table->string('occupation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('registration_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
