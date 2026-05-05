<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use Illuminate\Http\Request;

final class ListDriversDTO
{
    public function __construct(
        public readonly ?string $keyword = null,
        public readonly ?int    $kycStatus = null,
        public readonly ?bool   $isActive = null,
        public readonly int     $perPage = 15,
        public readonly int     $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            keyword:   $request->query('keyword'),
            kycStatus: $request->query('kyc_status') ? (int) $request->query('kyc_status') : null,
            isActive:  $request->has('is_active') ? $request->boolean('is_active') : null,
            perPage:   (int) $request->query('per_page', 15),
            page:      (int) $request->query('page', 1),
        );
    }

    public function toArray(): array
    {
        return [
            'keyword'    => $this->keyword,
            'kyc_status' => $this->kycStatus,
            'is_active'  => $this->isActive,
            'per_page'   => $this->perPage,
            'page'       => $this->page,
        ];
    }
}
