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
- *(More to be added as feature expands)*

## Quy Trình Tương Tác Giữa Các Component
1. Client gọi **API Route** → `RideController`.
2. Dữ liệu Request được validate qua các class thuộc `Http/Requests`.
3. `RideController` khởi tạo **DTO** `CreateDraftRideDTO` / `ApplyVoucherDTO`... từ request và chuyển xuống Service.
4. `RideService` đón DTO, gọi logic. VD: Tính toán km thông qua `MapService`, giá cước thông qua `PricingService`.
5. `RideService` trả `ServiceReturn` chứa dữ liệu `PriceEstimateDTO` hoặc mảng thông tin cho UI.
6. `RideController` format lại response thông qua `BaseController` (`sendSuccess`, `sendError`).
