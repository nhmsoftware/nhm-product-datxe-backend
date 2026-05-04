# Ride Module

## Đặc Tả Module
Module `Ride` chuyên xử lý các nghiệp vụ liên quan đến việc đặt xe, quản lý vòng đời chuyến đi và các tương tác của người dùng trong quá trình di chuyển. Đây là core business logic của ứng dụng.

## Kiến Trúc
Được xây dựng theo **Modular DDD-Inspired Layered Architecture**, tuân thủ hoàn toàn các tiêu chuẩn được định nghĩa tại `ai-context.md`.

### Cấu Trúc Thư Mục
- `Http/Controllers`: Chứa `RideController` xử lý các API endpoints (nhận yêu cầu từ web/app). Sử dụng FormRequests để validate dữ liệu đầu vào.
- `Http/Requests`: Chứa các FormRequest phục vụ validation cho API.
- `Services`: Chứa `RideService` – trái tim của module, thực hiện mọi business logic. Chỉ nhận các DTO từ Controller, tương tác với Repositories, API mở rộng (như MapService, PricingService), và trả về `ServiceReturn`. Các class Service áp dụng pattern `final`.
- `Interfaces`: Chứa các hợp đồng (interfaces) cho Repositories và Services (`RideServiceInterface`, `RideRepositoryInterface`), giúp module đảm bảo Dependency Inversion.
- `Repositories`: Chứa `RideRepository`, tương tác với DB qua Model. Thực hiện các logic queries đặc thù, bao gồm các domain methods như `applyVoucher()`, `clearVoucher()`.
- `Model`: Chứa `Ride` Model và các quan hệ. Định nghĩa các hằng số.
- `Model/Enums`: Các tập hợp Enum như `RideStatus`, `VehicleType`. Chứa các **domain methods** như `getLabel()`, `getCapacity()`, nhằm tránh leak logic vào Service.
- `DTO`: Data Transfer Objects dùng để giao tiếp an toàn giữa Controller và Service. Được khởi tạo bằng Immutable pattern, class là `final` và dùng factory method (`fromRequest`, `fromVehicleType`...).

## Chức Năng (Use Cases - UC)
- **UC-08 (Create Draft Ride):** Tạo bản nháp cho 1 chuyến xe khi người dùng đã nhập Điểm đón & Điểm đến thông qua validate Goong Map. Kiểm tra xem user đã xác thực SĐT hay chưa (A13 Flow).
- **UC-09 (Select Vehicle Options):** Lấy và hiển thị các loại xe (Bike, Car 4 seats, v.v.), sức chứa và ước tính thời gian chờ.
- **UC-10 (Price Estimate Detail):** Tính toán và trả về chi tiết giá cước dựa trên hệ số mở cửa, phụ phí kẹt xe/thời tiết (thông qua Pricing Module).
- **UC-11 (Apply/Remove Voucher):** Cho phép áp dụng khuyến mãi nếu đủ điều kiện, giảm trực tiếp vào `total_price` tại Ride Draft. Có thể clear để về lại giá trị gốc.
- **UC-13 (Track Driver):** Sau khi tài xế nhận chuyến, customer có thể lấy snapshot tracking, theo dõi ETA, vị trí hiện tại, trạng thái “đang đến đón khách”, xử lý các nhánh mất GPS, mất tracking, tài xế đến nơi hoặc hủy chuyến. Laravel publish sự kiện sang Redis và `nhm-product-datxe-realtime` phát realtime qua Socket.IO.
- **UC-14 (Chat/Call Driver):** Cho phép customer và driver chat theo ride, lưu hội thoại vào DB, khởi tạo cuộc gọi, cập nhật trạng thái failed/no-answer/completed và phát realtime qua Redis + Socket.IO.
- **UC-26 (Book Intercity Ride):** Đặt xe đi tỉnh với các thông tin ngày, giờ đi, loại xe (bao gồm xe ghép). Hệ thống tính giá và tìm tài xế phù hợp.
- **UC-27 (Book Airport Ride):** Đặt xe ra/đến sân bay bằng cách chọn sân bay, chiều đi/đến và thời gian đón. Hệ thống tự động xác thực sân bay hỗ trợ và tìm tài xế.
- **UC-28 (Request Ride Cancellation):** Khách hàng yêu cầu hủy chuyến. Nếu có tài xế, cần tài xế xác nhận. Hủy trực tiếp nếu đang tìm xe. Tích hợp cảnh báo gian lận khi tài xế ở quá gần điểm đón.
- **UC-29 (View Scheduled Ride details):** Xem thông tin chi tiết chuyến xe đã đặt, bao gồm cả thông tin tài xế, phương tiện và đánh giá nếu đã được nhận chuyến.
- **UC-47 (View Scheduled Ride List):** Tài xế xem danh sách các chuyến xe đặt trước (đi tỉnh/sân bay) phù hợp với loại xe của mình và có thể lọc theo thời gian, địa điểm, giá.
- **UC-48 (View Scheduled Ride Detail):** Tài xế xem chi tiết một chuyến xe đặt trước cụ thể để nắm bắt lộ trình, thu nhập và ghi chú trước khi quyết định nhận chuyến.
- **UC-49 (Accept Scheduled Ride):** Tài xế nhận chuyến xe đặt trước. Hệ thống thực hiện gán tài xế nguyên tử (atomic) và thông báo cho khách hàng ngay lập tức.
- **UC-50 (Request Driver Cancellation):** Tài xế yêu cầu hủy chuyến xe đặt trước đã nhận. Hệ thống kiểm tra điều kiện thời gian (vd: 30 phút sau khi nhận) để cho phép tự hủy hoặc bắt buộc liên hệ khách hàng.
- **UC-51 (Manage ride Scheduled bookings):** Tài xế quản lý danh sách các chuyến xe đã nhận, xem thông tin khách hàng và thực hiện các thao tác đón khách/bắt đầu chuyến.
- *(More to be added as feature expands)*

## Quy Trình Tương Tác Giữa Các Component
1. Client gọi **API Route** → `RideController`.
2. Dữ liệu Request được validate qua các class thuộc `Http/Requests`.
3. `RideController` khởi tạo **DTO** `CreateDraftRideDTO` / `ApplyVoucherDTO`... từ request và chuyển xuống Service.
4. `RideService` đón DTO, gọi logic. VD: Tính toán km thông qua `MapService`, giá cước thông qua `PricingService`.
5. `RideService` trả `ServiceReturn` chứa dữ liệu `PriceEstimateDTO` hoặc mảng thông tin cho UI.
6. `RideController` format lại response thông qua `BaseController` (`sendSuccess`, `sendError`).

## Realtime Tracking Integration
- API `GET /api/v1/ride/{rideId}/tracking` trả snapshot ban đầu cho màn hình UC-13.
- Các API driver-side:
- `POST /api/v1/ride/{rideId}/tracking/accept`
- `POST /api/v1/ride/{rideId}/tracking/location`
- `POST /api/v1/ride/{rideId}/tracking/arrived`
- `POST /api/v1/ride/{rideId}/tracking/driver-cancel`
- Mọi write operation dùng `execute(..., useTransaction: true)` và publish event `ride.tracking.events` sau commit để Node realtime service broadcast tới room `ride:{rideId}`.

## Realtime Communication Integration
- API `GET /api/v1/ride/{rideId}/communication/messages` lấy toàn bộ hội thoại của ride.
- API `POST /api/v1/ride/{rideId}/communication/messages` gửi chat message customer-driver.
- API `POST /api/v1/ride/{rideId}/communication/calls` tạo call intent và log lịch sử gọi.
- API `POST /api/v1/ride/{rideId}/communication/calls/{callId}/status` cập nhật trạng thái fail/no-answer/completed.
- Sau commit, backend publish event `ride.communication.events` để Node realtime service emit tới room `ride:{rideId}`.
