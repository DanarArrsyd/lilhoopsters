<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

// Public: redirect root to login
Route::get('/', fn() => redirect()->route('login'));

// ─── Auth routes (guests only) ───────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// ─── Logout (auth required) ──────────────────────────────────────────
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ─── Pending approval page ───────────────────────────────────────────
Route::get('/pending', fn() => view('auth.pending'))
    ->middleware('auth')
    ->name('pending');

// ─── Admin routes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,super_admin', 'registration.status'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard',   fn() => view('admin.dashboard'))->name('dashboard');
        Route::get('/locations',   fn() => view('admin.locations'))->name('locations');
        Route::get('/programs',    fn() => view('admin.programs'))->name('programs');
        Route::get('/packages',    fn() => view('admin.packages'))->name('packages');
        Route::get('/coaches',     fn() => view('admin.coaches'))->name('coaches');
        Route::get('/schedules',   fn() => view('admin.schedules'))->name('schedules');
        Route::get('/parents',     fn() => view('admin.parents'))->name('parents');
        Route::get('/players',     fn() => view('admin.players'))->name('players');
        Route::get('/enrollments', fn() => view('admin.enrollments'))->name('enrollments');
        Route::get('/payments',    fn() => view('admin.payments'))->name('payments');
    });

// ─── Super Admin routes ───────────────────────────────────────────────
Route::middleware(['auth', 'role:super_admin', 'registration.status'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/dashboard', fn() => view('superadmin.dashboard'))->name('dashboard');
    });

// ─── Coach routes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'role:coach', 'registration.status'])
    ->prefix('coach')
    ->name('coach.')
    ->group(function () {
        Route::get('/dashboard', fn() => view('coach.dashboard'))->name('dashboard');
    });

// ─── Parent routes ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:parent', 'registration.status'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('/dashboard', fn() => view('parent.dashboard'))->name('dashboard');
    });
