# AI Context: Kiến trúc Hệ thống & Data Schema

> Tệp tin này tóm tắt gọn gàng về các base, DB và quy tắc lập trình của dự án nhằm cung cấp ngữ cảnh đầy đủ nhưng không dài dòng khi bạn đặt câu hỏi. Không cần thiết nhắc lại Rule nếu đã cung cấp AI Context này.

## 1. Môi trường & Hệ thống Base (`app/Core`)

Kiến trúc hệ thống sử dụng Framework Laravel (mô hình Modular Layered Architecture). Các class được extends từ thư mục **`app/Core`**:

*   **Controller (`BaseController`)**: 
    *   Tích hợp sẵn trait `HandleApi`. 
    *   Các hàm Response hỗ trợ: `$this->sendSuccess($data, $message, $code)`, `$this->sendValidation()`, `$this->sendError()`.
    *   *Nhiệm vụ*: Chỉ đón Request, validate form, gọi Service và trả về Response. Tuyệt đối không code logic xử lý (`if-else` nghiệp vụ) ở đây.

*   **Service (`BaseService`)**: 
    *   Mọi thay đổi CSDL **bắt buộc** phải bọc qua hàm `execute()` với `useTransaction: true`. 
    *   Ví dụ: `return $this->execute(function() { ... }, useTransaction: true);`
    *   Trả về object `ServiceReturn` (gồm: `$success`, `$message`, `$data`, `$code`).
    *   Văng Exception chuẩn: `$this->throw('Lỗi nghiệp vụ', 400);` hoặc `$this->validate($cond, 'Lỗi');`
    *   *Nhiệm vụ*: Là nơi duy nhất chứa Business Logic. Service không bao giờ return thẳng JSON / HTTP Response.

*   **Repository (`BaseRepository`)**: 
    *   Hỗ trợ sẵn các hàm: `getAll()`, `find()`, `findById()`, `findByCondition()`, `create()`, `updateById()`, `deleteById()`. Cung cấp phương thức `$this->query()` để truy cập Builder.
    *   *Nhiệm vụ*: Tương tác với Database bằng Eloquent Model. Controller và Service **không được** gọi trực tiếp các hàm `User::create(...)` hay `User::where(...)`, mọi lệnh DB phải đi qua Repository.

## 2. Tiêu chuẩn viết Code (Rules)

1.  **Dòng chảy dữ liệu**: `Request` -> `Controller` -> `Service` -> `Repository` -> `Model` -> `DB`. **Tuyệt đối không nhảy lớp.**
2.  **Giao tiếp giữa Modules**: Bọc qua Dependency Injection dựa trên Interfaces. Khi Module A muốn gọi logic của Module B, phải gọi Interface của Service B chứ không gọi Concrete.
3.  **SoftDelete & Migrations**: Phải dùng Strict Typing (`string`, `?int`), mọi thao tác xoá đổi cột phải làm migration mới, tuyệt đối không được sửa migration cũ đã commit.

## 3. Kiến trúc CSDL Lõi Chú Ý (Database)

*   **Tài khoản (users)**: Có các flag như `is_active`, `is_verified` và chứa `role` (Admin=1, Customer=2, Driver=3, Merchants=4). Bảng này chỉ lo phần xác thực (`phone`, `email`, `google_id`, `apple_id`, `password`).
*   **Profiles**: Tuỳ theo `role` của người dùng, sẽ lưu Profile qua relation 1-1 với các bảng con (Ví dụ: khách hàng thì lưu `customer_profiles`, tài xế thì lưu vào `driver_profiles`). Bảng Profile chứa thông tin tên, tuổi, config và toạ độ. Tách riêng bảng Users và Profiles để làm sạch codebase.
*   **Thiết bị (user_devices)**: Chứa FCM Tokens và device metadata, quan hệ 1-N.
*   **OTP (user_otp)**: Bảng lưu OTP có rule chặt chẽ, chống brute force (`attempts`, `send_count`, `expired_at`). OTP hash được check validation kỹ trước khi active account.
*   **Hồ sơ duyệt (user_review_applications)**: Bảng xử lý logic định hình KYC cho tài xế/quán ăn, đi liền với `kyc_status` và snapshot đăng ký.

## 4. Work Flow Lập Trình Thường Ngày (Cho AI)
- Mọi Controller / Service / Repository bạn tạo ra đều phải Inject **Interface** (`FooInterface`). Bind tại `Providers`.
- Viết Logic DataBase vào Repository đầu tiên.
- Xây code Business Logic trong Service, áp dụng chuẩn `return $this->execute(...)`.
- Controller chỉ gọi Function của Service => Get Data từ \`$result->getData()\` => Trả về Format thông qua `$this->sendSuccess()`.
