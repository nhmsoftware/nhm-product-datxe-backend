<?php

declare(strict_types=1);

namespace App\Modules\Ride\Http\Resources;

use Illuminate\Http\Request;

/**
 * Resource cho dịch vụ Lái hộ (Chauffeur).
 */
final class AdminChauffeurRideResource extends AdminScheduledRideResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        /** @var \App\Modules\Ride\Model\Ride $this */
        return array_merge($data, [
            'chauffeur_license_plate' => $this->chauffeur_license_plate,
            'chauffeur_vehicle_type'  => $this->chauffeur_vehicle_type,
            'chauffeur_brand'         => $this->chauffeur_brand,
            'chauffeur_color'         => $this->chauffeur_color,
            'customer_phone'          => $this->customer?->phone,
            'driver_name'             => $this->driver?->full_name,
            'driver_phone'            => $this->driver?->phone,
        ]);
    }
}
