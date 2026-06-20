<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handleEvcPlus(Request $request): JsonResponse
    {
        $signature = $request->header('X-EVC-Signature', '');
        $secret = config('services.evc.webhook_secret', '');

        if ($secret !== '') {
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, $signature)) {
                return response()->json(['success' => false, 'message' => 'Invalid signature.'], 401);
            }
        }

        $data = $request->validate([
            'transaction_id' => 'required|string',
            'amount'         => 'required|numeric|min:0',
            'phone'          => 'required|string',
            'status'         => 'required|string',
            'reference'      => 'required|string',
        ]);

        if (strtolower($data['status']) !== 'success') {
            return response()->json(['success' => true, 'message' => 'Non-success status acknowledged.']);
        }

        $payment = Payment::query()
            ->where('receipt_number', $data['reference'])
            ->first();

        if ($payment) {
            if ($payment->status === 'pending') {
                $payment->update([
                    'status' => 'completed',
                    'reference_number' => $data['transaction_id'],
                ]);
            }
        } else {
            Log::info('EVC Plus webhook: no matching payment found for reference', [
                'reference'      => $data['reference'],
                'transaction_id' => $data['transaction_id'],
                'amount'         => $data['amount'],
                'phone'          => $data['phone'],
            ]);
        }

        return response()->json(['success' => true]);
    }
}
