<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_sessions', function (Blueprint $table) {
            // Set when the system auto-closes a session at its scheduled end
            // because the coach never checked out.
            $table->boolean('auto_closed')->default(false)->after('checked_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('coach_sessions', function (Blueprint $table) {
            $table->dropColumn('auto_closed');
        });
    }
};
