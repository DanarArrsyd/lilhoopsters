<?php

namespace App\Livewire\Portal;

use App\Models\PaymentAccount;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination, WithFileUploads;

    public string $filterStatus = '';

    public ?int   $uploadingId   = null;
    public        $proofFile     = null;
    public bool   $agreedToTnc  = false;

    public ?int   $confirmCancelId = null;

    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openUpload(int $id): void
    {
        Auth::user()->transactions()->findOrFail($id);

        $this->uploadingId  = $id;
        $this->proofFile    = null;
        $this->agreedToTnc  = false;
    }

    public function cancelUpload(): void
    {
        $this->reset(['uploadingId', 'proofFile', 'agreedToTnc']);
        $this->resetValidation();
    }

    public function confirmCancel(int $id): void
    {
        Auth::user()->transactions()->where('status', 'pending')->findOrFail($id);
        $this->confirmCancelId = $id;
    }

    public function dismissCancel(): void
    {
        $this->confirmCancelId = null;
    }

    public function cancelTransaction(): void
    {
        $trx = Auth::user()->transactions()
            ->where('status', 'pending')
            ->with(['enrollment.child'])
            ->findOrFail($this->confirmCancelId);

        DB::transaction(function () use ($trx) {
            $enrollment = $trx->enrollment;

            if ($enrollment) {
                if ($enrollment->type === 'registration') {
                    $enrollment->child?->update(['status' => 'unregistered']);
                }
                $enrollment->delete();
            }

            $trx->delete();
        });

        $this->confirmCancelId = null;
        session()->flash('success', 'Transaction cancelled successfully.');
    }

    public function uploadProof(): void
    {
        $this->validate([
            'proofFile'   => 'required|file|image|max:5120',
            'agreedToTnc' => 'accepted',
        ], [
            'agreedToTnc.accepted' => 'You must agree to the payment terms before submitting.',
        ]);

        $trx = Auth::user()->transactions()->findOrFail($this->uploadingId);

        $path = $this->proofFile->store('payment_proofs', 'public');

        $trx->update([
            'payment_proof' => $path,
        ]);

        NotificationService::toAdmins(
            'new_payment_proof',
            'Payment Proof Uploaded',
            Auth::user()->name . " has uploaded payment proof. Please verify.",
        );

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
            'pendingTotal'              => Auth::user()->transactions()->where('status', 'pending')->sum('amount'),
            'pendingCount'              => Auth::user()->transactions()->where('status', 'pending')->count(),
            'paymentAccounts'           => PaymentAccount::active()->get(),
            'uploadingTransaction'      => $this->uploadingId
                ? Auth::user()->transactions()->with(['child', 'package.location'])->find($this->uploadingId)
                : null,
            'confirmCancelTransaction'  => $this->confirmCancelId
                ? Auth::user()->transactions()->with(['child', 'package'])->find($this->confirmCancelId)
                : null,
        ]);
    }
}
