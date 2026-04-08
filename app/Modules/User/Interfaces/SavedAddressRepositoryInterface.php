<?php
declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\CustomerSavedAddress;
use Illuminate\Database\Eloquent\Collection;

interface SavedAddressRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách địa chỉ đã lưu của khách hàng.
     */
    public function getByCustomer(CustomerProfile $customerProfile): Collection;

    /**
     * Đếm số lượng địa chỉ đã lưu của khách hàng.
     */
    public function countByCustomer(CustomerProfile $customerProfile): int;

    /**
     * Tìm địa chỉ bị trùng lặp dựa trên tọa độ.
     */
    public function findDuplicate(CustomerProfile $customerProfile, float $lat, float $lng, ?int $excludeId = null): ?CustomerSavedAddress;

    /**
     * Bỏ đánh dấu mặc định cho các địa chỉ khác.
     */
    public function unsetDefaults(CustomerProfile $customerProfile, ?int $excludeId = null): void;

    /**
     * Tìm địa chỉ đầu tiên của khách hàng (để set default mới).
     */
    public function findFirstByCustomer(CustomerProfile $customerProfile): ?CustomerSavedAddress;
}
