<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

// Public: redirect root to login
Route::get('/', fn() => redirect()->route('login'));

// Public trial booking — anyone can submit; creates a web lead.
Route::get('/trial', \App\Livewire\Public\TrialBooking::class)->name('trial');

// ─── Auth routes (guests only) ───────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', \App\Livewire\Auth\RegisterWizard::class)->name('register');
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
        Route::get('/events',      fn() => view('admin.events'))->name('events');
        Route::get('/parents',     fn() => view('admin.parents'))->name('parents');
        Route::get('/players',     fn() => view('admin.players'))->name('players');
        Route::get('/leads',       fn() => view('admin.leads'))->name('leads');
        Route::get('/enrollments',    fn() => view('admin.enrollments'))->name('enrollments');
        Route::get('/payments',       fn() => view('admin.payments'))->name('payments');
        Route::get('/reports',        fn() => view('admin.reports'))->name('reports');
        Route::get('/owner',          fn() => view('admin.owner'))->name('owner');
        Route::get('/news',           fn() => view('admin.news'))->name('news');
        Route::get('/attendances',    fn() => view('admin.attendances'))->name('attendances');
        Route::get('/leave-requests', fn() => view('admin.leave-requests'))->name('leave-requests');
        Route::get('/makeup-classes', fn() => view('admin.makeup-classes'))->name('makeup-classes');
        Route::get('/report-cards',     fn() => view('admin.report-cards'))->name('report-cards');
        Route::get('/members-import',   fn() => view('admin.members-import'))->name('members-import');
        Route::get('/members-template', [\App\Http\Controllers\Admin\MembersTemplateController::class, 'download'])->name('members.template');
        Route::get('/profile',          fn() => view('admin.profile'))->name('profile');
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
        Route::get('/news',        fn() => view('coach.news'))->name('news');
        Route::get('/schedules',   fn() => view('coach.schedules'))->name('schedules');
        Route::get('/attendance',  fn() => view('coach.attendance'))->name('attendance');
        Route::get('/checkin',       fn() => view('coach.checkin'))->name('checkin');
        Route::get('/roster',        fn() => view('coach.roster'))->name('roster');
        Route::get('/report-cards',  fn() => view('coach.report-cards'))->name('report-cards');
        Route::get('/qr-scanner',    fn() => view('coach.qr-scanner'))->name('qr-scanner');
        Route::get('/profile',       fn() => view('coach.profile'))->name('profile');
    });

// ─── Parent routes ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:parent', 'registration.status'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('/complete-profile', \App\Livewire\Portal\CompleteProfile::class)->name('complete-profile');

        Route::middleware('profile.complete')->group(function () {
        Route::get('/dashboard',  fn() => redirect()->route('parent.home'))->name('dashboard');
        Route::get('/home',       \App\Livewire\Portal\Home::class)->name('home');
        Route::get('/players',    fn() => view('parent.players'))->name('players');
        Route::get('/enroll',     \App\Livewire\Portal\EnrollPlayer::class)->name('enroll');
        Route::get('/leaves',     \App\Livewire\Portal\LeaveRequests::class)->name('leaves');
        Route::get('/makeup',     \App\Livewire\Portal\MakeUpClasses::class)->name('makeup');
        Route::get('/private',    \App\Livewire\Portal\PrivateSessions::class)->name('private');
        Route::get('/payments',      fn() => view('parent.payments'))->name('payments');
        Route::get('/attendance',    fn() => view('parent.attendance'))->name('attendance');
        Route::get('/report-cards',  fn() => view('parent.report-cards'))->name('report-cards');
        Route::get('/news',       fn() => view('parent.news'))->name('news');
        Route::get('/profile',    fn() => view('parent.profile'))->name('profile');
        Route::get('/guide',      fn() => view('parent.guide'))->name('guide');
        }); // end profile.complete middleware group
    });
