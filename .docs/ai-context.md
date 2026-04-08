# AI Context: Kiến trúc Hệ thống, Quy Tắc & Schema Dữ Liệu

> Tệp tin này tóm tắt kiến trúc, các quy tắc lập trình bắt buộc và cấu trúc database của dự án. Cung cấp file này làm ngữ cảnh sẽ giúp AI hiểu rõ hệ thống và đưa ra các gợi ý code chính xác, tuân thủ đúng chuẩn của dự án.

---

## 1. Kiến Trúc & Dòng Chảy Dữ Liệu (Architecture & Data Flow)

Hệ thống được xây dựng trên Laravel theo kiến trúc **Modular Layered Architecture**. Dòng chảy của một request phải tuân thủ nghiêm ngặt thứ tự sau. **Tuyệt đối không được "nhảy lớp"**.

`Request` → `Controller` → `Service Interface` → `Service` → `Repository Interface` → `Repository` → `Model` → `DB`

| Lớp (Layer) | Nhiệm vụ chính | Quy tắc bắt buộc |
| :--- | :--- | :--- |
| **Controller** | Tiếp nhận Request, Validate Form, gọi Service và trả về Response. | **Cấm** viết logic nghiệp vụ (if-else, tính toán) hoặc gọi Model/DB trực tiếp. |
| **Service** | Chứa toàn bộ "linh hồn" nghiệp vụ (Business Logic). | **Cấm** trả về HTTP Response (JSON). Chỉ được trả về object `ServiceReturn`. |
| **Repository** | Tương tác duy nhất với Database thông qua Eloquent Model. | **Cấm** gọi sang Service hoặc Controller. |

### 1.1. Hệ thống Core (`app/Core`)
Các class base cung cấp các phương thức và thuộc tính cốt lõi:
*   **`BaseController`**: Tích hợp trait `HandleApi` để trả về response chuẩn (`sendSuccess`, `sendError`, `sendValidation`).
*   **`BaseService`**:
    *   Cung cấp hàm `execute()` để bọc logic và quản lý transaction. **Mọi hành động C/U/D bắt buộc phải được bọc trong `execute(..., useTransaction: true)`**.
    *   Cung cấp hàm `throw()` và `validate()` để ném exception nghiệp vụ chuẩn.
*   **`BaseRepository`**: Cung cấp các hàm truy vấn cơ bản (`find`, `create`, `updateById`, `deleteById`, `query()`).

### 1.2. Giao tiếp giữa các Module
*   **Đồng bộ (Synchronous):** Khi Module A cần gọi Module B, phải **Inject Interface của Service B** vào constructor của Service A.
*   **Bất đồng bộ (Asynchronous):** Ưu tiên sử dụng **Events & Listeners** của Laravel.
*   **Dữ liệu trao đổi:** Sử dụng `ServiceReturn` hoặc các class `DTO` (Data Transfer Object). Không dùng `array` lỏng lẻo.

---

## 2. Tiêu Chuẩn & Quy Tắc Lập Trình (Coding Standards & Rules)

### 2.1. Bắt buộc
1.  **Strict Typing:** Luôn khai báo kiểu dữ liệu cho tham số, thuộc tính và kiểu trả về (`string`, `int`, `?User`, `void`).
2.  **Dependency Injection (DI):** Luôn Inject **Interface** vào Constructor. **Cấm** dùng `new Class()` thủ công trong logic.
3.  **Interface Binding:**
    *   Mọi binding (`bind`, `singleton`) phải được khai báo trong `register()` của `ModuleServiceProvider` tương ứng.
    *   Trong Constructor, **tuyệt đối không Type-hint Class thực thi (Concrete Class)**. Phải luôn Type-hint Interface.
        *   *Sai:* `public function __construct(UserRepository $repo)`
        *   *Đúng:* `public function __construct(UserRepositoryInterface $repo)`
4.  **Database Migrations:** **Cấm** sửa file Migration đã được commit. Mọi thay đổi (thêm/sửa/xóa cột) phải tạo file Migration mới.
5.  **Soft Deletes:** Tất cả các bảng quan trọng phải sử dụng `SoftDeletes`.

### 2.2. Quy tắc đặt tên (Naming Conventions)
*   **Interfaces:** Hậu tố `Interface` (VD: `UserRepositoryInterface`).
*   **Services:** Hậu tố `Service` (VD: `AuthService`).
*   **Events:** Dạng `[Entity][Action]` (VD: `RideCancelled`, `PaymentSuccess`).

---

## 3. Kiến trúc CSDL (Database Schema - PostgreSQL)

### 3.1. Tổng quan Domains
*   **G1 – Users**: Quản lý tài khoản, vai trò, và profile chi tiết.
*   **G2 – Service**: Dịch vụ đặt xe, đặt đồ ăn, menu.
*   **G3 – Finance**: Ví, giao dịch, voucher.
*   **G4 – Operation**: Vận hành chuyến đi, đánh giá, phạt.
*   **G5 – System - Shared**: Cấu hình hệ thống, giá cước, tệp tin.

### 3.2. Enums quan trọng
```php
// UserRole: 1:Admin, 2:Customer, 3:Driver, 4:Merchants
// UserOtpType: 1:Verify_Register, 2:Verify_Login, 3:Verify_Forgot_Password
// KycStatus: 1:Pending, 2:Approved, 3:Rejected
// DriverStatus: 1:Active, 2:Cooldown, 3:Banned
// FileableType: 1:Avatar, 2:Driver_Review_Application_CCCD_Front, ...
```

### 3.3. Cấu trúc các bảng chính

#### `users`
*   **Nhiệm vụ**: Chỉ quản lý tài khoản đăng nhập, vai trò, trạng thái. Không lưu thông tin cá nhân.
*   **Cấu trúc**:
    *   `id` (bigint)
    *   `phone` (varchar, unique)
    *   `email` (varchar, unique, nullable)
    *   `password` (varchar)
    *   `role` (tinyint) - Tham chiếu `UserRole` enum.
    *   `is_verified` (boolean)
    *   `is_active` (boolean)
    *   `google_id`, `apple_id` (varchar, nullable)
    *   `deleted_at` (timestamp)

#### `customer_profiles`
*   **Nhiệm vụ**: Lưu thông tin khách hàng. Quan hệ 1-1 với `users`.
*   **Cấu trúc**: `id`, `user_id` (unique, FK), `full_name` (varchar), `gender` (tinyint).

#### `driver_profiles`
*   **Nhiệm vụ**: Lưu thông tin tài xế. Quan hệ 1-1 với `users`.
*   **Cấu trúc**: `id`, `user_id` (unique, FK), `full_name`, `driver_group_id`, `vehicle_type`, `vehicle_name`, `vehicle_color`, `vehicle_number`, `is_online`, `current_lat`, `current_lng`, `status` (tham chiếu `DriverStatus`).

#### `user_otp`
*   **Nhiệm vụ**: Lưu trữ OTP, chống brute-force.
*   **Cấu trúc**: `id`, `phone`, `otp_hash`, `type` (tham chiếu `UserOtpType`), `attempts`, `expired_at`, `verified_at`, `send_count`.

#### `user_review_applications`
*   **Nhiệm vụ**: Lưu hồ sơ đăng ký (KYC) của tài xế/quán ăn.
*   **Cấu trúc**: `id`, `user_id`, `snapshot_data` (jsonb), `kyc_type`, `kyc_status`.

#### `files`
*   **Nhiệm vụ**: Quản lý file theo quan hệ đa hình (polymorphic).
*   **Cấu trúc**: `id`, `name`, `path`, `disk`, `fileable_type` (enum), `fileable_id` (bigint). Index trên `(fileable_type, fileable_id)`.

---

## 4. Work Flow Lập Trình Thường Ngày (Cho AI)
1.  **Tạo/Sửa Interface**: Nếu có thay đổi về giao tiếp, cập nhật `Interface` trước.
2.  **Viết Logic Repository**: Implement các phương thức truy vấn DB trong `Repository`.
3.  **Viết Business Logic trong Service**:
    *   Inject các `RepositoryInterface` hoặc `ServiceInterface` cần thiết.
    *   Áp dụng chuẩn `return $this->execute(...)` cho các tác vụ ghi/xóa/sửa.
4.  **Hoàn thiện Controller**:
    *   Inject `Service` tương ứng.
    *   Gọi hàm của Service, lấy data từ `$result->getData()`.
    *   Trả về Response qua `return $this->sendSuccess($data)`.
