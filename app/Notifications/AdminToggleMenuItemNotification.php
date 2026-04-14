<?php

namespace App\Notifications;

use App\Models\ProviderMenuItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminToggleMenuItemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProviderMenuItem $menuItem,
        public bool $isBlocked
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->isBlocked ? __('blocked') : __('unblocked');
        $subject = __('Menu Item Status Update');

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting(__('Hello!'))
            ->line(__('Your menu item ":name" has been :status by an administrator.', [
                'name' => $this->menuItem->name,
                'status' => $status
            ]));

        if ($this->isBlocked) {
            $mail->line(__('This item is now hidden from recipients and cannot be modified until unblocked.'));
        } else {
            $mail->line(__('This item is now visible to recipients and you can modify it again.'));
        }

        return $mail->action(__('View Inventory'), route('provider.menu-items.index'));
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->isBlocked ? __('blocked') : __('unblocked');

        return [
            'type' => 'menu_item_status_changed',
            'menu_item_id' => $this->menuItem->id,
            'is_blocked' => $this->isBlocked,
            'message' => __('Menu item ":name" was :status by admin.', [
                'name' => $this->menuItem->name,
                'status' => $status
            ]),
            'subtitle' => __('Inventory Update'),
            'url' => route('provider.menu-items.index'),
        ];
    }
}
