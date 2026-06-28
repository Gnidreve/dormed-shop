<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\PaymentMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\AuthenticationException;
use Stripe\StripeClient;

class SettingController extends Controller
{
    private const SENSITIVE_KEYS = [
        'stripe.sandbox.secret_key',
        'stripe.sandbox.webhook_secret',
        'stripe.live.secret_key',
        'stripe.live.webhook_secret',
        'mail.smtp_password',
        'paypal.sandbox.client_secret',
        'paypal.live.client_secret',
        'paypal.webhook_id',
    ];

    public function showGeneral(): Response
    {
        $settings = $this->loadSettings();

        return Inertia::render('Admin/Settings/General', [
            'settings' => $settings,
        ]);
    }

    public function showMail(): Response
    {
        $settings = $this->loadSettings();

        return Inertia::render('Admin/Settings/Mail', [
            'settings' => $settings,
            'hasSensitive' => collect(self::SENSITIVE_KEYS)
                ->mapWithKeys(fn ($k) => [$k => Setting::get($k) !== null])
                ->all(),
        ]);
    }

    public function showPayment(): Response
    {
        $settings = $this->loadSettings();

        return Inertia::render('Admin/Settings/Payment', [
            'settings' => $settings,
            'hasSensitive' => collect(self::SENSITIVE_KEYS)
                ->mapWithKeys(fn ($k) => [$k => Setting::get($k) !== null])
                ->all(),
            'stripeWebhookUrl' => route('stripe.webhook'),
            'paymentMode' => PaymentMode::current(),
        ]);
    }

    public function index(): Response
    {
        $raw = Setting::all(['key', 'value'])->pluck('value', 'key');

        $settings = [
            'shop.name' => $raw->get('shop.name', ''),
            'shop.email' => $raw->get('shop.email', ''),
            'shop.phone' => $raw->get('shop.phone', ''),
            'mail.smtp_host' => $raw->get('mail.smtp_host', ''),
            'mail.smtp_port' => $raw->get('mail.smtp_port', ''),
            'mail.smtp_user' => $raw->get('mail.smtp_user', ''),
            'mail.smtp_password' => '',
            'stripe.publishable_key' => $raw->get('stripe.publishable_key', ''),
            'stripe.secret_key' => '',
            'stripe.webhook_secret' => '',
        ];

        foreach (self::SENSITIVE_KEYS as $key) {
            $decrypted = Setting::get($key);
            $settings[$key] = $decrypted ? '••••••••' : '';
        }

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'hasSensitive' => collect(self::SENSITIVE_KEYS)
                ->mapWithKeys(fn ($k) => [$k => Setting::get($k) !== null])
                ->all(),
            'webhookUrl' => route('stripe.webhook'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true) && $value === '••••••••') {
                continue;
            }

            Setting::set($key, $value ?: null);
        }

        return back()->with('success', 'Einstellungen gespeichert.');
    }

    public function checkMail(): JsonResponse
    {
        $host = Setting::get('mail.smtp_host');

        if (! $host) {
            return response()->json(['message' => 'Kein SMTP-Host konfiguriert.'], 422);
        }

        $smtpUser = Setting::get('mail.smtp_user');

        if (! $smtpUser) {
            return response()->json(['message' => 'Kein SMTP-Benutzer konfiguriert.'], 422);
        }

        try {
            Mail::raw('Dies ist eine Testmail von dormed24.', function (Message $message) use ($smtpUser): void {
                $message->from($smtpUser)->to($smtpUser)->subject('SMTP-Test — dormed24 Admin');
            });

            return response()->json(['message' => "Testmail an {$smtpUser} versendet."]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Fehler: '.$e->getMessage()], 422);
        }
    }

    public function checkStripe(): JsonResponse
    {
        $key = PaymentMode::isLive()
            ? Setting::get('stripe.live.secret_key')
            : Setting::get('stripe.sandbox.secret_key');

        if (! $key) {
            return response()->json(['message' => 'Kein Secret Key konfiguriert.'], 422);
        }

        try {
            $stripe = new StripeClient($key);
            $stripe->balance->retrieve();

            return response()->json(['message' => 'Verbindung erfolgreich.']);
        } catch (AuthenticationException) {
            return response()->json(['message' => 'Ungültiger API-Key.'], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Fehler: '.$e->getMessage()], 422);
        }
    }

    public function checkPayPal(): JsonResponse
    {
        $isLive = PaymentMode::isLive();
        $clientId = $isLive
            ? Setting::get('paypal.live.client_id')
            : Setting::get('paypal.sandbox.client_id');
        $clientSecret = $isLive
            ? Setting::get('paypal.live.client_secret')
            : Setting::get('paypal.sandbox.client_secret');

        if (! $clientId || ! $clientSecret) {
            return response()->json(['message' => 'Client-ID oder Secret nicht konfiguriert.'], 422);
        }

        $baseUrl = $isLive
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $mode = PaymentMode::current();

        try {
            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

            if ($response->successful() && isset($response->json()['access_token'])) {
                return response()->json(['message' => "Verbindung erfolgreich ({$mode})."]);
            }

            return response()->json(['message' => 'Authentifizierung fehlgeschlagen: '.($response->json()['error_description'] ?? 'Unbekannter Fehler')], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Fehler: '.$e->getMessage()], 422);
        }
    }

    private function loadSettings(): array
    {
        $raw = Setting::all(['key', 'value'])->pluck('value', 'key');

        $settings = [
            'shop.name' => $raw->get('shop.name', ''),
            'shop.email' => $raw->get('shop.email', ''),
            'shop.phone' => $raw->get('shop.phone', ''),
            'mail.smtp_host' => $raw->get('mail.smtp_host', ''),
            'mail.smtp_port' => $raw->get('mail.smtp_port', ''),
            'mail.smtp_user' => $raw->get('mail.smtp_user', ''),
            'mail.smtp_password' => '',
            'shop.notification_emails' => $raw->get('shop.notification_emails', ''),
            'payment.mode' => $raw->get('payment.mode', PaymentMode::current()),
            'payment.provider' => $raw->get('payment.provider', 'stripe'),
            'stripe.sandbox.publishable_key' => $raw->get('stripe.sandbox.publishable_key', ''),
            'stripe.sandbox.secret_key' => '',
            'stripe.sandbox.webhook_secret' => '',
            'stripe.live.publishable_key' => $raw->get('stripe.live.publishable_key', ''),
            'stripe.live.secret_key' => '',
            'stripe.live.webhook_secret' => '',
            'paypal.sandbox.client_id' => $raw->get('paypal.sandbox.client_id', ''),
            'paypal.sandbox.merchant_id' => $raw->get('paypal.sandbox.merchant_id', ''),
            'paypal.sandbox.client_secret' => '',
            'paypal.live.client_id' => $raw->get('paypal.live.client_id', ''),
            'paypal.live.app_id' => $raw->get('paypal.live.app_id', ''),
            'paypal.live.merchant_id' => $raw->get('paypal.live.merchant_id', ''),
            'paypal.live.client_secret' => '',
            'paypal.webhook_id' => '',
        ];

        foreach (self::SENSITIVE_KEYS as $key) {
            $decrypted = Setting::get($key);
            $settings[$key] = $decrypted ? '••••••••' : '';
        }

        return $settings;
    }
}
