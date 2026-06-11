<?php

namespace App\Livewire\Portal;

use App\Models\ReportCard;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ReportCards extends Component
{
    use WithPagination;

    public int|string $filterChild = '';

    // Detail modal
    public bool $showDetail  = false;
    public ?int $detailCardId = null;

    public function updatingFilterChild(): void { $this->resetPage(); }

    public function openDetail(int $id): void
    {
        $childIds = Auth::user()->children()->pluck('id');
        $card = ReportCard::whereIn('child_id', $childIds)
            ->where('status', 'published')
            ->find($id);

        if (!$card) return;

        $this->detailCardId = $id;
        $this->showDetail   = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail   = false;
        $this->detailCardId = null;
    }

    public function render()
    {
        $childIds = Auth::user()->children()->pluck('id');
        $children = Auth::user()->children()->orderBy('name')->get();

        $cards = ReportCard::whereIn('child_id', $childIds)
            ->where('status', 'published')
            ->when($this->filterChild, fn($q) => $q->where('child_id', $this->filterChild))
            ->with(['child', 'coach.user', 'scores'])
            ->latest('published_at')
            ->paginate(10);

        $detailCard = null;
        if ($this->detailCardId) {
            $detailCard = ReportCard::whereIn('child_id', $childIds)
                ->where('status', 'published')
                ->with(['child', 'coach.user', 'scores'])
                ->find($this->detailCardId);
        }

        return view('livewire.portal.report-cards', compact('cards', 'children', 'detailCard'));
    }
}
