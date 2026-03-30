# Database Design — Hệ thống Đặt Xe & Giao Đồ Ăn

> Thiết kế dựa trên yêu cầu nghiệp vụ: App Khách hàng, App Tài xế, App Quán ăn, Web Admin  
> Hệ quản trị CSDL: **PostgreSQL** + **Redis** (cache/queue)

# các bảng mặc dịnh của laravel
- sessions
- cache
- cache_locks
- jobs
- job_batches
- failed_jobs
- personal_access_tokens
- password_reset_tokens


# Tổng quan kiến trúc domains
- **G1 – Users**
  > Nhiệm vụ: Quản lý tài khoản đăng nhập, vai trò, phân quyền và profile của từng loại người dùng
- **G2 – Service**
  > Nhiệm vụ: Lưu trữ thông tin dịch vụ đặt xe, đặt đồ ăn, menu quán ăn
- **G3 – Finance**
  > Nhiệm vụ: Quản lý ví, giao dịch, gói thuê bao, voucher
- **G4 – Operation**
  > Nhiệm vụ: Quản lý vận hành — chuyến đi, đánh giá, phạt tài xế, chống gian lận
- **G5 – System - Shared**
  > Nhiệm vụ: Cấu hình hệ thống — giá cước, phát sóng cuốc xe, cài đặt chung, tệp tin chung, ...



# ---G1: Users

## Các enums trong module này
### UserRole
```
1: Admin (Quản trị viên)
2: Customer (Khách hàng)
3: Driver (Tài xế)
4: Merchants (Quán ăn)
```

### UserOtpType
```
1: Verify_Register (Xác thực đăng ký)
2: Verify_Forgot_Password (Xác thực quên mật khẩu)
```
### Gender
```
1: Nam
2: Nữ
3: Khác
```
### AddressLabel
```
1: Home (Nhà)
2: Office (Cơ quan)
3: Other (Khác)
```
### DriverGroupType
```
1: Internal (Đội xe nhà)
2: Partner (Tài xế đối tác)
```
### KycType

```
1: Driver (Tài xế)
2: Merchants (Quán ăn)
3: Change_Vehicle (Thay đổi xe)
```

### KycStatus
```
1: Pending (Chờ duyệt)
2: Approved (Đã duyệt)
3: Rejected (Từ chối)
```
### DriverStatus
```
1: Active (Đang hoạt động)
2: Cooldown (Đang bị đóng băng)
3: Banned (Bị khóa)
```
### VehicleColor
```
0: Other (Khác)
1: Red (Red)
2: Green (Green)
3: Blue (Blue)
4: Yellow (Yellow)
5: Orange (Orange)
6: Purple (Purple)
7: Brown (Brown)
8: Black (Black)
9: White (White)
```
### VehicleType
```
1: Car_5_Seats (Car)
2: Car_7_Seats (Car)
3: Car_9_Seats (Car)

```

## users
```
    # note
    - Quản lý tài khoản đăng nhập, vai trò, phân quyền
    - Không lưu các thông tin của người dùng vao đây, mục đích bảng này chỉ là quản lý tài khoản đăng nhập, vai trò, phân quyền

    # cấu trúc
    - id (unsigned bigint auto increment)
    - phone (varchar(50), not null) -- Số điện thoại
    - email (varchar(255), nullable) - Email
    - is_verified (boolean default false) - Trạng thái xác thực
    - google_id (varchar(255), nullable) - ID của người dùng trên provider Google
    - apple_id (varchar(255), nullable) - ID của người dùng trên provider Apple
    - password (varchar(255), not null) - Mật khẩu, phải được mã hóa
    - role (unsigned tinyint, not null) - Vai trò, lưu trữ trong UserRole
    - created_at (timestamp) - Thời gian tạo
    - updated_at (timestamp) - Thời gian cập nhật
    - deleted_at (timestamp, nullable) - Thời gian xóa (soft delete)

    # index
    - UNIQUE (phone)
    - UNIQUE (email) WHERE email IS NOT NULL
    - INDEX (role)
```

## user_otp
    # note
    - Bảng user_otp lưu trữ thông tin OTP (mã xác thực) của người dùng.

    # cấu trúc
    - id (unsigned bigint auto increment)
    - phone (varchar(50), not null) -- Số điện thoại
    - otp_hash (varchar(255), not null) -- mã OTP, được mã hóa
    - type (unsigned smallint, not null) - Loại OTP (trong enum UserOtpType)
    - attempts (unsigned tinyint, default 1) -- số lần thử lại     
    - expired_at (timestamp) -- thời gian hết hạn
    - verified_at (timestamp, nullable) -- thời gian xác thực
    - last_sent_at (timestamp, nullable) -- thời gian gửi OTP cuối cùng
    - send_count (unsigned tinyint, default 1) -- số lần gửi OTP
    - ip_address (varchar, nullable) -- địa chỉ IP
    - created_at (timestamp) -- thời gian tạo
    - updated_at (timestamp) -- thời gian cập nhật

    # index
    - index (phone, type) -- chỉ mục trên cột phone và type để tối ưu truy vấn

# user_devices
    # note
    - Bảng user_devices lưu trữ thông tin các thiết bị của người dùng.

    # cấu trúc
    - id (bigint, primary key, auto-increment)
    - user_id (bigint, foreign key to users.id) -- id người dùng
    - token (varchar) -- token của thiết bị
    - device_id (varchar) -- id thiết bị
    - device_type (varchar, nullable) -- loại thiết bị
    - unique(device_id, user_id) -- token và device_id phải là duy nhất
    
    - timestamps


## customer_profiles
```
    # note
    - Lưu thông tin profile của khách hàng (role = Customer)
    - Quan hệ 1-1 với bảng `users`
    
    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - user_id (unsigned bigint, not null, FK → users.id)
    - full_name (varchar(100), not null) — Họ tên
    - gender (unsigned tinyint, not null) — Giới tính, lưu trữ trong Gender
    - created_at (timestamp)
    - updated_at (timestamp)
    
    # index
    - UNIQUE (user_id)
```

## customer_saved_addresses
```
    # note
    - Lưu địa chỉ thường dùng của khách hàng (Nhà, Cơ quan, v.v.)
    - Mỗi khách có thể lưu nhiều địa chỉ
    
    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - customer_id (unsigned bigint, not null, FK → customer_profiles.id)
    - label (unsigned tinyint, not null) — Nhãn địa chỉ, lưu trong AddressLabel
    - name (varchar(100), nullable) — Tên gợi nhớ (dùng khi label = Other)
    - address_text (text, not null) — Địa chỉ đầy đủ
    - lat (decimal(10,7), not null) — Vĩ độ
    - lng (decimal(10,7), not null) — Kinh độ
    - is_default (boolean, default false)
    - created_at (timestamp)
    - updated_at (timestamp)
    
    # index
    - INDEX (customer_id)
    
    # enum
```

## user_review_applications
```
    # note
    - Bảng user_review_applications lưu trữ thông tin duyệt hồ sơ để thành Driver hoặc Merchants.
    - Mỗi người dùng có thể có 1 hồ sơ duyệt duy nhất. 

    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - user_id (unsigned bigint, not null, FK → users.id) -- id người dùng
    - snapshot_data (jsonb) 
        > dữ liệu hồ sơ dạng jsonb mô tả thông tin hồ sơ để upsert sang bảng driver_profiles hoặc merchants_profiles, 
        > đồng thời các loại tệp tin được lưu trong bảng files với `fileable_type` tương ứng với từng role mà apply

    - kyc_type (unsigned tinyint, not null) -- loại hồ sơ duyệt (trong enum KycType)
    - kyc_status (unsigned tinyint, not null) -- trạng thái duyệt hồ sơ (trong enum KycStatus)
    - cancel_reason (varchar(255), nullable) -- lý do bị hủy đơn
    
    - timestamps
```

## driver_groups
```
    # note
    - Lưu thông tin nhóm tài xế (role = Driver)
    - Quan hệ 1-1 với bảng `users`
    
    ### cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - name (varchar(100), not null) — Tên nhóm tài xế
    - description (text, nullable) — Mô tả nhóm tài xế
    - created_at (timestamp) — Thời gian tạo
    - updated_at (timestamp) — Thời gian cập nhật
    - deleted_at (timestamp, nullable) — Thời gian xóa (soft delete)
```

## driver_profiles
```
    # note
    - Lưu thông tin profile và trạng thái vận hành của tài xế (role = Driver)
    - Quan hệ 1-1 với bảng `users`
    - Phân biệt `driver_group`: Đội xe nhà (internal) và Tài xế đối tác (partner) 
        > dùng cho thuật toán phát sóng cuốc xe ưu tiên
    - `current_lat` / `current_lng` được cập nhật realtime, đồng bộ song song với Redis để đảm bảo tốc độ
    
    ### cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - user_id (unsigned bigint, not null, FK → users.id)
    - full_name (varchar(100), not null) — Họ tên
    - driver_group_id (unsigned bigint, nullable, FK → driver_groups.id) — ID nhóm tài xế (nếu có)
    - driver_group_type (unsigned tinyint, not null) — Loại nhóm tài xế, lưu trong DriverGroupType
    
    - vehicle_type (unsigned tinyint, not null) — Loại xe, lưu trong VehicleType
    - vehicle_name (varchar(255), not null) — Tên xe, không được trùng với xe khác
    - vehicle_color (unsigned tinyint, not null) — Màu xe, lưu trong VehicleColor
    - vehicle_number (varchar(255), not null) — Số xe, không được trùng với xe khác
    
    - is_online (boolean, default false) — Đang bật nhận cuốc hay không
    - current_lat (decimal(10,7), nullable) — Vĩ độ hiện tại
    - current_lng (decimal(10,7), nullable) — Kinh độ hiện tại
    - status (unsigned tinyint, not null) — Trạng thái tài khoản, lưu trong `DriverStatus`
    - cooldown_until (timestamp, nullable) — Thời điểm hết hạn cooldown
    - cancel_count_today (unsigned smallint, default 0) — Số lần hủy trong ngày (có thể reset tùy theo nghiệp vụ)
    - created_at (timestamp)
    - updated_at (timestamp)
    
    ### index
    - UNIQUE (user_id)
    - INDEX (driver_group_type, is_online, status) — Dùng cho thuật toán dispatch đơn sau này
    - INDEX (status, cooldown_until)
    - INDEX (vehicle_type)
```

# ---G2: Service

# ---G5: System - Shared
**Các enums trong module này**

### FileDisk
```
1: Public
2: Private
```

### FileableType
```
1: Avatar (Ảnh đại diện)
2: Driver_Review_Application_CCCD_Front (Hồ sơ duyệt tài xế CCCD trước)
3: Driver_Review_Application_CCCD_Back (Hồ sơ duyệt tài xế CCCD sau)
4: Driver_Review_Application_License (Hồ sơ duyệt tài xế giấy phép)
5: Driver_Review_Application_Vehicle_Registration (Hồ sơ duyệt xe)
...
```

## files
    # note
    - Bảng files lưu trữ thông tin các tệp tin được quản lý bởi hệ thống.
    - Tự định nghĩa relation thông qua `fileable_type` và `fileable_id`

    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - name (varchar(255), not null) -- tên tệp tin
    - real_name (varchar(255), not null) -- tên tệp tin gốc
    - path (varchar, not null) -- đường dẫn tệp tin
    - disk (unsigned tinyint, not null) -- đĩa lưu trữ (trong enum FileDisk)
    - size (unsigned bigint, not null) -- kích thước tệp tin (byte)
    - mime_type (varchar(50), not null) -- loại tệp: image/jpeg, ...
    - fileable_type (unsigned tinyint, not null) -- loại tệp tin (morphs) (trong enum FileableType)
    - fileable_id (unsigned bigint, not null) -- id của tệp tin (morphs) (FK) tới các bảng quan hệ dựa trên fileable_type

    - created_at (timestamp)
    - updated_at (timestamp)

    ### index
    - index(fileable_type, fileable_id) -- index cho polymorphic relation
    - timestamps
