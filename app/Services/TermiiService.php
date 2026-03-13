<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TermiiService
{
    private string $baseUrl;
    private string $apiKey;
    private string $senderId;
    private string $channel;

    public function __construct()
    {
        $this->apiKey   = config('services.termii.api_key');
        $this->senderId = config('services.termii.sender_id', 'IntecWorks');
        $this->baseUrl  = rtrim(config('services.termii.base_url', 'https://v3.api.termii.com'), '/');
        $this->channel  = config('services.termii.channel', 'generic');
    }

    /**
     * Send OTP to a phone number.
     * Returns the pin_id from Termii (needed for verification).
     */
    public function sendOtp(string $phone): ?string
    {
        try {
            $response = Http::post("{$this->baseUrl}/api/sms/otp/send", [
                'api_key'            => $this->apiKey,
                'message_type'       => 'NUMERIC',
                'to'                 => $this->normalizePhone($phone),
                'from'               => $this->senderId,
                'channel'            => $this->channel,
                'pin_attempts'       => 3,
                'pin_time_to_live'   => 10,
                'pin_length'         => 6,
                'pin_placeholder'    => '< 1234 >',
                'message_text'       => 'Your IntecWorks verification code is < 1234 >. It expires in 10 minutes.',
                'pin_type'           => 'NUMERIC',
            ]);

            $data = $response->json();

            // Termii returns both pinId and pin_id
            $pinId = $data['pinId'] ?? $data['pin_id'] ?? null;

            if ($response->successful() && $pinId) {
                Log::info('Termii OTP sent', ['phone' => $phone, 'pinId' => $pinId]);
                return $pinId;
            }

            Log::error('Termii OTP send failed', ['phone' => $phone, 'status' => $response->status(), 'response' => $data]);
            return null;

        } catch (\Exception $e) {
            Log::error('Termii OTP exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify an OTP using the pin_id from sendOtp().
     */
    public function verifyOtp(string $pinId, string $otp): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/api/sms/otp/verify", [
                'api_key' => $this->apiKey,
                'pin_id'  => $pinId,
                'pin'     => $otp,
            ]);

            $data = $response->json();

            // Termii returns verified as "True" (string) or true (bool)
            $verified = $data['verified'] ?? false;
            if ($response->successful() && (strtolower((string) $verified) === 'true' || $verified === true)) {
                Log::info('Termii OTP verified', ['pinId' => $pinId]);
                return true;
            }

            Log::warning('Termii OTP verify failed', ['pinId' => $pinId, 'response' => $data]);
            return false;

        } catch (\Exception $e) {
            Log::error('Termii verify exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Normalize phone to international format (Nigerian default).
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Convert 0xxx to 234xxx
        if (str_starts_with($phone, '0')) {
            $phone = '234' . substr($phone, 1);
        }

        // Strip leading +
        $phone = ltrim($phone, '+');

        return $phone;
    }
}
