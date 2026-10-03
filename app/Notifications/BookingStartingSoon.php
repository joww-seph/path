<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStartingSoon extends Notification implements ShouldQueue
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
            ->subject($this->title())
            ->line($this->body())
            ->action(__('Show my voucher'), route('tourist.bookings.show', $this->booking));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => route('tourist.bookings.show', $this->booking, absolute: false),
            'level' => 'info',
        ];
    }

    private function title(): string
    {
        return __('In about an hour: :listing', ['listing' => $this->booking->listing->name]);
    }

    private function body(): string
    {
        return __(':rate at :time. Have your QR voucher (:code) ready.', [
            'rate' => $this->booking->rate_name,
            'time' => date('g:i A', strtotime((string) $this->booking->time)),
            'code' => $this->booking->code,
        ]);
    }
}
