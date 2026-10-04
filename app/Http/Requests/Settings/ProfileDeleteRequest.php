<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }

    /**
     * Deleting an account removes its bookings, so open bookings, made or received, are settled first.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();

                $hasOpenBookings = Booking::query()
                    ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                    ->whereDate('date', '>=', now()->toDateString())
                    ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere(fn ($query) => $query->forPartner($user)))
                    ->exists();

                if ($hasOpenBookings) {
                    $validator->errors()->add('account', __('You have upcoming bookings. Cancel or complete them before deleting your account.'));
                }
            },
        ];
    }
}
