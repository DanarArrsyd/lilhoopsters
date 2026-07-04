# Attendances: Date-Range Filter + Excel Export — Design

**Date:** 2026-07-04
**Page:** `admin/attendances` (`app/Livewire/Admin/Attendances.php`, `resources/views/livewire/admin/attendances.blade.php`)

## Goal

Replace the single-date filter on the Attendances page with a from/to date range, and add an Excel export button that respects all active filters, with columns that adapt to the active tab (Students vs Coaches).

## 1. Date-range filter

Replace `public string $filterDate = ''` with:

```php
public string $filterDateFrom = '';
public string $filterDateTo   = '';
```

Remove `updatedFilterDate()`; add:

```php
public function updatedFilterDateFrom(): void { $this->resetPage(); }
public function updatedFilterDateTo(): void   { $this->resetPage(); }
```

### Query changes

A shared date-range scope is applied wherever `whereDate('attended_at', $this->filterDate)` or `whereDate('session_date', $this->filterDate)` currently appears (4 call sites: the main student query, the 3 stats sub-queries, and the coach-session query). Replace each with a `when()` chain:

```php
->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo))
```

(substitute `session_date` for the coach-session query). This naturally covers from-only, to-only, both, or neither (no filter = all data), without a separate branch per combination.

### Blade changes

The existing single `<input type="date" wire:model.live="filterDate">` becomes two inputs side by side:

```blade
<input type="date" wire:model.live="filterDateFrom" class="..." />
<span class="text-faint text-xs">–</span>
<input type="date" wire:model.live="filterDateTo" class="..." />
```

Same input styling as the current date field. No new component needed.

## 2. Excel export

### Method

Add one method to `Attendances.php`:

```php
public function export()
{
    return $this->activeTab === 'coaches'
        ? $this->exportCoaches()
        : $this->exportStudents();
}
```

`exportStudents()` and `exportCoaches()` each:
1. Re-run the same query as `render()` for their tab (same `when()` filter chain: search, filterDateFrom/To, filterStatus, filterSchedule), but `->get()` instead of `->paginate()` — full result set, not just the current page.
2. Build a `PhpOffice\PhpSpreadsheet\Spreadsheet`, write a header row (bold, light-gray fill — same styling approach as `MembersTemplateController`), then one row per record.
3. Stream via `response()->streamDownload()` writing through `PhpOffice\PhpSpreadsheet\Writer\Xlsx`.

### Columns

**Students** (`exportStudents`):
| Tanggal | Nama Anak | Program | Lokasi | Status | Sumber | Catatan |
|---|---|---|---|---|---|---|
| `attended_at` (Y-m-d) | `child.name` | `schedule.program.name` | `schedule.location.name` | `status` (present/no_show/sick/permit/make_up, human label) | `source` | `notes` |

**Coaches** (`exportCoaches`):
| Tanggal | Coach | Program | Lokasi | Check-in | Check-out | Durasi (menit) |
|---|---|---|---|---|---|---|
| `session_date` (Y-m-d) | `coach.user.name` | `schedule.program.name` | `schedule.location.name` | `checked_in_at` (H:i) | `checked_out_at` (H:i, blank if still open) | `checked_in_at->diffInMinutes(checked_out_at)` (blank if still open) |

### Filename

`attendances_siswa_<from>_<to>.xlsx` / `attendances_coach_<from>_<to>.xlsx`, where `<from>`/`<to>` are `filterDateFrom`/`filterDateTo` if set, else `semua` (literal, meaning "all/no bound").

### UI

One "Export Excel" button in the filter bar (`wire:click="export"`), visible on both tabs — label and icon stay generic ("Export Excel"); the column set silently follows whichever tab is active, per the approved design (no per-tab button duplication).

## Out of scope

- No async/queued export — full recordset is expected to stay small enough (single academy, per-location data) for synchronous streaming, consistent with the existing `Reports.php` CSV export doing the same.
- No new permission gate — export inherits the existing `admin`/`super_admin` route middleware already guarding `/admin/attendances`.
