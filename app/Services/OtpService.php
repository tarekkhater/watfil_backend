<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private const TTL_SECONDS = 300;

    public function generate(string $phone): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->cacheKey($phone), $otp, self::TTL_SECONDS);

        Log::info('OTP generated', ['phone' => $phone, 'otp' => $otp]);

        return $otp;
    }

    public function verify(string $phone, string $otp): bool
    {
        $cached = Cache::get($this->cacheKey($phone));

        if (! $cached || $cached !== $otp) {
            return false;
        }

        Cache::forget($this->cacheKey($phone));

        return true;
    }

    private function cacheKey(string $phone): string
    {
        return 'otp:' . $phone;
    }
}
