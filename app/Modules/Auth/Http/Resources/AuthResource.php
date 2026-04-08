<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{

    /**
     * Chuyển đổi tài nguyên thành một mảng.
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'phone'             => $this->phone,
            'email'             => $this->email,
            'role'              => $this->role->value,
            'is_verified'       => $this->is_verified,
            'is_phone_verified' => $this->is_phone_verified,
            'is_active'         => $this->is_active,
            'created_at'        => $this->created_at,
        ];
    }
}
