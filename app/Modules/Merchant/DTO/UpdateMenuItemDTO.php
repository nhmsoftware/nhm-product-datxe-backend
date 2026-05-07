<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class UpdateMenuItemDTO
{
    public function __construct(
        public readonly string        $itemId,
        public readonly string        $merchantProfileId,
        public readonly string        $name,
        public readonly float         $price,
        public readonly string        $categoryName,
        public readonly ?string       $categoryId = null,
        public readonly ?string       $description = null,
        public readonly ?UploadedFile $image = null,
        public readonly array         $sizes = [],
        public readonly array         $toppings = [],
    ) {}

    public static function fromRequest(Request $request, string $itemId): self
    {
        return new self(
            itemId: $itemId,
            merchantProfileId: (string) $request->user()->merchantProfile?->id,
            name: $request->input('name'),
            price: (float) $request->input('price'),
            categoryName: $request->input('category_name'),
            categoryId: $request->input('category_id'),
            description: $request->input('description'),
            image: $request->file('image'),
            sizes: $request->input('sizes', []),
            toppings: $request->input('toppings', []),
        );
    }
}
