<?php

namespace App\Livewire\Portal;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination, WithFileUploads;

    public string $filterStatus = '';

    public ?int   $uploadingId   = null;
    public        $proofFile     = null;
    public string $paymentNotes  = '';
    public string $paymentMethod = '';

    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openUpload(int $id): void
    {
        Auth::user()->transactions()->findOrFail($id);

        $this->uploadingId   = $id;
        $this->proofFile     = null;
        $this->paymentNotes  = '';
        $this->paymentMethod = '';
    }

    public function cancelUpload(): void
    {
        $this->uploadingId   = null;
        $this->proofFile     = null;
        $this->paymentNotes  = '';
        $this->paymentMethod = '';
    }

    public function uploadProof(): void
    {
        $this->validate([
            'proofFile'     => 'required|file|image|max:5120',
            'paymentMethod' => 'nullable|string|max:100',
            'paymentNotes'  => 'nullable|string|max:500',
        ]);

        $trx = Auth::user()->transactions()->findOrFail($this->uploadingId);

        $path = $this->proofFile->store('payment_proofs', 'public');

        $trx->update([
            'payment_proof'  => $path,
            'payment_method' => $this->paymentMethod ?: null,
            'payment_notes'  => $this->paymentNotes ?: null,
        ]);

        session()->flash('success', 'Payment proof uploaded. Waiting for admin verification.');
        $this->cancelUpload();
    }

    public function render()
    {
        return view('livewire.portal.payments', [
            'transactions' => Auth::user()->transactions()
                ->with(['child', 'package.location', 'enrollment'])
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->orderByRaw("FIELD(status,'pending','paid','rejected','expired')")
                ->orderBy('created_at', 'desc')
                ->paginate(15),
        ]);
    }
}
