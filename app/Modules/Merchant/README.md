# Merchant Module

## Overview
Module này quản lý toàn bộ nghiệp vụ liên quan đến Merchant (Quán ăn/Đối tác cửa hàng), bao gồm đăng ký, quản lý hồ sơ, và các hoạt động vận hành của Merchant.

## Use Cases implemented
- **UC-52 Register Merchant**: Cho phép người dùng đăng ký trở thành Merchant.
- **UC-53 Manage Store**: Quản lý thông tin vận hành cửa hàng.
- **UC-54 Set Opening Hours**: Thiết lập giờ mở cửa/đóng cửa chi tiết theo tuần (hỗ trợ bán xuyên đêm).
- **UC-55 Set Store Status**: Thay đổi trạng thái Đóng/Mở tức thời (override lịch hoạt động).
- **UC-56 Configure Commission**: Thay đổi gói chiết khấu/hoa hồng (Basic, Priority, Exclusive).
- **UC-45 Setup hours**: Thiết lập giờ mở cửa/đóng cửa (Cơ bản).
- **UC-46 Change status**: Thay đổi trạng thái hoạt động (Đóng/Mở).
- **UC-47 Configure discount**: Cấu hình % chiết khấu.
- **UC-61 Manage Combo**: Quản lý danh sách combo của cửa hàng.
- **UC-62 View Combo Detail**: Xem chi tiết thông tin combo.
- **UC-63 Add Combo**: Tạo mới combo cho cửa hàng.
- **UC-64 Update Combo**: Cập nhật thông tin combo.
- **UC-65 Delete Combo**: Xóa combo (xóa mềm).
- **UC-66 View total daily orders**: Xem tổng số đơn hàng trong ngày của cửa hàng.
- **UC-67 View daily revenue**: Xem tổng doanh thu trong ngày của cửa hàng.
- **UC-68 Toggle Availability**: Bật/tắt trạng thái bán của món ăn hoặc combo.
- **UC-69 Process Order**: Xử lý đơn hàng (Xác nhận, Từ chối, Chuẩn bị, Sẵn sàng).
- **UC-70 View Order Detail**: Xem chi tiết đơn hàng của Merchant.
- **UC-71 Accept Order**: Merchant xác nhận nhận đơn hàng.
- **UC-72 Reject Order**: Merchant từ chối đơn hàng kèm lý do.
- **UC-64 Mark Preparing**: Cập nhật đơn hàng đang trong quá trình chuẩn bị.
- **UC-73 Mark Ready**: Đánh dấu món đã sẵn sàng để tài xế đến lấy.
- **UC-75 Cancel Order**: Hủy đơn hàng (từ phía Merchant).
- **UC-74 Handle Cancellation**: Xử lý yêu cầu hủy từ khách hàng/hệ thống.
- **UC-76 Explore Nearby Merchants**: Tìm kiếm các quán ăn/nhà hàng lân cận dựa vào tọa độ GPS (vĩ độ, kinh độ) của khách hàng với công thức Haversine.
- **UC-77 View Merchant Menu**: Khách hàng xem chi tiết thực đơn gồm các danh mục và món ăn đang được bán của quán ăn (eager load size và topping).

## Architecture
Tuân thủ Modular DDD:
- **DTO**: Chứa các đối tượng chuyển đổi dữ liệu (`GetNearbyMerchantsDTO`, `MerchantFilterDTO`).
- **Services**: Chứa logic nghiệp vụ chính (`MerchantRegistrationService`, `MerchantStoreService`, `CustomerMerchantService`).
- **Repositories**: Xử lý truy vấn database thông qua Model `MerchantProfile` (`MerchantRepository`, `MenuRepository`).
- **Events**: Phát các sự kiện domain khi có thay đổi trạng thái quan trọng.

## API Endpoints
### Customer Endpoints (Explore)
- `GET /api/v1/customer/merchants`: Lấy danh sách các cửa hàng lân cận dựa vào vị trí hiện tại (vĩ độ, kinh độ), hỗ trợ tìm kiếm theo tên và lọc theo bán kính.
- `GET /api/v1/customer/merchants/{id}`: Xem chi tiết thông tin cửa hàng.
- `GET /api/v1/customer/merchants/{id}/menu`: Lấy thực đơn (Menu) chi tiết của cửa hàng bao gồm món ăn, các kích thước (sizes) và các loại toppings đi kèm.

### Registration
- `POST /api/v1/merchant/send-otp`: Gửi OTP xác thực số điện thoại để đăng ký.
- `POST /api/v1/merchant/verify-otp`: Xác thực mã OTP.
- `POST /api/v1/merchant/register`: Gửi hồ sơ đăng ký Merchant (sau khi verify OTP).

### Store Management
- `GET /api/v1/merchant/store`: Lấy thông tin cửa hàng hiện tại.
- `PUT /api/v1/merchant/store/status`: Cập nhật trạng thái đóng/mở.
- `PUT /api/v1/merchant/store/hours`: Cập nhật giờ hoạt động.
- `PUT /api/v1/merchant/store/discount`: Cập nhật cấu hình chiết khấu.
- `GET /api/v1/merchant/store/stats/daily-orders`: Xem tổng số đơn hàng hôm nay (UC-66).
- `GET /api/v1/merchant/store/stats/daily-revenue`: Xem tổng doanh thu hôm nay (UC-67).
- `PATCH /api/v1/merchant/menu/items/{id}/status`: Bật/tắt trạng thái món ăn (UC-68).
- `PATCH /api/v1/merchant/combos/{id}/status`: Bật/tắt trạng thái combo (UC-68).

### Order Management
- `GET /api/v1/merchant/orders/{id}`: Xem chi tiết đơn hàng (UC-70).
- `POST /api/v1/merchant/orders/{id}/accept`: Nhận đơn hàng (UC-71).
- `POST /api/v1/merchant/orders/{id}/reject`: Từ chối đơn hàng (UC-72).
- `POST /api/v1/merchant/orders/{id}/preparing`: Đang chuẩn bị (UC-64).
- `POST /api/v1/merchant/orders/{id}/ready`: Sẵn sàng giao (UC-73).
- `POST /api/v1/merchant/orders/{id}/cancel`: Hủy đơn hàng (UC-75).
- `POST /api/v1/merchant/orders/{id}/cancellation/handle`: Xử lý yêu cầu hủy (UC-74).

## Flow Đăng ký Merchant (UC-52)
1. User gọi API `send-otp`.
2. User gọi API `verify-otp`.
3. User gọi API `register` kèm theo thông tin cửa hàng và các tài liệu (đã upload trước đó).
4. Hệ thống tạo hồ sơ với trạng thái `Pending` và chờ Admin duyệt.
