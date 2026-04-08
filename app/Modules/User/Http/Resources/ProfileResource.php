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
        $data = [
            // Thông tin cơ bản (chung cho tất cả vai trò)
            'id' => $this->resource['id'] ?? null,
            'role' => $this->resource['role'] ?? null,
            'role_label' => $this->resource['role_label'] ?? null,
            'avatar' => $this->formatOptionalField($this->resource['avatar'] ?? null, 'avatar'),
            'full_name' => $this->formatOptionalField($this->resource['full_name'] ?? null, 'full_name'),
            'phone' => $this->resource['phone'],
            'email' => $this->formatOptionalField($this->resource['email'] ?? null, 'email'),
            'gender' => $this->formatOptionalField($this->resource['gender'] ?? null, 'gender'),
            'gender_label' => $this->resource['gender_label'] ?? null,
            'address' => $this->formatOptionalField($this->resource['address'] ?? null, 'address'),
            'citizen_id' => $this->formatOptionalField($this->resource['citizen_id'] ?? null, 'citizen_id'),
            'is_verified' => $this->resource['is_verified'] ?? false,
            'is_phone_verified' => $this->resource['is_phone_verified'] ?? false,
            'created_at' => $this->resource['created_at'] ?? null,
        ];

        // Thông tin riêng theo vai trò
        if (isset($this->resource['driver_specific'])) {
            $data['driver_specific'] = $this->buildDriverSpecific();
        }

        if (isset($this->resource['merchant_specific'])) {
            $data['merchant_specific'] = $this->buildMerchantSpecific();
        }

        if (isset($this->resource['customer_specific'])) {
            $data['customer_specific'] = $this->buildCustomerSpecific();
        }

        return $data;
    }

    /**
     * Build driver-specific profile data.
     *
     * @return array
     */
    private function buildDriverSpecific(): array
    {
        $driver = $this->resource['driver_specific'] ?? [];

        return [
            'full_name' => $this->formatOptionalField($driver['full_name'] ?? null, 'full_name'),
            'vehicle_info' => [
                'name' => $this->formatOptionalField($driver['vehicle_info']['name'] ?? null, 'vehicle_name'),
                'type' => $this->formatOptionalField($driver['vehicle_info']['type'] ?? null, 'vehicle_type'),
                'color' => $this->formatOptionalField($driver['vehicle_info']['color'] ?? null, 'vehicle_color'),
                'number' => $this->formatOptionalField($driver['vehicle_info']['number'] ?? null, 'vehicle_number'),
            ],
            'license' => [
                'number' => $this->formatOptionalField($driver['license']['number'] ?? null, 'license_number'),
                'front_image' => $driver['license']['front_image'] ?? null,
                'back_image' => $driver['license']['back_image'] ?? null,
            ],
            'stats' => [
                'average_rating' => $this->formatOptionalField($driver['stats']['average_rating'] ?? null, 'average_rating'),
                'total_trips' => $driver['stats']['total_trips'] ?? 0,
            ],
            'banking' => [
                'bank_name' => $this->formatOptionalField($driver['banking']['bank_name'] ?? null, 'bank_name'),
                'account_number' => $this->formatOptionalField($driver['banking']['account_number'] ?? null, 'bank_account_number'),
                'account_holder' => $this->formatOptionalField($driver['banking']['account_holder'] ?? null, 'bank_account_holder'),
            ],
        ];
    }

    /**
     * Build merchant-specific profile data.
     *
     * @return array
     */
    private function buildMerchantSpecific(): array
    {
        $merchant = $this->resource['merchant_specific'] ?? [];

        return [
            'store_name' => $this->formatOptionalField($merchant['store_name'] ?? null, 'store_name'),
            'store_address' => $this->formatOptionalField($merchant['store_address'] ?? null, 'store_address'),
            'location' => [
                'latitude' => $merchant['store_latitude'] ?? null,
                'longitude' => $merchant['store_longitude'] ?? null,
            ],
            'business_hours' => [
                'opening_time' => $this->formatOptionalField($merchant['opening_time'] ?? null, 'opening_time'),
                'closing_time' => $this->formatOptionalField($merchant['closing_time'] ?? null, 'closing_time'),
            ],
            'status' => [
                'is_open' => $merchant['is_open'] ?? true,
                'label' => ($merchant['is_open'] ?? true) ? 'Mở cửa' : 'Đóng cửa',
            ],
            'license' => [
                'number' => $this->formatOptionalField($merchant['business_license'] ?? null, 'business_license'),
                'image' => $merchant['business_license_image'] ?? null,
            ],
            'stats' => [
                'average_rating' => $this->formatOptionalField($merchant['average_rating'] ?? null, 'average_rating'),
                'total_orders' => $merchant['total_orders'] ?? 0,
            ],
        ];
    }

    /**
     * Build customer-specific profile data.
     *
     * @return array
     */
    private function buildCustomerSpecific(): array
    {
        // Customer chỉ hiển thị thông tin cơ bản
        return [];
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
