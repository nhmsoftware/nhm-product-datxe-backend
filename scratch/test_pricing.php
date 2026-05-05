<?php

use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\Model\Enums\VehicleType;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pricingService = app(PricingServiceInterface::class);

$dto = PricingRequestDTO::create(
    distance: 13000.0, // 13,000 km
    duration: 26000.0, // 26,000 minutes
    vehicleType: VehicleType::BIKE->value,
    surgeMultiplier: 1.0
);

$result = $pricingService->calculatePrice($dto);

if ($result->isError()) {
    echo "Error: " . $result->getMessage() . "\n";
    if ($result->getException()) {
        echo "Exception: " . $result->getException()->getMessage() . "\n";
        echo "Trace: " . $result->getException()->getTraceAsString() . "\n";
    }
} else {
    echo "Success: " . json_encode($result->getData()->toArray(), JSON_PRETTY_PRINT) . "\n";
}
