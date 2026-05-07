<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use App\Modules\Merchant\Http\Requests\ManageComboRequest;

final class ComboDTO
{
    /**
     * @param array<int, array{menu_item_id: string, quantity: int}> $items
     */
    public function __construct(
        public readonly string $merchantProfileId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
        public readonly ?string $imagePath,
        public readonly bool $isAvailable,
        public readonly int $order,
        public readonly array $items,
    ) {}

    public static function fromRequest(ManageComboRequest $request, string $merchantProfileId): self
    {
        return new self(
            merchantProfileId: $merchantProfileId,
            name: $request->string('name')->toString(),
            description: $request->string('description')->toString() ?: null,
            price: (float) $request->input('price'),
            imagePath: $request->string('image_path')->toString() ?: null,
            isAvailable: (bool) $request->input('is_available', true),
            order: (int) $request->input('order', 0),
            items: $request->input('items', []),
        );
    }
}
