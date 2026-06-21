<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsChannel
{
    public function send(string $to, string $message): void
    {
        $to = $this->normalizePhone($to);
        if ($to === '') {
            throw new RuntimeException('SMS recipient phone number is empty.');
        }

        $driver = config('sms.driver', 'log');

        match ($driver) {
            'twilio' => $this->sendTwilio($to, $message),
            'africastalking' => $this->sendAfricasTalking($to, $message),
            default => Log::info('SMS (log driver)', ['to' => $to, 'message' => $message]),
        };
    }

    protected function sendTwilio(string $to, string $message): void
    {
        $sid = config('sms.twilio.sid');
        $token = config('sms.twilio.token');
        $from = config('sms.twilio.from');

        if (! filled($sid) || ! filled($token) || ! filled($from)) {
            throw new RuntimeException('Twilio SMS is not configured.');
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Twilio SMS failed: '.$response->body());
        }
    }

    protected function sendAfricasTalking(string $to, string $message): void
    {
        $username = config('sms.africastalking.username');
        $apiKey = config('sms.africastalking.api_key');
        $from = config('sms.africastalking.from') ?: config('sms.from');

        if (! filled($username) || ! filled($apiKey)) {
            throw new RuntimeException('Africa\'s Talking SMS is not configured.');
        }

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $username,
            'to' => $to,
            'message' => $message,
            'from' => $from,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Africa\'s Talking SMS failed: '.$response->body());
        }
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';

        return $digits;
    }
}
