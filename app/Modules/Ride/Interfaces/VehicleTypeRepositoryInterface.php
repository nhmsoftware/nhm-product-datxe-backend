<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Ride\Model\VehicleTypeRef;
use Illuminate\Support\Collection;

interface VehicleTypeRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveVehicleTypes(): Collection;

    public function findByCode(string $code): ?VehicleTypeRef;

    public function getAllVehicleTypes(): Collection;

    public function getBookableVehicleTypesForService(?string $serviceType = null): Collection;
}
