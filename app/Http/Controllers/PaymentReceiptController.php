<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

class PaymentReceiptController extends Controller
{
    /**
     * Stream a PDF payment receipt for a verified (paid) transaction.
     * Only the owning parent may download, and only once the payment is paid.
     */
    public function download(Transaction $transaction): Response
    {
        abort_unless($transaction->user_id === Auth::id(), 403);
        abort_unless($transaction->status === 'paid', 404);

        $transaction->load(['child', 'package.location', 'enrollment', 'verifiedBy']);

        $settings = Setting::allKeyed();

        // QR encodes the transaction code so the receipt can be authenticated.
        $qrSvg = base64_encode(
            (string) QrCode::format('svg')->size(120)->margin(0)->generate($transaction->transaction_code)
        );

        $pdf = Pdf::loadView('pdf.payment-receipt', [
            'trx'      => $transaction,
            'settings' => $settings,
            'qrSvg'    => $qrSvg,
        ])->setPaper('a4');

        return $pdf->download("Receipt-{$transaction->transaction_code}.pdf");
    }
}
