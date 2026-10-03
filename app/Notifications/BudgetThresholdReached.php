<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetThresholdReached extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Trip $trip,
        public int $threshold,
        public float $spent,
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
        return (new MailMessage)
            ->subject($this->title())
            ->line($this->body())
            ->action(__('Open the budget'), route('tourist.trips.budget', $this->trip));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => route('tourist.trips.budget', $this->trip, absolute: false),
            'level' => $this->threshold >= 100 ? 'warning' : 'info',
        ];
    }

    private function title(): string
    {
        return $this->threshold >= 100
            ? __('":trip" is over budget', ['trip' => $this->trip->title])
            : __('":trip" has used :percent% of its budget', ['trip' => $this->trip->title, 'percent' => $this->threshold]);
    }

    private function body(): string
    {
        return __('You have spent ₱:spent of your ₱:budget budget.', [
            'spent' => number_format($this->spent, 2),
            'budget' => number_format((float) $this->trip->budget, 2),
        ]);
    }
}
