<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    public ?int $rejectingId    = null;
    public string $adminNote    = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function verify(int $id): void
    {
        $transaction = DB::transaction(function () use ($id) {
            $transaction = Transaction::with(['enrollment.child', 'child', 'package'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($transaction->status !== 'pending') {
                return null;
            }

            $transaction->update([
                'status'      => 'paid',
                'verified_by' => Auth::id(),
                'paid_at'     => now(),
            ]);

            AuditLog::record(
                'payment.verified',
                $transaction,
                "Verified payment {$transaction->transaction_code} for " . ($transaction->user?->name ?? 'unknown user'),
                ['amount' => $transaction->amount],
            );

            if ($transaction->enrollment) {
                $transaction->enrollment->update([
                    'status'      => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                $child = $transaction->enrollment->child;
            } else {
                $child = $transaction->child;
            }

            if ($child) {
                $child->update([
                    'status'        => 'active',
                    'registered_at' => $child->registered_at ?? now(),
                ]);
            }

            // Event registration paid for by this transaction → confirm it.
            \App\Models\EventRegistration::where('transaction_id', $transaction->id)
                ->update(['status' => 'confirmed']);

            return $transaction;
        });

        if (! $transaction) {
            session()->flash('error', 'This payment was already processed.');
            return;
        }

        if ($transaction->user_id) {
            NotificationService::send(
                $transaction->user_id,
                'payment_verified',
                'Payment Verified ✓',
                "Your payment of Rp " . number_format($transaction->amount, 0, ',', '.') . " has been verified. Enrollment is now active!",
                [],
                email: true,
                emailDetails: [
                    'Package'  => $transaction->package?->name ?? '—',
                    'Amount'   => 'Rp ' . number_format($transaction->amount, 0, ',', '.'),
                    'Code'     => $transaction->transaction_code,
                    'Paid at'  => optional($transaction->paid_at)->format('d M Y H:i'),
                ],
            );
        }

        session()->flash('success', 'Payment verified.');
    }

    public function reject(int $id): void
    {
        $this->rejectingId = $id;
        $this->adminNote   = '';
    }

    public function confirmReject(): void
    {
        $rejectingId = $this->rejectingId;
        $adminNote   = $this->adminNote;

        $transaction = DB::transaction(function () use ($rejectingId, $adminNote) {
            $transaction = Transaction::lockForUpdate()->findOrFail($rejectingId);

            if ($transaction->status !== 'pending') {
                return null;
            }

            $transaction->update([
                'status'      => 'rejected',
                'admin_notes' => $adminNote ?: null,
            ]);

            AuditLog::record(
                'payment.rejected',
                $transaction,
                "Rejected payment {$transaction->transaction_code} for " . ($transaction->user?->name ?? 'unknown user'),
                ['note' => $adminNote ?: null],
            );

            // Event registration paid for by this transaction → cancel it.
            \App\Models\EventRegistration::where('transaction_id', $transaction->id)
                ->update(['status' => 'cancelled']);

            return $transaction;
        });

        $this->rejectingId = null;
        $this->adminNote   = '';

        if (! $transaction) {
            session()->flash('error', 'This payment was already processed.');
            return;
        }

        if ($transaction->user_id) {
            NotificationService::send(
                $transaction->user_id,
                'payment_rejected',
                'Payment Not Verified',
                "Your payment could not be verified." . ($adminNote ? " Note: {$adminNote}" : " Please re-upload your proof."),
            );
        }

        session()->flash('success', 'Payment rejected.');
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->adminNote   = '';
    }

    public function render()
    {
        return view('livewire.admin.payments', [
            'transactions' => Transaction::with(['user', 'child', 'package.location'])
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->search, fn($q) => $q
                    ->where('transaction_code', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")))
                ->orderByRaw("FIELD(status,'pending','paid','rejected','expired')")
                ->orderBy('created_at', 'desc')
                ->paginate(15),
        ]);
    }
}
