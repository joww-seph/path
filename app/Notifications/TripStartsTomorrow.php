<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TripStartsTomorrow extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Trip $trip) {}

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
            ->subject(__(':trip starts tomorrow', ['trip' => $this->trip->title]))
            ->line(__('Your trip to Paoay starts tomorrow. Before you leave:'))
            ->line(__('• Open the trip and tap "Make available offline" while you have signal.'))
            ->line(__('• Check the weather and any advisories on your itinerary.'))
            ->line(__('• Keep your booking vouchers handy.'))
            ->action(__('Open my itinerary'), route('tourist.trips.show', $this->trip));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __(':trip starts tomorrow', ['trip' => $this->trip->title]),
            'body' => __('Save your trip for offline use and check the weather and advisories.'),
            'url' => route('tourist.trips.show', $this->trip, absolute: false),
            'level' => 'info',
        ];
    }
}
