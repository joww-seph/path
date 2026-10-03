<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\BusinessType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PartnerRegistrationController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(): Response
    {
        return Inertia::render('auth/PartnerRegister', [
            'businessTypes' => BusinessType::options(),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Create a partner account together with a business that waits for the tourism office to verify it.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => PhoneNumber::clean($request->input('phone')),
            'contact_phone' => PhoneNumber::clean($request->input('contact_phone')),
        ]);

        $validated = $request->validate([
            ...$this->profileRules(),
            'phone' => $this->phoneRules(required: true),
            'password' => $this->passwordRules(),
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', Rule::enum(BusinessType::class)],
            'permit_no' => ['required', 'string', 'max:100'],
            'contact_phone' => $this->phoneRules(),
            'address' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => PhoneNumber::normalize($validated['phone']),
            ]);
            $user->role = Role::Partner;
            $user->save();

            $user->businesses()->create([
                'name' => $validated['business_name'],
                'type' => $validated['business_type'],
                'permit_no' => $validated['permit_no'],
                'contact_phone' => PhoneNumber::normalize($validated['contact_phone'] ?? $validated['phone']),
                'contact_email' => $validated['email'],
                'address' => $validated['address'],
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
