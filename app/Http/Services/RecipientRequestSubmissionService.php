<?php

namespace App\Http\Services;

use App\Contracts\NotificationServiceInterface;
use App\Models\ProviderMenuItem;
use App\Models\Request as RequestModel;
use App\Models\User;
use RuntimeException;

class RecipientRequestSubmissionService
{
    public function __construct(
        private AuditService $auditService,
        private NotificationServiceInterface $notificationService
    ) {}

    /**
     * @param  array<int, array{id: int, quantity: int}>  $itemsData
     * @return array{total: float, requestItemsPayload: array<int, array{menu_item_id: int, quantity: int, price_snapshot: float}>}
     */
    public function computeLineItems(int $providerId, array $itemsData): array
    {
        $totalAmount = 0;
        $requestItemsPayload = [];

        $itemIds = array_column($itemsData, 'id');
        $dbItems = ProviderMenuItem::whereIn('id', $itemIds)->get()->keyBy('id');

        foreach ($itemsData as $item) {
            if (! isset($dbItems[$item['id']])) {
                throw new RuntimeException('Menu item no longer available.');
            }

            $menuItem = $dbItems[$item['id']];

            if ((int) $menuItem->provider_id !== $providerId) {
                throw new RuntimeException('Menu item does not belong to this provider.');
            }

            $qty = (int) $item['quantity'];
            $price = (float) $menuItem->price;
            $lineTotal = $price * $qty;

            $totalAmount += $lineTotal;

            $requestItemsPayload[] = [
                'menu_item_id' => $menuItem->id,
                'quantity' => $qty,
                'price_snapshot' => $price,
            ];
        }

        return [
            'total' => (float) $totalAmount,
            'requestItemsPayload' => $requestItemsPayload,
        ];
    }

    /**
     * @param  array<int, array{id: int, quantity: int}>  $itemsData
     */
    public function createRequest(User $user, int $providerId, array $itemsData): RequestModel
    {
        $computed = $this->computeLineItems($providerId, $itemsData);
        $totalAmount = $computed['total'];
        $requestItemsPayload = $computed['requestItemsPayload'];

        $req = RequestModel::create([
            'recipient_id' => $user->id,
            'provider_id' => $providerId,
            'reserved_amount' => $totalAmount,
            'funding_source' => 'CITY_FUND',
            'status' => 'REQUESTED',
            'is_flagged' => false,
        ]);

        foreach ($requestItemsPayload as $payload) {
            $req->items()->create($payload);
        }

        $this->auditService->log('request', 'created', [
            'request_id' => $req->id,
            'recipient_id' => $user->id,
            'provider_id' => $providerId,
            'amount' => $totalAmount,
        ]);

        $this->notificationService->sendNewRequestToProvider($req->load('provider'));

        return $req;
    }
}
