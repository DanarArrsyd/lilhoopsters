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
        Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
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
