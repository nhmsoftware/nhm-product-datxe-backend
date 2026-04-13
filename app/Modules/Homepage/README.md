# Homepage Module

## 1. Giới thiệu (Overview)
Module Homepage chịu trách nhiệm cung cấp dữ liệu tổng hợp cho màn hình chính của ứng dụng Khách hàng, bao gồm các dịch vụ nhanh, banner khuyến mãi, địa chỉ đã lưu và gợi ý quán ăn.

## 2. Đặc tả Nghiệp vụ (Specifications)
Nghiệp vụ tổng hợp dữ liệu dựa trên Use Case **UC-07** (View Homepage):
- **Đối tượng sử dụng:** Khách hàng (đã đăng nhập).
- **Dữ liệu cung cấp:**
    - `header`: Thông tin chào hỏi, avatar và thông báo.
    - `services`: Danh sách các dịch vụ chính (Đặt xe, Đồ ăn, Giao hàng, ...).
    - `saved_addresses`: Các địa chỉ Nhà riêng/Cơ quan đã lưu của khách hàng.
    - `banners`: Danh sách hình ảnh banner quảng cáo đang chạy.
    - `news_promotions`: Các tin tức và mã giảm giá nổi bật.
    - `restaurant_suggestions`: Danh sách các quán ăn gợi ý dựa trên vị trí hiện tại của khách hàng.

## 3. Cấu trúc Module (Module Structure)
- **`Http/`**:
    - `Controllers/HomepageController`: Tiếp nhận yêu cầu lấy dữ liệu trang chủ.
- **`Interfaces/`**:
    - `HomepageServiceInterface`: Định nghĩa phương thức `getHomepageData`.
- **`Services/`**:
    - `HomepageService`: Thực thi việc gom nhóm dữ liệu từ nhiều nguồn khác nhau (SavedAddressRepository, v.v.) và trả về mảng dữ liệu hoàn chỉnh.

## 4. Danh sách Chức năng (Features)
- [x] Lấy dữ liệu trang chủ cá nhân hóa (`getHomepageData`).
- [x] Gợi ý địa chỉ đã lưu (`getSavedAddresses`).
- [x] Gợi ý quán ăn theo tọa độ GPS (`getRestaurantSuggestions`).

## 5. Hướng dẫn sử dụng cho AI
Module này chủ yếu đóng vai trò là một "Aggregator" (bộ tổng hợp dữ liệu). Nếu cần thêm một section mới vào trang chủ, hãy cập nhật logic trong `HomepageService`. Luôn đảm bảo dữ liệu trả về mượt mà ngay cả khi thiếu tọa độ GPS (trả về gợi ý mặc định).
