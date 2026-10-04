<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        abort_unless(filled(config('services.google.client_id')), 404);

        return Socialite::driver('google')->redirect();
    }

    /**
     * Sign in with Google, linking to an existing account with the same email or creating a tourist account.
     */
    public function callback(Request $request): RedirectResponse
    {
        abort_unless(filled(config('services.google.client_id')), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return to_route('login')->withErrors(['email' => __('Google sign-in was cancelled or failed. Please try again.')]);
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', Str::lower((string) $googleUser->getEmail()))->first();

        if ($user === null) {
            $user = new User([
                'name' => $googleUser->getName() ?: Str::before((string) $googleUser->getEmail(), '@'),
                'email' => Str::lower((string) $googleUser->getEmail()),
            ]);
            $user->email_verified_at = now();
            // The Google button sits under a notice that continuing means agreeing to the privacy notice.
            $user->privacy_accepted_at = now();
        }

        if (! $user->isActive()) {
            return to_route('login')->withErrors(['email' => __('This account has been deactivated. Contact the PaTH administrator.')]);
        }

        $user->google_id = $googleUser->getId();
        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
