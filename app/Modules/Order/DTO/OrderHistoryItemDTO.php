<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

final class OrderHistoryItemDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $serviceType, // 'ride' or 'food'
        public readonly float $totalPrice,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly ?string $pickupAddress,
        public readonly ?string $destinationAddress,
        public readonly string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            serviceType: $data['service_type'],
            totalPrice: (float) $data['total_price'],
            status: (string) $data['status'],
            statusLabel: $data['status_label'],
            pickupAddress: $data['pickup_address'] ?? null,
            destinationAddress: $data['destination_address'] ?? null,
            createdAt: $data['created_at'],
        );
    }
}
