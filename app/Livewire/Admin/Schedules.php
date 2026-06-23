<?php

namespace App\Livewire\Admin;

use App\Models\Coach;
use App\Models\Location;
use App\Models\Program;
use App\Models\Schedule;
use Livewire\Component;
use Livewire\WithPagination;

class Schedules extends Component
{
    use WithPagination;

    public string $search        = '';
    public ?int $filterLocation  = null;
    public string $filterType    = '';
    public bool $showModal       = false;
    public ?int $editingId       = null;

    public ?int $location_id     = null;
    public ?int $program_id      = null;
    public ?int $coach_id        = null;
    public string $type          = 'regular';
    public string $day_of_week   = '';
    public string $start_time    = '';
    public string $end_time      = '';
    public int $max_capacity     = 20;
    public bool $is_active       = true;

    // 12-hour picker fields
    public string $startHour   = '8';
    public string $startMinute = '00';
    public string $startPeriod = 'AM';
    public string $endHour     = '9';
    public string $endMinute   = '00';
    public string $endPeriod   = 'AM';

    const DAYS = [
        'monday'    => 'Monday',
        'tuesday'   => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday'  => 'Thursday',
        'friday'    => 'Friday',
        'saturday'  => 'Saturday',
        'sunday'    => 'Sunday',
    ];

    protected function rules(): array
    {
        return [
            'location_id'  => 'required|integer|exists:locations,id',
            'program_id'   => 'required|integer|exists:programs,id',
            'type'         => 'required|in:regular,private',
            'coach_id'     => $this->type === 'private'
                                ? 'required|integer|exists:coaches,id'
                                : 'nullable|integer|exists:coaches,id',
            'day_of_week'  => 'required|in:' . implode(',', array_keys(self::DAYS)),
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => ['required', 'date_format:H:i', function ($attr, $value, $fail) {
                if (!$this->start_time || !$value) return;
                $start = \Carbon\Carbon::createFromFormat('H:i', $this->start_time);
                $end   = \Carbon\Carbon::createFromFormat('H:i', $value);
                // Support cross-midnight (e.g. 11 PM → 12 AM)
                if ($end->lte($start)) $end->addDay();
                if ($start->diffInMinutes($end) < 15) {
                    $fail('End time must be at least 15 minutes after start time.');
                }
            }],
            'max_capacity' => 'required|integer|min:1|max:100',
        ];
    }

    protected $messages = [
        'coach_id.required' => 'A coach must be assigned for Private schedules.',
    ];

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterLocation(): void { $this->resetPage(); }
    public function updatingFilterType(): void     { $this->resetPage(); }

    // Recompute 24h strings whenever any 12h picker field changes
    public function updatedStartHour(): void   { $this->start_time = $this->to24h($this->startHour, $this->startMinute, $this->startPeriod); }
    public function updatedStartMinute(): void { $this->start_time = $this->to24h($this->startHour, $this->startMinute, $this->startPeriod); }
    public function updatedStartPeriod(): void { $this->start_time = $this->to24h($this->startHour, $this->startMinute, $this->startPeriod); }
    public function updatedEndHour(): void     { $this->end_time   = $this->to24h($this->endHour,   $this->endMinute,   $this->endPeriod); }
    public function updatedEndMinute(): void   { $this->end_time   = $this->to24h($this->endHour,   $this->endMinute,   $this->endPeriod); }
    public function updatedEndPeriod(): void   { $this->end_time   = $this->to24h($this->endHour,   $this->endMinute,   $this->endPeriod); }

    public function toggleStartPeriod(): void
    {
        $this->startPeriod = $this->startPeriod === 'AM' ? 'PM' : 'AM';
        $this->start_time  = $this->to24h($this->startHour, $this->startMinute, $this->startPeriod);
    }

    public function toggleEndPeriod(): void
    {
        $this->endPeriod = $this->endPeriod === 'AM' ? 'PM' : 'AM';
        $this->end_time  = $this->to24h($this->endHour, $this->endMinute, $this->endPeriod);
    }

    private function to24h(string $hour, string $minute, string $period): string
    {
        $h = (int) $hour;
        $m = (int) $minute;
        if ($period === 'AM') {
            $h = ($h === 12) ? 0 : $h;
        } else {
            $h = ($h === 12) ? 12 : $h + 12;
        }
        return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
    }

    private function setPickerFrom24h(string $time24, string $prefix): void
    {
        [$hStr, $mStr] = explode(':', substr($time24, 0, 5)) + ['0', '00'];
        $h = (int) $hStr;
        $period = $h >= 12 ? 'PM' : 'AM';
        $h12    = $h % 12 === 0 ? 12 : $h % 12;

        $this->{$prefix . 'Hour'}   = (string) $h12;
        $this->{$prefix . 'Minute'} = str_pad((int) $mStr, 2, '0', STR_PAD_LEFT);
        $this->{$prefix . 'Period'} = $period;
    }

    public function updatedType(): void
    {
        if ($this->type === 'regular') {
            $this->coach_id = null;
        }
        $this->resetValidation('coach_id');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $schedule = Schedule::findOrFail($id);
        $this->editingId    = $id;
        $this->location_id  = $schedule->location_id;
        $this->program_id   = $schedule->program_id;
        $this->coach_id     = $schedule->coach_id;
        $this->type         = $schedule->type;
        $this->day_of_week  = $schedule->day_of_week;
        $this->start_time   = substr($schedule->start_time, 0, 5);
        $this->end_time     = substr($schedule->end_time, 0, 5);
        $this->setPickerFrom24h($this->start_time, 'start');
        $this->setPickerFrom24h($this->end_time,   'end');
        $this->max_capacity = $schedule->max_capacity;
        $this->is_active    = $schedule->is_active;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'location_id'  => $this->location_id,
            'program_id'   => $this->program_id,
            'type'         => $this->type,
            'coach_id'     => $this->type === 'private' ? $this->coach_id : null,
            'day_of_week'  => $this->day_of_week,
            'start_time'   => $this->start_time,
            'end_time'     => $this->end_time,
            'max_capacity' => $this->max_capacity,
            'is_active'    => $this->is_active,
        ];

        if ($this->editingId) {
            Schedule::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Schedule updated.');
        } else {
            Schedule::create($data);
            session()->flash('success', 'Schedule created.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->update(['is_active' => ! $schedule->is_active]);
    }

    public function confirmDelete(int $id): void
    {
        Schedule::findOrFail($id)->delete();
        session()->flash('success', 'Schedule deleted.');
    }

    public function resetForm(): void
    {
        $this->editingId    = null;
        $this->location_id  = null;
        $this->program_id   = null;
        $this->coach_id     = null;
        $this->type         = 'regular';
        $this->day_of_week  = '';
        $this->start_time   = '08:00';
        $this->end_time     = '09:00';
        $this->startHour    = '8';  $this->startMinute = '00'; $this->startPeriod = 'AM';
        $this->endHour      = '9';  $this->endMinute   = '00'; $this->endPeriod   = 'AM';
        $this->max_capacity = 20;
        $this->is_active    = true;
        $this->resetValidation();
    }

    public function render()
    {
        $schedules = Schedule::with(['location', 'program', 'coach.user'])
            ->when($this->filterLocation, fn($q) => $q->where('location_id', $this->filterLocation))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->search, fn($q) => $q
                ->whereHas('location', fn($l) => $l->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('program',  fn($p) => $p->where('name', 'like', "%{$this->search}%")))
            ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->orderBy('start_time')
            ->get();

        // Group by location (rows already ordered by day then start time).
        $groups = $schedules
            ->groupBy('location_id')
            ->sortBy(fn($items) => optional($items->first()->location)->name)
            ->values();

        return view('livewire.admin.schedules', [
            'groups'        => $groups,
            'totalCount'    => $schedules->count(),
            'locationCount' => $groups->count(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'programs'  => Program::where('is_active', true)->orderBy('min_age_months')->get(),
            'coaches'   => Coach::with('user')
                ->join('users', 'coaches.user_id', '=', 'users.id')
                ->where('coaches.is_active', true)
                ->orderBy('users.name')
                ->select('coaches.*')
                ->get(),
            'days'      => self::DAYS,
        ]);
    }
}
