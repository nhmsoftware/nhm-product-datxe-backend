<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Marketing\DTO\CreateBannerDTO;
use App\Modules\Marketing\DTO\UpdateBannerDTO;
use App\Modules\Marketing\Interfaces\BannerRepositoryInterface;
use App\Modules\Marketing\Interfaces\BannerServiceInterface;
use Illuminate\Support\Facades\Storage;

class BannerService extends BaseService implements BannerServiceInterface
{
    public function __construct(
        protected BannerRepositoryInterface $bannerRepository
    ) {}

    public function getList(int $perPage = 20): ServiceReturn
    {
        return $this->execute(function () use ($perPage) {
            $banners = $this->bannerRepository->getModel()::orderBy('order', 'asc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            return [
                'data' => $banners->items(),
                'meta' => [
                    'current_page' => $banners->currentPage(),
                    'last_page' => $banners->lastPage(),
                    'per_page' => $banners->perPage(),
                    'total' => $banners->total(),
                ]
            ];
        });
    }

    public function getDetail(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $banner = $this->bannerRepository->find($id);
            $this->validate($banner !== null, 'Không tìm thấy Banner.', 404);
            return $banner->toArray();
        });
    }

    public function create(CreateBannerDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $path = $dto->image->store('banners', 'public');
            $imageUrl = Storage::disk('public')->url($path);

            $banner = $this->bannerRepository->create([
                'title' => $dto->title,
                'description' => $dto->description,
                'image_url' => $imageUrl,
                'action_url' => $dto->action_url,
                'order' => $dto->order,
                'status' => $dto->status,
            ]);

            return $banner->toArray();
        }, useTransaction: true);
    }

    public function update(string $id, UpdateBannerDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($id, $dto) {
            $banner = $this->bannerRepository->find($id);
            $this->validate($banner !== null, 'Không tìm thấy Banner.', 404);

            $data = [];
            if ($dto->title !== null) $data['title'] = $dto->title;
            if ($dto->description !== null) $data['description'] = $dto->description;
            if ($dto->action_url !== null) $data['action_url'] = $dto->action_url;
            if ($dto->order !== null) $data['order'] = $dto->order;
            if ($dto->status !== null) $data['status'] = $dto->status;

            if ($dto->image !== null) {
                $path = $dto->image->store('banners', 'public');
                $data['image_url'] = Storage::disk('public')->url($path);
                
                // Note: Can delete old image if needed, but not strictly required for this simple CRUD
            }

            $this->bannerRepository->updateById($id, $data);

            return $this->bannerRepository->find($id)->toArray();
        }, useTransaction: true);
    }

    public function delete(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $banner = $this->bannerRepository->find($id);
            $this->validate($banner !== null, 'Không tìm thấy Banner.', 404);

            $this->bannerRepository->deleteById($id);

            return ['id' => $id];
        }, useTransaction: true);
    }
}
