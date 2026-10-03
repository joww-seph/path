<?php

namespace App\Notifications;

use App\Models\SosAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent straight away (not queued) to the tourist's emergency contacts by email, and to tourism office staff.
 */
class SosRaised extends Notification
{
    use Queueable;

    public function __construct(public SosAlert $alert) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->error()
            ->subject(__('SOS from :name in Paoay', ['name' => $this->alert->user->name]))
            ->line(__(':name pressed the SOS button in the PaTH app at :time.', [
                'name' => $this->alert->user->name,
                'time' => $this->alert->created_at->format('g:i A, F j'),
            ]));

        if ($this->alert->message) {
            $mail->line(__('Message: :message', ['message' => $this->alert->message]));
        }

        if ($url = $this->alert->mapUrl()) {
            $mail->action(__('See their location'), $url);
        }

        return $mail->line(__('If you cannot reach them, call 911.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('SOS from :name', ['name' => $this->alert->user->name]),
            'body' => $this->alert->message ?? __('No message. Location shared: :shared', ['shared' => $this->alert->latitude !== null ? __('yes') : __('no')]),
            'url' => route('office.sos.index', absolute: false),
            'level' => 'warning',
        ];
    }
}
