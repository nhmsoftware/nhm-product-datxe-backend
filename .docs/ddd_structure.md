# 🏛️ Tài liệu Cấu trúc Domain-Driven Design (DDD) - Laravel Modular

Tài liệu này quy định cách tổ chức mã nguồn cho các module (như `User`, `Auth`,...) nhằm đảm bảo tính tách biệt nghiệp vụ, dễ bảo trì và mở rộng.

---

## 🏗️ Tổng quan 4 Lớp Kiến trúc (Layers)

Mô hình này tuân thủ nguyên tắc **Dependency Rule**: Sự phụ thuộc chỉ hướng vào bên trong (Lớp ngoài biết lớp trong, lớp trong KHÔNG được biết lớp ngoài).

### 1. 🟢 Domain Layer (Trái tim của Hệ thống)
*Đây là nơi chứa logic nghiệp vụ thuần túy, không phụ thuộc vào Framework hay Database.*

* **Models / Entities**: Các đối tượng nghiệp vụ chính (VD: `User.php`).
* **Value Objects**: Các đối tượng định danh bằng giá trị (VD: `Email.php`, `Password.php`). Giúp tự validate logic ngay khi khởi tạo.
* **Interfaces (Contracts)**: Định nghĩa các bản hợp đồng cho Repositories hoặc Services (VD: `UserRepositoryInterface.php`).
* **Events**: Các sự kiện nghiệp vụ quan trọng (VD: `UserRegistered.php`).
* **Exceptions**: Các lỗi đặc thù của nghiệp vụ (VD: `AccountLockedException.php`).
* **Enums**: Các tập hợp giá trị cố định (VD: `UserStatus::ACTIVE`).

### 2. 🟣 Application Layer (Lớp Điều phối)
*Lớp này không chứa logic nghiệp vụ, nó chỉ điều phối các thành phần ở lớp Domain và Infrastructure.*

* **Actions / UseCases**: Mỗi class xử lý một nhiệm vụ duy nhất (Single Responsibility). (VD: `LoginAction.php`, `RegisterAction.php`).
* **DTOs (Data Transfer Objects)**: Các object dùng để vận chuyển dữ liệu giữa Presentation và Application. Giúp code tường minh, không dùng array thuần.
* **Listeners / Handlers**: Xử lý các hành động sau khi Event xảy ra (VD: `SendWelcomeEmail.php`).

### 3. 🔵 Infrastructure Layer (Thực thi chi tiết)
*Nơi triển khai các công nghệ cụ thể như Database, Mail, SMS, Third-party API.*

* **Repositories**: Triển khai (Implement) các Interface từ Domain bằng Eloquent hoặc Query Builder (VD: `EloquentUserRepository.php`).
* **External Services**: Gọi API bên ngoài (VD: `StripePaymentService.php`).
* **Mappers**: Chuyển đổi dữ liệu giữa Database và Domain Entity.
* **Providers**: Nơi đăng ký (bind) Interface với Implementation trong Service Container của Laravel.

### 4. 🟠 Presentation Layer (Giao diện & Giao tiếp)
*Nơi nhận yêu cầu từ người dùng và trả về kết quả.*

* **Controllers**: Tiếp nhận Request, gọi Action ở tầng Application và trả về Response.
* **Requests (Form Requests)**: Validate định dạng dữ liệu HTTP (VD: `required`, `email`, `max:255`).
* **Resources**: Định dạng lại dữ liệu trả về cho Client (API Transform).
* **Routes**: Khai báo các endpoint API của module.

---

## 🔄 Luồng dữ liệu (Data Flow) chuẩn

1.  **Client** gửi Request đến `Controller`.
2.  `Controller` dùng `FormRequest` để validate định dạng.
3.  `Controller` chuyển dữ liệu vào một `DTO`.
4.  `Controller` gọi một `Action` (Tầng Application) và truyền `DTO` vào.
5.  `Action` gọi đến `Repository Interface` (Tầng Domain).
6.  `Eloquent Repository` (Tầng Infrastructure) thực hiện truy vấn DB và trả về kết quả.
7.  `Action` xử lý kết quả, có thể bắn ra một `Event`.
8.  `Controller` nhận kết quả từ `Action` và trả về qua `Resource`.

---

## ⚠️ Quy tắc nghiêm ngặt

1.  **KHÔNG** truyền trực tiếp class `Request` của Laravel vào tầng Application hoặc Domain. Hãy dùng `DTO`.
2.  **KHÔNG** viết logic truy vấn DB (`where`, `join`) trực tiếp trong Controller hay Action. Hãy đưa vào `Repository`.
3.  **KHÔNG** gọi trực tiếp các class ở Infrastructure trong Controller. Hãy gọi thông qua `Interface` của Domain.
4.  **Action** chỉ nên thực hiện một nhiệm vụ duy nhất. Nếu quá phức tạp, hãy chia nhỏ hoặc dùng `Domain Service`.

---
