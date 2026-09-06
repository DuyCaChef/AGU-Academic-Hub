
# PHẦN 3: TÀI LIỆU KỸ THUẬT (TECHNICAL SPECIFICATION)
### 1\. Phân ước chung (Standards & Headers)

  * **Base URL:** `/api/v1`

  * **Default Headers:**
    
      * `Accept`: `application/json`
      * `Content-Type`: `application/json` (trừ các endpoint upload file dùng `multipart/form-data`)
      * `Authorization`: `Bearer <personal_access_token>` (dành cho các endpoint yêu cầu xác thực)

  * **Mã phản hồi chuẩn (HTTP Status Codes):**
    
      * **200 OK:** Truy vấn / cập nhật thành công.
      * **201 Created:** Tạo mới tài nguyên thành công.
      * **400 Bad Request:** Dữ liệu đầu vào không hợp lệ.
      * **401 Unauthorized:** Chưa đăng nhập hoặc Token hết hạn.
      * **403 Forbidden:** Không có quyền thực hiện thao tác (chặn bởi Policy/Role).
      * **404 Not Found:** Không tìm thấy tài nguyên.
      * **409 Conflict:** Xung đột dữ liệu (ví dụ: trùng mã băm file SHA-256).
      * **422 Unprocessable Entity:** Lỗi Validation Form.
      * **429 Too Many Requests:** Vượt quá giới hạn Rate Limit.

-----

### 2\. Danh sách Toàn bộ Endpoints theo Module

#### A. Module 1: Xác thực & Quản lý Tài khoản (Authentication & Profile)

| Phương thức | Endpoint                | Phân quyền    | Mô tả chức năng                                        |
| :---------- | :---------------------- | :------------ | :----------------------------------------------------- |
| POST        | `/auth/register`        | Public        | Đăng ký tài khoản sinh viên mới bằng Email trường      |
| POST        | `/auth/login`           | Public        | Đăng nhập hệ thống (nhận Bearer Token)                 |
| POST        | `/auth/logout`          | Authenticated | Đăng xuất và hủy Token hiện tại                        |
| POST        | `/auth/forgot-password` | Public        | Gửi email yêu cầu đặt lại mật khẩu                     |
| POST        | `/auth/reset-password`  | Public        | Cập nhật mật khẩu mới qua Token xác thực               |
| GET         | `/auth/me`              | Authenticated | Lấy thông tin tài khoản hiện tại kèm điểm uy tín       |
| PUT         | `/auth/profile`         | Authenticated | Cập nhật thông tin cá nhân (Họ tên, avatar)            |
| POST        | `/auth/kyc-submit`      | Authenticated | Gửi ảnh thẻ SV / Minh chứng cựu sinh viên để duyệt KYC |
| GET         | `/auth/kyc-status`      | Authenticated | Kiểm tra tiến độ duyệt KYC của tài khoản               |

#### B. Module 2: Danh mục Khoa & Môn học (Faculties & Courses)

| Phương thức | Endpoint                  | Phân quyền | Mô tả chức năng                                            |
| :---------- | :------------------------ | :--------- | :--------------------------------------------------------- |
| GET         | `/faculties`              | Public     | Lấy danh sách tất cả các Khoa/Bộ môn                       |
| GET         | `/faculties/{id}/courses` | Public     | Lấy danh sách môn học thuộc một Khoa cụ thể                |
| GET         | `/courses`                | Public     | Tìm kiếm danh sách môn học (hỗ trợ search theo mã/tên)     |
| GET         | `/courses/{course_code}`  | Public     | Lấy thông tin chi tiết môn học, số tín chỉ, điểm review TB |
| POST        | `/courses`                | Admin      | Thêm mới môn học vào hệ thống                              |
| PUT         | `/courses/{id}`           | Admin      | Chỉnh sửa thông tin môn học                                |
| DELETE      | `/courses/{id}`           | Admin      | Xóa môn học (kèm kiểm tra ràng buộc dữ liệu)               |

#### C. Module 3: Quản lý Kho Tài liệu (Academic Documents)

| Phương thức | Endpoint                           | Phân quyền        | Mô tả chức năng                                             |
| :---------- | :--------------------------------- | :---------------- | :---------------------------------------------------------- |
| GET         | `/courses/{course_code}/documents` | Verified User     | Danh sách tài liệu theo môn (bộ lọc category, sort, paging) |
| GET         | `/documents/{id}`                  | Verified User     | Lấy thông tin chi tiết của một tài liệu                     |
| GET         | `/documents/{id}/preview`          | Verified User     | Lấy luồng xem trước file PDF trực tuyến (Protected Stream)  |
| GET         | `/documents/{id}/download`         | Verified User     | Tải file về máy (tự động tăng downloads\_count)             |
| POST        | `/documents/check-duplicate`       | Verified User     | Kiểm tra trước mã băm SHA-256 trước khi tải file lớn lên    |
| POST        | `/documents/upload`                | Verified User     | Tải lên tài liệu mới (chạy qua Deduplication Pipeline)      |
| POST        | `/documents/{id}/create-version`   | Verified User     | Tải lên bản cập nhật mới (Version N+1) cho tài liệu có sẵn  |
| PUT         | `/documents/{id}`                  | Uploader / Mod    | Sửa thông tin mô tả, danh mục tài liệu                      |
| DELETE      | `/documents/{id}`                  | Policy Controlled | Xóa mềm tài liệu (Áp dụng luật Community Ownership)         |
| POST        | `/documents/{id}/upvote`           | Verified User     | Bấm thích / Bỏ thích tài liệu (Tăng điểm cho uploader)      |

#### D. Module 4: Đánh giá Môn học & Giảng viên (Course Reviews)

| Phương thức | Endpoint                         | Phân quyền     | Mô tả chức năng                                           |
| :---------- | :------------------------------- | :------------- | :-------------------------------------------------------- |
| GET         | `/courses/{course_code}/reviews` | Verified User  | Lấy danh sách đánh giá của môn học (tự động ẩn danh tính) |
| POST        | `/courses/{course_code}/reviews` | Student/Alumni | Gửi bài đánh giá môn học mới (chạy qua bộ lọc từ khóa)    |
| PUT         | `/reviews/{id}`                  | Review Author  | Chỉnh sửa bài đánh giá của chính mình                     |
| DELETE      | `/reviews/{id}`                  | Author / Mod   | Xóa bài đánh giá (Soft Delete)                            |
| POST        | `/reviews/{id}/helpful`          | Verified User  | Bình chọn bài review hữu ích                              |

#### E. Module 5: Yêu cầu Tài liệu & Săn Thưởng (Wishlist & Bounty)

| Phương thức | Endpoint                          | Phân quyền      | Mô tả chức năng                                             |
| :---------- | :-------------------------------- | :-------------- | :---------------------------------------------------------- |
| GET         | `/document-requests`              | Verified User   | Danh sách các yêu cầu tài liệu đang mở (Wishlist)           |
| POST        | `/document-requests`              | Verified User   | Tạo yêu cầu tìm tài liệu mới (đóng băng điểm thưởng bounty) |
| GET         | `/document-requests/{id}`         | Verified User   | Xem chi tiết yêu cầu và danh sách tài liệu được phản hồi    |
| POST        | `/document-requests/{id}/fulfill` | Verified User   | Tải lên tài liệu để giải quyết yêu cầu (nhận điểm thưởng)   |
| POST        | `/document-requests/{id}/confirm` | Request Creator | Người tạo xác nhận tài liệu đạt yêu cầu → Giải ngân điểm    |
| DELETE      | `/document-requests/{id}`         | Creator / Admin | Hủy yêu cầu và hoàn lại điểm thưởng vào ví                  |

#### F. Module 6: Báo cáo Vi phạm & Khiếu nại (Reports & Moderation)

| Phương thức | Endpoint                          | Phân quyền    | Mô tả chức năng                                           |
| :---------- | :-------------------------------- | :------------ | :-------------------------------------------------------- |
| POST        | `/reports/document`               | Verified User | Gửi báo cáo tài liệu (sai nội dung, bản quyền, virus)     |
| POST        | `/reports/review`                 | Verified User | Gửi báo cáo bài review có từ ngữ bôi nhọ, công kích       |
| GET         | `/admin/reports`                  | Mod / Admin   | Lấy danh sách hàng đợi các báo cáo vi phạm cần xử lý      |
| PUT         | `/admin/reports/{id}/resolve`     | Mod / Admin   | Xử lý báo cáo: Duyệt xóa tài liệu / Bác bỏ báo cáo        |
| POST        | `/documents/{id}/request-removal` | Uploader      | Tác giả gửi yêu cầu gỡ tài liệu đã thuộc sở hữu cộng đồng |

#### G. Module 7: Quản trị Hệ thống & KYC (Admin Control Panel)

| Phương thức | Endpoint                        | Phân quyền  | Mô tả chức năng                                           |
| :---------- | :------------------------------ | :---------- | :-------------------------------------------------------- |
| GET         | `/admin/kyc-queue`              | Admin / Mod | Lấy danh sách yêu cầu xác thực thẻ SV / Cựu sinh viên     |
| PUT         | `/admin/kyc/{id}/approve`       | Admin / Mod | Phê duyệt hồ sơ KYC → Kích hoạt tài khoản                 |
| PUT         | `/admin/kyc/{id}/reject`        | Admin / Mod | Từ chối hồ sơ KYC kèm lý do phản hồi                      |
| GET         | `/admin/users`                  | Admin       | Quản lý danh sách người dùng, lọc theo Role và Trạng thái |
| PUT         | `/admin/users/{id}/status`      | Admin       | Khóa / Mở khóa tài khoản người dùng (SUSPENDED / ACTIVE)  |
| GET         | `/admin/documents/deleted`      | Admin       | Danh sách tài liệu trong thùng rác (Soft Deleted)         |
| POST        | `/admin/documents/{id}/restore` | Admin       | Khôi phục tài liệu đã bị xóa mềm                          |
| GET         | `/admin/dashboard/stats`        | Admin       | Thống kê: Tổng tài nguyên, dung lượng lưu trữ, lượt tải   |

