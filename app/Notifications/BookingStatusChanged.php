<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public bool $forPartner,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->line($this->body());

        if (! $this->forPartner && $this->booking->status === BookingStatus::Confirmed && $this->booking->listing->business?->payment_instructions) {
            $mail->line(__('How to pay: :instructions', ['instructions' => $this->booking->listing->business->payment_instructions]));
        }

        return $mail->action(__('View booking'), url($this->url()));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'level' => in_array($this->booking->status, [BookingStatus::Declined, BookingStatus::Cancelled, BookingStatus::Expired], true) ? 'warning' : 'info',
        ];
    }

    private function title(): string
    {
        return __('Booking :code: :status', [
            'code' => $this->booking->code,
            'status' => __($this->booking->status->label()),
        ]);
    }

    private function body(): string
    {
        $booking = $this->booking->loadMissing('listing.business');

        return match ($booking->status) {
            BookingStatus::Confirmed => __(':listing confirmed your booking for :date. Show the QR voucher when you arrive.', ['listing' => $booking->listing->name, 'date' => $booking->date->format('F j, Y')]),
            BookingStatus::Declined => __(':listing could not take your booking for :date. Reason: :reason', ['listing' => $booking->listing->name, 'date' => $booking->date->format('F j, Y'), 'reason' => $booking->partner_note ?? '—']),
            BookingStatus::Cancelled => __('The booking at :listing for :date was cancelled.', ['listing' => $booking->listing->name, 'date' => $booking->date->format('F j, Y')]),
            BookingStatus::Expired => __(':listing did not answer in time, so your request for :date expired. Try another date or partner.', ['listing' => $booking->listing->name, 'date' => $booking->date->format('F j, Y')]),
            BookingStatus::Completed => __('You checked in at :listing. Enjoy, and leave a review afterwards!', ['listing' => $booking->listing->name]),
            BookingStatus::NoShow => __('You were marked as a no-show at :listing on :date.', ['listing' => $booking->listing->name, 'date' => $booking->date->format('F j, Y')]),
            BookingStatus::Pending => __('Your booking at :listing is waiting for the partner.', ['listing' => $booking->listing->name]),
        };
    }

    private function url(): string
    {
        return $this->forPartner
            ? route('partner.bookings.index', absolute: false)
            : route('tourist.bookings.show', $this->booking, absolute: false);
    }
}
