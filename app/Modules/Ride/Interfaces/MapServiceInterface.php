<?php

declare(strict_types=1);


namespace App\Modules\Ride\Interfaces;

use App\Modules\Ride\DTO\MapMatrixDTO;
use App\Modules\Ride\DTO\DirectionDTO;

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

    /**
     * Get direction polyline, distance and duration between two points.
     * (UC-34 Navigation)
     *
     * @param float $originLat
     * @param float $originLng
     * @param float $destLat
     * @param float $destLng
     * @return DirectionDTO
     */
    public function getDirection(float $originLat, float $originLng, float $destLat, float $destLng): DirectionDTO;

    /**
     * Calculate direct distance (bird-fly) between two points in meters.
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float Distance in meters
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float;
}
