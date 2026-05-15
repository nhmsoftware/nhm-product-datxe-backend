# Notification Module

Module quản lý thông báo người dùng cho hệ thống NHM Đặt Xe.

## Tính năng (UC-126)
- Xem danh sách thông báo theo phân loại (Khuyến mãi, Đơn hàng, Hệ thống).
- Đánh dấu đã đọc thông báo.
## Kiến trúc
- **Modular DDD**: Tuân thủ cấu trúc layer Interface -> Service -> Repository.
- **Event-Driven**: Phát event `NotificationReadStatusUpdated` khi có thay đổi trạng thái đọc.
- **Realtime**: Tích hợp Redis Pub/Sub để đẩy dữ liệu sang Node.js Realtime Server.

## Tính năng
### UC-126: View Notifications
- Xem danh sách thông báo theo phân loại (Khuyến mãi, Đơn hàng, Hệ thống).
- Đánh dấu đã đọc thông báo.
- Đánh dấu đã đọc tất cả.
- Xóa thông báo.
- Hỗ trợ realtime thông báo mới và badge số lượng chưa đọc qua Socket.io.

### UC-127: Receive Push Notification
- Lưu trữ và cập nhật FCM/Device Token cho người dùng.
- Tự động hủy đăng ký token khi người dùng logout.
- Gửi Push Notification (Firebase/Mock) khi có các sự kiện:
    - Tài xế nhận chuyến, khách hủy chuyến (Ride).
    - Có thông báo hệ thống mới (Global).
    - Cập nhật số dư ví (Finance).

## API Endpoints
### View Notifications
- `GET /api/v1/notifications`: Lấy danh sách thông báo (phân trang).
- `POST /api/v1/notifications/{id}/read`: Đánh dấu đã đọc.
- `POST /api/v1/notifications/read-all`: Đánh dấu đã đọc tất cả.
- `DELETE /api/v1/notifications/{id}`: Xóa thông báo.

### Push Notifications
- `POST /api/v1/notifications/update-token`: Cập nhật Device Token.
- `POST /api/v1/auth/logout`: (Mở rộng) Hủy đăng ký token khi logout.

## Realtime Events (Socket.io)
- `notification.created`: Khi có thông báo mới.
- `notification.unread_count_updated`: Khi số lượng chưa đọc thay đổi.
