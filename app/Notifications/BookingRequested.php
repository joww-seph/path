<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New booking request: :code', ['code' => $this->booking->code]))
            ->line($this->body())
            ->line(__('Please answer within :hours hours or the request expires.', ['hours' => Booking::RESPONSE_HOURS]))
            ->action(__('Open booking requests'), route('partner.bookings.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('New booking request: :code', ['code' => $this->booking->code]),
            'body' => $this->body(),
            'url' => route('partner.bookings.index', absolute: false),
            'level' => 'info',
        ];
    }

    private function body(): string
    {
        $booking = $this->booking->loadMissing(['listing', 'tourist']);

        return __(':name requested :rate at :listing on :date for :pax people.', [
            'name' => $booking->tourist->name,
            'rate' => $booking->rate_name,
            'listing' => $booking->listing->name,
            'date' => $booking->date->format('F j, Y'),
            'pax' => $booking->pax,
        ]);
    }
}
