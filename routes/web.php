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
        Route::get('/enrollments',    fn() => view('admin.enrollments'))->name('enrollments');
        Route::get('/payments',       fn() => view('admin.payments'))->name('payments');
        Route::get('/attendances',    fn() => view('admin.attendances'))->name('attendances');
        Route::get('/leave-requests', fn() => view('admin.leave-requests'))->name('leave-requests');
        Route::get('/makeup-classes', fn() => view('admin.makeup-classes'))->name('makeup-classes');
        Route::get('/report-cards',   fn() => view('admin.report-cards'))->name('report-cards');
    });

// ─── Super Admin routes ───────────────────────────────────────────────
Route::middleware(['auth', 'role:super_admin', 'registration.status'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/dashboard',       fn() => view('superadmin.dashboard'))->name('dashboard');
        Route::get('/admins',          fn() => view('superadmin.admins'))->name('admins');
        Route::get('/system-settings', fn() => view('superadmin.system-settings'))->name('system-settings');
    });

// ─── Coach routes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'role:coach', 'registration.status'])
    ->prefix('coach')
    ->name('coach.')
    ->group(function () {
        Route::get('/dashboard',   fn() => view('coach.dashboard'))->name('dashboard');
        Route::get('/schedules',   fn() => view('coach.schedules'))->name('schedules');
        Route::get('/attendance',  fn() => view('coach.attendance'))->name('attendance');
        Route::get('/checkin',       fn() => view('coach.checkin'))->name('checkin');
        Route::get('/roster',        fn() => view('coach.roster'))->name('roster');
        Route::get('/report-cards',  fn() => view('coach.report-cards'))->name('report-cards');
        Route::get('/qr-scanner',    fn() => view('coach.qr-scanner'))->name('qr-scanner');
    });

// ─── Parent routes ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:parent', 'registration.status'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('/dashboard',  fn() => view('parent.dashboard'))->name('dashboard');
        Route::get('/players',    fn() => view('parent.players'))->name('players');
        Route::get('/enroll',     fn() => view('parent.enroll'))->name('enroll');
        Route::get('/payments',   fn() => view('parent.payments'))->name('payments');
        Route::get('/leaves',     fn() => view('parent.leaves'))->name('leaves');
        Route::get('/attendance',    fn() => view('parent.attendance'))->name('attendance');
        Route::get('/makeup',        fn() => view('parent.makeup'))->name('makeup');
        Route::get('/report-cards',  fn() => view('parent.report-cards'))->name('report-cards');
        Route::get('/profile',       fn() => view('parent.profile'))->name('profile');
    });
