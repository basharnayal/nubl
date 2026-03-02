<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user (for polling / real-time display).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $notifications = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => $this->formatNotification($n));

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $n = $request->user()?->notifications()->find($notification);
        if ($n) {
            $n->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }

    private function formatNotification($notification): array
    {
        $data = $notification->data;
        $type = $data['type'] ?? 'unknown';

        $config = match ($type) {
            'donation_receipt' => [
                'icon' => 'success',
                'icon_svg' => 'check-circle',
                'title' => $data['message'] ?? __('Your donation was successful'),
                'subtitle' => __('Receipt has been sent to your email'),
                'url' => $data['url'] ?? route('donor.donations.index'),
            ],
            default => [
                'icon' => 'info',
                'icon_svg' => 'bell',
                'title' => $data['message'] ?? $data['title'] ?? __('Notification'),
                'subtitle' => $data['subtitle'] ?? '',
                'url' => $data['url'] ?? '#',
            ],
        };

        return [
            'id' => $notification->id,
            'type' => $type,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
            'title' => $config['title'],
            'subtitle' => $config['subtitle'],
            'url' => $config['url'],
            'icon' => $config['icon'],
            'icon_svg' => $config['icon_svg'],
        ];
    }
}
