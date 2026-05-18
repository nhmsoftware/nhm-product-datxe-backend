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
        
        $statusMap = [
            RideStatus::PENDING->value   => 'waiting',
            RideStatus::ACCEPTED->value  => 'assigned',
            RideStatus::COMPLETED->value => 'completed',
            RideStatus::CANCELLED->value => 'canceled',
        ];

        return [
            'id'                     => (string) $this->id,
            'ride_code'              => (string) $this->id, // Sử dụng ID làm mã chuyến tạm thời
            'customer_name'          => $this->customer?->full_name ?? 'Khách lẻ',
            'customer_phone'         => $this->customer?->phone ?? '',
            'driver_name'            => $this->driver?->full_name ?? '',
            'driver_phone'           => $this->driver?->phone ?? '',
            'ride_type_name'         => $this->ride_type?->getLabel() ?? 'Chuyến xe',
            'pickup_address'         => $this->pickup_address,
            'destination_address'    => $this->destination_address,
            'pickup_time_formatted'  => $this->travel_date ? $this->travel_date->format('d/m/Y') : '',
            'pickup_hour'            => $this->travel_time ? substr($this->travel_time, 0, 5) : '',
            'vehicle_type_name'      => $this->vehicle_type?->getLabel() ?? '',
            'final_fare'             => (float) $this->total_price,
            'base_price'             => (float) $this->base_price,
            'distance_price'         => (float) $this->distance_price,
            'time_fare'              => (float) $this->time_fare,
            'discount_amount'        => (float) $this->discount_amount,
            'voucher_code'           => $this->voucher_code,
            'distance_km'            => round($this->distance / 1000, 1),
            'duration_minutes'       => round($this->duration / 60),
            'status'                 => $statusMap[$this->status->value] ?? 'waiting',
            'created_at'             => $this->created_at?->toDateTimeString(),
        ];
    }
}
