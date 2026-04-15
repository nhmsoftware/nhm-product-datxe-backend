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
    // Validate điều kiện
    $this->validate($condition, 'Thông báo lỗi');
    // Hoặc ném lỗi có HTTP code
    $this->throw('Bạn không có quyền.', 403);

    $model = $this->repository->create($data);

    // Trả về thành công
    return $this->success($model->toArray(), 'Tạo thành công.');
}, useTransaction: true);
```

### 4.2. 🚫 CẤM dùng `ServiceReturn::` trực tiếp trong Service

Trong class Service, **tuyệt đối không** được gọi `ServiceReturn::success()` hay `ServiceReturn::error()` thủ công. Phải sử dụng các abstract method mà `BaseService` đã cung cấp:

| Method | Công dụng |
|--------|----------|
| `$this->success($data, $message)` | Trả kết quả thành công |
| `$this->throw($message, $code)` | Ném lỗi có HTTP status code |
| `$this->validate($condition, $message)` | Ném lỗi nếu điều kiện không đúng |

```php
// ❌ SAI — Không được làm thế này
return ServiceReturn::error('Không tìm thấy.', code: 404);

// ✅ ĐÚNG — Phải làm thế này
$this->validate($model !== null, 'Không tìm thấy.');
// hoặc
$this->throw('Không tìm thấy.', 404);
```

### 4.3. 🚫 CẤM dùng `Auth::` (Auth facade) trực tiếp trong Service

Lớp Service phải hoàn toàn "thuần khiết" (Pure) và không phụ thuộc vào trạng thái session của trình duyệt. Mọi thông tin người dùng (ID) phải được truyền vào từ Controller.

* **Lý do:** Đảm bảo Service có thể chạy được từ Queue, Job, Console Command và dễ dàng Unit Test.
* **Quy tắc:** Chỉ lấy `User ID` tại Controller và truyền xuống Service. Nếu Service cần Model User, hãy dùng `UserRepository` để tìm theo ID đã truyền.

```php
// ❌ SAI (Trong Service)
$user = Auth::user();

// ✅ ĐÚNG (Trong Controller)
$customerId = (int) Auth::id();
$this->rideService->createDraft($dto, $customerId);

// ✅ ĐÚNG (Trong Service)
$user = $this->userRepository->findById($customerId);
```

### 4.4. Database

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

## 6. Quy Tắc Khai Báo Interface (Binding Rules)

Việc sử dụng Interface là **bắt buộc** để đảm bảo tính lỏng lẻo (Loosely Coupled).

* **Vị trí khai báo:** Tất cả các lệnh `$this->app->bind` hoặc `singleton` phải nằm trong method `register()` của từng `ModuleServiceProvider`.
* **Repository Binding:** Mỗi Repository phải có một Interface tương ứng.
    * *Ví dụ:* `UserRepositoryInterface` -> `UserRepository`.
* **Service Binding:** Chỉ cần bind Interface cho các Service có tính chất "Public" (được gọi từ module khác). Các Internal Service không nhất thiết phải có Interface nếu chỉ dùng nội bộ.
* **Cấm gọi Class trực tiếp:** Trong Constructor của Controller hoặc Service, tuyệt đối không Type-hint Class thực thi (Concrete Class). Phải luôn Type-hint Interface.
    * *Sai:* `public function __construct(UserRepository $repo)`
    * *Đúng:* `public function __construct(UserRepositoryInterface $repo)`
