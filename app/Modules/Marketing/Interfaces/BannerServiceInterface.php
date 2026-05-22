<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Marketing\DTO\CreateBannerDTO;
use App\Modules\Marketing\DTO\UpdateBannerDTO;

interface BannerServiceInterface
{
    public function getList(int $perPage = 20): ServiceReturn;
    public function getDetail(string $id): ServiceReturn;
    public function create(CreateBannerDTO $dto): ServiceReturn;
    public function update(string $id, UpdateBannerDTO $dto): ServiceReturn;
    public function delete(string $id): ServiceReturn;
}
