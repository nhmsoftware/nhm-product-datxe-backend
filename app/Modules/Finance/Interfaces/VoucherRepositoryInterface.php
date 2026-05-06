<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use Illuminate\Support\Collection;

use App\Core\Interfaces\BaseRepositoryInterface;

/**
 * Interface cho VoucherRepository.
 */
interface VoucherRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm tất cả voucher đang hoạt động và chưa hết hạn.
     * 
     * @return Collection
     */
    public function findAllActive(): Collection;

    /**
     * Tìm voucher theo mã.
     * 
     * @param string $code
     * @return mixed
     */
    public function findByCode(string $code): mixed;

    /**
     * Tìm kiếm voucher cho Admin với phân trang.
     * 
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
