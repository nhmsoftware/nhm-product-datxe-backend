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
        public readonly ?int    $driverGroupType = null,
        public readonly int     $perPage = 20,
        public readonly int     $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            keyword:   $request->query('keyword'),
            kycStatus: $request->filled('kyc_status') || $request->query('kyc_status') === '0' ? (int) $request->query('kyc_status') : null,
            isActive:  $request->has('is_active') ? $request->boolean('is_active') : null,
            driverGroupType: $request->filled('driver_group_type') ? (int) $request->query('driver_group_type') : null,
            perPage:   (int) $request->query('per_page', 20),
            page:      (int) $request->query('page', 1),
        );
    }

    public function toArray(): array
    {
        return [
            'keyword'    => $this->keyword,
            'kyc_status' => $this->kycStatus,
            'is_active'  => $this->isActive,
            'driver_group_type' => $this->driverGroupType,
            'per_page'   => $this->perPage,
            'page'       => $this->page,
        ];
    }
}
