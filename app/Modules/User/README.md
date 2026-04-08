# Module: User

Module `User` chịu trách nhiệm quản lý tất cả các thông tin và hoạt động liên quan đến người dùng, bao gồm hồ sơ cá nhân, địa chỉ đã lưu, và các dữ liệu khác.

## Chức năng chính

- **Quản lý Hồ sơ (Profile Management):**
    - Lấy thông tin hồ sơ chi tiết của người dùng dựa trên vai trò (Customer, Driver, Merchant).
    - Cập nhật thông tin hồ sơ. Hệ thống phân biệt giữa các trường thông tin "nhạy cảm" (như SĐT, email) và các trường thông thường.
    - Việc thay đổi thông tin nhạy cảm yêu cầu xác thực OTP.
    - Thay đổi mật khẩu.
- **Quản lý Địa chỉ đã lưu (Saved Address Management):**
    - Chỉ áp dụng cho người dùng có vai trò `Customer`.
    - Cho phép khách hàng thêm, sửa, xóa, và xem danh sách các địa chỉ thường dùng.
    - Hỗ trợ đặt một địa chỉ làm "mặc định".
    - Có cơ chế kiểm tra và ngăn chặn việc lưu các địa chỉ trùng lặp.
    - Giới hạn số lượng địa chỉ tối đa mà một khách hàng có thể lưu.

## Cấu trúc Module

- **`Http/Controllers/`**: Chứa các controller xử lý request liên quan đến người dùng.
    - `ProfileController.php`: Lấy thông tin hồ sơ người dùng.
    - `EditProfileController.php`: Cập nhật hồ sơ và đổi mật khẩu.
    - `SavedAddressController.php`: Quản lý CRUD cho địa chỉ đã lưu.
- **`Http/Requests/`**: Các lớp Request để xác thực dữ liệu cho việc cập nhật hồ sơ, đổi mật khẩu, và quản lý địa chỉ.
- **`Services/`**:
    - `ProfileService.php`: Chứa logic nghiệp vụ để quản lý hồ sơ người dùng. Xử lý việc lấy và cập nhật dữ liệu từ nhiều bảng (`users`, `customer_profiles`, `driver_profiles`, v.v.) dựa trên vai trò.
    - `SavedAddressService.php`: Chứa logic nghiệp vụ cho việc quản lý địa chỉ đã lưu của khách hàng.
- **`Repositories/UserRepository.php`**: Cung cấp một lớp trừu tượng để truy vấn dữ liệu từ bảng `users` và các bảng profile liên quan.
- **`Model/`**: Chứa các lớp Eloquent Model đại diện cho các bảng trong cơ sở dữ liệu.
    - `User.php`: Model chính, đại diện cho bảng `users`.
    - `CustomerProfile.php`, `DriverProfile.php`, `MerchantProfile.php`: Các model cho thông tin mở rộng theo vai trò.
    - `CustomerSavedAddress.php`: Model cho địa chỉ đã lưu.
    - `Enums/`: Chứa các lớp Enum cho các giá trị cố định như `UserRole`, `Gender`.
- **`Interfaces/`**: Định nghĩa các "hợp đồng" (contracts) cho service và repository.
- **`Routes/api.php`**: Định nghĩa các API endpoint của module, yêu cầu xác thực (`auth:sanctum`).

## Luồng hoạt động chính

### 1. Cập nhật Hồ sơ

1.  **Client** -> `PUT /api/v1/user/profile` (với các trường cần cập nhật).
2.  **EditProfileController** -> **ProfileService (`updateProfile`)**:
    -   Kiểm tra xem có trường "nhạy cảm" nào (`phone`, `email`) bị thay đổi không.
    -   Nếu có, trả về lỗi yêu cầu xác thực OTP.
    -   Nếu không, cập nhật các trường thông thường vào bảng `users` và các bảng profile tương ứng (`customer_profiles`, `driver_profiles`...).
    -   Trả về thông tin hồ sơ đã được cập nhật.

### 2. Thêm Địa chỉ mới

1.  **Client** -> `POST /api/v1/user/addresses` (với thông tin địa chỉ).
2.  **SavedAddressController** -> **SavedAddressService (`createAddress`)**:
    -   Kiểm tra xem người dùng đã đạt giới hạn địa chỉ tối đa chưa.
    -   Kiểm tra xem địa chỉ (dựa trên lat/lng) đã tồn tại chưa.
    -   Nếu địa chỉ mới được đặt làm mặc định, hệ thống sẽ bỏ cờ mặc định ở các địa chỉ cũ.
    -   Tạo một bản ghi mới trong `customer_saved_addresses`.
    -   Trả về thông tin địa chỉ vừa tạo.

## Cơ sở dữ liệu

-   `users`: Bảng trung tâm, lưu thông tin cơ bản và thông tin đăng nhập.
-   `customer_profiles`: Thông tin mở rộng cho khách hàng (ví dụ: ngày sinh).
-   `driver_profiles`: Thông tin mở rộng cho tài xế (ví dụ: thông tin xe, bằng lái).
-   `merchant_profiles`: Thông tin mở rộng cho đối tác cửa hàng.
-   `customer_saved_addresses`: Bảng lưu các địa chỉ của khách hàng.

## Ghi chú quan trọng

- **Phân quyền theo vai trò:** Logic trong `ProfileService` được thiết kế để xử lý dữ liệu dựa trên `UserRole` của người dùng, đảm bảo tính đúng đắn và bảo mật.
- **Tách biệt nghiệp vụ:** `ProfileService` và `SavedAddressService` tách biệt rõ ràng hai chức năng chính của module, giúp mã nguồn dễ bảo trì và mở rộng.
