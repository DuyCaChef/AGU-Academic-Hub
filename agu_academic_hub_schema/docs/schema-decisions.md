# AGU Academic Hub - Quyết định schema cuối cùng

## Phạm vi nguồn
Thiết kế dựa trên bốn tài liệu được cung cấp:
- `agu_academic_hub.md`
- `Tài-liệu-yêu-cầu-dự-án.txt`
- `tài-liệu-thiết-kế.txt`
- `tài-liệu-kỹ-thuật.txt`

## Quyết định theo yêu cầu
| Chủ đề | Quyết định cuối cùng | Cách biểu diễn |
|---|---|---|
| Ngành - môn học | M:N | `course_majors(course_id, major_id)` |
| SHA-256 | Duy nhất trong cùng môn | `UNIQUE(course_id, file_hash_sha256)` |
| Versioning | Phiên bản trỏ bản ngay trước | `documents.parent_document_id` |
| Đánh giá sao tài liệu | Không có | Không tạo `document_ratings`, không có `average_rating` |
| Review môn học | Một người/môn/học kỳ | `UNIQUE(course_id, user_id, academic_term)` |

## Các quyết định bổ sung
1. Guest là khách chưa đăng nhập, không lưu thành user.
2. Danh mục file thống nhất: `EXAM`, `LECTURE`, `PROJECT`, `EXERCISE`, `QUIZ`, `OTHER`.
3. Status tài liệu thống nhất thành `PENDING_REVIEW`, `PUBLISHED`, `HIDDEN_FOR_REVIEW`, `REJECTED`, `SUSPENDED`.
4. Mã vai trò dùng `STUDENT`, `ALUMNI`, `MODERATOR`, `ADMIN`; trạng thái tài khoản `UNVERIFIED`, `ACTIVE`, `SUSPENDED`.
5. Có `course_review_reports` để hỗ trợ endpoint báo cáo review; có `personal_access_tokens` theo Sanctum và migration queue database tùy chọn. Có `document_processing_jobs` để theo dõi queue; `document_similarity_matches` để lưu cặp tài liệu tương đồng; `moderation_actions` và `audit_logs` để lưu vết; `point_wallets` và `point_transactions` để giữ sổ điểm.
6. Review môn học có `academic_term` do quyết định mỗi học kỳ. Format mẫu `YYYY-1` hoặc `YYYY-2` cần được validation ở ứng dụng.
7. Hash duy nhất vẫn bị chiếm bởi bản ghi soft-deleted. Quy tắc restore phải kiểm tra va chạm.
8. Bảng báo cáo có `UNIQUE(document_id, reporter_id)` để một tài khoản không spam nhiều báo cáo cùng tài liệu.
9. `documents` lưu từng file version riêng; `parent_document_id` trỏ đến phiên bản ngay trước đó. Phiên bản gốc có `NULL`.
10. Không tạo bảng đánh giá sao tài liệu. Tài liệu nguồn có quy tắc khóa xóa khi ≥10 lượt tải hoặc ≥4 sao nhưng yêu cầu hiện tại loại bỏ rating tài liệu; vì vậy chỉ có thể thực thi nhánh ≥10 lượt tải. Đây là thay đổi cần được chấp thuận ở nghiệp vụ.
11. Không thêm bảng `lecturers` vì API/tài liệu chỉ đề cập tên giảng viên trong mô tả tìm kiếm/Wishlist, chưa có yêu cầu quản lý giảng viên chuẩn hóa.
12. `course_id` được dùng làm khóa liên kết tài liệu; danh sách môn học theo khoa được suy ra qua `course_majors -> majors -> faculties`, không lưu `faculty_id` dư thừa trên `courses`.

## Quy tắc cần làm ở tầng ứng dụng
- Transaction + row locks + idempotency cho điểm thưởng.
- Laravel Policies cho quyền sửa/xóa/khôi phục, ownership và ngưỡng cộng đồng.
- Kiểm tra hash trước khi ghi file và xử lý duplicate-key khi upload đồng thời.
- Tính `downloads_count`/`likes_count` cùng transaction với event.
- Queue jobs cho hash, trích xuất text, similarity và malware scanning.
- Private storage và authorization cho minh chứng KYC.
- API review ẩn danh không trả `user_id`.
- Báo cáo ngưỡng ≥3 tài khoản uy tín phải được tính từ dữ liệu người báo cáo và xử lý nguyên tử.
- `parent_document_id` phải cùng `course_id` xuyên suốt version chain; validate ở service.
- `similarity_score` phải nằm trong [0,1]; validate ở service hoặc thêm DB check constraint khi môi trường xác nhận hỗ trợ.
