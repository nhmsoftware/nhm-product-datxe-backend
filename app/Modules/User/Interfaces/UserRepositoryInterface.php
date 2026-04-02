<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\User;
interface UserRepositoryInterface
{
    public function findByPhone(string $phone): ?User;
    public function existsByPhone(string $phone): bool;
    public function createCustomerProfile(int $userId, array $data): CustomerProfile;
    public function upsertDevice(int $userId, array $data): void;
    public function findByGoogleId(string $googleId): ?User;
    public function findByAppleId(string $appleId): ?User;
    public function findByEmail(?string $email): ?User;
}
