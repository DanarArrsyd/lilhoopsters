<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('parent_name');
            $table->string('child_name')->nullable();
            $table->string('whatsapp')->nullable();
            $table->enum('source', ['walk_in', 'instagram', 'whatsapp', 'referral', 'web', 'other'])->default('other');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->enum('status', ['new', 'contacted', 'trial_scheduled', 'trial_done', 'converted', 'lost'])->default('new');
            $table->date('trial_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
