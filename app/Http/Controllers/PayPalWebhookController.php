<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): JsonResponse
    {
        $headers = [
            'paypal-auth-algo' => (string) $request->header('paypal-auth-algo', ''),
            'paypal-cert-url' => (string) $request->header('paypal-cert-url', ''),
            'paypal-transmission-id' => (string) $request->header('paypal-transmission-id', ''),
            'paypal-transmission-sig' => (string) $request->header('paypal-transmission-sig', ''),
            'paypal-transmission-time' => (string) $request->header('paypal-transmission-time', ''),
        ];

        $payload = $request->all();

        if (! $payments->verifyWebhook($headers, $payload)) {
            return response()->json(['status' => 'invalid signature'], 400);
        }

        try {
            $payments->handleWebhook($payload);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}
