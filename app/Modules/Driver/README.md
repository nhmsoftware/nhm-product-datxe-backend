# Driver Module

> Quản lý đăng ký & vận hành Tài xế.
> Tuân thủ **Modular DDD-Inspired Layered Architecture** — tham chiếu từ Ride module.

---

## Use Cases

| UC | Tên | Endpoint | Status |
|----|-----|----------|--------|
| UC-30 | Register Driver — Bước 1 (Gửi OTP) | `POST /api/v1/driver/register/send-otp` | ✅ |
| UC-30 | Register Driver — Bước 2 (Nộp hồ sơ) | `POST /api/v1/driver/register/submit` | ✅ |

---

## Cấu trúc thư mục

```
app/Modules/Driver/
├── DTO/
│   ├── RegisterDriverInitiateDTO.php     ← UC-30 Step 1: validate thông tin
│   └── RegisterDriverSubmitDTO.php       ← UC-30 Step 2: OTP + 8 files
├── Events/
│   └── DriverApplicationSubmitted.php   ← Dispatch sau khi tạo hồ sơ
├── Http/
│   ├── Controllers/DriverController.php
│   └── Requests/
│       ├── RegisterDriverInitiateRequest.php
│       └── RegisterDriverSubmitRequest.php
├── Interfaces/
│   ├── DriverRegistrationRepositoryInterface.php
│   ├── FileRecordRepositoryInterface.php
│   └── DriverRegistrationServiceInterface.php
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
    └── DriverRegistrationService.php
```

---

## UC-30 Flow

```
POST /api/v1/driver/register/send-otp
  → RegisterDriverInitiateRequest  (validate cá nhân + phương tiện + vehicle_year)
  → RegisterDriverInitiateDTO
  → DriverRegistrationService::initiateRegistration()
     ├── findById() → $user->isDriver() [A9]
     ├── findActiveApplicationByUser() [A9/pending]
     ├── existsByCitizenId() [A6]
     ├── existsByVehicleNumber() [A7]
     └── generateOtp() — throttle 3 phút, tối đa 5/ngày [A12]

POST /api/v1/driver/register/submit  (multipart/form-data)
  → RegisterDriverSubmitRequest    (validate OTP + tất cả fields + 8 files)
  → RegisterDriverSubmitDTO
  → DriverRegistrationService::submitRegistration()
     ├── verifyOtp() — NGOÀI transaction [A10, A11]
     └── execute(useTransaction: true)
         ├── Double-check business rules [atomic]
         ├── createDriverApplication() → user_review_applications (Pending)
         ├── storeAllDocuments() → files table (local disk)
         ├── markLatestAsUsed()
         └── event(DriverApplicationSubmitted)
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
| `User` | `UserRepositoryInterface` | `findById()` để fetch User + `isDriver()` check (A9) |
| `Auth` | `AuthOtpRepositoryInterface` | OTP generate, verify, throttle |
| `Ride` | `VehicleType` enum | Dùng chung enum — cùng backing values trong DB |

---

## Database tables (không cần migration mới)

| Bảng | Dùng cho |
|------|---------|
| `user_review_applications` | Hồ sơ KYC (snapshot_data jsonb) |
| `files` | Tài liệu đính kèm |
| `user_otp` | OTP type=5 (VERIFY_DRIVER_REGISTER) |
| `driver_profiles` | Tạo sau khi Admin duyệt (UC tương lai) |
