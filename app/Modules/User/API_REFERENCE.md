# 📱 API Reference — Module User / Auth

> Base URL: `https://your-domain.com/api`  
> Content-Type: `application/json`  
> Auth: `Authorization: Bearer {token}` (Sanctum)

---

## Tổng quan luồng đăng ký

```
1. POST /auth/send-otp      → Gửi OTP về số điện thoại (type=1)
2. POST /auth/verify-otp    → Xác minh OTP
3. POST /auth/register      → Tạo tài khoản + nhận token
```

## Tổng quan luồng đăng nhập

```
1. POST /auth/login         → Nhận token ngay
```

---

## 1. Gửi OTP

**`POST /api/auth/send-otp`**

### Request
```json
{
  "phone": "0901234567",
  "type": 1
}
```

| Field  | Type    | Required | Mô tả |
|--------|---------|----------|-------|
| `phone`| string  | ✅       | SĐT Việt Nam (10 số, bắt đầu bằng 0[3-9]) |
| `type` | integer | ✅       | `1` = Đăng ký · `2` = Quên mật khẩu |

### Response `200`
```json
{
  "message": "Mã OTP đã được gửi tới số điện thoại của bạn."
}
```

### Errors
| Status | Code                    | Mô tả |
|--------|-------------------------|-------|
| `422`  | `VALIDATION_ERROR`      | Phone sai định dạng |
| `429`  | -                       | Gửi quá nhiều (chờ 60 giây) |

---

## 2. Xác minh OTP

**`POST /api/auth/verify-otp`**

### Request
```json
{
  "phone": "0901234567",
  "otp":   "123456",
  "type":  1
}
```

### Response `200`
```json
{
  "message": "Xác minh OTP thành công."
}
```

### Errors
| Status | Code                        | Mô tả |
|--------|-----------------------------|-------|
| `400`  | `OTP_EXPIRED`               | OTP hết hạn (5 phút) |
| `400`  | `OTP_INVALID`               | Mã sai / đã dùng |
| `429`  | `OTP_TOO_MANY_ATTEMPTS`     | Nhập sai quá 5 lần |

---

## 3. Đăng ký

**`POST /api/auth/register`**

### Request
```json
{
  "phone":                 "0901234567",
  "password":              "Secret@123",
  "password_confirmation": "Secret@123",
  "full_name":             "Nguyễn Văn A",

  "device_id":    "abc-device-uuid",
  "device_token": "fcm_token_here",
  "device_type":  "android"
}
```

| Field                   | Type   | Required | Mô tả |
|-------------------------|--------|----------|-------|
| `phone`                 | string | ✅       | SĐT Việt Nam |
| `password`              | string | ✅       | Tối thiểu 8 ký tự |
| `password_confirmation` | string | ✅       | Phải khớp password |
| `full_name`             | string | ✅       | Tối đa 100 ký tự |
| `device_id`             | string | ❌       | ID thiết bị (để push notification) |
| `device_token`          | string | ❌       | FCM/APNs token |
| `device_type`           | string | ❌       | `android` \| `ios` |

### Response `201`
```json
{
  "data": {
    "token":      "1|abcdefg...",
    "token_type": "Bearer",
    "user": {
      "id":          1,
      "phone":       "0901234567",
      "email":       null,
      "role":        2,
      "role_label":  "Khách hàng",
      "is_verified": false,
      "profile": {
        "full_name": "Nguyễn Văn A",
        "gender":    1
      },
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

### Errors
| Status | Code                  | Mô tả |
|--------|-----------------------|-------|
| `409`  | `USER_ALREADY_EXISTS` | SĐT đã đăng ký |
| `422`  | `VALIDATION_ERROR`    | Dữ liệu không hợp lệ |

---

## 4. Đăng nhập

**`POST /api/auth/login`**

### Request
```json
{
  "phone":        "0901234567",
  "password":     "Secret@123",
  "device_id":    "abc-device-uuid",
  "device_token": "fcm_token_here",
  "device_type":  "android"
}
```

### Response `200`
```json
{
  "data": {
    "token":      "2|xyz...",
    "token_type": "Bearer",
    "user": {
      "id":          1,
      "phone":       "0901234567",
      "role":        2,
      "role_label":  "Khách hàng",
      "is_verified": false,
      "profile": {
        "full_name": "Nguyễn Văn A",
        "gender":    1
      },
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

### Errors
| Status | Code          | Mô tả |
|--------|---------------|-------|
| `401`  | `AUTH_FAILED` | Sai SĐT hoặc mật khẩu |
| `429`  | -             | Brute-force (5 lần/phút) |

---

## 5. Thông tin cá nhân (Authenticated)

**`GET /api/auth/me`**  
Header: `Authorization: Bearer {token}`

### Response `200`
```json
{
  "data": {
    "id":          1,
    "phone":       "0901234567",
    "email":       null,
    "role":        2,
    "role_label":  "Khách hàng",
    "is_verified": false,
    "profile": {
      "full_name": "Nguyễn Văn A",
      "gender":    1
    },
    "created_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

### Errors
| Status | Code             | Mô tả |
|--------|------------------|-------|
| `401`  | `UNAUTHENTICATED`| Token không hợp lệ / hết hạn |

---

## 6. Đăng xuất (Authenticated)

**`POST /api/auth/logout`**  
Header: `Authorization: Bearer {token}`

### Request (optional)
```json
{
  "logout_all": false
}
```

| Field        | Type    | Default | Mô tả |
|--------------|---------|---------|-------|
| `logout_all` | boolean | `false` | `true` = Thu hồi tất cả token trên mọi thiết bị |

### Response `200`
```json
{
  "message": "Đăng xuất thành công."
}
```

---

## Enum Reference

### UserRole
| Value | Label |
|-------|-------|
| `1`   | Admin |
| `2`   | Khách hàng |
| `3`   | Tài xế |
| `4`   | Quán ăn |

### UserOtpType
| Value | Label |
|-------|-------|
| `1`   | Verify_Register (dùng khi đăng ký) |
| `2`   | Verify_Forgot_Password (dùng khi quên mật khẩu) |

### Gender
| Value | Label |
|-------|-------|
| `1`   | Nam |
| `2`   | Nữ |
| `3`   | Khác |

---

## Error Response Format

Tất cả lỗi đều theo cấu trúc sau:

```json
{
  "message": "Mô tả lỗi bằng tiếng Việt",
  "code":    "ERROR_CODE_IN_ENGLISH"
}
```

Riêng `422 VALIDATION_ERROR`:
```json
{
  "message": "Dữ liệu không hợp lệ.",
  "code":    "VALIDATION_ERROR",
  "errors": {
    "phone":    ["Số điện thoại không đúng định dạng Việt Nam."],
    "password": ["Mật khẩu phải có ít nhất 8 ký tự."]
  }
}
```

---

## Setup Notes (Backend)

### 1. Đăng ký ServiceProvider
Thêm vào `config/app.php` → `providers`:
```php
Modules\User\Infrastructure\Providers\UserServiceProvider::class,
```

### 2. Đăng ký Exception Handler
Trong `app/Exceptions/Handler.php`:
```php
use Modules\User\Presentation\Exceptions\UserModuleExceptionHandler;

public function register(): void
{
    $this->renderable(
        fn(Throwable $e, Request $r) =>
            (new UserModuleExceptionHandler())->handle($e, $r)
    );
}
```

### 3. Sanctum
Đảm bảo đã cài và config Laravel Sanctum:
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 4. Chạy migrations
```bash
php artisan migrate
```
