<?php

namespace App\Notifications;

use App\Enums\AdvisorySeverity;
use App\Models\Advisory;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdvisoryPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Advisory $advisory,
        public Trip $trip,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->advisory->severity === AdvisorySeverity::Info ? ['database'] : ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Advisory for your trip: :title', ['title' => $this->advisory->title]))
            ->line($this->advisory->body)
            ->action(__('Check your itinerary'), route('tourist.trips.show', $this->trip));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Advisory: :title', ['title' => $this->advisory->title]),
            'body' => $this->advisory->body,
            'url' => route('tourist.trips.show', $this->trip, absolute: false),
            'level' => $this->advisory->severity === AdvisorySeverity::Info ? 'info' : 'warning',
        ];
    }
}
