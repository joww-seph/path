<?php

namespace App\Http\Requests\Tourist;

use App\Models\TouristProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'interests' => ['array'],
            'interests.*' => ['string', Rule::in(TouristProfile::INTERESTS)],
            'group_size' => ['required', 'integer', 'min:1', 'max:50'],
            'budget_min' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'budget_max' => ['nullable', 'integer', 'min:0', 'max:10000000', 'gte:budget_min'],
            'accessibility_needs' => ['array'],
            'accessibility_needs.*' => ['string', Rule::in(TouristProfile::ACCESSIBILITY_NEEDS)],
            'home_province' => ['nullable', 'string', 'max:100'],
            'home_country' => ['required', 'string', 'size:2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'interests' => $this->input('interests', []),
            'accessibility_needs' => $this->input('accessibility_needs', []),
            'home_country' => strtoupper((string) $this->input('home_country', 'PH')),
        ]);
    }
}
