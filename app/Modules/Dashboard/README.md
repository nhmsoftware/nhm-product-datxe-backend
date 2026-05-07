# Dashboard Module

## Mục đích
Module Dashboard cung cấp các API để thống kê số liệu và hiển thị trên Dashboard của Admin.

## Chức năng
- **UC-76:** Xem Dashboard (Thống kê số lượng người dùng, đơn hàng, doanh thu, tài xế, quán ăn).

## Kiến trúc
Tuân thủ Modular DDD-Inspired Layered Architecture.

- `DashboardService` kết nối tới các Module khác (User, Ride) thông qua Interface (`UserRepositoryInterface`, `RideRepositoryInterface`, v.v.) để tổng hợp dữ liệu thống kê. Không trực tiếp gọi DB.

## API Endpoints
- `GET /api/v1/admin/dashboard` - Lấy thống kê hệ thống (Requires: Bearer Token).
