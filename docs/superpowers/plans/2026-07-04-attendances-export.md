# Attendances: Date-Range Filter + Excel Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the single-date filter on `admin/attendances` with a from/to date range, and add an "Export Excel" button whose columns adapt to the active tab (Students vs Coaches) and whose rows respect all currently active filters.

**Architecture:** `app/Livewire/Admin/Attendances.php` gains two date-range properties (replacing `filterDate`) used by the existing `when()` query chains, plus an `export()` method that re-runs the same filtered query unpaginated and streams an `.xlsx` file built with PhpSpreadsheet (already a project dependency, already used by `app/Http/Controllers/Admin/MembersTemplateController.php`).

**Tech Stack:** Laravel 11, Livewire 3, PhpSpreadsheet (`phpoffice/phpspreadsheet ^5.8`, already installed), Pest.

## Global Constraints

- Date-range filter replaces `filterDate` entirely — no separate single-date field kept alongside it (per spec section 1).
- Filter logic: `from`-only → `>=`, `to`-only → `<=`, both → between, neither → no date constraint (per spec section 1).
- Export respects all active filters (search, status, schedule, date range) — full result set, not just the current paginated page (per spec section 2).
- Export columns differ by tab; one "Export Excel" button, column set follows `activeTab` silently — no per-tab button duplication (per spec section 2 "UI").
- Filename pattern: `attendances_siswa_<from>_<to>.xlsx` / `attendances_coach_<from>_<to>.xlsx`, where `<from>`/`<to>` are `filterDateFrom`/`filterDateTo` if set, else the literal `semua` (per spec section 2 "Filename").
- Students export columns, in order: Tanggal, Nama Anak, Program, Lokasi, Status, Sumber, Catatan (per spec section 2 "Columns").
- Coaches export columns, in order: Tanggal, Coach, Program, Lokasi, Check-in, Check-out, Durasi (menit) (per spec section 2 "Columns").
- Status label in export = `ucfirst(str_replace('_', ' ', $status))` — same transform the blade table already uses at `resources/views/livewire/admin/attendances.blade.php:93`.
- No async/queued export, no new permission gate (per spec "Out of scope").

---

### Task 1: Date-range filter — component + query

**Files:**
- Modify: `app/Livewire/Admin/Attendances.php:17-33` (properties, `updated*` hooks), `:78-125` (`render()` query chains)
- Modify: `resources/views/livewire/admin/attendances.blade.php:51` (date input → two inputs)
- Test: `tests/Feature/Admin/AttendancesTest.php`

**Interfaces:**
- Produces: `public string $filterDateFrom`, `public string $filterDateTo` on `Attendances` — later tasks (Task 2) reuse these same properties inside `export()`'s query chain.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Admin/AttendancesTest.php` (append at end of file):

```php
it('filters students by date range', function () {
    $inRange  = Child::factory()->create(['name' => 'In Range Child']);
    $outRange = Child::factory()->create(['name' => 'Out Range Child']);

    Attendance::factory()->present()->create([
        'child_id'    => $inRange->id,
        'attended_at' => '2026-06-15',
    ]);
    Attendance::factory()->present()->create([
        'child_id'    => $outRange->id,
        'attended_at' => '2026-05-01',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('filterDateFrom', '2026-06-01')
        ->set('filterDateTo', '2026-06-30')
        ->assertSee('In Range Child')
        ->assertDontSee('Out Range Child');
});

it('filters students with only a from-date bound', function () {
    $recent = Child::factory()->create(['name' => 'Recent Child']);
    $old    = Child::factory()->create(['name' => 'Old Child']);

    Attendance::factory()->present()->create(['child_id' => $recent->id, 'attended_at' => '2026-06-15']);
    Attendance::factory()->present()->create(['child_id' => $old->id, 'attended_at' => '2026-01-01']);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('filterDateFrom', '2026-06-01')
        ->assertSee('Recent Child')
        ->assertDontSee('Old Child');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter "filters students by date range|filters students with only a from-date bound"`
Expected: FAIL — `filterDateFrom` is an undefined property on the Livewire component (the current property is `filterDate`).

- [ ] **Step 3: Replace `filterDate` with the range properties**

In `app/Livewire/Admin/Attendances.php`, replace line 19:

```php
    public string $filterDate  = '';
```

with:

```php
    public string $filterDateFrom = '';
    public string $filterDateTo   = '';
```

Replace line 31:

```php
    public function updatedFilterDate(): void   { $this->resetPage(); }
```

with:

```php
    public function updatedFilterDateFrom(): void { $this->resetPage(); }
    public function updatedFilterDateTo(): void   { $this->resetPage(); }
```

- [ ] **Step 4: Update the coach-session query in `render()`**

Replace line 90:

```php
                ->when($this->filterDate, fn($q) => $q->whereDate('session_date', $this->filterDate))
```

with:

```php
                ->when($this->filterDateFrom, fn($q) => $q->whereDate('session_date', '>=', $this->filterDateFrom))
                ->when($this->filterDateTo,   fn($q) => $q->whereDate('session_date', '<=', $this->filterDateTo))
```

- [ ] **Step 5: Update the student query and stats block in `render()`**

Replace line 107:

```php
            ->when($this->filterDate, fn($q) => $q->whereDate('attended_at', $this->filterDate))
```

with:

```php
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo))
```

Replace lines 112-117:

```php
        $stats = [
            'total'   => Attendance::when($this->filterDate, fn($q) => $q->whereDate('attended_at', $this->filterDate))->count(),
            'present' => Attendance::when($this->filterDate, fn($q) => $q->whereDate('attended_at', $this->filterDate))->where('status', 'present')->count(),
            'absent'  => Attendance::when($this->filterDate, fn($q) => $q->whereDate('attended_at', $this->filterDate))->where('status', 'no_show')->count(),
            'sick'    => Attendance::when($this->filterDate, fn($q) => $q->whereDate('attended_at', $this->filterDate))->whereIn('status', ['sick', 'permit'])->count(),
        ];
```

with:

```php
        $dateScope = fn ($q) => $q
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo));

        $stats = [
            'total'   => $dateScope(Attendance::query())->count(),
            'present' => $dateScope(Attendance::query())->where('status', 'present')->count(),
            'absent'  => $dateScope(Attendance::query())->where('status', 'no_show')->count(),
            'sick'    => $dateScope(Attendance::query())->whereIn('status', ['sick', 'permit'])->count(),
        ];
```

- [ ] **Step 6: Update the blade filter bar**

In `resources/views/livewire/admin/attendances.blade.php`, replace line 51:

```blade
        <x-input type="date" wire:model.live="filterDate" />
```

with:

```blade
        <div class="flex items-center gap-2">
            <x-input type="date" wire:model.live="filterDateFrom" class="flex-1" />
            <span class="text-faint text-xs shrink-0">–</span>
            <x-input type="date" wire:model.live="filterDateTo" class="flex-1" />
        </div>
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Admin/AttendancesTest.php`
Expected: PASS, all tests including the two new ones (no other test in this file references `filterDate`, confirmed by the file contents above).

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/Admin/Attendances.php resources/views/livewire/admin/attendances.blade.php tests/Feature/Admin/AttendancesTest.php
git commit -m "feat: replace attendances single-date filter with date range"
```

---

### Task 2: Excel export

**Files:**
- Modify: `app/Livewire/Admin/Attendances.php` (add `export()`, `exportStudents()`, `exportCoaches()` methods)
- Modify: `resources/views/livewire/admin/attendances.blade.php` (add export button)
- Test: `tests/Feature/Admin/AttendancesTest.php`

**Interfaces:**
- Consumes: `$this->filterDateFrom`, `$this->filterDateTo`, `$this->search`, `$this->filterStatus`, `$this->filterSchedule`, `$this->activeTab` (from Task 1 and existing component state).
- Produces: `export()` — public Livewire action, no params, returns a `StreamedResponse` (via `response()->streamDownload()`). Used only from the blade button; no other task depends on its internals.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Admin/AttendancesTest.php` (append at end of file):

```php
it('exports students attendance as xlsx with the correct filename', function () {
    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('filterDateFrom', '2026-06-01')
        ->set('filterDateTo', '2026-06-30')
        ->call('export')
        ->assertFileDownloaded('attendances_siswa_2026-06-01_2026-06-30.xlsx');
});

it('exports students attendance with default filename when no date range set', function () {
    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->call('export')
        ->assertFileDownloaded('attendances_siswa_semua_semua.xlsx');
});

it('exports coach sessions as xlsx when the coaches tab is active', function () {
    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('activeTab', 'coaches')
        ->call('export')
        ->assertFileDownloaded('attendances_coach_semua_semua.xlsx');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter "exports students attendance|exports coach sessions"`
Expected: FAIL — `Call to undefined method App\Livewire\Admin\Attendances::export()`.

- [ ] **Step 3: Add the export methods**

Add to `app/Livewire/Admin/Attendances.php`, just before `public function render()`:

```php
    private function exportRangeLabel(string $bound): string
    {
        return $bound !== '' ? $bound : 'semua';
    }

    public function export()
    {
        return $this->activeTab === 'coaches'
            ? $this->exportCoaches()
            : $this->exportStudents();
    }

    private function exportStudents()
    {
        $rows = Attendance::with(['child', 'schedule.program', 'schedule.location'])
            ->when($this->search, fn($q) => $q->whereHas('child', fn($c) => $c->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSchedule, fn($q) => $q->where('schedule_id', $this->filterSchedule))
            ->latest('attended_at')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendances');

        $headers = ['A' => 'Tanggal', 'B' => 'Nama Anak', 'C' => 'Program', 'D' => 'Lokasi', 'E' => 'Status', 'F' => 'Sumber', 'G' => 'Catatan'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        foreach (['A' => 16, 'B' => 24, 'C' => 22, 'D' => 22, 'E' => 14, 'F' => 12, 'G' => 30] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        foreach ($rows as $i => $a) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", optional($a->attended_at)->format('Y-m-d'));
            $sheet->setCellValue("B{$row}", $a->child?->name ?? '—');
            $sheet->setCellValue("C{$row}", $a->schedule?->program?->name ?? '—');
            $sheet->setCellValue("D{$row}", $a->schedule?->location?->name ?? '—');
            $sheet->setCellValue("E{$row}", ucfirst(str_replace('_', ' ', $a->status)));
            $sheet->setCellValue("F{$row}", $a->source);
            $sheet->setCellValue("G{$row}", $a->notes ?? '');
        }

        $from = $this->exportRangeLabel($this->filterDateFrom);
        $to   = $this->exportRangeLabel($this->filterDateTo);
        $filename = "attendances_siswa_{$from}_{$to}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function exportCoaches()
    {
        $rows = CoachSession::with(['coach.user', 'schedule.program', 'schedule.location'])
            ->when($this->search, fn($q) => $q->whereHas('coach.user', fn($u) => $u->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('session_date', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('session_date', '<=', $this->filterDateTo))
            ->when($this->filterSchedule, fn($q) => $q->where('schedule_id', $this->filterSchedule))
            ->latest('session_date')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Coach Sessions');

        $headers = ['A' => 'Tanggal', 'B' => 'Coach', 'C' => 'Program', 'D' => 'Lokasi', 'E' => 'Check-in', 'F' => 'Check-out', 'G' => 'Durasi (menit)'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        foreach (['A' => 16, 'B' => 24, 'C' => 22, 'D' => 22, 'E' => 12, 'F' => 12, 'G' => 16] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        foreach ($rows as $i => $s) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", optional($s->session_date)->format('Y-m-d'));
            $sheet->setCellValue("B{$row}", $s->coach?->user?->name ?? '—');
            $sheet->setCellValue("C{$row}", $s->schedule?->program?->name ?? '—');
            $sheet->setCellValue("D{$row}", $s->schedule?->location?->name ?? '—');
            $sheet->setCellValue("E{$row}", optional($s->checked_in_at)->format('H:i'));
            $sheet->setCellValue("F{$row}", $s->checked_out_at ? $s->checked_out_at->format('H:i') : '');
            $sheet->setCellValue("G{$row}", $s->checked_out_at ? $s->checked_in_at->diffInMinutes($s->checked_out_at) : '');
        }

        $from = $this->exportRangeLabel($this->filterDateFrom);
        $to   = $this->exportRangeLabel($this->filterDateTo);
        $filename = "attendances_coach_{$from}_{$to}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

```

- [ ] **Step 4: Add the export button to the blade view**

In `resources/views/livewire/admin/attendances.blade.php`, replace the filter-bar block (the `<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">...</div>` from Task 1 Step 6) so the button sits alongside it — replace:

```blade
    {{-- Shared filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <x-input wire:model.live.debounce.300ms="search"
                 :placeholder="$activeTab === 'coaches' ? 'Search by coach name...' : 'Search by child name...'" />
        <div class="flex items-center gap-2">
            <x-input type="date" wire:model.live="filterDateFrom" class="flex-1" />
            <span class="text-faint text-xs shrink-0">–</span>
            <x-input type="date" wire:model.live="filterDateTo" class="flex-1" />
        </div>
        <x-select wire:model.live="filterSchedule">
            <option value="">{{ __('messages.admin.attendances.all_schedules') }}</option>
            @foreach ($schedules as $s)
                <option value="{{ $s->id }}">{{ $s->program->name }} – {{ ucfirst($s->day_of_week) }}</option>
            @endforeach
        </x-select>
    </div>
```

with:

```blade
    {{-- Shared filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
        <x-input wire:model.live.debounce.300ms="search"
                 :placeholder="$activeTab === 'coaches' ? 'Search by coach name...' : 'Search by child name...'" />
        <div class="flex items-center gap-2">
            <x-input type="date" wire:model.live="filterDateFrom" class="flex-1" />
            <span class="text-faint text-xs shrink-0">–</span>
            <x-input type="date" wire:model.live="filterDateTo" class="flex-1" />
        </div>
        <x-select wire:model.live="filterSchedule">
            <option value="">{{ __('messages.admin.attendances.all_schedules') }}</option>
            @foreach ($schedules as $s)
                <option value="{{ $s->id }}">{{ $s->program->name }} – {{ ucfirst($s->day_of_week) }}</option>
            @endforeach
        </x-select>
        <x-btn variant="secondary" wire:click="export" wire:loading.attr="disabled" wire:target="export">
            {{ __('messages.common.export_excel') }}
        </x-btn>
    </div>
```

Add the translation key. In `lang/en/messages.php`, inside the `'common' => [...]` array, add:

```php
        'export_excel' => 'Export Excel',
```

In `lang/id/messages.php`, inside the `'common' => [...]` array, add:

```php
        'export_excel' => 'Export Excel',
```

(Check both files first — `grep -n "'common' => \[" lang/en/messages.php lang/id/messages.php` to find the right array before editing, since key order may differ between the two files.)

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Admin/AttendancesTest.php`
Expected: PASS, all tests including the three new export tests.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Admin/Attendances.php resources/views/livewire/admin/attendances.blade.php lang/en/messages.php lang/id/messages.php tests/Feature/Admin/AttendancesTest.php
git commit -m "feat: add xlsx export to attendances page"
```

---

### Task 3: Full verification

**Files:** none (verification only)

- [ ] **Step 1: Run the full attendances test file**

Run: `php artisan test tests/Feature/Admin/AttendancesTest.php`
Expected: PASS, all tests (original + Task 1 + Task 2 additions).

- [ ] **Step 2: Run the full test suite**

Run: `php -d memory_limit=512M ./vendor/bin/pest`
Expected: PASS, no regressions (per the environment's known 128M CLI memory ceiling, documented in this repo's session history — use the raised limit for the full-suite run).

- [ ] **Step 3: Manual smoke check**

Visit `admin/attendances` as an admin user in the browser:
- Confirm the two date inputs render side by side with a "–" separator.
- Set only "from", confirm the table narrows to that bound; set only "to", confirm the same; set both, confirm range narrows further.
- Click "Export Excel" on the Students tab, confirm an `.xlsx` file downloads and opens with the 7 documented columns.
- Switch to the Coaches tab, click "Export Excel" again, confirm the coach-specific columns and filename prefix (`attendances_coach_...`).
