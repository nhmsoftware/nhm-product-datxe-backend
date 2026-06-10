<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface;
use App\Modules\Ride\Model\VehicleTypeRef;
use Illuminate\Support\Collection;

final class VehicleTypeRepository extends BaseRepository implements VehicleTypeRepositoryInterface
{
    public function getModel(): string
    {
        return VehicleTypeRef::class;
    }

    public function getAllVehicleTypes(): Collection
    {
        return $this->getQuery()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getActiveVehicleTypes(): Collection
    {
        return $this->getQuery()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function findByCode(string $code): ?VehicleTypeRef
    {
        /** @var VehicleTypeRef|null */
        return $this->getQuery()->where('code', $code)->first();
    }

    public function findByName(string $name): ?VehicleTypeRef
    {
        /** @var VehicleTypeRef|null */
        return $this->getQuery()->whereRaw('LOWER(name_vi) = ?', [mb_strtolower($name)])->first();
    }

    public function getBookableVehicleTypesForService(?string $serviceType = null): Collection
    {
        $types = $this->getQuery()
            ->where('is_active', true)
            ->where('is_bookable', true)
            ->orderBy('sort_order')
            ->get();

        if ($serviceType === null) {
            return $types;
        }

        return $types->filter(function (VehicleTypeRef $type) use ($serviceType): bool {
            $scopes = $type->service_scopes;
            if (!is_array($scopes) || $scopes === []) {
                return true;
            }

            return in_array($serviceType, $scopes, true);
        })->values();
    }
}
