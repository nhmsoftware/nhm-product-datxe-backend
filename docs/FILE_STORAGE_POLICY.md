# 📁 File Storage Policy — NHM DatXe Backend

> **Tài liệu này là nguồn sự thật duy nhất (Single Source of Truth) cho mọi quyết định về lưu trữ và phục vụ file ảnh/tài liệu trong hệ thống.**

---

## 🔑 Nguyên tắc cốt lõi

| Disk | Thư mục thực tế | Truy cập | Dùng khi nào |
|------|-----------------|----------|--------------|
| `local` (private) | `storage/app/private/` | Qua API endpoint `/api/v1/files/serve?path=...` | File nhạy cảm hoặc cần kiểm soát |
| `public` | `storage/app/public/` ↔ `public/storage/` | Trực tiếp qua URL `/storage/...` | **KHÔNG sử dụng trong dự án này** |

> ⚠️ **TUYỆT ĐỐI KHÔNG dùng `disk('public')` hoặc `'public'` disk trong bất kỳ module nào.**
> Mọi file đều lưu vào `disk('local')` và phục vụ qua `FileHelper::serveUrl()`.

---

## 📋 Phân loại ảnh: Private vs Public (Không cần Auth)

### ✅ PUBLIC — Không cần đăng nhập để xem
> Vẫn lưu vào `local` disk, nhưng route `/api/v1/files/serve` **không yêu cầu auth token**.

| Loại ảnh | Module | Thư mục lưu | Lý do public |
|----------|--------|-------------|--------------|
| Ảnh banner quảng cáo | Marketing | `banners/` | Hiển thị trang chủ app, không cần đăng nhập |
| Ảnh bài tin tức | Marketing | `news/` | Đọc tin tức là nội dung public |
| Ảnh món ăn menu nhà hàng | Merchant | `merchant/menu-items/` | Khách xem menu trước khi đặt, chưa đăng nhập |
| Ảnh combo nhà hàng | Merchant | `merchant/combos/` | Tương tự menu item |
| Avatar/ảnh đại diện merchant | Merchant | `merchant/avatars/` | Hiển thị trong danh sách nhà hàng |

### 🔒 PRIVATE — Cần đăng nhập (bearer token) mới xem được
> Route `/api/v1/driver/files/{id}` yêu cầu `auth:sanctum`.

| Loại ảnh / tài liệu | Module | Thư mục lưu | Lý do private |
|---------------------|--------|-------------|---------------|
| CMND/CCCD mặt trước | Driver (KYC) | Qua `files` table (local disk) | Thông tin cá nhân nhạy cảm |
| CMND/CCCD mặt sau | Driver (KYC) | Qua `files` table (local disk) | Thông tin cá nhân nhạy cảm |
| Bằng lái xe mặt trước (`license_front_image`) | Driver Profile | `driver/licenses/` | Tài liệu pháp lý |
| Bằng lái xe mặt sau (`license_back_image`) | Driver Profile | `driver/licenses/` | Tài liệu pháp lý |
| Ảnh xác nhận đón khách (`pickup_proof_photo_url`) | Ride | `rides/proofs/` | Dữ liệu chuyến đi, chỉ tài xế/admin xem |
| Ảnh xác nhận giao hàng (`delivery_proof_photo_url`) | Ride | `rides/proofs/` | Dữ liệu chuyến đi, chỉ tài xế/admin xem |
| Hồ sơ đăng ký tài xế (giấy tờ xe) | Driver (KYC) | Qua `files` table (local disk) | Tài liệu nội bộ xét duyệt |

---

## 🛠️ Cách implement đúng

### Upload file
```php
// ✅ ĐÚNG — dùng FileHelper
use App\Core\Helpers\FileHelper;

$path = FileHelper::uploadToPrivate($request->file('image'), 'merchant/menu-items');
// Trả về: 'merchant/menu-items/uuid.jpg'  ← lưu vào DB
```

```php
// ❌ SAI — KHÔNG được dùng
$path = $file->store('merchant/menu-items', 'public');
$path = Storage::disk('public')->put('folder', $file);
```

### Xóa file
```php
// ✅ ĐÚNG
FileHelper::deleteFromPrivate($model->getRawOriginal('image_path'));

// ❌ SAI — accessor đã chuyển thành URL đầy đủ, không phải path
FileHelper::deleteFromPrivate($model->image_path); // BUG: truyền vào URL thay vì path
```

### Trả URL trong API response (Model Accessor)
```php
// Trong Model — thêm accessor để tự động convert path → URL khi serialize
public function getImagePathAttribute(?string $value): ?string
{
    return \App\Core\Helpers\FileHelper::serveUrl($value);
}
```

### Route `/api/v1/files/serve`
```php
// ✅ ĐÚNG — route này KHÔNG có auth (ảnh public)
Route::get('/v1/files/serve', [FileServeController::class, 'serve'])->name('files.serve');

// Route KYC tài xế — CÓ auth (ảnh private)
Route::prefix('v1/driver')->group(function () {
    Route::get('files/{id}', [FileController::class, 'show'])->name('driver.files.show');
    // ^ route này nằm trong public group nhưng FileController đọc từ local disk
    //   — xem xét thêm auth:sanctum nếu cần bảo mật thêm
});
```

---

## 🤖 Prompt cho AI Agent

Khi implement tính năng upload/hiển thị ảnh trong dự án NHM DatXe Backend, **bắt buộc** tuân thủ các quy tắc sau:

```
SYSTEM CONTEXT — NHM DatXe Backend File Storage Rules:

1. DISK: Tất cả file luôn lưu vào local disk (storage/app/private/).
   KHÔNG BAO GIỜ dùng 'public' disk, Storage::disk('public'), hoặc ->store(..., 'public').

2. UPLOAD: Luôn dùng FileHelper::uploadToPrivate($file, $folder).
   Không bao giờ gọi $file->store() hay Storage::put() trực tiếp.

3. DELETE: Luôn dùng FileHelper::deleteFromPrivate($path).
   Khi xóa ảnh cũ trước khi upload mới, lấy raw value từ DB bằng $model->getRawOriginal('field_name'),
   KHÔNG dùng $model->field_name (đã qua accessor, là URL đầy đủ, không phải path).

4. URL: Luôn dùng FileHelper::serveUrl($path) để sinh URL.
   URL format: /api/v1/files/serve?path=<encoded_path>

5. MODEL ACCESSOR: Mọi model có trường ảnh (image_path, image_url, photo_url, v.v.)
   PHẢI có accessor để tự động convert path → URL:
   public function getImagePathAttribute(?string $value): ?string {
       return \App\Core\Helpers\FileHelper::serveUrl($value);
   }

6. ROUTE AUTH:
   - Ảnh PUBLIC (banner, news, menu item, combo): route /api/v1/files/serve KHÔNG có middleware auth.
   - Ảnh PRIVATE (KYC tài xế, bằng lái, proof chuyến đi): route phải có middleware auth:sanctum.
   
   Phân loại:
   PUBLIC  → banners/, news/, merchant/menu-items/, merchant/combos/, merchant/avatars/
   PRIVATE → driver/licenses/, driver/kyc/, rides/proofs/

7. THÊM MODULE MỚI: Khi tạo module mới có upload ảnh:
   a. Xác định ảnh thuộc loại PUBLIC hay PRIVATE (xem bảng phân loại ở trên).
   b. Dùng FileHelper::uploadToPrivate() để upload.
   c. Thêm accessor vào Model.
   d. Nếu PUBLIC: không thêm auth vào route files/serve (đã global).
   e. Nếu PRIVATE: tạo route riêng có auth:sanctum để serve file đó.
```

---

## 📊 Trạng thái hiện tại (cập nhật 2026-05-22)

| Module | Upload | Accessor | Route serve | Trạng thái |
|--------|--------|----------|-------------|------------|
| Marketing/Banner | ✅ `uploadToPrivate` | ✅ `getImageUrlAttribute` | ✅ public `/files/serve` | OK |
| Marketing/News | ✅ `uploadToPrivate` | ✅ `getImageUrlAttribute` | ✅ public `/files/serve` | OK |
| Merchant/MenuItem (Admin) | ✅ `uploadToPrivate` | ✅ `getImagePathAttribute` | ✅ public `/files/serve` | OK |
| Merchant/MenuItem (Merchant) | ✅ `uploadToPrivate` | ✅ `getImagePathAttribute` | ✅ public `/files/serve` | ✅ Fixed 2026-05-22 |
| Merchant/Combo | ⚠️ Không có upload logic ảnh | ✅ `getImagePathAttribute` | ✅ public `/files/serve` | Accessor added |
| Driver KYC (`files` table) | ✅ local disk | N/A (link qua `getLinkAttribute`) | `driver/files/{id}` (public route) | OK |
| Driver Profile (license_*) | ✅ `uploadToPrivate` | ❌ Không có accessor | `/files/serve` qua AdminDriverService | Cần thêm accessor |
| Ride/Proof photos | ✅ `uploadToPrivate` | ❌ Không có accessor | `/files/serve` qua RideService | Cần thêm accessor |
