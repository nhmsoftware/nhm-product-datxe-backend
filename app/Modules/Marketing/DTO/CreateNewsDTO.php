<?php

declare(strict_types=1);

namespace App\Modules\Marketing\DTO;

use App\Modules\Marketing\Http\Requests\CreateNewsRequest;

final class CreateNewsDTO
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $content,
        public readonly \Illuminate\Http\UploadedFile $image,
        public readonly ?string $tag,
        public readonly int $order,
        public readonly int $status,
    ) {}

    public static function fromRequest(CreateNewsRequest $request): self
    {
        return new self(
            title: $request->input('title'),
            description: $request->input('description'),
            content: $request->input('content'),
            image: $request->file('image'),
            tag: $request->input('tag'),
            order: (int) $request->input('order', 0),
            status: (int) $request->input('status', 1),
        );
    }
}
