<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\UiDateTime;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies recipients when a weekly allowance change is scheduled or when it takes effect.
 */
class WeeklyAllowanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $event,
        public readonly float $limitSar,
        public readonly ?string $effectiveAtIso = null,
        public readonly ?User $actor = null
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $line] = $this->lines();

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hello!'))
            ->line($line);

        if ($this->actor) {
            $mail->line(__('Action by').': '.$this->actor->name);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        [, $line] = $this->lines();

        return [
            'type' => 'weekly_allowance_changed',
            'event' => $this->event,
            'limit_sar' => $this->limitSar,
            'effective_at' => $this->effectiveAtIso,
            'message' => $line,
            'subtitle' => __('Weekly allowance'),
            'url' => route('recipient.dashboard'),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function lines(): array
    {
        $amount = number_format($this->limitSar, 2).' '.__('SAR');

        return match ($this->event) {
            'scheduled' => [
                __('Weekly allowance change scheduled'),
                __('Your weekly city-fund allowance will change to :amount starting :when.', [
                    'amount' => $amount,
                    'when' => $this->effectiveAtIso
                        ? UiDateTime::mediumWith12h(Carbon::parse($this->effectiveAtIso)->timezone(config('app.timezone')))
                        : '',
                ]),
            ],
            'applied' => [
                __('Weekly allowance updated'),
                __('Your weekly city-fund allowance is now :amount.', ['amount' => $amount]),
            ],
            default => [
                __('Weekly allowance'),
                __('Your weekly allowance settings were updated.'),
            ],
        };
    }
}
