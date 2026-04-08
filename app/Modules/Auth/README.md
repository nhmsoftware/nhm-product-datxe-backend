# Module: Auth

Module `Auth` chịu trách nhiệm cho tất cả các quy trình liên quan đến xác thực và quản lý phiên làm việc của người dùng.

## Chức năng chính

- **Đăng ký (Registration):** Cho phép người dùng mới tạo tài khoản bằng số điện thoại và mật khẩu. Luồng này bao gồm xác thực OTP qua số điện thoại để đảm bảo tính hợp lệ.
- **Đăng nhập (Login):** Xác thực người dùng dựa trên số điện thoại và mật khẩu.
- **Đăng nhập qua mạng xã hội:** Hỗ trợ đăng nhập/đăng ký nhanh thông qua tài khoản Google và Apple.
- **Quên mật khẩu (Forgot Password):** Cung cấp luồng để người dùng đặt lại mật khẩu của họ một cách an toàn thông qua xác thực OTP.
- **Gửi và xác thực OTP:** Cung cấp một hệ thống OTP linh hoạt cho các mục đích khác nhau (đăng ký, đăng nhập, quên mật khẩu).
- **Quản lý phiên (Session Management):** Tạo và quản lý token (sử dụng Laravel Sanctum) để xác thực các yêu cầu API sau khi đăng nhập.
- **Đăng xuất (Logout):** Cho phép người dùng kết thúc phiên làm việc hiện tại hoặc tất cả các phiên trên mọi thiết bị.

## Cấu trúc Module

- **`Http/Controllers/AuthController.php`**: Tiếp nhận các yêu cầu HTTP, gọi đến `AuthService` để xử lý logic và trả về phản hồi JSON.
- **`Http/Requests/`**: Chứa các lớp Request để xác thực (validate) dữ liệu đầu vào cho từng endpoint (ví dụ: `RegisterRequest`, `LoginRequest`).
- **`Services/AuthService.php`**: Lớp lõi chứa toàn bộ logic nghiệp vụ của module. Nó điều phối hoạt động giữa các repository và các dịch vụ bên ngoài (ví dụ: gửi OTP, xác thực token Google/Apple).
- **`Repositories/AuthOtpRepository.php`**: Chịu trách nhiệm truy vấn và thao tác với cơ sở dữ liệu liên quan đến mã OTP (`user_otps`).
- **`Interfaces/`**: Định nghĩa các "hợp đồng" (contracts) cho service và repository, giúp cho việc dependency injection và testing dễ dàng hơn.
- **`Routes/api.php`**: Định nghĩa tất cả các API endpoint của module.

## Luồng hoạt động chính

### 1. Đăng ký (UC-01)

1.  **Client** -> `POST /api/v1/auth/authenticate-otp` (với `phone` và `type=1`).
2.  **AuthController** -> **AuthService (`sendOtp`)**: Kiểm tra số điện thoại chưa tồn tại, tạo và gửi OTP.
3.  **Client** -> `POST /api/v1/auth/register` (với `phone`, `otp`, `full_name`, `password`, `password_confirmation`).
4.  **AuthController** -> **AuthService (`register`)**:
    -   Xác thực OTP (`verifyOtpOrFail`).
    -   Kiểm tra lại SĐT đã tồn tại chưa.
    -   Tạo `User` mới với mật khẩu đã được mã hóa.
    -   Tạo `CustomerProfile`.
    -   Tạo token và trả về cho client.

### 3. Quên mật khẩu (UC-03)

1.  **Client** -> `POST /api/v1/auth/authenticate-otp` (với `phone` và `type=2` - loại dành cho quên mật khẩu).
2.  **AuthController** -> **AuthService (`sendOtp`)**:
    -   Kiểm tra số điện thoại có tồn tại trong hệ thống không. Nếu không, trả về lỗi "Số điện thoại chưa được đăng ký" (A3).
    -   Tạo và gửi mã OTP 6 chữ số qua SMS.
3.  **Client** -> `POST /api/v1/auth/reset-password` (với `phone`, `otp`, `password`, `password_confirmation`).
4.  **AuthController** (`resetPassword` method, using `ResetPasswordRequest`):
    -   `ResetPasswordRequest` sẽ xác thực đầu vào, đảm bảo mật khẩu mới hợp lệ và khớp với mật khẩu xác nhận (A4).
5.  **AuthService (`resetPassword`)**:
    -   Xác thực OTP (`verifyOtpOrFail`). Xử lý các trường hợp OTP sai (A1) hoặc hết hạn (A2).
    -   Tìm người dùng bằng số điện thoại.
    -   Cập nhật mật khẩu mới (đã được mã hóa) cho người dùng.
    -   Thông báo đặt lại mật khẩu thành công. Người dùng sau đó có thể tiến hành đăng nhập với mật khẩu mới.

### 2. Đăng nhập (UC-02)

1.  **Client** -> `POST /api/v1/auth/login` (với `phone` và `password`).
2.  **AuthController** -> **AuthService (`login`)**:
    -   Tìm `User` bằng số điện thoại.
    -   So sánh mật khẩu (`Hash::check`).
    -   Kiểm tra tài khoản có `is_active` không.
    -   Tạo token và trả về cho client.

## Cơ sở dữ liệu

Module này tương tác chủ yếu với các bảng:

-   `users`: Lưu thông tin cơ bản của người dùng, bao gồm thông tin đăng nhập.
-   `user_otps`: Lưu trữ mã OTP, thời gian hết hạn, và trạng thái sử dụng.
-   `customer_profiles`, `driver_profiles`, etc.: Lưu thông tin chi tiết theo vai trò người dùng (do `AuthService` tạo ra nhưng thuộc về module `User`).
-   `personal_access_tokens`: Bảng của Laravel Sanctum để quản lý token.

## Ghi chú quan trọng

- **Bảo mật:** Mật khẩu được mã hóa bằng `bcrypt`. Token đăng nhập có thời hạn và nên được gửi qua header `Authorization`.
- **OTP Throttling:** Hệ thống có cơ chế giới hạn số lần gửi OTP trong một khoảng thời gian để tránh spam.
