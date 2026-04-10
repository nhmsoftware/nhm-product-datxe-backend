<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->buildProfileData();
    }

    /**
     * Build complete profile data with optional fields marked.
     *
     * @return array
     */
    private function buildProfileData(): array
    {
        $user = $this->resource;

        $data = [
            // Thông tin cơ bản (chung cho tất cả vai trò)
            'id' => $user->id,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'avatar' => $this->formatOptionalField($user->avatar, 'avatar'),
            'full_name' => $this->formatOptionalField($user->full_name, 'full_name'),
            'birthday' => $user->customerProfile?->birthday?->toDateString(),
            'phone' => $user->phone ?? null,
            'email' => $this->formatOptionalField($user->email, 'email'),
            'gender' => $this->formatOptionalField($user->gender?->value, 'gender'),
            'gender_label' => $user->gender?->label(),
            'address' => $this->formatOptionalField($user->address, 'address'),
            'citizen_id' => $this->formatOptionalField($user->citizen_id, 'citizen_id'),
            'is_verified' => $user->is_verified,
            'is_phone_verified' => $user->is_phone_verified,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'deleted_at' => $user->deleted_at?->toIso8601String(),
        ];

        // Thông tin riêng theo vai trò
        if ($user->driverProfile) {
            $data['driver_specific'] = $this->buildDriverSpecific($user->driverProfile);
        }

        if ($user->merchantProfile) {
            $data['merchant_specific'] = $this->buildMerchantSpecific($user->merchantProfile);
        }

        if ($user->customerProfile) {
            $data['customer_specific'] = $this->buildCustomerSpecific($user->customerProfile);
        }

        return $data;
    }

    /**
     * Build driver-specific profile data.
     *
     * @return array
     */
    private function buildDriverSpecific($driver): array
    {
        return [
            'full_name' => $this->formatOptionalField($driver->full_name, 'full_name'),
            'vehicle_info' => [
                'name' => $this->formatOptionalField($driver->vehicle_name, 'vehicle_name'),
                'type' => $this->formatOptionalField($driver->vehicle_type, 'vehicle_type'),
                'color' => $this->formatOptionalField($driver->vehicle_color, 'vehicle_color'),
                'number' => $this->formatOptionalField($driver->vehicle_number, 'vehicle_number'),
            ],
            'license' => [
                'number' => $this->formatOptionalField($driver->license_number, 'license_number'),
                'front_image' => $driver->license_front_image,
                'back_image' => $driver->license_back_image,
            ],
            'stats' => [
                'average_rating' => $this->formatOptionalField($driver->average_rating, 'average_rating'),
                'total_trips' => $driver->total_trips ?? 0,
            ],
            'banking' => [
                'bank_name' => $this->formatOptionalField($driver->bank_name, 'bank_name'),
                'account_number' => $this->formatOptionalField($driver->bank_account_number, 'bank_account_number'),
                'account_holder' => $this->formatOptionalField($driver->bank_account_holder, 'bank_account_holder'),
            ],
        ];
    }

    /**
     * Build merchant-specific profile data.
     *
     * @return array
     */
    private function buildMerchantSpecific($merchant): array
    {
        return [
            'store_name' => $this->formatOptionalField($merchant->store_name, 'store_name'),
            'store_address' => $this->formatOptionalField($merchant->store_address, 'store_address'),
            'lat' => $merchant->lat,
            'lng' => $merchant->lng,
            'business_hours' => [
                'opening_time' => $this->formatOptionalField($merchant->opening_time, 'opening_time'),
                'closing_time' => $this->formatOptionalField($merchant->closing_time, 'closing_time'),
            ],
            'status' => [
                'is_open' => $merchant->is_open ?? true,
                'label' => ($merchant->is_open ?? true) ? 'Mở cửa' : 'Đóng cửa',
            ],
            'license' => [
                'number' => $this->formatOptionalField($merchant->business_license, 'business_license'),
                'image' => $merchant->business_license_image,
            ],
            'stats' => [
                'average_rating' => $this->formatOptionalField($merchant->average_rating, 'average_rating'),
                'total_orders' => $merchant->total_orders ?? 0,
            ],
        ];
    }

    /**
     * Build customer-specific profile data.
     *
     * @return array
     */
    private function buildCustomerSpecific($customer): array
    {
        return [
            'birthday' => $this->formatOptionalField($customer->birthday?->toDateString(), 'birthday'),
        ];
    }

    /**
     * Format optional field with pending_update flag.
     * A2/A1 - Trường chưa có dữ liệu sẽ hiển thị "Chưa cập nhật"
     *
     * @param mixed $value
     * @param string $fieldName
     * @return array
     */
    private function formatOptionalField(mixed $value, string $fieldName): array
    {
        $isEmpty = $value === null || $value === '';

        return [
            'value' => $isEmpty ? null : $value,
            'display' => $isEmpty ? 'Chưa cập nhật' : $value,
            'is_pending' => $isEmpty,
            'field' => $fieldName,
        ];
    }
}
