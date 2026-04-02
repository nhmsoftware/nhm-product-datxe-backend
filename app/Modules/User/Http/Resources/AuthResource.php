<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'phone'             => $this->phone,
            'email'             => $this->email,
            'full_name'         => $this->full_name,
            'role'              => $this->role->value,
            'role_label'        => $this->role->label(),
            'gender'            => $this->gender,
            'is_verified'       => $this->is_verified,
            'is_phone_verified' => $this->is_phone_verified,
            'is_active'         => $this->is_active,
            'created_at'        => $this->created_at,
        ];
    }
}
