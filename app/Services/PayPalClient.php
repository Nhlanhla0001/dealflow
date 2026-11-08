<?php

namespace App\Services;

use App\Models\PaymentOrder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalClient
{
    /**
     * @return array<string, mixed>
     */
    public function createOrder(PaymentOrder $paymentOrder, string $returnUrl, string $cancelUrl): array
    {
        $response = $this->client()->post($this->baseUrl().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'transaction-'.$paymentOrder->transaction_id,
                'description' => Str::limit($paymentOrder->transaction->title, 120),
                'amount' => [
                    'currency_code' => $paymentOrder->currency,
                    'value' => $this->amount($paymentOrder->amount_minor),
                ],
            ]],
            'application_context' => [
                'brand_name' => Str::limit((string) config('app.name'), 127),
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $response = $this->client()
            ->withHeaders(['PayPal-Request-Id' => 'capture-'.$paypalOrderId])
            ->withBody('{}', 'application/json')
            ->post($this->baseUrl().'/v2/checkout/orders/'.$paypalOrderId.'/capture');

        if ($response->failed()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        return $response->json();
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhook(array $headers, array $payload): bool
    {
        $webhookId = (string) config('services.paypal.webhook_id', '');

        if ($webhookId === '') {
            return false;
        }

        try {
            $response = $this->client()->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $headers['paypal-auth-algo'] ?? '',
                'cert_url' => $headers['paypal-cert-url'] ?? '',
                'transmission_id' => $headers['paypal-transmission-id'] ?? '',
                'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
                'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                'webhook_id' => $webhookId,
                'webhook_event' => $payload,
            ]);
        } catch (ConnectionException|RequestException) {
            return false;
        }

        return $response->successful()
            && $response->json('verification_status') === 'SUCCESS';
    }

    public function accessToken(): string
    {
        return Cache::remember('paypal.access_token', now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->withBasicAuth(
                    (string) config('services.paypal.client_id'),
                    (string) config('services.paypal.secret'),
                )
                ->timeout(30)
                ->post($this->baseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                throw new RuntimeException($this->errorMessage($response));
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('PayPal did not return an access token.');
            }

            return $token;
        });
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 500);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com'), '/');
    }

    private function amount(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }

    private function errorMessage(Response $response): string
    {
        return 'PayPal request failed ('.$response->status().'): '.$response->json('message', 'Unknown error');
    }
}
