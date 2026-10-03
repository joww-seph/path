<?php

namespace App\Http\Requests\Trips;

use App\Enums\TravelMode;
use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TripRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxDays = Trip::MAX_DAYS - 1;

        return [
            'title' => ['required', 'string', 'max:120'],
            'start_date' => ['required', 'date', $this->isMethod('post') ? 'after_or_equal:today' : 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', function (string $attribute, mixed $value, \Closure $fail) use ($maxDays) {
                $start = strtotime((string) $this->input('start_date'));

                if ($start && strtotime((string) $value) > strtotime("+{$maxDays} days", $start)) {
                    $fail(__('A trip can be up to :days days long.', ['days' => Trip::MAX_DAYS]));
                }
            }],
            'pax' => ['required', 'integer', 'min:1', 'max:50'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'day_starts_at' => ['nullable', 'date_format:H:i'],
            'travel_mode' => ['nullable', Rule::enum(TravelMode::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'template' => ['nullable', 'string', Rule::exists('itinerary_templates', 'slug')->where('is_published', true)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tripAttributes(): array
    {
        return collect($this->validated())
            ->except('template')
            ->filter(fn ($value, string $key) => $value !== null || in_array($key, ['budget', 'notes'], true))
            ->all();
    }
}
