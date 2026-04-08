<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'label' => $this->resource['label'],
            'label_text' => $this->resource['label_text'] ?? $this->getLabelText($this->resource['label']),
            'name' => $this->resource['name'],
            'address_text' => $this->resource['address_text'],
            'location' => $this->resource['location'],
            'receiver_name' => $this->resource['receiver_name'],
            'receiver_phone' => $this->resource['receiver_phone'],
            'note' => $this->resource['note'],
            'is_default' => $this->resource['is_default'],
            'created_at' => $this->resource['created_at'],
            'updated_at' => $this->resource['updated_at'],
        ];
    }

    /**
     * Get label text from label value.
     */
    private function getLabelText(int $label): string
    {
        return match ($label) {
            1 => 'Nhà',
            2 => 'Công ty',
            3 => 'Nhà hàng yêu thích',
            4 => 'Khác',
            default => 'Khác',
        };
    }
}