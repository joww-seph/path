<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'phone' => $this->phoneRules(),
        ];
    }

    /**
     * Philippine mobile numbers, written as 09XXXXXXXXX or +639XXXXXXXXX.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phoneRules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'regex:'.PhoneNumber::PATTERN];
    }

    /**
     * New accounts must agree to the privacy notice (Data Privacy Act of 2012, RA 10173).
     *
     * @return array<int, string>
     */
    protected function privacyConsentRules(): array
    {
        return ['accepted'];
    }

    /**
     * @return array<string, string>
     */
    protected function privacyConsentMessages(): array
    {
        return ['privacy_consent.accepted' => __('Please agree to the privacy notice to create an account.')];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
