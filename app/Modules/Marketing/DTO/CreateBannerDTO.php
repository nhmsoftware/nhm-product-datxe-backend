<?php

declare(strict_types=1);

namespace App\Modules\Marketing\DTO;

use App\Modules\Marketing\Http\Requests\CreateBannerRequest;

final class CreateBannerDTO
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $label,
        public readonly ?string $tag,
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
            label: $request->input('label'),
            tag: $request->input('tag'),
            image: $request->file('image'),
            action_url: $request->input('action_url'),
            order: (int) $request->input('order', 0),
            status: (int) $request->input('status', 1),
        );
    }
}
