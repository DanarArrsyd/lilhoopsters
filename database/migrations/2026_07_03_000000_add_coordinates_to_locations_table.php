<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Venue geofence: centre point + allowed radius for attendance checks.
            $table->decimal('latitude', 10, 8)->nullable()->after('maps_url');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->unsignedSmallInteger('radius_m')->default(200)->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'radius_m']);
        });
    }
};
