<?php

namespace App\Livewire\Admin;

use App\Models\Transaction;
use Livewire\Component;

class VerifyPayment extends Component
{
    public bool $scannerActive = false;

    public string $manualCode = '';

    /** valid | unpaid | not_found | null */
    public ?string $resultStatus = null;

    /** Resolved receipt details for the result card. */
    public ?array $result = null;

    public function activateScanner(): void
    {
        $this->scannerActive = true;
        $this->clearResult();
    }

    public function deactivateScanner(): void
    {
        $this->scannerActive = false;
    }

    public function clearResult(): void
    {
        $this->resultStatus = null;
        $this->result       = null;
    }

    /** Called by the camera on a successful QR decode. */
    public function processQr(string $decoded): void
    {
        $this->verify($decoded);
    }

    /** Manual fallback when the camera is unavailable. */
    public function verifyManual(): void
    {
        $this->validate(['manualCode' => 'required|string|max:50']);
        $this->verify($this->manualCode);
    }

    private function verify(string $code): void
    {
        // A receipt QR may encode either the raw code or a URL ending in it.
        $code = trim($code);
        if (str_contains($code, '/')) {
            $code = rtrim(substr($code, strrpos($code, '/') + 1));
        }

        $trx = Transaction::where('transaction_code', $code)
            ->with(['user', 'child', 'package.location', 'verifiedBy'])
            ->first();

        if (! $trx) {
            $this->resultStatus = 'not_found';
            $this->result       = ['code' => $code];
            return;
        }

        $this->result = [
            'code'        => $trx->transaction_code,
            'status'      => $trx->status,
            'parent'      => $trx->user?->name,
            'child'       => $trx->child?->name,
            'package'     => $trx->package?->name,
            'location'    => $trx->package?->location?->name,
            'amount'      => $trx->amount,
            'paid_at'     => $trx->paid_at?->translatedFormat('d M Y, H:i'),
            'verified_by' => $trx->verifiedBy?->name,
        ];

        $this->resultStatus = $trx->status === 'paid' ? 'valid' : 'unpaid';
    }

    public function render()
    {
        return view('livewire.admin.verify-payment');
    }
}
