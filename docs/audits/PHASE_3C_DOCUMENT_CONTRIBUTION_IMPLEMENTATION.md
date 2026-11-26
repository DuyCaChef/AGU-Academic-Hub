# BÁO CÁO KỸ THUẬT: HOÀN THIỆN ĐÓNG GÓP TÀI LIỆU, SĂN THƯỞNG VÀ TÍCH HỢP KIỂM DUYỆT (PHASE 3C)

- **Dự án**: AGU Academic Hub
- **Phân hệ**: Student / Alumni Modules & Course Detail Integration
- **Nhánh**: `feature/walletAndbounty`
- **Ngày thực hiện**: 07/11/2026
- **Trạng thái**: Hoàn thành toàn diện (Verified 100%)

---

## 1. TỔNG QUAN VÀ BỐI CẢNH

Sau khi triển khai Phase 3B1 (Document Listing), Phase 3B2 (Course Reviews & Community Voting), và Phase 3B3 (Course Wishlist & Bounty Integration), hệ thống đã phát hiện một số điểm nghẽn kỹ thuật và rủi ro bảo mật quan trọng:
1. **Lỗi truy vấn SQLSTATE[42S22]**: Lỗi `Unknown column 'ten_tep_goc' in 'field list'` khi truy vấn nạp kèm (`eager loading`) tài liệu đáp ứng yêu cầu săn thưởng do sai lệch schema bảng `tai_lieu`.
2. **Rủi ro bảo mật AUD-H01**: Thiếu kiểm tra quyền sở hữu và trạng thái duyệt (`DA_DUYET`) khi nộp tài liệu từ kho cá nhân (Method A), cũng như thiếu chốt chặn ngăn chặn giải ngân điểm thưởng nếu tài liệu nộp chưa qua kiểm duyệt.
3. **Rủi ro bảo mật AUD-H02**: Thiếu kiểm soát quyền truy cập chi tiết (`show`), xem trước (`preview`), và tải xuống (`download`) đối với các tài liệu ở trạng thái chưa được phê duyệt (`CHO_DUYET`, `TU_CHOI`), dẫn đến nguy cơ lộ lọt tài liệu nhạy cảm hoặc vi phạm quy tắc hệ thống.
4. **Thiếu cơ chế Tải file trực tiếp từ máy local (Method B)**: Người dùng bắt buộc phải đăng tài liệu lên hệ thống trước rồi mới chọn được vào Bounty, gây đứt đoạn trải nghiệm (UX friction).

---

## 2. CHI TIẾT CÁC THAY ĐỔI & GIẢI PHÁP KỸ THUẬT

### 2.1. Sửa lỗi Eager Loading Schema Bảng `tai_lieu` (SQLSTATE[42S22])
- **Vấn đề**: Bảng `tai_lieu` trong cơ sở dữ liệu thực tế không có các cột `ten_tep_goc`, `dung_luong_byte`, `loai_tep`. Các cột thực tế bao gồm: `ma_tai_lieu`, `tieu_de`, `duong_dan_tep`, `dinh_dang_tep`, `dung_luong_kb`, `phan_loai`, `luot_tai`, `trang_thai`.
- **Tập tin chỉnh sửa**: `app/Modules/Bounties/Infrastructure/Persistence/WishlistBountyRepository.php`
- **Nội dung sửa đổi**:
  - Tại dòng 74 (`findRequestById`): Cập nhật quan hệ eager loading:
    ```php
    'dapUngs.taiLieu:ma_tai_lieu,tieu_de,duong_dan_tep,dinh_dang_tep,dung_luong_kb,phan_loai,luot_tai,trang_thai'
    ```
  - Tại dòng 100 (`findSubmission`): Bổ sung eager load quan hệ `taiLieu`:
    ```php
    ->with(['nguoiDongGop', 'taiLieu'])
    ```

### 2.2. Khắc phục lỗ hổng AUD-H01 (Bảo mật Đóng góp & Nghiệm thu Săn thưởng)
- **Vấn đề**: Người dùng có thể sử dụng tài liệu của người khác, tài liệu chưa duyệt, hoặc tài liệu của môn học khác để nộp nhận thưởng; người tạo yêu cầu có thể xác nhận giải ngân cho tài liệu chưa qua kiểm duyệt.
- **Tập tin chỉnh sửa**:
  1. `app/Modules/Bounties/Domain/Services/BountyRuleEvaluator.php`:
     - Bổ sung phương thức `canFulfillWithDocument(NguoiDung $user, YeuCauTaiLieu $yeuCau, ?TaiLieu $document): array`:
       - Kiểm tra tài liệu tồn tại và không bị xóa mềm (`trashed`).
       - Kiểm tra quyền tác giả: `$document->ma_nguoi_dang === $user->ma_nguoi_dung`.
       - Kiểm tra trạng thái kiểm duyệt: Bắt buộc `$document->trang_thai === 'DA_DUYET'`.
       - Kiểm tra tính gắn kết học thuật: `$document->ma_mon_hoc === $yeuCau->ma_mon_hoc`.
  2. `app/Modules/Bounties/Application/Actions/ConfirmBountyFulfillmentAction.php`:
     - Bổ sung chốt chặn trước khi mở giao dịch chuyển điểm (`DB::transaction`):
       ```php
       if ($dapUng->taiLieu && $dapUng->taiLieu->trang_thai !== 'DA_DUYET' && $dapUng->taiLieu->trang_thai !== TrangThaiTaiLieu::DA_DUYET) {
           return [
               'success' => false,
               'message' => 'Tài liệu đóng góp này chưa được Ban kiểm duyệt phê duyệt, chưa thể xác nhận giải ngân điểm.',
           ];
       }
       ```
     - Điểm thưởng của Requester tiếp tục được đóng băng an toàn trong `diem_tam_giu` cho đến khi Moderator phê duyệt tài liệu.

### 2.3. Khắc phục lỗ hổng AUD-H02 (Bảo vệ Tài liệu Chưa Phê duyệt)
- **Vấn đề**: Khách vãng lai và sinh viên không liên quan có thể truy cập URL trực tiếp để xem chi tiết, stream xem trước, hoặc tải file tài liệu đang ở trạng thái `CHO_DUYET` / `TU_CHOI`.
- **Tập tin chỉnh sửa**:
  1. `app/Modules/Documents/Application/Queries/GetDocumentDetailQuery.php`:
     - Nhận tham số `?NguoiDung $user = null`.
     - Nếu trạng thái khác `DA_DUYET`: Chỉ cho phép tác giả (`ma_nguoi_dang === $user->ma_nguoi_dung`), Quản trị viên (`laQuanTriVien()`), hoặc Kiểm duyệt viên (`laKiemDuyetVien()`). Tất cả đối tượng khác nhận HTTP 403 Forbidden.
  2. `app/Modules/Documents/Application/Actions/PreviewDocumentAction.php`:
     - Chặn stream file trực tuyến nếu tài liệu chưa được duyệt và người yêu cầu không phải tác giả hay cán bộ quản trị/kiểm duyệt (HTTP 403).
  3. `app/Modules/Documents/Application/Actions/DownloadDocumentAction.php`:
     - Chặn tải tệp nếu tài liệu chưa được duyệt và người yêu cầu không phải tác giả hay cán bộ quản trị/kiểm duyệt (HTTP 403).
  4. `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php`:
     - Truyền `$request->user()` vào `GetDocumentDetailQuery`, `PreviewDocumentAction`, và `DownloadDocumentAction`.

### 2.4. Triển khai Phương thức B: Tải tệp trực tiếp từ máy tính (Local File Upload)
- **Yêu cầu nghiệp vụ**: Cho phép sinh viên nộp tài liệu giải quyết yêu cầu tìm tài liệu trực tiếp từ máy cá nhân mà không cần thao tác trung gian.
- **Tập tin chỉnh sửa**:
  1. `app/Modules/Bounties/Presentation/Http/Requests/FulfillBountyRequest.php`:
     - Hỗ trợ linh hoạt 2 kịch bản (Scenario A: `ma_tai_lieu` từ kho cá nhân; Scenario B: `file`, `tieu_de`, `phan_loai`, `ghi_chu_dong_gop`).
     - Quy chuẩn validation file: MIME type hợp lệ (`pdf, docx, pptx, zip`), dung lượng tối đa 50MB (51,200 KB).
  2. `app/Modules/Bounties/Application/Actions/FulfillBountyRequestAction.php`:
     - Xử lý phân nhánh `handleLocalUpload()`:
       - **Rate Limit**: Áp dụng hạn mức tối đa 10 lượt tải lên/ngày/tài khoản (theo SRS mục 3.2.1).
       - **File Integrity**: Kiểm tra dung lượng `getSize() > 0`.
       - **Binary Deduplication**: Tính toán mã băm SHA-256 nhị phân (`hash_file('sha256', ...)`) và kiểm tra trùng lặp 100% trong môn học qua `DocumentDeduplicationEvaluator`.
       - **Storage**: Lưu trữ an toàn trên disk `local` qua `DocumentFileStorage::store($file, 'doc')`.
       - **Transaction**: Tạo đồng thời bản ghi `TaiLieu` (trạng thái `CHO_DUYET`) và bản ghi `DapUngYeuCauTaiLieu` (trạng thái `CHO_DUYET`).
  3. `app/Modules/Bounties/Presentation/Http/Controllers/WishlistBountyController.php`:
     - Nhận và truyền `$request->file('file')` vào Action.
  4. `resources/js/Components/Wishlist/FulfillRequestModal.jsx`:
     - Thiết kế giao diện Tab chuyển đổi trực quan:
       - **Tab 1 - Kho tài liệu cá nhân**: Chọn tài liệu đã duyệt của bản thân kèm bộ lọc môn học hiện tại.
       - **Tab 2 - Tải lên tệp mới từ máy**: Kéo thả tệp, hiển thị dung lượng, tính toán trước SHA-256 qua Web Crypto API, gọi API tiền kiểm trùng lặp, nhập tiêu đề, danh mục tài liệu.
       - Hiển thị hộp thông báo bảo mật rõ ràng: Tài liệu tải lên sẽ được chuyển vào hàng đợi kiểm duyệt trước khi điểm thưởng có thể được nghiệm thu.
  5. `resources/js/Pages/Courses/Show.jsx` & `resources/js/Pages/Wishlist/Show.jsx`:
     - Truyền prop `course={course}` vào component `FulfillRequestModal`.

---

## 3. MA TRẬN TẬP TIN THAY ĐỔI

| STT | Đường dẫn tập tin | Loại thay đổi | Mục đích chính |
|---|---|---|---|
| 1 | `app/Modules/Bounties/Infrastructure/Persistence/WishlistBountyRepository.php` | Backend / Repository | Sửa cột eager load hợp lệ, eager load `taiLieu` cho submission |
| 2 | `app/Modules/Bounties/Domain/Services/BountyRuleEvaluator.php` | Backend / Domain | Bổ sung kiểm tra quyền tác giả, trạng thái `DA_DUYET`, môn học |
| 3 | `app/Modules/Bounties/Application/Actions/ConfirmBountyFulfillmentAction.php` | Backend / Action | Chặn giải ngân điểm nếu tài liệu chưa `DA_DUYET` |
| 4 | `app/Modules/Bounties/Presentation/Http/Requests/FulfillBountyRequest.php` | Backend / FormRequest | Validate linh hoạt cho cả Method A và Method B |
| 5 | `app/Modules/Bounties/Application/Actions/FulfillBountyRequestAction.php` | Backend / Action | Tích hợp Method B (Rate limit 10/ngày, SHA-256 dedup, DocumentFileStorage, tạo TaiLieu CHO_DUYET) |
| 6 | `app/Modules/Bounties/Presentation/Http/Controllers/WishlistBountyController.php` | Backend / Controller | Chuyển file tải lên vào action |
| 7 | `app/Modules/Documents/Application/Queries/GetDocumentDetailQuery.php` | Backend / Query | Chặn truy cập tài liệu chưa duyệt (HTTP 403 đối với người ngoài) |
| 8 | `app/Modules/Documents/Application/Actions/PreviewDocumentAction.php` | Backend / Action | Chặn xem trước tài liệu chưa duyệt |
| 9 | `app/Modules/Documents/Application/Actions/DownloadDocumentAction.php` | Backend / Action | Chặn tải xuống tài liệu chưa duyệt |
| 10 | `app/Modules/Documents/Presentation/Http/Controllers/DocumentController.php` | Backend / Controller | Truyền user hiện tại vào query & action |
| 11 | `resources/js/Components/Wishlist/FulfillRequestModal.jsx` | Frontend / Component | Giao diện Tab A/B, Drag-drop, SHA-256 pre-check, moderation notice |
| 12 | `resources/js/Pages/Courses/Show.jsx` | Frontend / Page | Truyền prop `course={course}` vào `FulfillRequestModal` |
| 13 | `resources/js/Pages/Wishlist/Show.jsx` | Frontend / Page | Truyền prop `course={item.mon_hoc}` vào `FulfillRequestModal` |
| 14 | `tests/Feature/DocumentContributionBountyIntegrationTest.php` | Testing / Feature | Bộ kiểm thử tự động toàn diện 16 kịch bản mới |

---

## 4. KẾT LUẬN

Tất cả các rủi ro bảo mật (AUD-H01, AUD-H02) và lỗi schema SQLSTATE[42S22] đã được giải quyết triệt để. Luồng đóng góp tài liệu từ máy cá nhân (Method B) hoạt động liền mạch và tuân thủ chặt chẽ kiến trúc kiểm duyệt phân tán của hệ thống AGU Academic Hub.

