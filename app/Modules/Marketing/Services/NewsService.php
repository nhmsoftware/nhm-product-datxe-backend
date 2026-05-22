<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Marketing\DTO\CreateNewsDTO;
use App\Modules\Marketing\DTO\UpdateNewsDTO;
use App\Modules\Marketing\Interfaces\NewsRepositoryInterface;
use App\Modules\Marketing\Interfaces\NewsServiceInterface;
use Illuminate\Support\Facades\Storage;

class NewsService extends BaseService implements NewsServiceInterface
{
    public function __construct(
        protected NewsRepositoryInterface $newsRepository
    ) {}

    public function getList(int $perPage = 20): ServiceReturn
    {
        return $this->execute(function () use ($perPage) {
            $news = $this->newsRepository->getModel()::orderBy('order', 'asc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            return [
                'data' => $news->items(),
                'meta' => [
                    'current_page' => $news->currentPage(),
                    'last_page' => $news->lastPage(),
                    'per_page' => $news->perPage(),
                    'total' => $news->total(),
                ]
            ];
        });
    }

    public function getDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $news = $this->newsRepository->find($id);
            $this->validate($news !== null, 'Không tìm thấy Tin tức.', 404);
            return $news->toArray();
        });
    }

    public function create(CreateNewsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $path = $dto->image->store('news', 'public');
            $imageUrl = Storage::disk('public')->url($path);

            $news = $this->newsRepository->create([
                'title' => $dto->title,
                'description' => $dto->description,
                'content' => $dto->content,
                'image_url' => $imageUrl,
                'tag' => $dto->tag,
                'order' => $dto->order,
                'status' => $dto->status,
            ]);

            return $news->toArray();
        }, useTransaction: true);
    }

    public function update(string $id, UpdateNewsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($id, $dto) {
            $news = $this->newsRepository->find($id);
            $this->validate($news !== null, 'Không tìm thấy Tin tức.', 404);

            $data = [];
            if ($dto->title !== null) $data['title'] = $dto->title;
            if ($dto->description !== null) $data['description'] = $dto->description;
            if ($dto->content !== null) $data['content'] = $dto->content;
            if ($dto->tag !== null) $data['tag'] = $dto->tag;
            if ($dto->order !== null) $data['order'] = $dto->order;
            if ($dto->status !== null) $data['status'] = $dto->status;

            if ($dto->image !== null) {
                $path = $dto->image->store('news', 'public');
                $data['image_url'] = Storage::disk('public')->url($path);
            }

            $this->newsRepository->updateById($id, $data);

            return $this->newsRepository->find($id)->toArray();
        }, useTransaction: true);
    }

    public function delete(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $news = $this->newsRepository->find($id);
            $this->validate($news !== null, 'Không tìm thấy Tin tức.', 404);

            $this->newsRepository->deleteById($id);

            return ['id' => $id];
        }, useTransaction: true);
    }
}
