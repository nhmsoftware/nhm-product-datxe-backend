<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\SavedAddressRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\CustomerSavedAddress;
use Illuminate\Database\Eloquent\Collection;

class SavedAddressRepository extends BaseRepository implements SavedAddressRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getModel(): string
    {
        return CustomerSavedAddress::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getByCustomer(CustomerProfile $customerProfile): Collection
    {
        return $this->model->where('customer_id', $customerProfile->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function countByCustomer(CustomerProfile $customerProfile): int
    {
        return $this->model->where('customer_id', $customerProfile->id)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function findDuplicate(CustomerProfile $customerProfile, float $lat, float $lng, ?int $excludeId = null): ?CustomerSavedAddress
    {
        // Bán kính khoảng 50 mét
        $radius = 0.0005;

        $query = $this->model->where('customer_id', $customerProfile->id)
            ->whereBetween('lat', [$lat - $radius, $lat + $radius])
            ->whereBetween('lng', [$lng - $radius, $lng + $radius]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * {@inheritdoc}
     */
    public function unsetDefaults(CustomerProfile $customerProfile, ?int $excludeId = null): void
    {
        $query = $this->model->where('customer_id', $customerProfile->id)
            ->where('is_default', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->update(['is_default' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function findFirstByCustomer(CustomerProfile $customerProfile): ?CustomerSavedAddress
    {
        return $this->model->where('customer_id', $customerProfile->id)->first();
    }
}
