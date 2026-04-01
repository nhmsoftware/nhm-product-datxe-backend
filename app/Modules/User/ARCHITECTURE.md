# 🏗️ Kiến Trúc Hệ Thống (Architecture Guide)

Dự án được tổ chức theo mô hình **Clean Architecture** kết hợp với **Domain-Driven Design (DDD)**. Mục tiêu cốt lõi là cô lập **Lõi Nghiệp vụ (Domain)** khỏi sự phụ thuộc vào Framework và Công nghệ (Database, Third-party).

---

## 🧭 Nguyên tắc Phụ thuộc (Dependency Rule)
> **"Sự phụ thuộc chỉ hướng vào trong."** > Các lớp bên ngoài (Infrastructure, Presentation) phụ thuộc vào lớp bên trong (Application, Domain). Tầng **Domain** là trung tâm và tuyệt đối không phụ thuộc vào bất kỳ thành phần kỹ thuật nào bên ngoài.

---

## 📂 Chi tiết cấu trúc các Lớp (Layers)

### 🟢 1. Domain (Lõi nghiệp vụ - Quan trọng nhất)
Đây là nơi chứa "luật chơi" của hệ thống, không chứa code của Framework.
* **Entities**: Các đối tượng có định danh (ID), chứa logic tự thay đổi trạng thái (ví dụ: `User`, `Order`).
* **Value Objects**: Các đối tượng không có ID, bất biến (Immutable), xác định bằng giá trị (ví dụ: `Email`, `Address`).
* **Interfaces**: Các bản hợp đồng (Contracts) cho Repository hoặc Service.
* **Enums / Events / Exceptions**: Định nghĩa trạng thái, sự kiện và lỗi đặc thù của nghiệp vụ.

### 🔵 2. Application (Tầng điều hành)
Đóng vai trò "người chỉ huy", thực hiện các Use Case cụ thể của ứng dụng.
* **Actions**: Mỗi class xử lý duy nhất một nhiệm vụ (Single Responsibility). Ví dụ: `CreateUserAction`.
* **DTOs (Data Transfer Objects)**: Vật chứa dữ liệu để truyền tải giữa các tầng, giúp cấu trúc dữ liệu tường minh.

### 🟡 3. Infrastructure (Tầng hạ tầng)
Triển khai kỹ thuật cụ thể cho các Interface định nghĩa ở tầng Domain.
* **Persistence**: Cài đặt Repository thực tế (Eloquent, Query Builder, Redis...).
* **Providers**: Kết nối dịch vụ bên thứ ba (Mail, SMS, Payment Gateway).
* **Routes**: Khai báo các điểm cuối (Endpoints) của module.

### 🔴 4. Presentation (Tầng giao tiếp)
Nơi tiếp nhận và phản hồi yêu cầu từ môi trường bên ngoài (HTTP, CLI).
* **Controllers**: Tiếp nhận Request, gọi Action và trả về Response.
* **Requests**: Validation dữ liệu đầu vào từ Client.
* **Resources**: Định dạng lại dữ liệu đầu ra (API Transformation/Presenter).

---

## 🔄 Luồng dữ liệu (Data Flow)

1. **Presentation**: Nhận HTTP Request → **Validate** → Chuyển dữ liệu vào **DTO**.
2. **Application**: Controller gọi **Action** → Action nhận DTO.
3. **Domain**: Action sử dụng **Entity** và gọi **Interface** (Repository) để xử lý.
4. **Infrastructure**: Thực thi Interface (ví dụ: Lưu dữ liệu vào Database).
5. **Output**: Dữ liệu ngược dòng về Controller → Map qua **Resource** → Trả về Client.

---

## 🚀 Tại sao cấu trúc này tối ưu?

* **Dễ dàng Testing**: Có thể Unit Test tầng Domain và Application mà không cần chạy Database hay Web Server.
* **Tính linh hoạt (Decoupling)**: Thay đổi Database hoặc thư viện bên thứ ba chỉ cần sửa tầng Infrastructure, không ảnh hưởng đến Logic.
* **Ngôn ngữ chung**: Code phản ánh chính xác thuật ngữ chuyên môn của dự án (Ubiquitous Language).
* **Dễ bảo trì**: Giảm thiểu rủi ro khi cập nhật Framework vì logic nghiệp vụ đã được tách biệt hoàn toàn.

---
