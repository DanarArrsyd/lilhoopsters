<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\CoachSession;
use App\Models\Schedule;
use Livewire\Component;
use Livewire\WithPagination;

class Attendances extends Component
{
    use WithPagination;

    public string $activeTab    = 'students';
    public string $search      = '';
    public string $filterDateFrom = '';
    public string $filterDateTo   = '';
    public string $filterStatus = '';
    public int|string $filterSchedule = '';

    // Override modal
    public bool $showOverride    = false;
    public ?int $overrideId      = null;
    public string $overrideStatus = '';
    public string $overrideNotes  = '';

    public function updatedActiveTab(): void    { $this->resetPage(); $this->search = ''; }
    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterDateFrom(): void { $this->resetPage(); }
    public function updatedFilterDateTo(): void   { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterSchedule(): void { $this->resetPage(); }

    public function openOverride(int $id): void
    {
        $record = Attendance::findOrFail($id);
        $this->overrideId     = $id;
        $this->overrideStatus = $record->status;
        $this->overrideNotes  = $record->notes ?? '';
        $this->showOverride   = true;
    }

    public function saveOverride(): void
    {
        $this->validate([
            'overrideStatus' => 'required|in:present,no_show,sick,permit,make_up',
            'overrideNotes'  => 'nullable|string|max:500',
        ]);

        $attendance = Attendance::findOrFail($this->overrideId);
        $oldStatus  = $attendance->status;

        $attendance->update([
            'status' => $this->overrideStatus,
            'notes'  => $this->overrideNotes ?: null,
            'source' => 'manual',
        ]);

        AuditLog::record(
            'attendance.overridden',
            $attendance,
            'Overrode attendance for ' . ($attendance->child?->name ?? 'unknown child') . " to {$this->overrideStatus}",
            ['old_status' => $oldStatus, 'new_status' => $this->overrideStatus],
        );

        $this->showOverride = false;
        $this->overrideId   = null;
        session()->flash('success', 'Attendance updated.');
    }

    public function closeOverride(): void
    {
        $this->showOverride = false;
        $this->overrideId   = null;
    }

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

    public function render()
    {
        $schedules = Schedule::where('is_active', true)->with(['program', 'location'])->get();

        if ($this->activeTab === 'coaches') {
            // Close any sessions left open past their scheduled end time.
            CoachSession::autoCloseStale();

            $coachSessions = CoachSession::with(['coach.user', 'schedule.program', 'schedule.location'])
                ->when($this->search, fn($q) =>
                    $q->whereHas('coach.user', fn($u) => $u->where('name', 'like', "%{$this->search}%"))
                )
                ->when($this->filterDateFrom, fn($q) => $q->whereDate('session_date', '>=', $this->filterDateFrom))
                ->when($this->filterDateTo,   fn($q) => $q->whereDate('session_date', '<=', $this->filterDateTo))
                ->when($this->filterSchedule, fn($q) => $q->where('schedule_id', $this->filterSchedule))
                ->latest('session_date')
                ->paginate(20);

            return view('livewire.admin.attendances', [
                'attendances'   => null,
                'coachSessions' => $coachSessions,
                'stats'         => [],
                'schedules'     => $schedules,
            ]);
        }

        $query = Attendance::with(['child', 'schedule.program', 'schedule.location', 'coach.user'])
            ->when($this->search, function ($q) {
                $q->whereHas('child', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSchedule, fn($q) => $q->where('schedule_id', $this->filterSchedule))
            ->latest('attended_at');

        $dateScope = fn ($q) => $q
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo));

        $stats = [
            'total'   => $dateScope(Attendance::query())->count(),
            'present' => $dateScope(Attendance::query())->where('status', 'present')->count(),
            'absent'  => $dateScope(Attendance::query())->where('status', 'no_show')->count(),
            'sick'    => $dateScope(Attendance::query())->whereIn('status', ['sick', 'permit'])->count(),
        ];

        return view('livewire.admin.attendances', [
            'attendances'   => $query->paginate(20),
            'coachSessions' => null,
            'stats'         => $stats,
            'schedules'     => $schedules,
        ]);
    }
}
