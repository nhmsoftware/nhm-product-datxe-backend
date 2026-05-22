<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Helpers\FileHelper;
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
        private readonly MenuRepositoryInterface                $menuRepository,
        private readonly MenuItemRepositoryInterface            $menuItemRepository,
        private readonly MerchantMenuEditLogRepositoryInterface $editLogRepository,
    )
    {
    }

    public function getMerchantMenu(string $merchantProfileId): Collection
    {
        return $this->menuRepository->getFullMenu($merchantProfileId);
    }

    public function getMerchantCategories(string $merchantProfileId): Collection
    {
        return $this->menuRepository->getCategories($merchantProfileId);
    }

    public function createMenuItem(AdminCreateMenuItemDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $category = $this->resolveCategory($dto->merchantProfileId, $dto->categoryId, $dto->categoryName);

            if ($this->menuItemRepository->isNameExistsInCategory($dto->merchantProfileId, (string)$category->id, $dto->name)) {
                $this->throw('Tên món ăn đã tồn tại trong danh mục này.', 422);
            }

            // Handle Image Upload — lưu vào private disk
            $imagePath = null;
            if ($dto->image) {
                $imagePath = FileHelper::uploadToPrivate($dto->image, 'merchant/menu-items');
            }

            $data = [
                'merchant_profile_id' => $dto->merchantProfileId,
                'category_id' => $category->id,
                'name' => $dto->name,
                'price' => $dto->price,
                'description' => $dto->description,
                'image_path' => $imagePath,
                'is_available' => true,
            ];

            $item = $this->menuItemRepository->createItem($data, $dto->sizes, $dto->toppings);

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $dto->merchantProfileId,
                'actor_id' => $dto->actorId,
                'action' => 'create_item',
                'description' => "Admin đã thêm món ăn mới: '{$item->name}' vào danh mục '{$category->name}'",
                'new_values' => [
                    'name' => $item->name,
                    'price' => $item->price,
                    'category' => $category->name,
                    'sizes' => $dto->sizes,
                    'toppings' => $dto->toppings,
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
                'name' => $item->name,
                'price' => $item->price,
                'category' => $item->category?->name ?? 'Chưa phân loại',
                'sizes' => $item->sizes->toArray(),
                'toppings' => $item->toppings->toArray(),
                'description' => $item->description,
            ];

            $imagePath = $item->image_path;
            if ($dto->image) {
                if ($imagePath) {
                    FileHelper::deleteFromPrivate($imagePath);
                }
                $imagePath = FileHelper::uploadToPrivate($dto->image, 'merchant/menu-items');
            }

            $data = [
                'category_id' => $category->id,
                'name' => $dto->name,
                'price' => $dto->price,
                'description' => $dto->description,
                'image_path' => $imagePath,
            ];

            $updatedItem = $this->menuItemRepository->updateItem($dto->itemId, $data, $dto->sizes, $dto->toppings);

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $dto->merchantProfileId,
                'actor_id' => $dto->actorId,
                'action' => 'update_item',
                'description' => "Admin đã cập nhật thông tin món ăn: '{$updatedItem->name}'",
                'old_values' => $oldValues,
                'new_values' => [
                    'name' => $updatedItem->name,
                    'price' => $updatedItem->price,
                    'category' => $category->name,
                    'sizes' => $dto->sizes,
                    'toppings' => $dto->toppings,
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
                'actor_id' => $actorId,
                'action' => 'delete_item',
                'description' => "Admin đã xóa món ăn: '{$item->name}'",
                'old_values' => [
                    'name' => $item->name,
                    'price' => $item->price,
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
                'actor_id' => $actorId,
                'action' => 'update_status',
                'description' => "Admin đã cập nhật trạng thái bán của món '{$item->name}' thành: '{$statusLabel}'",
                'old_values' => ['is_available' => $item->is_available],
                'new_values' => ['is_available' => $isAvailable]
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
        if (!class_exists('ZipArchive')) {
            $this->throw('Hệ thống thiếu thư viện ZipArchive để xuất file Excel.', 500);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->throw('Không thể tạo file tạm Excel.', 500);
        }

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Menu Template" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
  <borders count="1"><border><left/><right/><top/><bottom/></border></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>';

        $sharedStrings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="16" uniqueCount="16">
  <si><t>Tên Món Ăn</t></si>
  <si><t>Danh Mục</t></si>
  <si><t>Giá Bán</t></si>
  <si><t>Mô Tả</t></si>
  <si><t>Kích Thước (Tên:Giá;Tên:Giá)</t></si>
  <si><t>Topping (Tên:Giá;Tên:Giá)</t></si>
  <si><t>Cơm tấm sườn bì chả</t></si>
  <si><t>Món chính</t></si>
  <si><t>Cơm tấm sườn nướng mật ong thơm ngon</t></si>
  <si><t>Lớn:10000;Đặc biệt:15000</t></si>
  <si><t>Thêm sườn:15000;Trứng ốp la:5000</t></si>
  <si><t>Trà sữa trân châu</t></si>
  <si><t>Đồ uống</t></si>
  <si><t>Trà sữa truyền thống trân châu đen</t></si>
  <si><t>Lớn:5000</t></si>
  <si><t>Trân châu đen:5000;Thạch dừa:5000</t></si>
</sst>';

        $sheet1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="s"><v>0</v></c>
      <c r="B1" t="s"><v>1</v></c>
      <c r="C1" t="s"><v>2</v></c>
      <c r="D1" t="s"><v>3</v></c>
      <c r="E1" t="s"><v>4</v></c>
      <c r="F1" t="s"><v>5</v></c>
    </row>
    <row r="2">
      <c r="A2" t="s"><v>6</v></c>
      <c r="B2" t="s"><v>7</v></c>
      <c r="C2"><v>45000</v></c>
      <c r="D2" t="s"><v>8</v></c>
      <c r="E2" t="s"><v>9</v></c>
      <c r="F2" t="s"><v>10</v></c>
    </row>
    <row r="3">
      <c r="A3" t="s"><v>11</v></c>
      <c r="B3" t="s"><v>12</v></c>
      <c r="C3"><v>30000</v></c>
      <c r="D3" t="s"><v>13</v></c>
      <c r="E3" t="s"><v>14</v></c>
      <c r="F3" t="s"><v>15</v></c>
    </row>
  </sheetData>
</worksheet>';

        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStrings);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1);
        $zip->close();

        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    public function importMenu(string $merchantProfileId, UploadedFile $file, string $actorId): ServiceReturn
    {
        return $this->execute(function () use ($merchantProfileId, $file, $actorId) {
            $fileRows = $this->parseFileToArray($file);
            if (empty($fileRows) || count($fileRows) < 2) {
                $this->throw('Không tìm thấy dòng dữ liệu nào hợp lệ để nhập.', 400);
            }

            // Check headers
            $headers = array_map(function ($h) {
                return trim(str_replace(['"', "'"], '', (string)$h));
            }, $fileRows[0]);

            $requiredHeaders = ['Tên Món Ăn', 'Danh Mục', 'Giá Bán'];
            for ($i = 0; $i < 3; $i++) {
                $expected = $requiredHeaders[$i];
                $actual = $headers[$i] ?? '';
                if ($actual !== $expected) {
                    $this->throw("File mẫu không đúng định dạng. Cột thứ " . ($i + 1) . " bắt buộc phải là '{$expected}' (nhận được: '{$actual}').", 400);
                }
            }

            // Collect data rows and validate duplicates & types
            $importRows = [];
            $seenInFile = [];

            for ($idx = 1; $idx < count($fileRows); $idx++) {
                $row = $fileRows[$idx];
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $name = trim($row[0] ?? '');
                $categoryName = trim($row[1] ?? '');
                $priceVal = trim($row[2] ?? '');

                if ($name === '' && $categoryName === '' && $priceVal === '') {
                    continue;
                }

                if ($name === '') {
                    $this->throw("Dòng số " . ($idx + 1) . " thiếu 'Tên Món Ăn'.", 400);
                }
                if (mb_strlen($name) > 255) {
                    $this->throw("Dòng số " . ($idx + 1) . ": 'Tên Món Ăn' không được vượt quá 255 ký tự.", 400);
                }

                if ($categoryName === '') {
                    $this->throw("Dòng số " . ($idx + 1) . " thiếu 'Danh Mục'.", 400);
                }
                if (mb_strlen($categoryName) > 255) {
                    $this->throw("Dòng số " . ($idx + 1) . ": 'Danh Mục' không được vượt quá 255 ký tự.", 400);
                }

                if ($priceVal === '') {
                    $this->throw("Dòng số " . ($idx + 1) . " thiếu 'Giá Bán'.", 400);
                }
                if (!is_numeric($priceVal) || (float)$priceVal < 0) {
                    $this->throw("Dòng số " . ($idx + 1) . ": 'Giá Bán' phải là số không âm (lớn hơn hoặc bằng 0).", 400);
                }

                // Check duplicate within the uploaded file
                $fileKey = strtolower($categoryName . '||' . $name);
                if (isset($seenInFile[$fileKey])) {
                    $this->throw("Tên món ăn '{$name}' bị trùng lặp trong danh mục '{$categoryName}' ngay trong file import (Dòng " . ($seenInFile[$fileKey] + 1) . " và Dòng " . ($idx + 1) . ").", 400);
                }
                $seenInFile[$fileKey] = $idx;

                // Check duplicate in database
                $category = $this->menuRepository->findCategoryByName($merchantProfileId, $categoryName);

                if ($category) {
                    $existingItem = $this->menuItemRepository->findItemByName($merchantProfileId, (string)$category->id, $name);

                    if ($existingItem) {
                        $this->throw("Món ăn '{$name}' đã tồn tại trong danh mục '{$categoryName}' của cửa hàng.", 400);
                    }
                }

                // 2. Parse and Validate Sizes
                $sizes = [];
                $sizesRaw = trim($row[4] ?? '');
                if ($sizesRaw !== '') {
                    $parts = explode(';', $sizesRaw);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if ($part === '') {
                            continue;
                        }
                        $subParts = explode(':', $part);
                        if (count($subParts) < 2) {
                            $this->throw("Dòng số " . ($idx + 1) . ": Kích thước '{$part}' sai định dạng. Vui lòng nhập theo định dạng 'Tên:Giá'.", 400);
                        }
                        $sizeName = trim($subParts[0]);
                        $sizePriceVal = trim($subParts[1]);
                        if ($sizeName === '') {
                            $this->throw("Dòng số " . ($idx + 1) . ": Tên kích thước không được để trống.", 400);
                        }
                        if (mb_strlen($sizeName) > 255) {
                            $this->throw("Dòng số " . ($idx + 1) . ": Tên kích thước '{$sizeName}' không được vượt quá 255 ký tự.", 400);
                        }
                        if (!is_numeric($sizePriceVal) || (float)$sizePriceVal < 0) {
                            $this->throw("Dòng số " . ($idx + 1) . ": Giá bán của kích thước '{$sizeName}' phải là số không âm (lớn hơn hoặc bằng 0).", 400);
                        }
                        $sizes[] = [
                            'name' => $sizeName,
                            'price' => (float)$sizePriceVal,
                            'is_default' => false,
                        ];
                    }
                }

                // 3. Parse and Validate Toppings
                $toppings = [];
                $toppingsRaw = trim($row[5] ?? '');
                if ($toppingsRaw !== '') {
                    $parts = explode(';', $toppingsRaw);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if ($part === '') {
                            continue;
                        }
                        $subParts = explode(':', $part);
                        if (count($subParts) < 2) {
                            $this->throw("Dòng số " . ($idx + 1) . ": Topping '{$part}' sai định dạng. Vui lòng nhập theo định dạng 'Tên:Giá'.", 400);
                        }
                        $toppingName = trim($subParts[0]);
                        $toppingPriceVal = trim($subParts[1]);
                        if ($toppingName === '') {
                            $this->throw("Dòng số " . ($idx + 1) . ": Tên topping không được để trống.", 400);
                        }
                        if (mb_strlen($toppingName) > 255) {
                            $this->throw("Dòng số " . ($idx + 1) . ": Tên topping '{$toppingName}' không được vượt quá 255 ký tự.", 400);
                        }
                        if (!is_numeric($toppingPriceVal) || (float)$toppingPriceVal < 0) {
                            $this->throw("Dòng số " . ($idx + 1) . ": Giá bán của topping '{$toppingName}' phải là số không âm (lớn hơn hoặc bằng 0).", 400);
                        }
                        $toppings[] = [
                            'name' => $toppingName,
                            'price' => (float)$toppingPriceVal,
                            'max_quantity' => 1,
                            'is_required' => false,
                        ];
                    }
                }

                $importRows[] = [
                    'name' => $name,
                    'categoryName' => $categoryName,
                    'price' => (float)$priceVal,
                    'description' => trim($row[3] ?? ''),
                    'sizes' => $sizes,
                    'toppings' => $toppings,
                ];
            }

            if (empty($importRows)) {
                $this->throw('Không tìm thấy dòng dữ liệu nào hợp lệ để nhập.', 400);
            }

            // Now perform import
            $importCount = 0;
            foreach ($importRows as $rowData) {
                // 1. Resolve Category
                $category = $this->resolveCategory($merchantProfileId, null, $rowData['categoryName']);

                // 2. Create Item
                $itemData = [
                    'merchant_profile_id' => $merchantProfileId,
                    'category_id' => $category->id,
                    'name' => $rowData['name'],
                    'price' => $rowData['price'],
                    'description' => $rowData['description'],
                    'is_available' => true,
                ];

                $this->menuItemRepository->createItem($itemData, $rowData['sizes'], $rowData['toppings']);
                $importCount++;
            }

            // Audit Log
            $this->editLogRepository->logAction([
                'merchant_profile_id' => $merchantProfileId,
                'actor_id' => $actorId,
                'action' => 'import_excel',
                'description' => "Admin đã nhập thực đơn từ file Excel (Thêm/Cập nhật thành công {$importCount} món ăn)",
                'new_values' => [
                    'imported_count' => $importCount
                ]
            ]);

            return $this->success(['imported_count' => $importCount], "Đã nhập thành công {$importCount} món ăn vào thực đơn.");
        }, useTransaction: true);
    }

    /**
     * Parse uploaded file (CSV, TXT, XLSX, XLSE) to an array of rows.
     */
    private function parseFileToArray(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                $this->throw('Không thể đọc file upload.', 400);
            }

            // Detect delimiter (comma or semicolon)
            $firstLine = fgets($handle);
            $delimiter = ',';
            if ($firstLine !== false) {
                $commaCount = substr_count($firstLine, ',');
                $semiCount = substr_count($firstLine, ';');
                if ($semiCount > $commaCount) {
                    $delimiter = ';';
                }
            }
            rewind($handle);

            // Remove UTF-8 BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
            return $rows;
        }

        if (in_array($extension, ['xlsx', 'xlse'])) {
            return $this->parseXlsxFile($file->getRealPath());
        }

        if ($extension === 'xls') {
            $this->throw('Định dạng file .xls (Excel cũ) không được hỗ trợ. Vui lòng chuyển đổi sang .xlsx hoặc .csv.', 400);
        }

        $this->throw('Định dạng file không được hỗ trợ.', 400);
    }

    /**
     * Parse XLSX file structure using standard ZipArchive and SimpleXMLElement.
     */
    private function parseXlsxFile(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            $this->throw('Hệ thống thiếu thư viện ZipArchive để đọc file Excel.', 500);
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            $this->throw('Không thể mở file Excel. Tệp tin có thể bị hỏng.', 400);
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsEntry = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsEntry !== false) {
            try {
                $xml = simplexml_load_string($sharedStringsEntry);
                if ($xml && isset($xml->si)) {
                    foreach ($xml->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string)$si->t;
                        } elseif (isset($si->r)) {
                            $text = '';
                            foreach ($si->r as $r) {
                                $text .= (string)$r->t;
                            }
                            $sharedStrings[] = $text;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore XML parsing errors for shared strings
            }
        }

        // 2. Read sheet1.xml
        $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetData === false) {
            $zip->close();
            $this->throw('Không tìm thấy sheet dữ liệu trong file Excel.', 400);
        }

        try {
            $xml = simplexml_load_string($sheetData);
        } catch (\Exception $e) {
            $zip->close();
            $this->throw('File Excel không đúng định dạng XML.', 400);
        }
        $zip->close();

        if (!$xml || !isset($xml->sheetData)) {
            $this->throw('File Excel không có dữ liệu.', 400);
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowIndex = (int)$row['r'];
            $rowData = [];

            if (isset($row->c)) {
                foreach ($row->c as $cell) {
                    $rAttr = (string)$cell['r']; // e.g. "A1", "B1"
                    preg_match('/([A-Z]+)/', $rAttr, $matches);
                    $colName = $matches[1] ?? '';
                    $colIndex = $this->columnLetterToIndex($colName);

                    $type = (string)$cell['t'];
                    $val = '';
                    if (isset($cell->v)) {
                        $val = (string)$cell->v;
                        if ($type === 's') {
                            $val = $sharedStrings[(int)$val] ?? '';
                        }
                    }
                    $rowData[$colIndex] = $val;
                }
            }

            if (!empty($rowData)) {
                $maxCol = max(array_keys($rowData));
                for ($i = 0; $i <= $maxCol; $i++) {
                    if (!isset($rowData[$i])) {
                        $rowData[$i] = '';
                    }
                }
                ksort($rowData);
                $rows[$rowIndex] = $rowData;
            }
        }

        if (empty($rows)) {
            return [];
        }

        // Fill missing row indices and sort
        $maxRow = max(array_keys($rows));
        $result = [];
        for ($i = 1; $i <= $maxRow; $i++) {
            $result[] = $rows[$i] ?? [];
        }
        return $result;
    }

    private function columnLetterToIndex(string $col): int
    {
        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }
        return $index - 1;
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
