<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\AuthenticationException;
use Stripe\StripeClient;

class SettingController extends Controller
{
    private const SENSITIVE_KEYS = [
        'stripe.secret_key',
        'stripe.webhook_secret',
        'mail.smtp_password',
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
            'webhookUrl' => route('stripe.webhook'),
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
        $key = Setting::get('stripe.secret_key');

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
            'stripe.publishable_key' => $raw->get('stripe.publishable_key', ''),
            'stripe.secret_key' => '',
            'stripe.webhook_secret' => '',
        ];

        foreach (self::SENSITIVE_KEYS as $key) {
            $decrypted = Setting::get($key);
            $settings[$key] = $decrypted ? '••••••••' : '';
        }

        return $settings;
    }

}
