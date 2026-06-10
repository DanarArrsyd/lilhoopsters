<?php

namespace App\Livewire\Portal;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceHistory extends Component
{
    use WithPagination;

    public string $filterChildId = '';
    public string $filterStatus  = '';

    public function updatingFilterChildId(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void  { $this->resetPage(); }

    public function render()
    {
        $user     = Auth::user();
        $childIds = $user->children()->pluck('id');

        $attendances = Attendance::whereIn('child_id', $childIds)
            ->with(['child', 'schedule.program', 'schedule.location', 'enrollment.package'])
            ->when($this->filterChildId, fn($q) => $q->where('child_id', $this->filterChildId))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('attended_at', 'desc')
            ->paginate(20);

        // Summary stats per child (all records, not paginated)
        $stats = [];
        foreach ($user->children()->where('status', 'active')->get() as $child) {
            $records = Attendance::where('child_id', $child->id)->get();
            $total   = $records->count();
            $stats[$child->id] = [
                'name'    => $child->name,
                'total'   => $total,
                'present' => $records->where('status', 'present')->count(),
                'absent'  => $records->whereIn('status', ['no_show'])->count(),
                'leave'   => $records->whereIn('status', ['sick', 'permit'])->count(),
                'rate'    => $total > 0
                    ? round($records->where('status', 'present')->count() / $total * 100)
                    : null,
            ];
        }

        $children = $user->children()->whereIn('status', ['active', 'inactive'])->orderBy('name')->get();

        return view('livewire.portal.attendance', compact('attendances', 'stats', 'children'));
    }
}
