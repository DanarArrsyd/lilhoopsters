<?php

namespace App\Livewire\Admin;

use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Enrollments extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';
    public string $filterType   = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }

    public function approve(int $id): void
    {
        DB::transaction(function () use ($id) {
            $enrollment = Enrollment::with(['child', 'package'])->findOrFail($id);

            $data = [
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ];

            if ($enrollment->type === 'registration') {
                $enrollment->child->update([
                    'status'       => 'active',
                    'registered_at'=> now(),
                ]);
            } elseif ($enrollment->type === 'program') {
                $data['started_at'] = today();
                $data['expires_at'] = $enrollment->package->period_end
                    ?? ($enrollment->package->validity_days
                        ? today()->addDays($enrollment->package->validity_days)
                        : null);

                if ($enrollment->package->session_count) {
                    $data['total_sessions']     = $enrollment->package->session_count;
                    $data['remaining_sessions'] = $enrollment->package->session_count;
                }
            }

            $enrollment->update($data);
        });

        session()->flash('success', 'Enrollment approved.');
    }

    public function reject(int $id): void
    {
        Enrollment::findOrFail($id)->update(['status' => 'rejected']);
        session()->flash('success', 'Enrollment rejected.');
    }

    public function render()
    {
        return view('livewire.admin.enrollments', [
            'enrollments' => Enrollment::with(['child', 'package.location', 'schedule'])
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->filterType,   fn($q) => $q->where('type', $this->filterType))
                ->when($this->search, fn($q) => $q
                    ->whereHas('child', fn($c) => $c->where('name', 'like', "%{$this->search}%")))
                ->orderByRaw("FIELD(status,'pending','approved','rejected','expired')")
                ->orderBy('created_at', 'desc')
                ->paginate(15),
        ]);
    }
}
