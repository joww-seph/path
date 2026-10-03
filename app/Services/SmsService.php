<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Sends text messages through Semaphore, the Philippine SMS gateway.
 *
 * With no API key configured (local development and tests) messages are written to the log.
 */
class SmsService
{
    public function __construct(
        private ?string $apiKey,
        private string $senderName,
    ) {}

    public function send(string $phone, string $message): void
    {
        if (blank($this->apiKey)) {
            Log::info('SMS (not sent, no Semaphore key)', ['to' => $phone, 'message' => $message]);

            return;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->retry(2, 500, throw: false)
            ->post('https://api.semaphore.co/api/v4/messages', [
                'apikey' => $this->apiKey,
                'number' => $phone,
                'message' => $message,
                'sendername' => $this->senderName,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Semaphore rejected the message to {$phone}: {$response->status()}");
        }
    }
}
