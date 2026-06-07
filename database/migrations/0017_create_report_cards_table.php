<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('coaches')->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('period_label'); // e.g. "Mei 2026"
            $table->date('period_start');
            $table->date('period_end');
            $table->text('overall_notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::create('report_card_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained('report_cards')->cascadeOnDelete();
            $table->enum('category', ['dribbling', 'passing', 'shooting', 'defense', 'attitude', 'discipline']);
            $table->unsignedTinyInteger('score'); // 1-5
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['report_card_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_scores');
        Schema::dropIfExists('report_cards');
    }
};
