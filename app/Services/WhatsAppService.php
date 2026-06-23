<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function send(string $number, string $message): void
    {
        $token = config('services.fonnte.token');
        if (!$token) return;

        $number = $this->normalize($number);
        if (!$number) return;

        try {
            Http::withHeaders(['Authorization' => $token])
                ->timeout(5)
                ->post('https://api.fonnte.com/send', [
                    'target'  => $number,
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp send failed: ' . $e->getMessage());
        }
    }

    private function normalize(string $number): ?string
    {
        $number = preg_replace('/\D/', '', $number);
        if (!$number) return null;
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }
        return $number;
    }
}
