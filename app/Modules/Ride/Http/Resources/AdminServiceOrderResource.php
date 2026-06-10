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
        $vehicleTypeRef = $this->relationLoaded('vehicleTypeRef') ? $this->vehicleTypeRef : null;
        $vehicleTypeId = $this->vehicle_type !== null ? (int) $this->vehicle_type : null;
        $vehicleTypeName = $vehicleTypeRef?->name_vi ?? ($vehicleTypeId !== null ? ('Loại xe #' . $vehicleTypeId) : '');

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
            'is_delivery'            => $this->ride_type === RideType::DELIVERY,
            'customer_name'          => $this->customer?->full_name ?? 'Khách lẻ',
            'customer_phone'         => $this->customer?->phone ?? '',
            'driver_id'              => $this->driver?->id ? (string) $this->driver->id : null,
            'driver_name'            => $this->driver?->full_name ?? '',
            'driver_phone'           => $this->driver?->phone ?? '',
            'pickup_address'         => $this->pickup_address,
            'pickup_lat'             => (float) $this->pickup_lat,
            'pickup_lng'             => (float) $this->pickup_lng,
            'destination_address'    => $this->destination_address,
            'destination_lat'        => (float) $this->destination_lat,
            'destination_lng'        => (float) $this->destination_lng,
            'vehicle_type'           => $vehicleTypeId,
            'vehicle_type_name'      => $vehicleTypeName,
            'total_amount'           => (float) $this->total_price,
            'final_fare'             => (float) $this->total_price,
            'base_price'             => (float) $this->base_price,
            'discount_amount'        => (float) $this->discount_amount,
            'voucher_code'           => $this->voucher_code,
            'distance_km'            => round($this->distance / 1000, 1),
            'duration_minutes'       => round($this->duration / 60),
            'status'                 => $statusMap[$this->status->value] ?? 'waiting',
            'status_value'           => $this->status->value,
            'status_label'           => $statusLabelMap[$this->status->value] ?? $this->status->getLabel(),
            'cancel_reason'          => $this->cancel_reason,
            'can_edit'               => in_array($this->status->value, [RideStatus::PENDING->value, RideStatus::ACCEPTED->value], true),
            'can_cancel'             => !in_array($this->status->value, [RideStatus::COMPLETED->value, RideStatus::CANCELLED->value], true),
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
