# Reminder Automation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refine the payment reminder to fire H-3 before a transaction's implicit deadline, and add a new H-1 "session tomorrow" reminder combined per parent.

**Architecture:** Both changes live in the existing `App\Services\ReminderService`, which already powers the daily `reminders:send` scheduled command and is used consistently across the codebase for dedup + `NotificationService` delivery (in-app + WhatsApp + optional email). The H-1 reminder adds one new thin Artisan command (`reminders:sessions`) registered in `routes/console.php`, scheduled separately at 19:00 since it targets "tomorrow" rather than "today's backlog."

**Tech Stack:** Laravel 11 scheduler (`Illuminate\Console\Scheduling`), Pest tests, existing `ChildSchedulePlanner` support class for day-of-week session resolution.

## Global Constraints

- No new migrations — the payment due date is computed (`created_at + TransactionExpiryService::DEFAULT_DAYS`), not stored.
- Notification titles/bodies in this codebase are **not translated** (existing `ReminderService::sendRenewal`/`sendPayment` use hardcoded English strings) — this plan follows that existing convention rather than introducing new `lang/*.php` keys, which would be inconsistent with the rest of `ReminderService`.
- Server cron already runs `php artisan schedule:run` every minute (required by the pre-existing `reminders:send`/`transactions:expire`/`attendance:mark-no-shows` jobs) — no new ops setup needed.
- Manual reminder sends (`Owner::sendPaymentReminder`) call `ReminderService::sendPayment($t, force: true)` directly and must remain unaffected by the H-3 date-window change to `remindOutstanding()`.

---

### Task 1: Refine payment reminder to H-3 window

**Files:**
- Modify: `app/Services/ReminderService.php:39-45` (the `remindOutstanding()` method)
- Modify: `tests/Feature/Admin/ReminderTest.php`

**Interfaces:**
- Consumes: `App\Services\TransactionExpiryService::DEFAULT_DAYS` (currently `7`, already defined in `app/Services/TransactionExpiryService.php`).
- Produces: `ReminderService::remindOutstanding(): int` — same signature as today, only its selection query changes. `ReminderService::sendPayment()` is unchanged (still callable directly with `force: true` for manual sends).

- [ ] **Step 1: Write the failing test — old transaction (2 days old) is not reminded**

Add to `tests/Feature/Admin/ReminderTest.php`, replacing the existing `'command creates payment reminder for pending transaction'` test block with these three (a transaction 2 days old is too fresh, one 5 days old is in the H-3 window, one 8 days old would already be expired):

```php
it('does not remind a payment less than 4 days old (not due soon yet)', function () {
    Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 200000,
        'created_at' => today()->subDays(2),
    ]);

    $this->artisan('reminders:send')->assertSuccessful();

    expect(Notification::where('type', ReminderService::PAYMENT)->count())->toBe(0);
});

it('reminds a payment 4-7 days old (within 3 days of the implicit due date)', function () {
    $t = Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 200000,
        'created_at' => today()->subDays(5),
    ]);

    $this->artisan('reminders:send')->assertSuccessful();

    $note = Notification::where('type', ReminderService::PAYMENT)->first();
    expect($note)->not->toBeNull();
    expect($note->user_id)->toBe($t->user_id);
    expect($note->data['transaction_id'])->toBe($t->id);
});

it('does not remind a payment older than 7 days (already past the auto-expiry window)', function () {
    Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 200000,
        'created_at' => today()->subDays(9),
    ]);

    $this->artisan('reminders:send')->assertSuccessful();

    expect(Notification::where('type', ReminderService::PAYMENT)->count())->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify the "4-7 days old" and boundary tests fail**

Run: `php artisan test tests/Feature/Admin/ReminderTest.php --filter="does not remind a payment less than 4 days old|reminds a payment 4-7 days old|does not remind a payment older than 7 days"`

Expected: all three FAIL — every pending transaction currently gets a reminder regardless of age, so the "less than 4 days" and "older than 7 days" cases will wrongly show a notification.

- [ ] **Step 3: Refine `remindOutstanding()`**

In `app/Services/ReminderService.php`, add the import at the top (alongside the existing `use` statements):

```php
use App\Services\TransactionExpiryService;
```

Replace the existing `remindOutstanding()` method:

```php
public static function remindOutstanding(): int
{
    $pending = Transaction::query()
        ->where('status', 'pending')
        ->with(['user', 'child', 'package'])
        ->get();

    return $pending->reduce(fn($n, $t) => $n + (self::sendPayment($t) ? 1 : 0), 0);
}
```

with:

```php
public static function remindOutstanding(): int
{
    // A pending transaction has no stored due date — TransactionExpiryService
    // auto-expires it after DEFAULT_DAYS, so that's its implicit deadline.
    // Remind only in the last 3 days of that window (age 4..DEFAULT_DAYS).
    $dueSoonFrom = today()->subDays(TransactionExpiryService::DEFAULT_DAYS);
    $dueSoonTo   = today()->subDays(TransactionExpiryService::DEFAULT_DAYS - 3);

    $pending = Transaction::query()
        ->where('status', 'pending')
        ->whereDate('created_at', '>=', $dueSoonFrom)
        ->whereDate('created_at', '<=', $dueSoonTo)
        ->with(['user', 'child', 'package'])
        ->get();

    return $pending->reduce(fn($n, $t) => $n + (self::sendPayment($t) ? 1 : 0), 0);
}
```

- [ ] **Step 4: Fix the pre-existing test that assumed "every pending transaction gets reminded"**

The original `'command creates payment reminder for pending transaction'` test created a transaction with the default `created_at` (today, age 0 — now outside the window). It was replaced in Step 1's three new tests, so there is nothing further to change here — confirm no other test in the file creates a `Transaction::factory()` and expects a reminder without setting `created_at`. Search: `grep -n "Transaction::factory" tests/Feature/Admin/ReminderTest.php` should show only the three new tests plus the untouched `'manual payment reminder creates a notification'` test (which calls `sendPayment()` directly via `Owner::sendPaymentReminder`, bypassing `remindOutstanding()`'s date filter entirely, so it needs no change).

- [ ] **Step 5: Run the full reminder test file**

Run: `php artisan test tests/Feature/Admin/ReminderTest.php`

Expected: all tests PASS (renewal tests untouched, three new payment-window tests pass, manual-send test still passes since it doesn't go through `remindOutstanding()`).

- [ ] **Step 6: Commit**

```bash
git add app/Services/ReminderService.php tests/Feature/Admin/ReminderTest.php
git commit -m "feat: refine payment reminder to fire H-3 before implicit due date"
```

---

### Task 2: Add H-1 "session tomorrow" reminder

**Files:**
- Create: `app/Console/Commands/SendSessionReminders.php`
- Modify: `app/Services/ReminderService.php` (add `SESSION` constant, `remindTomorrowSessions()`, and a date-based dedup helper)
- Modify: `routes/console.php` (register the new scheduled command)
- Create: `tests/Feature/Console/SendSessionRemindersTest.php`

**Interfaces:**
- Consumes: `App\Support\ChildSchedulePlanner::approvedEnrollments(Child $child): Collection` and `ChildSchedulePlanner::sessionsOn(Collection $enrollments, Carbon $date): Collection` (both already exist, unchanged — return `Enrollment` models with `schedule.program`/`schedule.location` eager-loaded). Consumes `App\Services\NotificationService::send(int $userId, string $type, string $title, string $body, array $data = [], bool $email = false, array $emailDetails = [])` (already exists, unchanged).
- Produces: `ReminderService::SESSION` (string constant `'session_reminder'`), `ReminderService::remindTomorrowSessions(): int`. The Artisan command `reminders:sessions` (no arguments).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Console/SendSessionRemindersTest.php`:

```php
<?php

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->location = Location::factory()->create();
    $this->program  = Program::factory()->create(['name' => 'MVP']);
    $this->tomorrow = strtolower(now()->addDay()->format('l'));
});

function enrolledChildForTomorrow(
    string $tomorrow,
    Location $location,
    ?Program $program,
    string $type = 'regular',
    ?string $childName = null,
): Child {
    $parent = User::factory()->withRole('parent')->approved()->create();
    $child  = Child::factory()->create(['user_id' => $parent->id, 'name' => $childName ?? 'Test Child']);

    $schedule = Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $type === 'private' ? null : $program->id,
        'type'        => $type,
        'day_of_week' => $tomorrow,
        'start_time'  => '16:00:00',
        'end_time'    => '17:00:00',
    ]);

    Enrollment::factory()->program()->approved()->create([
        'child_id'    => $child->id,
        'schedule_id' => $schedule->id,
        'started_at'  => today()->subMonth(),
    ]);

    return $child;
}

it('sends a session reminder for a child with a regular session tomorrow', function () {
    $child = enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program);

    $this->artisan('reminders:sessions')->assertSuccessful();

    $note = Notification::where('type', ReminderService::SESSION)->first();
    expect($note)->not->toBeNull();
    expect($note->user_id)->toBe($child->user_id);
    expect($note->body)->toContain($child->name);
    expect($note->body)->toContain('MVP');
});

it('sends a session reminder with a fallback label for a private session', function () {
    enrolledChildForTomorrow($this->tomorrow, $this->location, null, 'private');

    $this->artisan('reminders:sessions')->assertSuccessful();

    $note = Notification::where('type', ReminderService::SESSION)->first();
    expect($note->body)->toContain('Private Session');
});

it('combines multiple children of the same parent into one notification', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $child1 = Child::factory()->create(['user_id' => $parent->id, 'name' => 'Widia']);
    $child2 = Child::factory()->create(['user_id' => $parent->id, 'name' => 'Kai']);

    foreach ([$child1, $child2] as $child) {
        $schedule = Schedule::factory()->create([
            'location_id' => $this->location->id,
            'program_id'  => $this->program->id,
            'type'        => 'regular',
            'day_of_week' => $this->tomorrow,
        ]);

        Enrollment::factory()->program()->approved()->create([
            'child_id'    => $child->id,
            'schedule_id' => $schedule->id,
            'started_at'  => today()->subMonth(),
        ]);
    }

    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->where('user_id', $parent->id)->count())->toBe(1);

    $note = Notification::where('type', ReminderService::SESSION)->first();
    expect($note->body)->toContain('Widia');
    expect($note->body)->toContain('Kai');
});

it('sends separate notifications to different parents', function () {
    $childA = enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program, 'regular', 'Child A');
    $childB = enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program, 'regular', 'Child B');

    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->count())->toBe(2);
    expect(Notification::where('type', ReminderService::SESSION)->where('user_id', $childA->user_id)->exists())->toBeTrue();
    expect(Notification::where('type', ReminderService::SESSION)->where('user_id', $childB->user_id)->exists())->toBeTrue();
});

it('does not notify a parent whose child has no session tomorrow', function () {
    $otherDay = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
        ->first(fn($d) => $d !== $this->tomorrow);

    enrolledChildForTomorrow($otherDay, $this->location, $this->program);

    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->count())->toBe(0);
});

it('does not duplicate a session reminder if the command runs twice the same day', function () {
    enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program);

    $this->artisan('reminders:sessions')->assertSuccessful();
    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->count())->toBe(1);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Console/SendSessionRemindersTest.php`

Expected: FAIL — `reminders:sessions` command does not exist yet (`Symfony\Component\Console\Exception\CommandNotFoundException` or Pest's artisan-not-found failure).

- [ ] **Step 3: Add `ReminderService::SESSION`, dedup helper, and `remindTomorrowSessions()`**

In `app/Services/ReminderService.php`, add these two imports alongside the existing `use` statements at the top of the file:

```php
use App\Models\Child;
use App\Support\ChildSchedulePlanner;
```

Add the constant next to the existing `RENEWAL`/`PAYMENT` constants:

```php
public const SESSION = 'session_reminder';
```

Add the new method (place it near `remindOutstanding()`, in the "Batch entrypoints" section):

```php
public static function remindTomorrowSessions(): int
{
    $tomorrow = today()->addDay();
    $dateKey  = $tomorrow->toDateString();

    $children = Child::query()
        ->whereHas('enrollments', fn($q) => $q
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id'))
        ->get();

    // Group each child's tomorrow-session line under their parent, so a
    // parent with multiple kids training tomorrow gets one combined message.
    $linesByParent = collect();

    foreach ($children as $child) {
        $enrollments = ChildSchedulePlanner::approvedEnrollments($child);
        $sessions    = ChildSchedulePlanner::sessionsOn($enrollments, $tomorrow);

        foreach ($sessions as $enrollment) {
            $schedule = $enrollment->schedule;

            $line = sprintf(
                '%s — %s, %s, %s',
                $child->name,
                $schedule->program?->name ?? 'Private Session',
                $schedule->location->name,
                Carbon::parse($schedule->start_time)->format('H:i'),
            );

            $linesByParent->put(
                $child->user_id,
                ($linesByParent->get($child->user_id) ?? collect())->push($line),
            );
        }
    }

    $sentCount = 0;

    foreach ($linesByParent as $parentId => $lines) {
        if (self::alreadySentForDate($parentId, self::SESSION, $dateKey)) {
            continue;
        }

        NotificationService::send(
            $parentId,
            self::SESSION,
            'Session Tomorrow',
            $lines->implode('; '),
            ['date' => $dateKey],
            email: true,
            emailDetails: $lines->values()->mapWithKeys(
                fn($line, $i) => ['Session ' . ($i + 1) => $line]
            )->toArray(),
        );

        $sentCount++;
    }

    return $sentCount;
}
```

Add the dedup helper next to the existing `recentlySent()` private method (in the "Dedup" section):

```php
private static function alreadySentForDate(int $userId, string $type, string $dateKey): bool
{
    return Notification::query()
        ->where('user_id', $userId)
        ->where('type', $type)
        ->get(['data'])
        ->contains(fn($n) => ($n->data['date'] ?? null) === $dateKey);
}
```

- [ ] **Step 4: Create the Artisan command**

Create `app/Console/Commands/SendSessionReminders.php`:

```php
<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendSessionReminders extends Command
{
    protected $signature = 'reminders:sessions';

    protected $description = 'Send a combined reminder to each parent whose child has a session tomorrow';

    public function handle(): int
    {
        $sent = ReminderService::remindTomorrowSessions();

        $this->info("Session reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Register the schedule**

In `routes/console.php`, add after the existing `reminders:send` schedule entry:

```php
// H-1 reminder: one combined notification per parent listing tomorrow's sessions.
Schedule::command('reminders:sessions')->dailyAt('19:00');
```

- [ ] **Step 6: Run the new test file**

Run: `php artisan test tests/Feature/Console/SendSessionRemindersTest.php`

Expected: all 6 tests PASS.

- [ ] **Step 7: Run the full test suite**

Run: `php artisan test`

Expected: all tests PASS (no regressions in `ReminderTest.php`, `Owner`-related tests, or anywhere else that touches `ReminderService`/`NotificationService`).

- [ ] **Step 8: Commit**

```bash
git add app/Console/Commands/SendSessionReminders.php app/Services/ReminderService.php routes/console.php tests/Feature/Console/SendSessionRemindersTest.php
git commit -m "feat: add H-1 session-tomorrow reminder, combined per parent"
```

---

## Post-implementation note (manual, not part of test suite)

The `reminders:sessions` schedule entry relies on the same server cron (`* * * * * php artisan schedule:run`) already required by `reminders:send`/`transactions:expire`/`attendance:mark-no-shows`. No new ops setup is needed — confirm this only if deploying to a fresh server that doesn't already run those.
