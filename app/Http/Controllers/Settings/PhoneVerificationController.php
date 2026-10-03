<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PhoneVerificationController extends Controller
{
    /**
     * How long a one-time code stays valid, in minutes.
     */
    private const CODE_TTL_MINUTES = 10;

    /**
     * Text a six-digit one-time code to the phone number on the account.
     */
    public function send(Request $request, SmsService $sms): RedirectResponse
    {
        $user = $request->user();

        if (blank($user->phone)) {
            throw ValidationException::withMessages(['phone' => __('Add a mobile number to your profile first.')]);
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->cacheKey($user->id), [
            'hash' => Hash::make($code),
            'phone' => $user->phone,
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        $sms->send($user->phone, __('Your PaTH verification code is :code. It expires in :minutes minutes.', [
            'code' => $code,
            'minutes' => self::CODE_TTL_MINUTES,
        ]));

        Inertia::flash('toast', ['type' => 'info', 'message' => __('We sent a code to :phone.', ['phone' => $user->phone])]);

        return back();
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        $pending = Cache::get($this->cacheKey($user->id));

        if (! is_array($pending) || $pending['phone'] !== $user->phone || ! Hash::check($validated['code'], $pending['hash'])) {
            throw ValidationException::withMessages(['code' => __('That code is wrong or has expired.')]);
        }

        Cache::forget($this->cacheKey($user->id));

        $user->forceFill(['phone_verified_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mobile number verified.')]);

        return back();
    }

    private function cacheKey(int $userId): string
    {
        return "phone-otp:{$userId}";
    }
}
