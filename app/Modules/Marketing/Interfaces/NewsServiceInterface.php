<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Marketing\DTO\CreateNewsDTO;
use App\Modules\Marketing\DTO\UpdateNewsDTO;

interface NewsServiceInterface
{
    public function getList(int $perPage = 20): ServiceReturn;
    public function getDetail(string $id): ServiceReturn;
    public function create(CreateNewsDTO $dto): ServiceReturn;
    public function update(string $id, UpdateNewsDTO $dto): ServiceReturn;
    public function delete(string $id): ServiceReturn;
}
