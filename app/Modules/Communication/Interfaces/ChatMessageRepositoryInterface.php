<?php

declare(strict_types=1);

namespace App\Modules\Communication\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Communication\Model\ChatMessage;
use Illuminate\Support\Collection;

interface ChatMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy lịch sử chat bộ của một chuyến xe.
     * @return Collection<int, ChatMessage>
     */
    public function getByRideId(string $rideId): Collection;

    /**
     * Lưu tin nhắn mới.
     */
    public function saveMessage(array $data): ChatMessage;
}
