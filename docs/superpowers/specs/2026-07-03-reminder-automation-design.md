# Reminder Automation — Design Spec

**Status:** Approved for planning
**Part of:** System-development roadmap — subsystem 1 of 5 (scheduler infra folded in here → reminders → audit log → backup → analytics)

## Context

The app already has a working scheduler foundation:

- `Schedule::command('reminders:send')->dailyAt('08:00')` → `ReminderService::runDueReminders()`
  - **Renewal reminder**: enrollments expiring soon (days or ≤2 sessions left)
  - **Payment reminder**: currently reminds on *every* pending transaction daily, deduped by a 3-day cooldown (not tied to an actual deadline)
- `Schedule::command('transactions:expire')->dailyAt('02:00')` → flips pending transactions older than 7 days (`TransactionExpiryService::DEFAULT_DAYS`) to `expired`
- `Schedule::command('attendance:mark-no-shows')->dailyAt('03:00')`

All three are registered in `routes/console.php` and rely on the server cron running `php artisan schedule:run` every minute (already required infra — no new cron entry needed for this subsystem).

**Gap identified:** two things the user asked for don't exist yet:
1. No "tomorrow you have a session" reminder at all.
2. Payment reminder isn't tied to an actual due date — it's a blanket daily sweep.

`Transaction` has no stored due-date column. `expired_at` is only stamped *after* a transaction dies (post-mortem timestamp, not a deadline). The de facto deadline is `created_at + TransactionExpiryService::DEFAULT_DAYS` (7 days). No migration needed — the due date is computed, not stored.

## Scope

Two changes, both reusing the existing `ReminderService` / `NotificationService` pattern:

1. **New: H-1 session reminder.** A new scheduled command sends one combined notification per parent at 19:00, listing every session (regular or private) any of their children have tomorrow.
2. **Refine: H-3 payment reminder.** `ReminderService::remindOutstanding()` changes from "remind every pending transaction daily" to "remind only when the computed due date (`created_at + 7 days`) is ≤3 days away and the transaction hasn't expired yet."

Out of scope: due-date column on `Transaction`, changing the 08:00/02:00/03:00 schedule times for existing jobs, SMS channel, admin-configurable reminder timing (hardcoded like the existing jobs).

## Design

### 1. H-1 Session Reminder

**New command:** `app/Console/Commands/SendSessionReminders.php`
- Signature: `reminders:sessions`
- Scheduled: `Schedule::command('reminders:sessions')->dailyAt('19:00')` in `routes/console.php`
- Delegates to `ReminderService::remindTomorrowSessions()`

**New method:** `ReminderService::remindTomorrowSessions(): int`
- Target date = `today()->addDay()`
- Query: all `Child` records with an approved `program`-type enrollment (regular or private — both live in `schedules` with a `day_of_week`) whose schedule's `day_of_week` matches tomorrow's weekday, and the enrollment is currently active (`started_at <= tomorrow` and `expires_at` null-or->=tomorrow, mirroring `ChildSchedulePlanner::isSessionValidOn`). Reuse `ChildSchedulePlanner::sessionsOn()` per child rather than duplicating the day/expiry logic.
- Group results by parent (`child->user_id`). Skip parents with zero sessions tomorrow (no notification sent = no-op, not an empty message).
- For each parent with ≥1 session tomorrow, build one combined message:

  ```
  Title: "Sesi Besok" / "Session Tomorrow"
  Body:  one line per child+session — "{child} — {program}, {location}, {HH:MM}"
         (for private sessions with no program row, fall back to "Private Session")
  ```

- Dedup key: `type = 'session_reminder'`, `data.date = <tomorrow's date string>`, scoped per parent — reuses the existing `Notification.data` JSON column and a cooldown check identical in shape to `ReminderService::recentlySent()` but keyed by date instead of a foreign id (a session reminder for the same date should never resend, no 3-day window needed — the "already sent for this date" check is a simple equality, not a rolling cooldown).
- Channel: `NotificationService::send(..., email: true)` — in-app + WhatsApp (existing behavior of `send()`) + email (new: pass `email: true` with a short `emailDetails` table of child/session rows, reusing `ParentNotification` mailable).

### 2. H-3 Payment Reminder (refined)

`ReminderService::remindOutstanding()` changes its query:

```php
$dueSoonFrom = today()->subDays(TransactionExpiryService::DEFAULT_DAYS); // oldest allowed
$dueSoonTo   = today()->subDays(TransactionExpiryService::DEFAULT_DAYS - 3); // 3 days left

$pending = Transaction::query()
    ->where('status', 'pending')
    ->whereDate('created_at', '>=', $dueSoonFrom)   // not yet expired
    ->whereDate('created_at', '<=', $dueSoonTo)      // ≤3 days remain
    ->with(['user', 'child', 'package'])
    ->get();
```

`sendPayment()` stays as-is (message content unchanged); only the *selection* query changes. The existing `recentlySent()` 3-day cooldown still guards against duplicate sends if the command runs more than once in the window (belt-and-suspenders — `dailyAt` already prevents this in practice).

A pending transaction now gets reminded **once**, on the day it crosses into the "≤3 days left" window (a 3-day-wide date range hitting a daily job naturally fires once per transaction, then the cooldown suppresses any accidental repeats).

### 3. Localization

New lang keys under `messages.reminders.*` (EN + ID) for the H-1 title/body template and email subject. Reuse existing `messages.status.*` where applicable.

### 4. Testing

- `tests/Feature/Console/SendSessionRemindersTest.php`:
  - Child with a regular session tomorrow → parent gets one notification containing the program/location/time.
  - Child with a private session tomorrow → parent gets one notification (fallback label if no program).
  - Two children of the same parent, both with sessions tomorrow → **one** combined notification, not two.
  - Two children of different parents → each parent gets their own notification.
  - Child with no session tomorrow → no notification.
  - Running the command twice in the same day → no duplicate notification (dedup).
- `tests/Feature/Admin/ReminderTest.php` (extend existing): add cases for `remindOutstanding()` — transaction 2 days old → not reminded; 4 days old (3 days left) → reminded; 6 days old (1 day left) → reminded; 8 days old (already past the expiry window, would've been auto-expired) → not reminded (defensive; `TransactionExpiryService` would have already flipped it in production, but the query itself should also not match).

### 5. Files touched

- New: `app/Console/Commands/SendSessionReminders.php`
- New: `tests/Feature/Console/SendSessionRemindersTest.php`
- Edit: `app/Services/ReminderService.php` (new `remindTomorrowSessions()` method + new dedup helper; refined `remindOutstanding()` query)
- Edit: `routes/console.php` (register the new scheduled command)
- Edit: `lang/en/messages.php`, `lang/id/messages.php` (new `reminders.*` keys)
- Edit: `tests/Feature/Admin/ReminderTest.php` (new H-3 window test cases)

No new migrations. No changes to `NotificationService` (its existing `email` flag already supports what's needed).
