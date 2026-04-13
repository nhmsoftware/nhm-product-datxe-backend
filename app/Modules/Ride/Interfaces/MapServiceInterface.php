<?php

declare(strict_types=1);


namespace App\Modules\Ride\Interfaces;

use App\Modules\Ride\DTO\MapMatrixDTO;

interface MapServiceInterface
{
    /**
     * Get distance and duration between two points.
     *
     * @param float $originLat
     * @param float $originLng
     * @param float $destLat
     * @param float $destLng
     * @return MapMatrixDTO Distance in meters, duration in seconds.
     */
    public function getDistanceMatrix(float $originLat, float $originLng, float $destLat, float $destLng): MapMatrixDTO;
}
