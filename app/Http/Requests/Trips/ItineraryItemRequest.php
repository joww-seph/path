<?php

namespace App\Http\Requests\Trips;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItineraryItemRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $trip = $this->route('trip');
        $creating = $this->isMethod('post');

        return [
            'listing_id' => $creating
                ? ['nullable', 'required_without:custom_title', Rule::exists('listings', 'id')->where('status', 'published')]
                : ['prohibited'],
            'custom_title' => $creating
                ? ['nullable', 'string', 'max:150', 'required_without:listing_id']
                : ['sometimes', 'nullable', 'string', 'max:150'],
            'custom_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:custom_longitude'],
            'custom_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:custom_latitude'],
            'day_number' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:'.$trip->dayCount()],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:720'],
            'fixed_start_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_done' => ['sometimes', 'boolean'],
            'client_uuid' => ['nullable', 'uuid'],
        ];
    }
}
