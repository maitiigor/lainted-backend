<?php

namespace App\Domain\Users\Services;

use App\Domain\Payments\Models\Wallet;
use App\Domain\Users\Exceptions\AuthException;
use App\Domain\Users\Exceptions\OtpException;
use App\Domain\Users\Models\Otp;
use App\Domain\Users\Models\User;
use App\Support\Enums\OtpPurpose;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    private const OTP_LENGTH = 6;

    private const OTP_TTL_MINUTES = 10;

    private const MAX_VERIFY_ATTEMPTS = 5;

    public function requestOtp(string $phone): array
    {
        $purpose = User::where('phone', $phone)->exists() ? OtpPurpose::Login : OtpPurpose::Signup;

        // Invalidate any still-live unverified OTPs for this phone+purpose so
        // verify only ever has one candidate row and the real guess budget
        // stays capped at MAX_VERIFY_ATTEMPTS, not multiplied by re-requests.
        Otp::where('phone', $phone)
            ->where('purpose', $purpose->value)
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);

        $code = (string) random_int(10 ** (self::OTP_LENGTH - 1), (10 ** self::OTP_LENGTH) - 1);
        \Log::info($code);
        Otp::create([
            'phone' => $phone,
            'code' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        $this->sendSms(
            $phone,
            "Your Lainted verification code is {$code}. It expires in ".self::OTP_TTL_MINUTES.' minutes.'
        );

        return [
            'is_new_user' => $purpose === OtpPurpose::Signup,
        ];
    }

    public function verifyOtp(string $phone, string $code): array
    {
        $this->consumeValidOtp($phone, $code);

        $isNewUser = ! User::where('phone', $phone)->exists();

        $user = User::firstOrCreate(
            ['phone' => $phone],
            ['name' => $phone],
        );

        if ($isNewUser) {
            $user->assignRole('buyer');
            Wallet::create(['user_id' => $user->id]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'needs_profile_setup' => $user->needsProfileSetup(),
        ];
    }

    /**
     * Fast alternative to OTP for returning users, so a login doesn't cost
     * an SMS every time. First-time phone verification still requires OTP -
     * there's no other way to prove phone ownership.
     */
    public function loginWithPassword(string $phone, string $password): array
    {
        $user = User::where('phone', $phone)->first();

        if ($user && $user->password === null) {
            throw new AuthException("You haven't set a password yet. Use 'Send code' to log in.");
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthException('Incorrect phone number or password.');
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
            'needs_profile_setup' => $user->needsProfileSetup(),
        ];
    }

    /**
     * Doc: "Change phone number (OTP verification)" - reuses requestOtp()
     * as-is to send the code (its Login-vs-Signup purpose determination
     * doesn't matter here, only that a code gets sent), and this same
     * OTP-row validation verifyOtp() uses, but skips verifyOtp's
     * firstOrCreate-a-user login side effect entirely - the user is
     * already authenticated, this only ever updates their phone column.
     */
    public function changePhone(User $user, string $newPhone, string $code): User
    {
        $this->consumeValidOtp($newPhone, $code);

        return DB::transaction(function () use ($user, $newPhone) {
            if (User::where('phone', $newPhone)->where('id', '!=', $user->id)->exists()) {
                throw new AuthException('This phone number is already in use.');
            }

            $user->update(['phone' => $newPhone]);

            return $user->fresh();
        });
    }

    private function consumeValidOtp(string $phone, string $code): Otp
    {
        $otp = Otp::where('phone', $phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp) {
            throw new OtpException('This code has expired. Please request a new one.');
        }

        if ($otp->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            throw new OtpException('Too many attempts. Please request a new code.');
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');
            throw new OtpException('Incorrect code.');
        }

        $otp->update(['verified_at' => now()]);

        return $otp;
    }

    private function sendSms(string $phone, string $message): void
    {
        $response = Http::asForm()->post(rtrim(config('services.termii.base_url'), '/').'/api/sms/send', [
            'api_key' => config('services.termii.api_key'),
            'to' => ltrim($phone, '+'),
            'from' => config('services.termii.sender_id'),
            'sms' => $message,
            'type' => 'plain',
            'channel' => 'generic',
        ]);

        if (! $response->successful()) {
            Log::warning('Termii SMS send failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
