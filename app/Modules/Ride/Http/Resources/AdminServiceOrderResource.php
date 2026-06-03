<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Resources;

use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource cho Admin quản lý đơn dịch vụ (Giao hàng, Đồ ăn).
 * Khác với AdminScheduledRideResource (chuyến xe hành khách),
 * resource này hiển thị thêm thông tin liên quan đến đơn dịch vụ.
 */
class AdminServiceOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Modules\Ride\Model\Ride $this */

        $statusMap = [
            RideStatus::PENDING->value   => 'waiting',
            RideStatus::ACCEPTED->value  => 'assigned',
            RideStatus::PICKED_UP->value => 'picked_up',
            RideStatus::IN_PROGRESS->value => 'in_progress',
            RideStatus::COMPLETED->value => 'completed',
            RideStatus::CANCELLED->value => 'canceled',
        ];

        $statusLabelMap = [
            RideStatus::PENDING->value     => 'Đang chờ tài xế',
            RideStatus::ACCEPTED->value    => 'Tài xế đã nhận',
            RideStatus::PICKED_UP->value   => 'Đã lấy hàng',
            RideStatus::IN_PROGRESS->value => 'Đang giao',
            RideStatus::COMPLETED->value   => 'Hoàn thành',
            RideStatus::CANCELLED->value   => 'Đã hủy',
        ];

        $data = [
            'id'                     => (string) $this->id,
            'order_code'             => (string) $this->id,
            'ride_type'              => $this->ride_type?->value,
            'ride_type_name'         => $this->ride_type?->getLabel() ?? 'Dịch vụ',
            'is_food_delivery'       => $this->ride_type === RideType::FOOD_DELIVERY,
            'customer_name'          => $this->customer?->full_name ?? 'Khách lẻ',
            'customer_phone'         => $this->customer?->phone ?? '',
            'driver_name'            => $this->driver?->full_name ?? '',
            'driver_phone'           => $this->driver?->phone ?? '',
            'pickup_address'         => $this->pickup_address,
            'destination_address'    => $this->destination_address,
            'vehicle_type'           => $this->vehicle_type?->value,
            'vehicle_type_name'      => $this->vehicle_type?->getLabel() ?? '',
            'final_fare'             => (float) $this->total_price,
            'base_price'             => (float) $this->base_price,
            'discount_amount'        => (float) $this->discount_amount,
            'voucher_code'           => $this->voucher_code,
            'distance_km'            => round($this->distance / 1000, 1),
            'duration_minutes'       => round($this->duration / 60),
            'status'                 => $statusMap[$this->status->value] ?? 'waiting',
            'status_label'           => $statusLabelMap[$this->status->value] ?? $this->status->getLabel(),
            'pickup_proof_photo_url' => $this->pickup_proof_photo_url,
            'delivery_proof_photo_url' => $this->delivery_proof_photo_url,
            'completed_at'           => $this->completed_at?->toDateTimeString(),
            'created_at'             => $this->created_at?->toDateTimeString(),
        ];

        // Thêm thông tin giao hàng nếu là DELIVERY
        if ($this->relationLoaded('deliveryOrder') && $this->deliveryOrder) {
            $data['delivery_info'] = [
                'sender_name'    => $this->deliveryOrder->sender_name,
                'sender_phone'   => $this->deliveryOrder->sender_phone,
                'receiver_name'  => $this->deliveryOrder->receiver_name,
                'receiver_phone' => $this->deliveryOrder->receiver_phone,
                'goods_type'     => $this->deliveryOrder->goods_type,
                'goods_weight'   => $this->deliveryOrder->goods_weight,
                'goods_note'     => $this->deliveryOrder->goods_note,
                'is_fragile'     => (bool) $this->deliveryOrder->is_fragile,
            ];
        }

        return $data;
    }
}
