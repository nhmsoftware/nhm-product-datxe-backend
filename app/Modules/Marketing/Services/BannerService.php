<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Services;

use App\Core\Helpers\FileHelper;
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
            // Lưu vào private disk — không dùng public disk
            $path = FileHelper::uploadToPrivate($dto->image, 'banners');

            $banner = $this->bannerRepository->create([
                'title'       => $dto->title,
                'description' => $dto->description,
                'label'       => $dto->label,
                'tag'         => $dto->tag,
                'image_url'   => $path, // Lưu path, URL sinh động qua FileHelper::serveUrl()
                'action_url'  => $dto->action_url,
                'order'       => $dto->order,
                'status'      => $dto->status,
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
            if ($dto->label !== null) $data['label'] = $dto->label;
            if ($dto->tag !== null) $data['tag'] = $dto->tag;
            if ($dto->action_url !== null) $data['action_url'] = $dto->action_url;
            if ($dto->order !== null) $data['order'] = $dto->order;
            if ($dto->status !== null) $data['status'] = $dto->status;

            if ($dto->image !== null) {
                // Xóa file cũ nếu có
                if ($banner->image_url && !filter_var($banner->image_url, FILTER_VALIDATE_URL)) {
                    FileHelper::deleteFromPrivate($banner->image_url);
                }
                $data['image_url'] = FileHelper::uploadToPrivate($dto->image, 'banners');
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
