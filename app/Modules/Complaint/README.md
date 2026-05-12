# Complaint Module

Module quản lý khiếu nại (Complaints) của hệ thống NHM Đặt Xe.

## Tính năng
- Quản lý danh sách khiếu nại (Admin).
- Xem chi tiết khiếu nại kèm thông tin Booking/Order liên quan.
- Xử lý khiếu nại (Hoàn tiền, Cảnh báo tài xế, Cảnh báo khách hàng, Từ chối, Yêu cầu thêm thông tin).

## Use Cases
- **UC-108**: Handle Complaints.

## Các lớp chính
- `ComplaintController`: Điều phối API.
- `ComplaintService`: Xử lý business logic.
- `ComplaintRepository`: Truy xuất dữ liệu.
- `NotifyRealtimeOnComplaintHandled`: Gửi thông báo realtime qua Redis -> Node.js.

## Database
- `complaints`: Lưu thông tin khiếu nại.
- `user_violations`: Lưu lịch sử vi phạm/cảnh báo (nằm trong RiskManagement nhưng được Complaint module sử dụng).
