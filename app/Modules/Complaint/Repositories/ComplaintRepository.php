<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Complaint\Interfaces\ComplaintRepositoryInterface;
use App\Modules\Complaint\Model\Complaint;
use Illuminate\Pagination\LengthAwarePaginator;

final class ComplaintRepository extends BaseRepository implements ComplaintRepositoryInterface
{
    public function getModel(): string
    {
        return Complaint::class;
    }

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->getQuery();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }

        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('content', 'like', '%' . $filters['keyword'] . '%')
                  ->orWhere('id', 'like', '%' . $filters['keyword'] . '%');
            });
        }

        return $query->with(['sender'])->latest()->paginate($perPage);
    }

    public function findWithDetails(string $id): ?Complaint
    {
        /** @var Complaint|null */
        return $this->getQuery()->with(['sender', 'complaintable', 'processor'])->find($id);
    }
}
