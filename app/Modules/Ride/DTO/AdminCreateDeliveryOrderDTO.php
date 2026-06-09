<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\AdminCreateDeliveryOrderRequest;

final class AdminCreateDeliveryOrderDTO
{
    public function __construct(
        public readonly string $senderName,
        public readonly string $senderPhone,
        public readonly string $pickupAddress,
        public readonly ?float $pickupLat,
        public readonly ?float $pickupLng,
        public readonly string $receiverName,
        public readonly string $receiverPhone,
        public readonly string $destinationAddress,
        public readonly ?float $destinationLat,
        public readonly ?float $destinationLng,
        public readonly string $goodsType,
        public readonly ?string $goodsNote,
        public readonly float $totalPrice,
        public readonly ?float $distanceKm = null,
        public readonly ?int $durationMinutes = null,
        public readonly ?string $driverId = null,
    ) {}

    public static function fromRequest(AdminCreateDeliveryOrderRequest $request): self
    {
        return new self(
            senderName: $request->string('sender_name')->toString(),
            senderPhone: $request->string('sender_phone')->toString(),
            pickupAddress: $request->string('pickup_address')->toString(),
            pickupLat: $request->filled('pickup_lat') ? (float) $request->input('pickup_lat') : null,
            pickupLng: $request->filled('pickup_lng') ? (float) $request->input('pickup_lng') : null,
            receiverName: $request->string('receiver_name')->toString(),
            receiverPhone: $request->string('receiver_phone')->toString(),
            destinationAddress: $request->string('destination_address')->toString(),
            destinationLat: $request->filled('destination_lat') ? (float) $request->input('destination_lat') : null,
            destinationLng: $request->filled('destination_lng') ? (float) $request->input('destination_lng') : null,
            goodsType: $request->string('goods_type')->toString(),
            goodsNote: $request->input('goods_note'),
            totalPrice: (float) $request->input('total_price'),
            distanceKm: $request->filled('distance_km') ? (float) $request->input('distance_km') : null,
            durationMinutes: $request->filled('duration_minutes') ? (int) $request->input('duration_minutes') : null,
            driverId: $request->input('driver_id'),
        );
    }
}
