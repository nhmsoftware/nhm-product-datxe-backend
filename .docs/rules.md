# 📜 Dự Án: Hệ Thống Quản Lý Xe Ôm & Nhà Xe
## Bộ Quy Tắc Phát Triển (Project Guidelines & Rules)

Tài liệu này quy định các tiêu chuẩn về kiến trúc, lập trình và quy trình làm việc cho team (3 người). Mục tiêu là giữ cho Codebase luôn sạch, dễ bảo trì và mở rộng theo mô hình **Modular + Service/Repo**.

---

## 1. Kiến Trúc Hệ Thống (Architecture)

Hệ thống được chia thành các lớp (Layers) nghiêm ngặt. Tuyệt đối không được "nhảy lớp".

| Lớp (Layer) | Nhiệm vụ chính | Quy tắc cấm |
| :--- | :--- | :--- |
| **Controller** | Tiếp nhận Request, điều phối Service và trả về Response. | **Cấm** viết logic nghiệp vụ (if-else, tính toán) hoặc gọi Model trực tiếp. |
| **Service** | Chứa toàn bộ "linh hồn" nghiệp vụ (Business Logic). | **Cấm** trả về HTTP Response (chỉ trả về `ServiceReturn`). |
| **Repository** | Tương tác duy nhất với Database thông qua Eloquent. | **Cấm** gọi sang Service hoặc Controller. |
| **Module** | Đóng gói một tính năng hoàn chỉnh (User, Ride, Wallet). | **Cấm** Module này can thiệp vào Database của Module kia. |

---

## 2. Giao Tiếp Giữa Các Module (Inter-module Communication)

Để tránh hệ thống trở thành một đống "mì tôm", việc giao tiếp phải tuân thủ:

* **Đồng bộ (Synchronous):** Khi Module A cần dữ liệu/hành động tức thì từ Module B, phải gọi qua **Interface** (được Inject vào Constructor).
* **Bất đồng bộ (Asynchronous):** Ưu tiên sử dụng **Events & Listeners**. Module A làm xong việc thì bắn Event, Module B (hoặc NestJS) tự lắng nghe và xử lý.
* **Dữ liệu trao đổi:** Sử dụng class `ServiceReturn` hoặc các class `DTO` (Data Transfer Object). Không dùng `array` lỏng lẻo.

---

## 3. Tiêu Chuẩn Lập Trình (Coding Standards)

### 3.1. Định dạng Code
* **Strict Typing:** Luôn khai báo kiểu dữ liệu cho tham số và kiểu trả về (Return type).
    * *Đúng:* `public function getByPhone(string $phone): ?User`
* **Dependency Injection (DI):** Luôn Inject Interface vào Constructor. Tuyệt đối không dùng `new Class()` thủ công trong logic.

### 3.2. Quy tắc đặt tên (Naming)
* **Interfaces:** Phải có hậu tố `Interface` (VD: `UserRepositoryInterface`).
* **Services:** Phải có hậu tố `Service` (VD: `RideService`).
* **Events:** Đặt tên theo dạng `[Thực thể][Hành động]` (VD: `RideCancelled`, `PaymentSuccess`).

---

## 4. Quản Lý Dữ Liệu & Transaction

### 4.1. Quy tắc Vàng về Transaction
Mọi hành động có thay đổi dữ liệu (Create/Update/Delete) trong Service **bắt buộc** phải bọc trong method `execute()` của `BaseService`:

```php
return $this->execute(function() {
    // Logic của bạn ở đây
}, useTransaction: true);
```

### 4.2. Database

Soft Deletes: Tất cả các bảng quan trọng phải sử dụng SoftDeletes.

Migrations: Tuyệt đối không sửa file Migration đã push lên Git. Nếu cần thay đổi, phải tạo file Migration mới (Add/Drop/Rename).


## 5. Quy Trình Làm Việc Team (Workflow)

Interface First: Trước khi code logic, các thành viên phải thống nhất Interface giao tiếp giữa các Module với nhau.

Single Responsibility: Một Class/Function chỉ làm một nhiệm vụ duy nhất. Nếu Service của bạn quá 500 dòng, hãy chia nhỏ nó ra.

```markdown
Git:
- main: Code ổn định để deploy.
- dev: Code đang phát triển chung.
- dev/[name]: Nhánh làm việc riêng của từng người.
- fix/[name]: Nhánh fix bug hoặc cải thiện.
```
