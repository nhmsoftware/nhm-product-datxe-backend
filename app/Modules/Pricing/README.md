# Pricing Module

## 1. Giới thiệu (Overview)
Module Pricing chịu trách nhiệm tính toán giá cước cho toàn bộ các dịch vụ của hệ thống (Ride, Food, Delivery, ...). Nó trừu tượng hóa logic tính giá phức tạp (quãng đường, thời gian, loại xe, hệ số cao điểm) ra khỏi các module nghiệp vụ khác.

## 2. Đặc tả Nghiệp vụ (Specifications)
Nghiệp vụ tính giá dựa trên Use Case **UC-10** trong tài liệu đặc tả chung:
- **Tham số đầu vào:**
    - `distance`: Khoảng cách di chuyển (đơn vị: km).
    - `duration`: Thời gian di chuyển ước tính (đơn vị: phút).
    - `vehicle_type`: Loại phương tiện (Bike, Car 4/7/9 seats).
    - `surge_multiplier`: Hệ số nhân (mặc định 1.0, dùng cho cao điểm/thời tiết).
- **Công thức tính:** 
    `Tổng tiền = (Giá khởi điểm + Giá quãng đường vượt mức + Giá thời gian) * Hệ số nhân`
- **Quy tắc làm tròn:** Tổng tiền cuối cùng được làm tròn đến hàng nghìn gần nhất.

## 3. Cấu trúc Module (Module Structure)
- **`DTO/`**: 
    - `PricingRequestDTO`: Chứa dữ liệu đầu vào cần tính toán.
    - `PricingResultDTO`: Chứa kết quả tính toán chi tiết (cước cơ bản, cước km, cước thời gian, tổng tiền).
- **`Interfaces/`**: 
    - `PricingServiceInterface`: Hợp đồng giao tiếp chính để các module khác gọi đến.
- **`Services/`**: 
    - `PricingService`: Thực thi logic tính toán. Hiện tại sử dụng cấu hình tĩnh `RATE_CONFIG` trong code (có thể chuyển sang Database về sau).

## 4. Danh sách Chức năng (Features)
- [x] Tính toán giá cước linh hoạt theo từng loại xe (`calculatePrice`).
- [x] Hỗ trợ hệ số nhân Surge Pricing.
- [x] Tự động tính toán giá dựa trên khoảng cách lũy tiến (sau 2km đầu).
- [x] Làm tròn tiền theo chuẩn VNĐ.

## 5. Hướng dẫn sử dụng cho AI
Khi cần tính giá cho bất kỳ đơn hàng nào, hãy Inject `PricingServiceInterface` và truyền vào `PricingRequestDTO`. Không được tự tính toán logic giá bên ngoài module này.
