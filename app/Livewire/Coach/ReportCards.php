<?php

namespace App\Livewire\Coach;

use App\Models\ReportCard;
use App\Models\ReportCardScore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ReportCards extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    // Score input modal
    public bool $showScoreModal = false;
    public ?int $reportCardId   = null;
    public array $scores        = [];

    const CATEGORIES = ['dribbling', 'passing', 'shooting', 'defense', 'attitude', 'discipline'];

    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openScoreModal(int $id): void
    {
        $card = $this->getOwnedCard($id);

        $this->reportCardId = $id;
        $existing = $card->scores->keyBy('category');

        $this->scores = [];
        foreach (self::CATEGORIES as $cat) {
            $this->scores[$cat] = [
                'score' => $existing->get($cat)?->score ?? '',
                'notes' => $existing->get($cat)?->notes ?? '',
            ];
        }
        $this->showScoreModal = true;
    }

    public function closeScoreModal(): void
    {
        $this->showScoreModal = false;
        $this->reportCardId   = null;
    }

    public function saveScores(): void
    {
        $rules = [];
        foreach (self::CATEGORIES as $cat) {
            $rules["scores.{$cat}.score"] = 'required|integer|min:1|max:5';
            $rules["scores.{$cat}.notes"] = 'nullable|string|max:500';
        }
        $this->validate($rules);

        $card = $this->getOwnedCard($this->reportCardId);

        foreach (self::CATEGORIES as $cat) {
            ReportCardScore::updateOrCreate(
                ['report_card_id' => $card->id, 'category' => $cat],
                ['score' => $this->scores[$cat]['score'], 'notes' => $this->scores[$cat]['notes'] ?? null]
            );
        }

        if ($card->status === 'draft') {
            $card->update(['status' => 'submitted']);
        }

        $this->showScoreModal = false;
        session()->flash('success', 'Scores saved.');
    }

    private function getOwnedCard(int $id): ReportCard
    {
        $coach = Auth::user()->coach;
        if (!$coach) abort(403);

        $card = ReportCard::where('id', $id)->where('coach_id', $coach->id)->with('scores')->first();
        if (!$card) abort(403);

        return $card;
    }

    public function render()
    {
        $coach = Auth::user()->coach;

        $cards = ReportCard::where('coach_id', $coach?->id)
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->with(['child', 'scores', 'enrollment'])
            ->latest()
            ->paginate(15);

        return view('livewire.coach.report-cards', compact('cards'));
    }
}
