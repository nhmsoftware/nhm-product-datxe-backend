<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Domain\Enums\UserRole;

/**
 * Shape:
 * {
 *   "id": 1,
 *   "phone": "0901234567",
 *   "email": null,
 *   "role": 2,
 *   "role_label": "Khách hàng",
 *   "is_verified": false,
 *   "profile": { "full_name": "...", "gender": 1 },
 *   "created_at": "2025-01-01T00:00:00.000000Z"
 * }
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'role'        => $this->role->value,
            'role_label'  => $this->role->label(),
            'is_verified' => $this->is_verified,
            'profile'     => $this->buildProfile(),
            'created_at'  => $this->created_at,
        ];
    }

    private function buildProfile(): ?array
    {
        return match (true) {
            $this->isCustomer() && $this->relationLoaded('customerProfile') && $this->customerProfile
                => [
                    'full_name' => $this->customerProfile->full_name,
                    'gender'    => $this->customerProfile->gender?->value,
                ],
            $this->isDriver() && $this->relationLoaded('driverProfile') && $this->driverProfile
                => [
                    'full_name'    => $this->driverProfile->full_name,
                    'vehicle_type' => $this->driverProfile->vehicle_type?->value,
                    'is_online'    => $this->driverProfile->is_online,
                    'status'       => $this->driverProfile->status?->value,
                ],
            default => null,
        };
    }
}
