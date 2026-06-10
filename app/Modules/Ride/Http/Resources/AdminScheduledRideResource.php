<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Resources;

use App\Modules\Ride\Model\Enums\RideStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminScheduledRideResource extends JsonResource
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
            RideStatus::COMPLETED->value => 'completed',
            RideStatus::CANCELLED->value => 'canceled',
        ];

        return [
            'id'                     => (string) $this->id,
            'ride_code'              => (string) $this->id, // Sử dụng ID làm mã chuyến tạm thời
            'ride_type'              => $this->ride_type?->value,
            'customer_id'            => $this->customer?->id ? (string) $this->customer->id : null,
            'customer_name'          => $this->customer?->full_name ?? 'Khách lẻ',
            'customer_phone'         => $this->customer?->phone ?? '',
            'customer_email'         => $this->customer?->email,
            'driver_id'              => $this->driver?->id ? (string) $this->driver->id : null,
            'driver_name'            => $this->driver?->full_name ?? '',
            'driver_phone'           => $this->driver?->phone ?? '',
            'ride_type_name'         => $this->ride_type?->getLabel() ?? 'Chuyến xe',
            'pickup_address'         => $this->pickup_address,
            'pickup_lat'             => (float) $this->pickup_lat,
            'pickup_lng'             => (float) $this->pickup_lng,
            'destination_address'    => $this->destination_address,
            'destination_lat'        => (float) $this->destination_lat,
            'destination_lng'        => (float) $this->destination_lng,
            'travel_date'            => $this->travel_date ? $this->travel_date->format('Y-m-d') : null,
            'travel_time'            => $this->travel_time,
            'pickup_time_formatted'  => $this->travel_date ? $this->travel_date->format('d/m/Y') : '',
            'pickup_hour'            => $this->travel_time ? substr($this->travel_time, 0, 5) : '',
            'vehicle_type'           => $vehicleTypeId,
            'vehicle_type_name'      => $vehicleTypeName,
            'final_fare'             => (float) $this->total_price,
            'base_price'             => (float) $this->base_price,
            'distance_price'         => (float) $this->distance_price,
            'time_fare'              => (float) $this->time_fare,
            'discount_amount'        => (float) $this->discount_amount,
            'voucher_code'           => $this->voucher_code,
            'distance_km'            => round($this->distance / 1000, 1),
            'duration_minutes'       => round($this->duration / 60),
            'status'                 => $statusMap[$this->status->value] ?? 'waiting',
            'status_value'           => $this->status->value,
            'status_label'           => $this->status->getLabel(),
            'cancel_reason'          => $this->cancel_reason,
            'can_edit'               => in_array($this->status->value, [RideStatus::PENDING->value, RideStatus::ACCEPTED->value], true),
            'can_cancel'             => !in_array($this->status->value, [RideStatus::COMPLETED->value, RideStatus::CANCELLED->value], true),
            'created_at'             => $this->created_at?->toDateTimeString(),
        ];
    }
}
