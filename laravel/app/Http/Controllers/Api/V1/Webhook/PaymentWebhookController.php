<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Services\EvcPlusWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function handleEvcPlus(Request $request, EvcPlusWebhookService $webhooks): JsonResponse
    {
        if (! config('evc.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'EVC Plus webhooks are disabled.',
            ], 503);
        }

        $rawBody = $request->getContent();
        $signature = $request->header(config('evc.signature_header'));

        if (! $webhooks->verifySignature($rawBody, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        $payload = $request->json()->all();

        try {
            $result = $webhooks->process($payload);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'payment_id' => $result['payment_id'] ?? null,
        ], $result['status'] === 'duplicate' ? 200 : 201);
    }
}
