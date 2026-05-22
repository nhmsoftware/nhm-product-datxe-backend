<?php

declare(strict_types=1);

namespace App\Modules\Marketing\DTO;

use App\Modules\Marketing\Http\Requests\CreateBannerRequest;

final class CreateBannerDTO
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly \Illuminate\Http\UploadedFile $image,
        public readonly ?string $action_url,
        public readonly int $order,
        public readonly int $status,
    ) {}

    public static function fromRequest(CreateBannerRequest $request): self
    {
        return new self(
            title: $request->input('title'),
            description: $request->input('description'),
            image: $request->file('image'),
            action_url: $request->input('action_url'),
            order: (int) $request->input('order', 0),
            status: (int) $request->input('status', 1),
        );
    }
}
