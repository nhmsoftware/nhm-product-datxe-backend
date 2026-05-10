<?php

declare(strict_types=1);

namespace App\Modules\Food\DTO;

use App\Modules\Food\Http\Requests\CreateFoodOrderRequest;

final class CreateFoodOrderDTO
{
    /**
     * @param FoodOrderItemDTO[] $items
     */
    public function __construct(
        public readonly int $customerId,
        public readonly int $merchantId,
        public readonly array $items,
        public readonly string $deliveryAddress,
        public readonly float $deliveryLat,
        public readonly float $deliveryLng,
        public readonly string $customerPhone,
        public readonly ?string $notes = null,
        public readonly ?string $voucherCode = null,
    ) {}

    public static function fromRequest(CreateFoodOrderRequest $request): self
    {
        $items = [];
        foreach ($request->input('items', []) as $itemData) {
            $options = [];
            foreach ($itemData['options'] ?? [] as $optionData) {
                $options[] = new FoodOrderItemOptionDTO(
                    optionName: $optionData['name'],
                    optionValue: $optionData['value'],
                    price: (float) ($optionData['price'] ?? 0)
                );
            }

            $items[] = new FoodOrderItemDTO(
                menuItemId: (int) $itemData['menu_item_id'],
                quantity: (int) $itemData['quantity'],
                notes: $itemData['notes'] ?? null,
                options: $options
            );
        }

        return new self(
            customerId: (int) $request->user()->id,
            merchantId: (int) $request->input('merchant_id'),
            items: $items,
            deliveryAddress: $request->string('delivery_address')->toString(),
            deliveryLat: (float) $request->input('delivery_lat'),
            deliveryLng: (float) $request->input('delivery_lng'),
            customerPhone: $request->string('customer_phone')->toString(),
            notes: $request->input('notes'),
            voucherCode: $request->input('voucher_code'),
        );
    }
}
