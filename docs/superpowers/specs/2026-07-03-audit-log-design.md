# Audit Log — Design Spec

**Status:** Approved for planning
**Part of:** System-development roadmap — subsystem 2 of 5 (reminders ✅ → **audit log** → backup → analytics)

## Context

No audit trail infrastructure exists today (confirmed: no `activitylog`/`audit` references anywhere in `app/` or `composer.json`). Several models already carry informal "who did it" columns (`verified_by`, `approved_by`, `reviewed_by`, `admin_notes`) but there's no unified, queryable log of "who changed what, when" across the admin/coach-facing governance actions.

## Scope

**Targeted, explicit logging** — not a blanket model-observer/auto-log system. A new `AuditLog::record()` call is added at each of 12 specific action sites in existing Livewire components. Routine CRUD (editing a location, program, package, schedule) is out of scope — only actions that approve/reject/verify/modify something another user submitted, or that change account/settings state.

**Actors logged:** admin, super_admin, coach (their sensitive actions only — report card scoring). Parent and general coach actions (leave submission, check-in) are not logged — those already have their own audit-adjacent trail via `Attendance`/`CoachSession`/`Notification` records.

**Viewers:** admin and super_admin (both already share the `role:admin,super_admin` middleware on the `admin.*` route group — no new route group needed).

## Design

### 1. Schema

New migration, table `audit_logs`:

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('actor_name');   // snapshot — survives actor deletion
    $table->string('actor_role');   // snapshot — 'admin' | 'super_admin' | 'coach'
    $table->string('action');       // e.g. 'payment.verified'
    $table->string('subject_type')->nullable();  // e.g. 'App\Models\Transaction'
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->string('description'); // human-readable summary
    $table->json('meta')->nullable(); // e.g. {"old_status": "pending", "new_status": "paid"}
    $table->string('ip_address', 45)->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['subject_type', 'subject_id']);
    $table->index('actor_id');
    $table->index('action');
});
```

No `updated_at` — the log is immutable (no update/delete UI, no model methods to mutate a row after creation).

### 2. Model + recording helper

`app/Models/AuditLog.php`:

```php
class AuditLog extends Model
{
    public $timestamps = false; // only created_at, set via useCurrent()

    protected $fillable = [
        'actor_id', 'actor_name', 'actor_role', 'action',
        'subject_type', 'subject_id', 'description', 'meta', 'ip_address',
    ];

    protected $casts = ['meta' => 'array'];

    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function subject(): MorphTo { return $this->morphTo(); }

    public static function record(string $action, ?Model $subject, string $description, array $meta = []): self
    {
        $user = Auth::user();

        return self::create([
            'actor_id'     => $user?->id,
            'actor_name'   => $user?->name ?? 'System',
            'actor_role'   => $user?->role?->name ?? 'system',
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'description'  => $description,
            'meta'         => $meta ?: null,
            'ip_address'   => request()?->ip(),
        ]);
    }
}
```

`subject()` uses Eloquent's standard `morphTo()` convention (`subject_type`/`subject_id`), even though nothing currently needs to load the subject back — it's the standard Laravel shape for this pattern and costs nothing extra.

### 3. Call sites (12 actions, 8 files)

| # | Action string | File : Method | Trigger point |
|---|---|---|---|
| 1 | `payment.verified` | `Admin\Payments::verify()` | after `$transaction->update([...'status' => 'paid'...])`, subject = `$transaction` |
| 2 | `payment.rejected` | `Admin\Payments::confirmReject()` | after the `Transaction::findOrFail($this->rejectingId)->update(['status' => 'rejected', ...])` call — **not** in `reject()`, which only opens the confirm dialog |
| 3 | `enrollment.approved` | `Admin\Enrollments::approve()` | after the `DB::transaction()` block completes, subject = `$enrollment` |
| 4 | `enrollment.rejected` | `Admin\Enrollments::reject()` | after `$enrollment->update(['status' => 'rejected'])` |
| 5 | `leave_request.approved` / `leave_request.rejected` | `Admin\LeaveRequests::saveReview()` | after `$leaveRequest->update([...])` — action string picked from the already-computed `$status` local var (`'approved'` → `leave_request.approved`, else `leave_request.rejected`) |
| 6 | `makeup_class.approved` / `makeup_class.rejected` | `Admin\MakeUpClasses::saveReview()` | after the `MakeUpClass::findOrFail(...)->update([...])` call — same `$status`-driven action string pattern as #5 |
| 7 | `report_card.scored` | `Coach\ReportCards::saveScores()` | after the scores loop + status-flip block, subject = `$card` |
| 8 | `attendance.overridden` | `Admin\Attendances::saveOverride()` | after `Attendance::findOrFail($this->overrideId)->update([...])` — capture the record's *previous* status in `meta.old_status` before the update, and `meta.new_status` = `$this->overrideStatus` |
| 9 | `admin_account.created` | `Superadmin\AdminAccounts::create()` | after `User::create([...])`, subject = the new `User` |
| 10 | `admin_account.activated` / `admin_account.deactivated` | `Superadmin\AdminAccounts::toggleActive()` | after `$admin->update(['is_active' => !$admin->is_active])` — action string driven by the *new* `is_active` value |
| 11 | `admin_account.deactivated` | `Superadmin\AdminAccounts::deactivate()` | after `$admin->update(['is_active' => false])` — this is the dedicated confirm-modal deactivate path, separate call site from #10's toggle path, same action string |
| 12 | `system_settings.updated` | `Superadmin\SystemSettings::save()` | after `Setting::setMany([...])`, subject = `null` (no single Eloquent model represents "settings"), `meta` = the array passed to `setMany()` |

For #8 (attendance override), #5/#6 (approve/reject with a single shared method), the implementer must capture any "old value" needed for `meta` *before* calling `->update()`, since the model is mutated in place afterward.

`description` strings are short human-readable sentences built from data already in scope at each call site, e.g.:
- `"Verified payment {$transaction->transaction_code} for {$transaction->user?->name}"`
- `"Approved enrollment for {$enrollment->child->name}"`
- `"Overrode attendance for {$attendance->child->name} to {$this->overrideStatus}"`
- `"Updated system settings"`

Exact strings are finalized per-call-site during planning, not enumerated exhaustively here — the pattern is consistent enough that a plan can specify them per task without ambiguity.

### 4. UI — Audit Log page

New Livewire component `Admin\AuditLog`, view `livewire.admin.audit-log`, wrapper `admin.audit-log`, registered as `Route::get('/audit-log', ...)->name('audit-log')` **inside the existing `admin` route group** (already `role:admin,super_admin`) — no new middleware/route group.

List page follows the existing admin list-page pattern (`x-admin.page-header`, `x-card`, paginated table, `x-input`/`x-select` filters):

- **Filters:** actor (dropdown of users who have ≥1 log entry), action type (dropdown of distinct `action` values), date range (from/to), free-text search over `description`.
- **Table columns:** Waktu (`created_at`, WIB) | Actor (name + role badge) | Action (badge, human-cased) | Subject (`subject_type` short name + `subject_id`, e.g. "Transaction #42") | Description | IP Address.
- Paginated, 20/page, newest first — matches `Admin\Attendances`/`Admin\Payments` conventions already in the codebase.
- Read-only: no edit/delete actions anywhere on this page (immutable log).

**Nav:** link added to `admin-nav-desktop.blade.php`/`admin-nav.blade.php` (visible to both admin and super_admin since both browse under `/admin`), and a second link added to `superadmin-nav.blade.php` pointing at the same `admin.audit-log` route (so a super_admin reaches it directly from their own nav without needing to know the URL).

### 5. Testing

- `tests/Feature/AuditLogTest.php`: unit-level tests on `AuditLog::record()` (creates a row with correct actor snapshot fields, handles `null` actor gracefully for console/system contexts, `meta` round-trips as an array).
- `tests/Feature/Admin/AuditLogPageTest.php`: page renders for admin/super_admin, 403 for coach/parent, filters work (actor/action/date/search), pagination.
- Extend each of the 8 existing test files covering the 12 action call sites (`PaymentsTest`, `EnrollmentsTest`, `LeaveRequestsTest` (admin), `MakeUpClassesTest` (admin), `ReportCardsTest` (coach), `AttendancesTest`, `AdminAccountsTest`, `SystemSettingsTest`) with one assertion each: performing the action creates an `AuditLog` row with the expected `action` string and `actor_id`.

### 6. Files touched

- New: `database/migrations/..._create_audit_logs_table.php`
- New: `app/Models/AuditLog.php`
- New: `app/Livewire/Admin/AuditLog.php`, `resources/views/livewire/admin/audit-log.blade.php`, `resources/views/admin/audit-log.blade.php`
- New: `tests/Feature/AuditLogTest.php`, `tests/Feature/Admin/AuditLogPageTest.php`
- Edit: `routes/web.php` (1 new route in the existing admin group)
- Edit: `resources/views/components/admin-nav-desktop.blade.php`, `admin-nav.blade.php`, `superadmin-nav.blade.php` (nav links)
- Edit: `app/Livewire/Admin/Payments.php`, `Enrollments.php`, `LeaveRequests.php`, `MakeUpClasses.php`, `Attendances.php` (call sites 1-8)
- Edit: `app/Livewire/Coach/ReportCards.php` (call site 7)
- Edit: `app/Livewire/Superadmin/AdminAccounts.php`, `SystemSettings.php` (call sites 9-12)
- Edit: `tests/Feature/Admin/PaymentsTest.php`, `EnrollmentsTest.php`, `LeaveRequestsTest.php`, `MakeUpClassesTest.php`, `AttendancesTest.php`, `tests/Feature/Superadmin/AdminAccountsTest.php`, `SystemSettingsTest.php`, `tests/Feature/Coach/ReportCardsTest.php` (one new assertion each)

No lang keys needed — this is an internal governance tool; action/description strings follow the existing hardcoded-English convention used by `ReminderService`/`NotificationService`.
