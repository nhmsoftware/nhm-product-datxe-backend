<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Modules\Ride\DTO\MapMatrixDTO;
use App\Modules\Ride\DTO\DirectionDTO;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoongMapService implements MapServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://rsapi.goong.io';

    public function __construct()
    {
        $this->apiKey = config('services.goong.api_key') ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getDistanceMatrix(float $originLat, float $originLng, float $destLat, float $destLng): MapMatrixDTO
    {
        if (empty($this->apiKey) || $this->apiKey === 'key_goong_api') {
            Log::warning('Goong API Key is missing or default. Returning dynamic fallback distance.');
            return $this->getFallbackMatrix($originLat, $originLng, $destLat, $destLng);
        }

        try {
            $response = Http::get("{$this->baseUrl}/DistanceMatrix", [
                'origins' => "{$originLat},{$originLng}",
                'destinations' => "{$destLat},{$destLng}",
                'vehicle' => 'car',
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Goong Distance Matrix response structure
                $element = $data['rows'][0]['elements'][0] ?? null;

                if ($element && isset($element['status']) && $element['status'] === 'OK') {
                    return MapMatrixDTO::create(
                        distance: (int) ($element['distance']['value'] ?? 0), // meters
                        duration: (int) ($element['duration']['value'] ?? 0)  // seconds
                    );
                }
            }

            Log::error('Goong Matrix API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'origin' => "{$originLat},{$originLng}",
                'dest' => "{$destLat},{$destLng}"
            ]);
        } catch (\Exception $e) {
            Log::error('Goong Matrix API Exception', [
                'message' => $e->getMessage(),
                'origin' => "{$originLat},{$originLng}",
                'dest' => "{$destLat},{$destLng}"
            ]);
        }

        // Fallback values if API fails
        return $this->getFallbackMatrix($originLat, $originLng, $destLat, $destLng);
    }

    /**
     * @inheritDoc
     */
    public function getDirection(float $originLat, float $originLng, float $destLat, float $destLng): DirectionDTO
    {
        if (empty($this->apiKey) || $this->apiKey === 'key_goong_api') {
            Log::warning('Goong API Key is missing or default. Returning dynamic fallback direction.');
            $matrix = $this->getFallbackMatrix($originLat, $originLng, $destLat, $destLng);
            return DirectionDTO::create(
                distance: $matrix->distance,
                duration: $matrix->duration,
                polyline: '', 
                bounds: []
            );
        }

        try {
            $response = Http::get("{$this->baseUrl}/Direction", [
                'origin'      => "{$originLat},{$originLng}",
                'destination' => "{$destLat},{$destLng}",
                'vehicle'     => 'car',
                'api_key'     => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data  = $response->json();
                $route = $data['routes'][0] ?? null;

                if ($route) {
                    $leg = $route['legs'][0] ?? [];
                    return DirectionDTO::create(
                        distance: (int) ($leg['distance']['value'] ?? 0),
                        duration: (int) ($leg['duration']['value'] ?? 0),
                        polyline: (string) ($route['overview_polyline']['points'] ?? ''),
                        bounds:   (array) ($route['bounds'] ?? [])
                    );
                }
            }

            Log::error('Goong Direction API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            Log::error('Goong Direction API Exception', ['message' => $e->getMessage()]);
        }

        return DirectionDTO::create(5000, 600, '', []);
    }

    /**
     * Tính quãng đường đường chim bay và ước tính quãng đường thực tế (UC-09 Fallback).
     * Rule: Road distance = Bird-fly distance * 1.3 (Winding factor).
     */
    private function getFallbackMatrix(float $originLat, float $originLng, float $destLat, float $destLng): MapMatrixDTO
    {
        $birdDistance = $this->calculateDistance($originLat, $originLng, $destLat, $destLng);
        $roadDistance = (int) ($birdDistance * 1.3);
        
        // Ước tính thời gian dựa trên vận tốc trung bình 30km/h (8.33 m/s)
        $duration = (int) ($roadDistance / 8.33);

        return MapMatrixDTO::create(
            distance: $roadDistance,
            duration: max($duration, 60) // Tối thiểu 1 phút
        );
    }

    /**
     * @inheritDoc
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (float) ($earthRadius * $c);
    }
}
