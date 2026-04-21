<?php

declare(strict_types=1);

namespace App\Modules\Communication\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Communication\Interfaces\ChatMessageRepositoryInterface;
use App\Modules\Communication\Model\ChatMessage;
use Illuminate\Support\Collection;

final class ChatMessageRepository extends BaseRepository implements ChatMessageRepositoryInterface
{
    public function getModel(): string
    {
        return ChatMessage::class;
    }

    public function getByRideId(string $rideId): Collection
    {
        return $this->model
            ->where('ride_id', $rideId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function saveMessage(array $data): ChatMessage
    {
        return $this->model->create($data);
    }
}
