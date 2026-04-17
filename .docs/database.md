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
2: Verify_Login (Xác thực đăng nhập)
3: Verify_Forgot_Password (Xác thực quên mật khẩu)
4: Change_Profile (Xác nhận thay đổi thông tin)
5: Verify_Driver_Register (Xác thực đăng ký tài xế — UC-30)
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
    - phone (varchar(50), nullable) -- Số điện thoại
    - email (varchar(255), nullable) - Email
    - is_verified (boolean default false) - Trạng thái xác thực đăng nhập
    - is_phone_verified (boolean default false) - Trạng thái xác thực số điện thoại 
    - google_id (varchar(255), nullable) - ID của người dùng trên provider Google
    - apple_id (varchar(255), nullable) - ID của người dùng trên provider Apple
    - password (varchar(255), not null) - Mật khẩu, phải được mã hóa
    - role (unsigned tinyint, not null) - Vai trò, lưu trữ trong UserRole
    - is_active (boolean, default true) - Trạng thái tài khoản có hoạt động hay không
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
    - used_at -- (timestamp, nullable) -- thời gian dùng OTP
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
    - gender (unsigned tinyint, nullable) — Giới tính, lưu trữ trong Gender
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

# ---G3: Finance

## Các enums trong module này

### VoucherServiceType
```
1: Ride (Chuyến đi)
2: Food (Giao đồ ăn)
3: Both (Cả hai)
```

### VoucherDiscountType
```
1: Fixed (Giảm giá cố định)
2: Percent (Giảm giá theo phần trăm)
```

### RewardTransactionType
```
1: Earn (Tích điểm từ đơn hàng)
2: Redeem (Tiêu điểm)
3: Expire (Điểm hết hạn)
```

## vouchers
```
    # note
    - Bảng vouchers quản lý thông tin các mã giảm giá của hệ thống.

    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - code (varchar(255), unique) -- Mã voucher hiển thị
    - service_type (unsigned tinyint) -- Loại dịch vụ áp dụng (VoucherServiceType)
    - discount_type (unsigned tinyint) -- Loại giảm giá (VoucherDiscountType)
    - discount_value (decimal 15,2) -- Giá trị giảm (số tiền hoặc %)
    - min_order_amount (decimal 15,2, default 0) -- Giá trị đơn tối thiểu để áp dụng
    - max_discount_amount (decimal 15,2, nullable) -- Giảm tối đa (dùng cho loại Percent)
    - valid_from (timestamp) -- Ngày bắt đầu
    - valid_until (timestamp) -- Ngày kết thúc
    - total_usage_limit (unsigned integer, nullable) -- Tổng số lượt sử dụng tối đa
    - used_count (unsigned integer, default 0) -- Số lượt đã dùng
    - is_active (boolean, default true)
    - description (text, nullable)
    - timestamps
    - softDeletes

    # index
    - index(service_type, is_active, valid_until)
```

## voucher_wallets
```
    # note
    - Bảng voucher_wallets quản lý các voucher mà khách hàng đã lưu, và trạng thái sử dụng của từng mã.

    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - customer_id (unsigned bigint, FK -> users)
    - voucher_id (unsigned bigint, FK -> vouchers)
    - saved_at (timestamp)
    - used_at (timestamp, nullable) -- Nếu null là chưa dùng
    - timestamps
    - softDeletes

    # index
    - unique(customer_id, voucher_id)
    - index(customer_id)
```

## reward_wallets
```
    # note
    - Bảng reward_wallets quản lý tổng số điểm thưởng hiện tại của mỗi khách hàng.
    - Mỗi khách hàng chỉ có 1 ví điểm thưởng.

    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - customer_id (unsigned bigint, FK -> users) -- Khách hàng sở hữu ví
    - balance (unsigned integer, default 0) -- Số điểm khả dụng hiện tại
    - total_earned (unsigned integer, default 0) -- Tổng điểm đã tích lũy từ trước đến nay
    - total_used (unsigned integer, default 0) -- Tổng điểm đã tiêu
    - timestamps
    - softDeletes

    # index
    - unique(customer_id)
```

## reward_transactions
```
    # note
    - Bảng reward_transactions ghi lại lịch sử giao dịch cộng, trừ điểm của khách hàng.
    - Sử dụng reference_type và reference_id để liên kết đến giao dịch gốc (chuyến đi, đơn hàng...).

    # cấu trúc
    - id (unsigned bigint, auto increment, primary key)
    - customer_id (unsigned bigint, FK -> users) -- Khách hàng thực hiện giao dịch
    - type (unsigned tinyint) -- Loại giao dịch (RewardTransactionType: Earn, Redeem, Expire)
    - points (integer) -- Số điểm giao dịch (dương cho tích, âm cho sử dụng/hết hạn)
    - description (varchar 255) -- Ghi chú hiển thị cho user (Vd: Tích điểm từ đơn hàng XE-123)
    - reference_type (varchar 255, nullable) -- Loại entity liên quan (Morphs type: Ride, FoodOrder)
    - reference_id (unsigned bigint, nullable) -- ID của entity liên quan (Morphs ID)
    - timestamps
    - softDeletes

    # index
    - index(customer_id, type)
    - index(reference_type, reference_id)
```

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
2: Driver_Review_CCCD_Front (Hồ sơ tài xế - CCCD mặt trước)
3: Driver_Review_CCCD_Back (Hồ sơ tài xế - CCCD mặt sau)
4: Driver_Review_License (Hồ sơ tài xế - Bằng lái xe)
5: Driver_Review_Vehicle_Reg (Hồ sơ tài xế - Giấy đăng ký xe)
6: Driver_Review_Criminal_Record (Hồ sơ tài xế - Lý lịch tư pháp)
7: Driver_Review_Health_Cert (Hồ sơ tài xế - Giấy khám sức khỏe)
8: Driver_Review_Portrait (Ảnh chân dung tài xế)
9: Driver_Review_Insurance (Bảo hiểm trách nhiệm dân sự)
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
