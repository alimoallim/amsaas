<?php

namespace App\Services\Collections;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsGatewayService
{
    public function send(string $phone, string $message): bool
    {
        $driver = config('services.sms.driver', 'log');

        return match ($driver) {
            'africastalking' => $this->sendViaAfricasTalking($phone, $message),
            default          => $this->sendViaLog($phone, $message),
        };
    }

    private function sendViaLog(string $phone, string $message): bool
    {
        Log::info('SMS [log driver]', ['to' => $phone, 'message' => $message]);

        return true;
    }

    private function sendViaAfricasTalking(string $phone, string $message): bool
    {
        Log::info('SMS [africastalking stub]', ['to' => $phone, 'message' => $message]);

        return true;
    }
}
