<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog as AuditLogModel;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLog extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterAction  = '';
    public string $filterActorId = '';
    public string $filterDateFrom = '';
    public string $filterDateTo   = '';

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterAction(): void   { $this->resetPage(); }
    public function updatingFilterActorId(): void  { $this->resetPage(); }
    public function updatingFilterDateFrom(): void { $this->resetPage(); }
    public function updatingFilterDateTo(): void   { $this->resetPage(); }

    public function render()
    {
        $logs = AuditLogModel::query()
            ->with('actor')
            ->when($this->search, fn($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->when($this->filterAction, fn($q) => $q->where('action', $this->filterAction))
            ->when($this->filterActorId, fn($q) => $q->where('actor_id', $this->filterActorId))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        $actionOptions = AuditLogModel::query()->distinct()->orderBy('action')->pluck('action');
        $actorOptions  = AuditLogModel::query()
            ->select('actor_id', 'actor_name')
            ->whereNotNull('actor_id')
            ->distinct()
            ->orderBy('actor_name')
            ->get();

        return view('livewire.admin.audit-log', [
            'logs'           => $logs,
            'actionOptions'  => $actionOptions,
            'actorOptions'   => $actorOptions,
        ]);
    }
}
