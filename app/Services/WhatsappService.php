<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function status(): array
    {
        $provider = config('services.whatsapp.provider', 'log');

        if ($provider !== 'web') {
            return [
                'provider' => $provider,
                'ready' => $provider !== 'log',
                'message' => "WhatsApp provider is {$provider}.",
            ];
        }

        if (! filled(config('services.whatsapp_web.status_endpoint'))) {
            return [
                'provider' => 'web',
                'ready' => false,
                'message' => 'WhatsApp Web status endpoint is not configured.',
            ];
        }

        try {
            $request = Http::acceptJson()->timeout(3);

            if (filled(config('services.whatsapp_web.token'))) {
                $request = $request->withToken(config('services.whatsapp_web.token'));
            }

            $response = $request->get(config('services.whatsapp_web.status_endpoint'));
        } catch (\Throwable $exception) {
            return [
                'provider' => 'web',
                'ready' => false,
                'message' => 'WhatsApp Web service is not running.',
                'error' => $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'provider' => 'web',
                'ready' => false,
                'message' => 'WhatsApp Web service status check failed.',
                'status' => $response->status(),
            ];
        }

        $body = $response->json() ?? [];

        return [
            'provider' => 'web',
            'ready' => (bool) ($body['ready'] ?? false),
            'message' => ($body['ready'] ?? false)
                ? 'WhatsApp Web is connected.'
                : 'WhatsApp Web is not connected. Scan the QR first.',
        ];
    }

    public function sendTextNow(string $mobile, string $message): array
    {
        $provider = config('services.whatsapp.provider', 'log');

        if ($provider === 'twilio') {
            return $this->sendViaTwilio($mobile, $message);
        }

        if ($provider === 'web') {
            return $this->sendViaWhatsappWeb($mobile, $message);
        }

        if ($provider === 'gupshup') {
            return $this->sendViaGupshup($mobile, $message);
        }

        Log::info('WhatsApp message skipped or logged.', [
            'mobile' => $mobile,
            'message' => $message,
            'provider' => $provider,
        ]);

        return [
            'sent' => false,
            'provider' => $provider,
            'status' => 'logged',
        ];
    }

    private function sendViaWhatsappWeb(string $mobile, string $message): array
    {
        if (! filled(config('services.whatsapp_web.endpoint'))) {
            Log::info('WhatsApp Web message skipped or logged.', [
                'mobile' => $mobile,
                'message' => $message,
                'provider' => 'web',
                'reason' => 'WhatsApp Web endpoint is not configured.',
            ]);

            return [
                'sent' => false,
                'provider' => 'web',
                'status' => 'logged',
            ];
        }

        try {
            $request = Http::acceptJson()->timeout(15);

            if (filled(config('services.whatsapp_web.token'))) {
                $request = $request->withToken(config('services.whatsapp_web.token'));
            }

            $response = $request->post(config('services.whatsapp_web.endpoint'), [
                'mobile' => $this->normalizeMobile($mobile),
                'message' => $message,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp Web message could not be sent.', [
                'mobile' => $mobile,
                'error' => $exception->getMessage(),
            ]);

            return [
                'sent' => false,
                'provider' => 'web',
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            Log::warning('WhatsApp Web message failed.', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return [
            'sent' => $response->successful(),
            'provider' => 'web',
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
    }

    private function sendViaTwilio(string $mobile, string $message): array
    {
        if (! $this->twilioConfigured()) {
            Log::info('WhatsApp message skipped or logged.', [
                'mobile' => $mobile,
                'message' => $message,
                'provider' => 'twilio',
                'reason' => 'Twilio WhatsApp is not configured.',
            ]);

            return [
                'sent' => false,
                'provider' => 'twilio',
                'status' => 'logged',
            ];
        }

        try {
            $accountSid = config('services.twilio.account_sid');
            $response = Http::asForm()
                ->withBasicAuth($accountSid, config('services.twilio.auth_token'))
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $this->twilioWhatsappNumber(config('services.twilio.whatsapp_from')),
                    'To' => $this->twilioWhatsappNumber($this->normalizeMobile($mobile)),
                    'Body' => $message,
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Twilio WhatsApp message could not be sent.', [
                'mobile' => $mobile,
                'error' => $exception->getMessage(),
            ]);

            return [
                'sent' => false,
                'provider' => 'twilio',
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            Log::warning('Twilio WhatsApp message failed.', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return [
            'sent' => $response->successful(),
            'provider' => 'twilio',
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
    }

    private function sendViaGupshup(string $mobile, string $message): array
    {
        if (! $this->gupshupConfigured()) {
            Log::info('WhatsApp message skipped or logged.', [
                'mobile' => $mobile,
                'message' => $message,
                'provider' => 'gupshup',
                'reason' => 'Gupshup WhatsApp is not configured.',
            ]);

            return [
                'sent' => false,
                'provider' => 'gupshup',
                'status' => 'logged',
            ];
        }

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'apikey' => config('services.gupshup.api_key'),
                ])
                ->post(config('services.gupshup.endpoint'), [
                    'channel' => 'whatsapp',
                    'source' => config('services.gupshup.source_number'),
                    'destination' => $this->normalizeMobile($mobile),
                    'src.name' => config('services.gupshup.app_name'),
                    'message' => json_encode([
                        'type' => 'text',
                        'text' => $message,
                    ]),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('WhatsApp message could not be sent.', [
                'mobile' => $mobile,
                'error' => $exception->getMessage(),
            ]);

            return [
                'sent' => false,
                'provider' => 'gupshup',
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            Log::warning('WhatsApp message failed.', [
                'mobile' => $mobile,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return [
            'sent' => $response->successful(),
            'provider' => 'gupshup',
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];
    }

    private function twilioConfigured(): bool
    {
        return filled(config('services.twilio.account_sid'))
            && filled(config('services.twilio.auth_token'))
            && filled(config('services.twilio.whatsapp_from'));
    }

    private function gupshupConfigured(): bool
    {
        return filled(config('services.gupshup.api_key'))
            && filled(config('services.gupshup.source_number'))
            && filled(config('services.gupshup.app_name'));
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        return $digits;
    }

    private function twilioWhatsappNumber(string $mobile): string
    {
        if (str_starts_with($mobile, 'whatsapp:')) {
            return $mobile;
        }

        return 'whatsapp:+'.ltrim($mobile, '+');
    }
}
