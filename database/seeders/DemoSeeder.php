<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'name');

        // Super Admin
        User::firstOrCreate(['email' => 'superadmin@demo.com'], [
            'name'                => 'Super Admin',
            'password'            => Hash::make('password'),
            'role_id'             => $roles['super_admin'],
            'registration_status' => 'approved',
            'whatsapp_number'     => '081234567890',
        ]);

        // Admin
        User::firstOrCreate(['email' => 'admin@demo.com'], [
            'name'                => 'Admin Demo',
            'password'            => Hash::make('password'),
            'role_id'             => $roles['admin'],
            'registration_status' => 'approved',
        ]);

        // Coach
        $coachUser = User::firstOrCreate(['email' => 'coach@demo.com'], [
            'name'                => 'Coach Budi',
            'password'            => Hash::make('password'),
            'role_id'             => $roles['coach'],
            'registration_status' => 'approved',
        ]);
        $coach = Coach::firstOrCreate(['user_id' => $coachUser->id], [
            'phone'          => '082345678901',
            'specialization' => 'Dribbling & Shooting',
            'is_active'      => true,
        ]);

        // Parent
        User::firstOrCreate(['email' => 'parent@demo.com'], [
            'name'                => 'Orang Tua Demo',
            'password'            => Hash::make('password'),
            'role_id'             => $roles['parent'],
            'registration_status' => 'approved',
        ]);

        // Sample data: Location, Program, Package, Schedule
        $location = Location::firstOrCreate(['name' => 'Lil Hoopsters Jakarta Selatan'], [
            'address'   => 'Jl. Sudirman No. 1, Jakarta Selatan',
            'is_active' => true,
        ]);

        $program = Program::firstOrCreate(['name' => 'Mini Ballers (3–5yr)'], [
            'description'     => 'Program untuk anak usia 3–5 tahun',
            'min_age_months'  => 36,
            'max_age_months'  => 60,
            'is_active'       => true,
        ]);

        Package::firstOrCreate(['name' => 'Registrasi Mini Ballers'], [
            'location_id'   => $location->id,
            'type'          => 'registration',
            'price'         => 500000,
            'session_count' => 0,
            'is_active'     => true,
        ]);

        Package::firstOrCreate(['name' => '8 Sesi / Bulan'], [
            'location_id'   => $location->id,
            'type'          => 'regular',
            'price'         => 800000,
            'session_count' => 8,
            'validity_days' => 30,
            'is_active'     => true,
        ]);

        Schedule::firstOrCreate([
            'location_id' => $location->id,
            'program_id'  => $program->id,
            'day_of_week' => strtolower(now()->format('l')),
            'start_time'  => '09:00:00',
        ], [
            'coach_id'     => $coach->id,
            'end_time'     => '10:00:00',
            'max_capacity' => 12,
            'is_active'    => true,
        ]);
    }
}
