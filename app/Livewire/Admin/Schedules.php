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
    public bool $showModal       = false;
    public ?int $editingId       = null;

    public ?int $location_id     = null;
    public ?int $program_id      = null;
    public ?int $coach_id        = null;
    public string $day_of_week   = '';
    public string $start_time    = '';
    public string $end_time      = '';
    public int $max_capacity     = 20;
    public bool $is_active       = true;

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
            'coach_id'     => 'nullable|integer|exists:coaches,id',
            'day_of_week'  => 'required|in:' . implode(',', array_keys(self::DAYS)),
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'max_capacity' => 'required|integer|min:1|max:100',
        ];
    }

    protected $messages = [
        'end_time.after' => 'End time must be after start time.',
    ];

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterLocation(): void { $this->resetPage(); }

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
        $this->day_of_week  = $schedule->day_of_week;
        $this->start_time   = substr($schedule->start_time, 0, 5);
        $this->end_time     = substr($schedule->end_time, 0, 5);
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
            'coach_id'     => $this->coach_id,
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
        $this->day_of_week  = '';
        $this->start_time   = '';
        $this->end_time     = '';
        $this->max_capacity = 20;
        $this->is_active    = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.schedules', [
            'schedules' => Schedule::with(['location', 'program', 'coach.user'])
                ->when($this->filterLocation, fn($q) => $q->where('location_id', $this->filterLocation))
                ->when($this->search, fn($q) => $q
                    ->whereHas('location', fn($l) => $l->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('program',  fn($p) => $p->where('name', 'like', "%{$this->search}%")))
                ->orderBy('location_id')
                ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
                ->orderBy('start_time')
                ->paginate(15),
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
