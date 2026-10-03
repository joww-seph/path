<?php

namespace App\Http\Requests\Listings;

use App\Enums\Role;
use App\Models\Listing;
use App\Support\Barangays;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a listing created or edited by a partner or the tourism office.
 *
 * Opening hours arrive as hours[mon][open], hours[mon][close] and hours[mon][closed].
 */
class ListingRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'barangay' => ['nullable', Rule::in(Barangays::slugs())],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:17.9,18.3', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:120.3,120.7', 'required_with:latitude'],
            'hours_known' => ['boolean'],
            'hours' => ['array'],
            'hours.*.closed' => ['boolean'],
            'hours.*.open' => ['nullable', 'date_format:H:i'],
            'hours.*.close' => ['nullable', 'date_format:H:i'],
            'entrance_fee' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'price_min' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'max:1000000', 'gte:price_min'],
            'visit_minutes' => ['required', 'integer', 'min:10', 'max:1440'],
            'contact_phone' => ['nullable', 'string', 'regex:'.PhoneNumber::PATTERN],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'is_bookable' => ['boolean'],
            'is_accessible' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.between' => __('The map pin must be inside Paoay.'),
            'longitude.between' => __('The map pin must be inside Paoay.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_phone' => PhoneNumber::clean($this->input('contact_phone')),
            'hours_known' => $this->boolean('hours_known'),
            'is_bookable' => $this->boolean('is_bookable'),
            'is_accessible' => $this->boolean('is_accessible'),
            'is_featured' => $this->boolean('is_featured'),
            'hours' => collect(Listing::WEEKDAYS)->mapWithKeys(fn (string $day) => [$day => [
                'closed' => filter_var($this->input("hours.{$day}.closed"), FILTER_VALIDATE_BOOLEAN),
                'open' => $this->input("hours.{$day}.open") ?: null,
                'close' => $this->input("hours.{$day}.close") ?: null,
            ]])->all(),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->boolean('hours_known')) {
                    return;
                }

                foreach ($this->input('hours', []) as $day => $hours) {
                    if ($hours['closed']) {
                        continue;
                    }

                    if (! $hours['open'] || ! $hours['close']) {
                        $validator->errors()->add("hours.{$day}", __('Enter opening and closing times, or mark the day closed.'));
                    } elseif ($hours['close'] <= $hours['open']) {
                        $validator->errors()->add("hours.{$day}", __('Closing time must be after opening time.'));
                    }
                }
            },
        ];
    }

    /**
     * The attributes to save on the listing.
     *
     * @return array<string, mixed>
     */
    public function listingAttributes(): array
    {
        $attributes = collect($this->validated())
            ->except(['hours', 'hours_known', 'is_featured'])
            ->all();

        $attributes['contact_phone'] = PhoneNumber::normalize($attributes['contact_phone'] ?? null);
        $attributes['opening_hours'] = $this->openingHours();

        return $attributes;
    }

    /**
     * Only the tourism office can feature a listing on the home and explore pages.
     */
    public function canFeature(): bool
    {
        return $this->user()->hasRole(Role::TourismOfficer, Role::Admin);
    }

    /**
     * @return array<string, array{open: string, close: string}>|null
     */
    private function openingHours(): ?array
    {
        if (! $this->boolean('hours_known')) {
            return null;
        }

        return collect($this->validated('hours'))
            ->reject(fn (array $hours) => $hours['closed'])
            ->map(fn (array $hours) => ['open' => $hours['open'], 'close' => $hours['close']])
            ->all();
    }
}
