<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies admins (and the affected provider) when the allocation engine is paused or resumed.
 * Not queued so the bell updates immediately.
 */
class AllocationEngineStatusChangedNotification extends Notification
{
    public function __construct(
        public readonly string $event,
        public readonly User $actor,
        public readonly ?User $targetProvider = null
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
        [$subject, $line] = $this->eventText();

        return (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hello!'))
            ->line($line)
            ->line(__('Action by').': '.$this->actor->name);
    }

    public function toArray(object $notifiable): array
    {
        [, $line] = $this->eventText();

        return [
            'type' => 'allocation_engine_status_changed',
            'event' => $this->event,
            'provider_id' => $this->targetProvider?->id,
            'actor_id' => $this->actor->id,
            'message' => $line,
            'subtitle' => __('Allocation engine update'),
            'url' => $this->notificationUrl($notifiable),
        ];
    }

    private function notificationUrl(object $notifiable): string
    {
        if ($notifiable instanceof User && $notifiable->hasRole('admin')) {
            return route('admin.allocation.status');
        }

        return route('provider.dashboard');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function eventText(): array
    {
        return match ($this->event) {
            'paused_globally' => [
                __('Allocation engine paused'),
                __('The allocation engine has been paused globally by :name.', ['name' => $this->actor->name]),
            ],
            'resumed_globally' => [
                __('Allocation engine resumed'),
                __('The allocation engine has been resumed globally by :name.', ['name' => $this->actor->name]),
            ],
            'paused_for_provider' => [
                __('Allocation paused for provider'),
                __('Allocation was paused for provider :provider by :name.', [
                    'provider' => $this->targetProvider?->name ?? '-',
                    'name' => $this->actor->name,
                ]),
            ],
            'resumed_for_provider' => [
                __('Allocation resumed for provider'),
                __('Allocation was resumed for provider :provider by :name.', [
                    'provider' => $this->targetProvider?->name ?? '-',
                    'name' => $this->actor->name,
                ]),
            ],
            'paused_globally_provider' => [
                __('Allocation paused for maintenance'),
                __('Fund allocation is temporarily paused system-wide for maintenance. New allocations may be delayed until service resumes.'),
            ],
            'resumed_globally_provider' => [
                __('Fund allocation resumed'),
                __('Fund allocation has resumed platform-wide. Pending allocations will be processed shortly.'),
            ],
            'paused' => [
                __('Allocation paused for maintenance'),
                __('Fund allocation to your account is temporarily stopped for maintenance. Contact support if you need help.'),
            ],
            'resumed' => [
                __('Your allocation has been resumed'),
                __('Fund allocation to your account has resumed. Pending allocations will be processed shortly.'),
            ],
            default => [
                __('Allocation engine update'),
                __('Allocation engine status changed.'),
            ],
        };
    }
}
