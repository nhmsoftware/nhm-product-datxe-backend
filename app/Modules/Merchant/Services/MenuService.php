<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\CreateMenuItemDTO;
use App\Modules\Merchant\DTO\GetMenuDTO;
use App\Modules\Merchant\Events\MenuItemCreated;
use App\Modules\Merchant\Interfaces\MenuRepositoryInterface;
use App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface;
use App\Modules\Merchant\Interfaces\MenuServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class MenuService extends BaseService implements MenuServiceInterface
{
    public function __construct(
        private readonly MenuRepositoryInterface $menuRepository,
        private readonly MenuItemRepositoryInterface $menuItemRepository
    ) {}

    public function getMerchantMenu(GetMenuDTO $dto): Collection
    {
        return $this->menuRepository->getFullMenu($dto->merchantProfileId);
    }

    public function getMerchantCategories(\App\Modules\Merchant\DTO\GetMenuCategoriesDTO $dto): Collection
    {
        return $this->menuRepository->getCategories($dto->merchantProfileId);
    }

    public function createMenuItem(CreateMenuItemDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Resolve Category (A5)
            $category = $this->resolveCategory($dto);

            // Check duplicate name in category
            if ($this->menuItemRepository->isNameExistsInCategory($dto->merchantProfileId, (string)$category->id, $dto->name)) {
                $this->throw('Tên món ăn đã tồn tại trong danh mục này.', 422);
            }

            // 2. Handle Image Upload
            $imagePath = null;
            if ($dto->image) {
                $imagePath = $dto->image->store('merchant/menu-items', 'public');
            }

            // 3. Prepare Data
            $data = [
                'merchant_profile_id' => $dto->merchantProfileId,
                'category_id'         => $category->id,
                'name'                => $dto->name,
                'price'               => $dto->price,
                'description'         => $dto->description,
                'image_path'          => $imagePath,
                'is_available'        => true,
            ];

            // 4. Create Item with Sizes and Toppings
            $item = $this->menuItemRepository->createItem($data, $dto->sizes, $dto->toppings);

            // 5. Dispatch Event
            event(new MenuItemCreated(
                itemId: (string) $item->id,
                merchantProfileId: $dto->merchantProfileId,
                categoryId: (string) $category->id
            ));

            return $this->success($item, 'Thêm món ăn thành công.');
        }, true, 'CreateMenuItem');
    }

    public function updateMenuItem(\App\Modules\Merchant\DTO\UpdateMenuItemDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Find item and verify ownership
            /** @var \App\Modules\Merchant\Model\MenuItem $item */
            $item = $this->menuItemRepository->findItem($dto->itemId);
            if (!$item || $item->merchant_profile_id !== $dto->merchantProfileId) {
                $this->throw('Không tìm thấy món ăn.', 404);
            }

            // 2. Resolve Category
            $category = $this->resolveCategoryForUpdate($dto);

            // 3. Check duplicate name in category (A7)
            if ($this->menuItemRepository->isNameExistsInCategory($dto->merchantProfileId, (string)$category->id, $dto->name, $dto->itemId)) {
                $this->throw('Tên món ăn đã tồn tại trong danh mục này.', 422);
            }

            // 4. Handle Image Update
            $imagePath = $item->image_path;
            if ($dto->image) {
                // Delete old image if exists
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $dto->image->store('merchant/menu-items', 'public');
            }

            // 5. Prepare Data
            $data = [
                'category_id'  => $category->id,
                'name'         => $dto->name,
                'price'        => $dto->price,
                'description'  => $dto->description,
                'image_path'   => $imagePath,
            ];

            // 6. Update Item with Sizes and Toppings
            $item = $this->menuItemRepository->updateItem($dto->itemId, $data, $dto->sizes, $dto->toppings);

            // 7. Dispatch Event
            event(new \App\Modules\Merchant\Events\MenuItemUpdated(
                itemId: (string) $item->id,
                merchantProfileId: $dto->merchantProfileId,
                categoryId: (string) $category->id
            ));

            return $this->success($item, 'Cập nhật món ăn thành công.');
        }, true, 'UpdateMenuItem');
    }

    public function deleteMenuItem(\App\Modules\Merchant\DTO\DeleteMenuItemDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Find item and verify ownership
            /** @var \App\Modules\Merchant\Model\MenuItem $item */
            $item = $this->menuItemRepository->findItem($dto->itemId);
            if (!$item || $item->merchant_profile_id !== $dto->merchantProfileId) {
                $this->throw('Không tìm thấy món ăn.', 404);
            }

            // 2. Check if item is in active orders (A3 - Placeholder)
            // if ($this->hasActiveOrders($dto->itemId)) {
            //     $this->throw('Món đang có trong đơn hàng. Không thể xóa, chỉ có thể tạm ngừng bán.', 400);
            // }

            // 3. Delete Item
            $this->menuItemRepository->deleteItem($dto->itemId);

            // 4. Dispatch Event
            event(new \App\Modules\Merchant\Events\MenuItemDeleted(
                itemId: $dto->itemId,
                merchantProfileId: $dto->merchantProfileId
            ));

            return $this->success(null, 'Xóa món ăn thành công.');
        }, true, 'DeleteMenuItem');
    }

    private function resolveCategory(CreateMenuItemDTO $dto): \App\Modules\Merchant\Model\MenuCategory
    {
        if ($dto->categoryId) {
            $category = $this->menuRepository->find($dto->categoryId);
            if ($category) {
                return $category;
            }
        }

        return $this->menuRepository->findOrCreateCategory($dto->merchantProfileId, $dto->categoryName);
    }

    private function resolveCategoryForUpdate(\App\Modules\Merchant\DTO\UpdateMenuItemDTO $dto): \App\Modules\Merchant\Model\MenuCategory
    {
        if ($dto->categoryId) {
            $category = $this->menuRepository->find($dto->categoryId);
            if ($category) {
                return $category;
            }
        }

        return $this->menuRepository->findOrCreateCategory($dto->merchantProfileId, $dto->categoryName);
    }

    public function updateMenuItemStatus(string $itemId, string $merchantProfileId, bool $isAvailable): ServiceReturn
    {
        return $this->execute(function () use ($itemId, $merchantProfileId, $isAvailable) {
            // 1. Find item and verify ownership
            $item = $this->menuItemRepository->findItem($itemId);
            if (!$item || $item->merchant_profile_id !== $merchantProfileId) {
                $this->throw('Không tìm thấy món ăn.', 404);
            }

            // 2. Update Status
            $this->menuItemRepository->updateItemStatus($itemId, $isAvailable);

            // 3. Dispatch Event
            event(new \App\Modules\Merchant\Events\MenuItemUpdated(
                itemId: $itemId,
                merchantProfileId: $merchantProfileId,
                categoryId: (string) $item->category_id
            ));

            return $this->success(null, 'Cập nhật trạng thái thành công.');
        }, true, 'UpdateMenuItemStatus');
    }
}
