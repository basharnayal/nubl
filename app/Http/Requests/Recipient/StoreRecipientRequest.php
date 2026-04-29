<?php

namespace App\Http\Requests\Recipient;

use App\Models\ProviderMenuItem;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRecipientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole('recipient') && $this->user()->hasFullAccess());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:provider_menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $validator->getData();
            $providerId = $data['provider_id'] ?? null;
            $items = $data['items'] ?? [];

            if (! $providerId) {
                return;
            }

            // 1. Validate Provider
            $provider = User::find($providerId);
            if (! $provider || ! $provider->hasRole('provider')) {
                $validator->errors()->add('provider_id', 'Invalid provider selected.');

                return;
            }

            // Account active + shop open (accepting orders)
            if (! $provider->isOpenForRecipients()) {
                $validator->errors()->add('provider_id', 'This provider is currently inactive or not accepting orders.');

                return;
            }

            // Check Capacity
            $operatingInfo = $provider->providerOperatingInfo;
            if (! $operatingInfo || $operatingInfo->daily_capacity <= 0) {
                $validator->errors()->add('provider_id', 'This provider is currently not accepting orders (Capacity Full/Off).');

                return;
            }

            if (empty($items)) {
                return;
            }

            // 2. Validate Items
            // Improve perf: fetch all items at once
            $itemIds = array_column($items, 'id');
            $dbItems = ProviderMenuItem::whereIn('id', $itemIds)->get()->keyBy('id');

            foreach ($items as $index => $itemData) {
                $itemId = $itemData['id'] ?? null;
                $quantity = $itemData['quantity'] ?? 0;

                if (! $itemId || ! isset($dbItems[$itemId])) {
                    $validator->errors()->add("items.{$index}.id", 'Invalid menu item selected.');

                    continue;
                }

                $menuItem = $dbItems[$itemId];

                // Check Ownership
                if ($menuItem->provider_id != $providerId) {
                    $validator->errors()->add("items.{$index}.id", "Item '{$menuItem->localized_name}' does not belong to the selected provider.");
                }

                // Check Active
                if (! $menuItem->is_active) {
                    $validator->errors()->add("items.{$index}.id", "Item '{$menuItem->localized_name}' is currently unavailable.");
                }

                // Check Max Quantity
                if ($menuItem->max_per_request && $quantity > $menuItem->max_per_request) {
                    $validator->errors()->add("items.{$index}.quantity", "You can only order up to {$menuItem->max_per_request} of '{$menuItem->localized_name}' per request.");
                }
            }
        });
    }
}
