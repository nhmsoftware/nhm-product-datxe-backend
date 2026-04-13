<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Modules\Ride\DTO\MapMatrixDTO;
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
        if (empty($this->apiKey)) {
            Log::warning('Goong API Key is missing. Returning mocked distance.');
            return MapMatrixDTO::create(
                distance: 5000, // 5km fallback
                duration: 600   // 10 mins fallback
            );
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

            Log::error('Goong API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'origin' => "{$originLat},{$originLng}",
                'dest' => "{$destLat},{$destLng}"
            ]);
        } catch (\Exception $e) {
            Log::error('Goong API Exception', [
                'message' => $e->getMessage(),
                'origin' => "{$originLat},{$originLng}",
                'dest' => "{$destLat},{$destLng}"
            ]);
        }

        // Fallback values if API fails
        return MapMatrixDTO::create(
            distance: 5000,
            duration: 600
        );
    }
}
