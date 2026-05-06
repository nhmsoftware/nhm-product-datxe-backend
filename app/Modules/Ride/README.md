# 🚗 Module Ride (Quản lý Chuyến xe)

Module này chịu trách nhiệm xử lý toàn bộ vòng đời của một chuyến xe, từ lúc khách hàng tìm xe, tính giá, đặt xe cho đến khi hoàn thành hoặc hủy chuyến.

## 🏗 Kiến trúc API mới (Stateless & One-shot)

Để tối ưu trải nghiệm người dùng và giảm độ trễ, luồng đặt xe đã được refactor rút gọn:

1.  **Bước 1 (UC-09):** Khách hàng chọn điểm đón/đến -> Gọi API lấy danh sách loại xe kèm giá.
2.  **Bước 2 (UC-12):** Khách hàng chọn loại xe & bấm đặt -> Gọi API đặt xe (Hệ thống tự tạo chuyến & tìm tài xế ngay lập tức).

---

## 🔐 Authentication & Headers
- **Prefix:** `/api/v1/ride`
- **Auth:** Bearer Token (Sanctum)
- **Content-Type:** `application/json`

---

## 📡 API Reference (Customer Side)

### 1. Lấy danh sách loại xe & giá ước tính (UC-09)
Dùng để hiển thị các lựa chọn xe (Bike, Car 4, Car 7...) kèm giá tiền tương ứng dựa trên tọa độ.

- **Endpoint:** `GET /vehicles`
- **Query Params:**
    - `pickup_lat` (float, required): Vĩ độ điểm đón.
    - `pickup_lng` (float, required): Kinh độ điểm đón.
    - `destination_lat` (float, required): Vĩ độ điểm đến.
    - `destination_lng` (float, required): Kinh độ điểm đến.
- **Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "type": 1,
            "name": "Xe máy (Bike)",
            "estimated_price": 15000,
            "icon": "bike-icon-url"
        },
        {
            "type": 2,
            "name": "Ô tô 4 chỗ (Car)",
            "estimated_price": 45000,
            "icon": "car-icon-url"
        }
    ]
}
```

### 2. Xác nhận đặt xe (UC-12)
Tạo chuyến xe chính thức và bắt đầu tìm kiếm tài xế.

- **Endpoint:** `POST /book`
- **Body:**
```json
{
    "pickup_address": "Số 1 Đào Duy Anh, Hà Nội",
    "pickup_lat": 21.0072,
    "pickup_lng": 105.8428,
    "destination_address": "Vincom Mega Mall Ocean Park",
    "destination_lat": 20.9944,
    "destination_lng": 105.9458,
    "vehicle_type": 2,
    "expected_price": 45000,
    "voucher_codes": ["GIAM20K"],
    "note": "Đón ở sảnh A"
}
```
- **Response (200):** Trả về chi tiết chuyến xe và trạng thái `PENDING`.

### 3. Xem chi tiết giá ước tính (UC-10)
Dùng để xem breakdown giá (giá gốc, phí cầu đường, giảm giá...) sau khi đã chọn xe (dùng `rideId` từ bước /book nếu cần xem lại).

- **Endpoint:** `GET /{rideId}/price`
- **Response (200):** Trả về `fare_breakdown` chi tiết.

### 4. Áp dụng/Xóa Voucher (UC-11)
- **Áp dụng:** `POST /{rideId}/voucher` (Body: `voucher_code`)
- **Xóa:** `DELETE /{rideId}/voucher`

### 5. Hủy chuyến xe (UC-15)
- **Endpoint:** `POST /{rideId}/cancel`
- **Body:** `{"reason": "Thay đổi kế hoạch"}`

---

## 📡 API Reference (Driver Side)

- **Prefix:** `/api/v1/driver`
- **Các API quan trọng:**
    - `GET /scheduled-rides`: Xem danh sách chuyến đặt trước.
    - `POST /scheduled-rides/{id}/accept`: Nhận chuyến đặt trước.
    - `GET /managed-rides`: Danh sách chuyến đã nhận.

---

## 🔄 Luồng Realtime (Event-Driven)

Khi có thay đổi trạng thái chuyến xe, Backend Laravel sẽ phát Domain Event qua Redis. Node.js Server sẽ nhận và broadcast tới client qua Socket.io.

### Các Event chính (Socket.io):
1.  **`ride.booked`**: Thông báo có chuyến mới (cho Driver).
2.  **`ride.accepted`**: Tài xế đã nhận chuyến (cho Customer).
3.  **`ride.arrived`**: Tài xế đã đến điểm đón (cho Customer).
4.  **`tracking.location.updated`**: Cập nhật vị trí tài xế trên bản đồ (Realtime GPS).

### Socket Room:
- Tham gia room: `socket.emit('join:ride', rideId)`
- Nhận tọa độ GPS (chỉ dành cho App Tài xế): `socket.emit('driver:location', {ride_id, lat, lng, heading})`

---

## 🛠 Self-Audit Checklist cho Developer
- [ ] Luôn kiểm tra `is_phone_verified` trước khi cho phép đặt xe.
- [ ] Xử lý lỗi `409 (Conflict)` khi giá thay đổi so với `expected_price`.
- [ ] Lắng nghe event `tracking.location.updated` để vẽ xe chạy trên bản đồ.
- [ ] Luôn gọi `join:ride` ngay khi vào màn hình chi tiết chuyến xe.
