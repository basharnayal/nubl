<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewPendingAdminRequestNotification extends Notification
{
    use Queueable;

    private RequestModel $requestModel;

    public function __construct(RequestModel $requestModel)
    {
        $this->requestModel = $requestModel;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_pending_admin_request',
            'title' => __('New Pending Request Needs Approval'),
            'body' => __('Request #:id from :name exceeds their limit and requires admin approval.', [
                'id' => $this->requestModel->id,
                'name' => $this->requestModel->recipient->name,
            ]),
            'action_url' => route('admin.requests.index'),
            'icon' => 'warning',
            'color' => 'warning',
        ];
    }
}
