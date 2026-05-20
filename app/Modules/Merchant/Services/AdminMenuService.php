<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\AdminCreateMenuItemDTO;
use App\Modules\Merchant\DTO\AdminUpdateMenuItemDTO;
use App\Modules\Merchant\Interfaces\AdminMenuServiceInterface;
use App\Modules\Merchant\Interfaces\MenuRepositoryInterface;
use App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface;
use App\Modules\Merchant\Interfaces\MerchantMenuEditLogRepositoryInterface;
use App\Modules\Merchant\Model\MenuCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

final class AdminMenuService extends BaseService implements AdminMenuServiceInterface
{
    public function __construct(
        private readonly MenuRepositoryInterface                   $menuRepository,
        private readonly MenuItemRepositoryInterface               $menuItemRepository,
        private readonly MerchantMenuEditLogRepositoryInterface    $editLogRepository,
    ) {}

    public function getMerchantMenu(string $merchantProfileId): Collection
    {
        return $this->menuRepository->getFullMenu($merchantProfileId);
    }

    public function createMenuItem(AdminCreateMenuItemDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $category = $this->resolveCategory($dto->merchantProfileId, $dto->categoryId, $dto->categoryName);

            if ($this->menuItemRepository->isNameExistsInCategory($dto->merchantProfileId, (string)$category->id, $dto->name)) {
                $this->throw('Tên món ăn đã tồn tại trong danh mục này.', 422);
            }

            // Handle Image Upload
            $imagePath = null;
            if ($dto->image) {
                $imagePath = $dto->image->store('merchant/menu-items', 'public');
            }

            $data = [
                'merchant_profile_id' => $dto->merchantProfileId,
                'category_id'         => $category->id,
                'name'                => $dto->name,
                'price'               => $dto->price,
                'description'         => $dto->description,
                'image_path'          => $imagePath,
                'is_available'        => true,
            ];

            $item = $this->menuItemRepository->createItem($data, $dto->sizes, $dto->toppings);

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $dto->merchantProfileId,
                'actor_id'            => $dto->actorId,
                'action'              => 'create_item',
                'description'         => "Admin đã thêm món ăn mới: '{$item->name}' vào danh mục '{$category->name}'",
                'new_values'          => [
                    'name'        => $item->name,
                    'price'       => $item->price,
                    'category'    => $category->name,
                    'sizes'       => $dto->sizes,
                    'toppings'    => $dto->toppings,
                    'description' => $item->description,
                ]
            ]);

            return $this->success($item, 'Thêm món ăn thành công.');
        }, useTransaction: true);
    }

    public function updateMenuItem(AdminUpdateMenuItemDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $item = $this->menuItemRepository->findItem($dto->itemId);
            if (!$item || $item->merchant_profile_id !== $dto->merchantProfileId) {
                $this->throw('Không tìm thấy món ăn.', 404);
            }

            $category = $this->resolveCategory($dto->merchantProfileId, $dto->categoryId, $dto->categoryName);

            if ($this->menuItemRepository->isNameExistsInCategory($dto->merchantProfileId, (string)$category->id, $dto->name, $dto->itemId)) {
                $this->throw('Tên món ăn đã tồn tại trong danh mục này.', 422);
            }

            // Capture old state for audit
            $item->load(['sizes', 'toppings', 'category']);
            $oldValues = [
                'name'        => $item->name,
                'price'       => $item->price,
                'category'    => $item->category?->name ?? 'Chưa phân loại',
                'sizes'       => $item->sizes->toArray(),
                'toppings'    => $item->toppings->toArray(),
                'description' => $item->description,
            ];

            $imagePath = $item->image_path;
            if ($dto->image) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $dto->image->store('merchant/menu-items', 'public');
            }

            $data = [
                'category_id'  => $category->id,
                'name'         => $dto->name,
                'price'        => $dto->price,
                'description'  => $dto->description,
                'image_path'   => $imagePath,
            ];

            $updatedItem = $this->menuItemRepository->updateItem($dto->itemId, $data, $dto->sizes, $dto->toppings);

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $dto->merchantProfileId,
                'actor_id'            => $dto->actorId,
                'action'              => 'update_item',
                'description'         => "Admin đã cập nhật thông tin món ăn: '{$updatedItem->name}'",
                'old_values'          => $oldValues,
                'new_values'          => [
                    'name'        => $updatedItem->name,
                    'price'       => $updatedItem->price,
                    'category'    => $category->name,
                    'sizes'       => $dto->sizes,
                    'toppings'    => $dto->toppings,
                    'description' => $updatedItem->description,
                ]
            ]);

            return $this->success($updatedItem, 'Cập nhật món ăn thành công.');
        }, useTransaction: true);
    }

    public function deleteMenuItem(string $itemId, string $merchantProfileId, string $actorId): ServiceReturn
    {
        return $this->execute(function () use ($itemId, $merchantProfileId, $actorId) {
            $item = $this->menuItemRepository->findItem($itemId);
            if (!$item || $item->merchant_profile_id !== $merchantProfileId) {
                $this->throw('Không tìm thấy món ăn.', 404);
            }

            $this->menuItemRepository->deleteItem($itemId);

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $merchantProfileId,
                'actor_id'            => $actorId,
                'action'              => 'delete_item',
                'description'         => "Admin đã xóa món ăn: '{$item->name}'",
                'old_values'          => [
                    'name'        => $item->name,
                    'price'       => $item->price,
                    'description' => $item->description,
                ]
            ]);

            return $this->success(null, 'Xóa món ăn thành công.');
        }, useTransaction: true);
    }

    public function updateMenuItemStatus(string $itemId, string $merchantProfileId, bool $isAvailable, string $actorId): ServiceReturn
    {
        return $this->execute(function () use ($itemId, $merchantProfileId, $isAvailable, $actorId) {
            $item = $this->menuItemRepository->findItem($itemId);
            if (!$item || $item->merchant_profile_id !== $merchantProfileId) {
                $this->throw('Không tìm thấy món ăn.', 404);
            }

            $this->menuItemRepository->updateItemStatus($itemId, $isAvailable);
            $statusLabel = $isAvailable ? 'Còn bán' : 'Tạm ngưng bán';

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $merchantProfileId,
                'actor_id'            => $actorId,
                'action'              => 'update_status',
                'description'         => "Admin đã cập nhật trạng thái bán của món '{$item->name}' thành: '{$statusLabel}'",
                'old_values'          => ['is_available' => $item->is_available],
                'new_values'          => ['is_available' => $isAvailable]
            ]);

            return $this->success(null, 'Cập nhật trạng thái món ăn thành công.');
        }, useTransaction: true);
    }

    public function getEditLogs(string $merchantProfileId): ServiceReturn
    {
        return $this->execute(function () use ($merchantProfileId) {
            return $this->editLogRepository->getLogsByMerchant($merchantProfileId);
        });
    }

    public function exportTemplate(): string
    {
        $headers = [
            'Tên Món Ăn',
            'Danh Mục',
            'Giá Bán',
            'Mô Tả',
            'Kích Thước (Tên:Giá;Tên:Giá)',
            'Topping (Tên:Giá;Tên:Giá)'
        ];

        $exampleRow1 = [
            'Cơm tấm sườn bì chả',
            'Món chính',
            '45000',
            'Cơm tấm sườn nướng mật ong thơm ngon',
            'Lớn:10000;Đặc biệt:15000',
            'Thêm sườn:15000;Trứng ốp la:5000'
        ];

        $exampleRow2 = [
            'Trà sữa trân châu',
            'Đồ uống',
            '30000',
            'Trà sữa truyền thống trân châu đen',
            'Lớn:5000',
            'Trân châu đen:5000;Thạch dừa:5000'
        ];

        $output = fopen('php://temp', 'r+');
        
        // Add UTF-8 BOM
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, $headers);
        fputcsv($output, $exampleRow1);
        fputcsv($output, $exampleRow2);
        
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    public function importMenu(string $merchantProfileId, UploadedFile $file, string $actorId): ServiceReturn
    {
        return $this->execute(function () use ($merchantProfileId, $file, $actorId) {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                $this->throw('Không thể đọc file upload.', 400);
            }

            // Remove UTF-8 BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Read headers
            $headers = fgetcsv($handle);
            if (!$headers || count($headers) < 3) {
                fclose($handle);
                $this->throw('File mẫu không đúng định dạng. Cần ít nhất 3 cột (Tên Món Ăn, Danh Mục, Giá Bán).', 400);
            }

            $importCount = 0;
            $rowNum = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $name = trim($row[0] ?? '');
                $categoryName = trim($row[1] ?? '');
                $priceVal = trim($row[2] ?? '');
                $description = trim($row[3] ?? '');
                $sizesRaw = trim($row[4] ?? '');
                $toppingsRaw = trim($row[5] ?? '');

                if ($name === '' || $categoryName === '' || $priceVal === '') {
                    continue; // Skip invalid rows
                }

                $price = (float) $priceVal;
                if ($price < 0) {
                    $price = 0.0;
                }

                // 1. Resolve Category
                $category = $this->resolveCategory($merchantProfileId, null, $categoryName);

                // 2. Check if item already exists in this category
                // If it does, we delete the old one or skip. Let's delete the old one to overwrite, or just create as duplicate. Overwrite name in same category is clean!
                $existingItem = \App\Modules\Merchant\Model\MenuItem::where('merchant_profile_id', $merchantProfileId)
                    ->where('category_id', $category->id)
                    ->where('name', $name)
                    ->first();

                if ($existingItem) {
                    $this->menuItemRepository->deleteItem((string)$existingItem->id);
                }

                // 3. Parse Sizes
                $sizes = [];
                if ($sizesRaw !== '') {
                    $parts = explode(';', $sizesRaw);
                    foreach ($parts as $part) {
                        $subParts = explode(':', $part);
                        if (count($subParts) >= 2) {
                            $sizes[] = [
                                'name'       => trim($subParts[0]),
                                'price'      => (float)trim($subParts[1]),
                                'is_default' => false,
                            ];
                        }
                    }
                }

                // 4. Parse Toppings
                $toppings = [];
                if ($toppingsRaw !== '') {
                    $parts = explode(';', $toppingsRaw);
                    foreach ($parts as $part) {
                        $subParts = explode(':', $part);
                        if (count($subParts) >= 2) {
                            $toppings[] = [
                                'name'         => trim($subParts[0]),
                                'price'        => (float)trim($subParts[1]),
                                'max_quantity' => 1,
                                'is_required'  => false,
                            ];
                        }
                    }
                }

                // 5. Create Item
                $itemData = [
                    'merchant_profile_id' => $merchantProfileId,
                    'category_id'         => $category->id,
                    'name'                => $name,
                    'price'               => $price,
                    'description'         => $description,
                    'is_available'        => true,
                ];

                $this->menuItemRepository->createItem($itemData, $sizes, $toppings);
                $importCount++;
            }

            fclose($handle);

            if ($importCount === 0) {
                $this->throw('Không tìm thấy dòng dữ liệu nào hợp lệ để nhập.', 400);
            }

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $merchantProfileId,
                'actor_id'            => $actorId,
                'action'              => 'import_excel',
                'description'         => "Admin đã nhập thực đơn từ file Excel (Thêm/Cập nhật thành công {$importCount} món ăn)",
                'new_values'          => [
                    'imported_count' => $importCount
                ]
            ]);

            return $this->success(['imported_count' => $importCount], "Đã nhập thành công {$importCount} món ăn vào thực đơn.");
        }, useTransaction: true);
    }

    private function resolveCategory(string $merchantProfileId, ?string $categoryId, string $categoryName): MenuCategory
    {
        if ($categoryId) {
            $category = $this->menuRepository->find($categoryId);
            if ($category) {
                return $category;
            }
        }

        return $this->menuRepository->findOrCreateCategory($merchantProfileId, $categoryName);
    }
}
