<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

use App\Modules\Order\Http\Requests\AdminUpdateFoodOrderRequest;

final class AdminUpdateFoodOrderDTO
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $merchantId,
        /** @var array<int, array<string, mixed>> */
        public readonly array $items,
        public readonly string $deliveryAddress,
        public readonly ?float $deliveryLat,
        public readonly ?float $deliveryLng,
        public readonly ?string $notes,
        public readonly float $subtotalPrice,
        public readonly float $deliveryFee,
        public readonly float $serviceFee,
        public readonly float $totalPrice,
        public readonly ?float $distanceKm = null,
        public readonly ?int $durationMinutes = null,
        public readonly ?string $driverId = null,
    ) {}

    public static function fromRequest(AdminUpdateFoodOrderRequest $request, string $orderId): self
    {
        return new self(
            orderId: $orderId,
            merchantId: (string) $request->input('merchant_id'),
            items: (array) $request->input('items', []),
            deliveryAddress: $request->string('delivery_address')->toString(),
            deliveryLat: $request->filled('delivery_lat') ? (float) $request->input('delivery_lat') : null,
            deliveryLng: $request->filled('delivery_lng') ? (float) $request->input('delivery_lng') : null,
            notes: $request->input('notes'),
            subtotalPrice: (float) $request->input('subtotal_price'),
            deliveryFee: (float) $request->input('delivery_fee'),
            serviceFee: (float) $request->input('service_fee'),
            totalPrice: (float) $request->input('total_price'),
            distanceKm: $request->filled('distance_km') ? (float) $request->input('distance_km') : null,
            durationMinutes: $request->filled('duration_minutes') ? (int) $request->input('duration_minutes') : null,
            driverId: $request->input('driver_id'),
        );
    }
}
