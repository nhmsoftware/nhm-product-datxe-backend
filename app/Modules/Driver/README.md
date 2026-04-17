# Driver Module

> Quản lý đăng ký & vận hành Tài xế.
> Tuân thủ **Modular DDD-Inspired Layered Architecture** — tham chiếu từ Ride module.

---

## Use Cases

| UC | Tên | Endpoint | Status |
|----|-----|----------|--------|
| UC-30 | Register Driver — Nộp hồ sơ | `POST /api/v1/driver/register/submit` | ✅ |
| UC-31 | Go Online / Go Offline | `PUT /api/v1/driver/status` | ✅ |

---

## Cấu trúc thư mục

```
app/Modules/Driver/
├── DTO/
│   ├── RegisterDriverSubmitDTO.php       ← UC-30: Thông tin + 8 files
│   └── ToggleOnlineStatusDTO.php         ← UC-31
├── Events/
│   └── DriverApplicationSubmitted.php   ← Dispatch sau khi tạo hồ sơ
├── Http/
│   ├── Controllers/
│   │   ├── DriverController.php
│   │   └── DriverOperationController.php
│   └── Requests/
│       ├── RegisterDriverSubmitRequest.php
│       └── ToggleOnlineStatusRequest.php
├── Interfaces/
│   ├── DriverRegistrationRepositoryInterface.php
│   ├── FileRecordRepositoryInterface.php
│   ├── DriverRegistrationServiceInterface.php
│   └── DriverOperationServiceInterface.php
├── Model/
│   ├── Enums/
│   │   ├── KycType.php        (1=Driver, 2=Merchants, 3=Change_Vehicle)
│   │   ├── KycStatus.php      (1=Pending, 2=Approved, 3=Rejected)
│   │   ├── VehicleColor.php   (0=Other … 9=White)
│   │   ├── FileableType.php   (1=Avatar, 2–9=Driver KYC docs)
│   │   └── FileDisk.php       (1=Public, 2=Private)
│   ├── UserReviewApplication.php  ← Aggregate Root — maps user_review_applications
│   └── FileRecord.php             ← maps files table
├── Providers/
│   └── DriverServiceProvider.php
├── Repositories/
│   ├── DriverRegistrationRepository.php  ← 1 repo = UserReviewApplication
│   └── FileRecordRepository.php          ← 1 repo = FileRecord
├── Routes/
│   └── api.php
└── Services/
    ├── DriverRegistrationService.php
    └── DriverOperationService.php
```

---

## UC-30 Flow

> [!NOTE]
> Tài xế đã được xác thực mã OTP ở cấp độ tài khoản người dùng trước khi truy cập tính năng này.

```
POST /api/v1/driver/register/submit  (multipart/form-data)
  → RegisterDriverSubmitRequest    (validate tất cả fields + 8 files)
  → RegisterDriverSubmitDTO
  → DriverRegistrationService::submitRegistration()
     ├── double check User isActive() + !isDriver()
     ├── check active application pending
     ├── check CCCD / Biển số trùng
     └── execute(useTransaction: true)
         ├── createDriverApplication() → user_review_applications (Pending)
         ├── storeAllDocuments() → files table (local disk)
         └── event(DriverApplicationSubmitted)
```

## UC-31 Flow

```
PUT /api/v1/driver/status
  → ToggleOnlineStatusRequest        (boolean is_online, lat/lng nếu true)
  → ToggleOnlineStatusDTO
  → DriverOperationService::toggleOnlineStatus()
     ├── check User isActive()
     ├── DriverProfileRepository::findByUserId() [A1]
     ├── check profile->status (ACTIVE/BANNED/COOLDOWN) [A5]
     ├── RideRepository::hasActiveRideByDriver() [A3]
     └── DriverProfileRepository::updateOnlineStatus()
```

---

## Tài liệu bắt buộc (UC-30)

| Field key | FileableType | Mô tả |
|-----------|-------------|-------|
| `cccd_front` | 2 | CCCD mặt trước |
| `cccd_back` | 3 | CCCD mặt sau |
| `driver_license` | 4 | Bằng lái xe |
| `vehicle_reg` | 5 | Giấy đăng ký xe |
| `criminal_record` | 6 | Lý lịch tư pháp |
| `health_cert` | 7 | Giấy khám sức khỏe |
| `portrait` | 8 | Ảnh chân dung |
| `insurance` | 9 | Bảo hiểm TNDS |

> **Format:** JPEG / JPG / PNG / PDF — tối đa 5MB/file.
> **Storage:** `local` disk tại `driver-kyc/{applicationId}/`.

---

## Phụ thuộc module khác

| Module | Thứ gì | Lý do |
|--------|--------|-------|
| `User` | `UserRepositoryInterface`, `DriverProfileRepositoryInterface` | Quản lý Profile, tài khoản và trạng thái hoạt động online / offline. |
| `Ride` | `RideRepositoryInterface`, `VehicleType` | Kiểm tra chuyến xe đang chạy (A3), map Enum thông tin xe |

---

## Database tables (không cần migration mới)

| Bảng | Dùng cho |
|------|---------|
| `user_review_applications` | Hồ sơ KYC (snapshot_data jsonb) |
| `files` | Tài liệu đính kèm |
| `driver_profiles` | Tạo sau khi Admin duyệt (UC tương lai) |
