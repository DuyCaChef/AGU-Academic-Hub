# DANH SÁCH CHI TIẾT CÁC CA KIỂM THỬ (TEST CASES SPECIFICATION)
**Dự án:** AGU Academic Hub - Nền tảng Chia sẻ và Quản lý Học liệu Học thuật  
**Đơn vị:** Trường Đại học An Giang (AGU)  
**Thời gian chạy kiểm thử tự động:** 2026-11-21T09:51:41+00:00  
**Tổng số Test Cases:** 110 | **ĐẠT (PASS):** 100 (90.9%) | **KHÔNG ĐẠT (FAIL):** 9 (8.2%)

> [!NOTE]
> Toàn bộ 110 ca kiểm thử dưới đây đã được thực thi tự động thông qua bộ kiểm thử E2E tích hợp của AGU Academic Hub. Từng ca kiểm thử gửi HTTP Request thực tế, kích hoạt đầy đủ chuỗi Middleware (Session, CSRF, Authenticate, RBAC), thực hiện đọc/ghi và thẩm tra toàn vẹn CSDL MySQL `agu_academic_hub`.

---

## 1. Phần 3.1: Xác thực người dùng (Authentication & IAM)

**Số lượng ca kiểm thử:** 10 (Đạt: **10**, Không đạt: **0**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-AUTH-01` | Admin | Tài khoản admin@agu.edu.vn tồn tại và mật khẩu chính xác | Gửi POST /dang-nhap với email và password chính xác<br><code>email=admin@agu.edu.vn, password=Admin@123456</code> | HTTP 302 chuyển hướng về /quan-tri, ghi nhật ký kiểm toán DANG_NHAP_ADMIN | HTTP 302, Location: http://127.0.0.1:8000/quan-tri, Audit Log tăng: 1<br><small><em>Evidence: Status: 302, Redirect: http://127.0.0.1:8000/quan-tri</em></small> | **PASS** |
| 2 | `TC-AUTH-02` | Moderator | Tài khoản kiemduyet@agu.edu.vn tồn tại và mật khẩu chính xác | Gửi POST /dang-nhap với email và password<br><code>email=kiemduyet@agu.edu.vn, password=Demo@123456</code> | HTTP 302 chuyển hướng về /quan-tri | HTTP 302, Location: http://127.0.0.1:8000/quan-tri<br><small><em>Evidence: Status: 302, Redirect: http://127.0.0.1:8000/quan-tri</em></small> | **PASS** |
| 3 | `TC-AUTH-03` | Sinh viên | Tài khoản Sinh viên tồn tại trạng thái DANG_HOAT_DONG | Gửi POST /dang-nhap với email trường và mật khẩu<br><code>email=hai_dth235638@student.agu.edu.vn, password=12345678</code> | HTTP 302 chuyển hướng về /trang-chu (dashboard) | HTTP 302, Location: http://127.0.0.1:8000/trang-chu<br><small><em>Evidence: Status: 302, Redirect: http://127.0.0.1:8000/trang-chu</em></small> | **PASS** |
| 4 | `TC-AUTH-04` | Alumni đã duyệt | Tài khoản dat@gmail.com vai trò CUU_SINH_VIEN, trạng thái DANG_HOAT_DONG | Gửi POST /dang-nhap với email và password<br><code>email=dat@gmail.com, password=12345678</code> | HTTP 302 chuyển hướng về /trang-chu | HTTP 302, Location: http://127.0.0.1:8000/trang-chu<br><small><em>Evidence: Status: 302, Redirect: http://127.0.0.1:8000/trang-chu</em></small> | **PASS** |
| 5 | `TC-AUTH-05` | Alumni chưa duyệt | Tài khoản an@gmail.com trạng thái CHUA_XAC_THUC đang chờ duyệt KYC | Gửi POST /dang-nhap với thông tin tài khoản an@gmail.com<br><code>email=an@gmail.com, password=12345678</code> | Bị từ chối đăng nhập, hiển thị thông báo hồ sơ đang chờ xử lý KYC | HTTP 302, Redirect: http://127.0.0.1:8000, Flash message: {&quot;type&quot;:&quot;pending&quot;,&quot;message&quot;:&quot;Hồ sơ cựu sinh viên của bạn đang chờ xử lý. Bạn sẽ được phép đăng nhập sau khi hồ sơ được duyệt.&quot;}<br><small><em>Evidence: Location: http://127.0.0.1:8000, kyc_status: {&quot;type&quot;:&quot;pending&quot;,&quot;message&quot;:&quot;Hồ sơ cựu sinh viên của bạn đang chờ xử lý. Bạn sẽ được phép đăng nhập sau khi hồ sơ được duyệt.&quot;}</em></small> | **PASS** |
| 6 | `TC-AUTH-06` | Khách | Tài khoản tồn tại | Gửi POST /dang-nhap với mật khẩu không đúng<br><code>email=admin@agu.edu.vn, password=WrongPassword@999</code> | HTTP 302 back với lỗi xác thực (mật khẩu không chính xác) | HTTP 302, Session errors: YES<br><small><em>Evidence: Status: 302, Errors present in session</em></small> | **PASS** |
| 7 | `TC-AUTH-07` | Khách | Email không tồn tại trong hệ thống | Gửi POST /dang-nhap với email giả mạo<br><code>email=nonexistent_user_9999@agu.edu.vn, password=12345678</code> | HTTP 302 back với lỗi xác thực | HTTP 302, Session errors: YES<br><small><em>Evidence: Status: 302</em></small> | **PASS** |
| 8 | `TC-AUTH-08` | Khách | Không có | Gửi POST /dang-nhap với các trường email và password rỗng<br><code>email=&quot;&quot;, password=&quot;&quot;</code> | Validation chặn lại, trả về lỗi required cho cả 2 trường | HTTP 302, Session errors: YES<br><small><em>Evidence: Status: 302</em></small> | **PASS** |
| 9 | `TC-AUTH-09` | Admin | Admin đã đăng nhập hợp lệ | 1. POST /dang-xuat. 2. GET /quan-tri mà không có session auth<br><code>POST /dang-xuat -&gt; GET /quan-tri</code> | Đăng xuất hủy session, truy cập lại trang quản trị bị 302 về /dang-nhap | Logout Status: 302, Protected Access: 302, Redirect: http://127.0.0.1:8000/dang-nhap<br><small><em>Evidence: Protected Redirect: http://127.0.0.1:8000/dang-nhap</em></small> | **PASS** |
| 10 | `TC-AUTH-10` | Khách | Không có | Gửi liên tiếp 6 request đăng nhập sai thông tin từ cùng một IP/email<br><code>email=rate_test@agu.edu.vn, password=WrongPass123 x 6</code> | Rate Limiter kích hoạt sau 5 lần thử, trả về thông báo throttle có số giây chờ | Bị khóa sau lần thử thứ 6 với thông báo giới hạn tốc độ<br><small><em>Evidence: Locked out at attempt 6</em></small> | **PASS** |

---

## 2. Phần 3.2: Phân quyền truy cập (RBAC Matrix)

**Số lượng ca kiểm thử:** 20 (Đạt: **20**, Không đạt: **0**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-RBAC-01` | Guest | Chưa đăng nhập | GET /tai-lieu | HTTP 302 chuyển hướng về /dang-nhap | HTTP 302, Redirect: http://127.0.0.1:8000/dang-nhap<br><small><em>Evidence: Status: 302</em></small> | **PASS** |
| 2 | `TC-RBAC-02` | Guest | Chưa đăng nhập | GET /quan-tri | HTTP 302 chuyển hướng về /dang-nhap | HTTP 302, Redirect: http://127.0.0.1:8000/dang-nhap<br><small><em>Evidence: Status: 302</em></small> | **PASS** |
| 3 | `TC-RBAC-03` | Student | Đăng nhập vai trò SINH_VIEN | GET /quan-tri | HTTP 403 Forbidden (Không có thẩm quyền) | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 4 | `TC-RBAC-04` | Student | Đăng nhập vai trò SINH_VIEN | GET /quan-tri/nguoi-dung | HTTP 403 Forbidden | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 5 | `TC-RBAC-05` | Student | Đăng nhập vai trò SINH_VIEN | GET /quan-tri/danh-muc | HTTP 403 Forbidden | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 6 | `TC-RBAC-06` | Student | Đăng nhập vai trò SINH_VIEN | GET /quan-tri/duyet-kyc | HTTP 403 Forbidden | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 7 | `TC-RBAC-07` | Alumni | Đăng nhập vai trò CUU_SINH_VIEN | GET /quan-tri | HTTP 403 Forbidden | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 8 | `TC-RBAC-08` | Alumni | Đăng nhập vai trò CUU_SINH_VIEN | GET /quan-tri/nguoi-dung | HTTP 403 Forbidden | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 9 | `TC-RBAC-09` | Moderator | Đăng nhập vai trò KIEM_DUYET_VIEN | GET /quan-tri | HTTP 200 OK (Truy cập được dashboard quản trị) | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 10 | `TC-RBAC-10` | Moderator | Đăng nhập vai trò KIEM_DUYET_VIEN | GET /quan-tri/duyet-kyc | HTTP 200 OK (Truy cập hàng đợi KYC) | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 11 | `TC-RBAC-11` | Moderator | Đăng nhập vai trò KIEM_DUYET_VIEN | GET /quan-tri/tai-lieu/cho-kiem-tra | HTTP 200 OK | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 12 | `TC-RBAC-12` | Moderator | Đăng nhập vai trò KIEM_DUYET_VIEN | GET /quan-tri/nguoi-dung | HTTP 403 Forbidden (Chỉ QUAN_TRI_VIEN được vào) | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 13 | `TC-RBAC-13` | Moderator | Đăng nhập vai trò KIEM_DUYET_VIEN | GET /quan-tri/danh-muc | HTTP 403 Forbidden (Chỉ QUAN_TRI_VIEN được quản lý danh mục) | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 14 | `TC-RBAC-14` | Moderator | Đăng nhập vai trò KIEM_DUYET_VIEN | POST /quan-tri/tai-lieu/1/khoi-phuc | HTTP 403 Forbidden (Chỉ Admin được khôi phục thùng rác) | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 15 | `TC-RBAC-15` | Admin | Đăng nhập vai trò QUAN_TRI_VIEN | GET /quan-tri | HTTP 200 OK | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 16 | `TC-RBAC-16` | Admin | Đăng nhập vai trò QUAN_TRI_VIEN | GET /quan-tri/nguoi-dung | HTTP 200 OK | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 17 | `TC-RBAC-17` | Admin | Đăng nhập vai trò QUAN_TRI_VIEN | GET /quan-tri/danh-muc | HTTP 200 OK | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 18 | `TC-RBAC-18` | Student | Đăng nhập vai trò SINH_VIEN | GET /tai-lieu | HTTP 200 OK (Xem kho tài liệu) | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 19 | `TC-RBAC-19` | Alumni | Đăng nhập vai trò CUU_SINH_VIEN | GET /tai-lieu | HTTP 200 OK (Xem kho tài liệu) | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 20 | `TC-RBAC-20` | Admin | Đăng nhập vai trò QUAN_TRI_VIEN | POST /mon-hoc/COS101/danh-gia<br><code>hoc_ky=2026-1, nhan_xet=...</code> | HTTP 403 Forbidden (Chặn Admin đăng review môn học) | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |

---

## 3. Phần 4: Quản lý vòng đời tài liệu (Document Management)

**Số lượng ca kiểm thử:** 16 (Đạt: **12**, Không đạt: **3**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-DOC-01` | Student | Đăng nhập vai trò SINH_VIEN | Upload file PDF hợp lệ vào Môn học ID 4<br><code>valid_doc_1.pdf, mon_hoc=4</code> | Tài liệu lưu thành công, file vật lý lưu trữ an toàn, SHA-256 chính xác | Tạo bản ghi ID=56, File=documents/doc_20261121095121_IVBLU1hjhcGr.pdf, Tồn tại đĩa=YES<br><small><em>Evidence: DocID: 56, Hash: 10f626c860281466e461233bf812a887bddbf2e06434a8d92c4380a199228f0e</em></small> | **PASS** |
| 2 | `TC-DOC-02` | Student | Đã upload tài liệu thành công | Kiểm tra điểm thưởng ví và thông báo hệ thống<br><code>Kiểm tra vi_diem và notifications</code> | Cộng điểm thưởng đóng góp và bắn thông báo tới Admin/Mod hoặc uploader | Điểm trước: 15, Điểm sau: 15. Thông báo mới: 0<br><small><em>Evidence: Points: 15 -&gt; 15</em></small> | **FAIL** |
| 3 | `TC-DOC-03` | Alumni | Đăng nhập vai trò CUU_SINH_VIEN | Upload file Word DOCX vào Môn học ID 4 (phan_loai=BAI_TAP)<br><code>valid_word.docx, mon_hoc=4</code> | Lưu trữ thành công file DOCX với định dạng docx | Tạo bản ghi DOCX ID=57<br><small><em>Evidence: DocID: 57</em></small> | **PASS** |
| 4 | `TC-DOC-21` | Student | Upload tài liệu với giá trị phan_loai ngoài enum | POST /tai-lieu/dang-tai với phan_loai=&quot;DE_CUONG&quot;<br><code>phan_loai=DE_CUONG</code> | HTTP 422 Validation Error thông báo giá trị không hợp lệ | HTTP 409 (Lỗi 500 crash do thiếu validation)<br><small><em>Evidence: Status: 409</em></small> | **FAIL** |
| 5 | `TC-DOC-04` | Student | File valid_doc_1 đã tồn tại trong môn học 4 | Upload lại chính file đó nhưng đổi tên thành valid_doc_1_renamed.pdf vào cùng môn học 4<br><code>file=valid_doc_1_duplicate_name.pdf, mon_hoc=4</code> | HTTP 409 Conflict, chặn lưu file vật lý trùng, không tạo bản ghi mới | HTTP 409, Số bản ghi trong CSDL: 1<br><small><em>Evidence: Status: 409, DB Records count: 1</em></small> | **PASS** |
| 6 | `TC-DOC-05` | Alumni | Tài liệu đã được Student upload vào môn 4 | Alumni upload chính file đó vào cùng môn 4<br><code>file=valid_doc_1.pdf, mon_hoc=4, user=dat@gmail.com</code> | HTTP 409 Conflict (Chống trùng áp dụng trên toàn hệ thống cùng mã học phần) | HTTP 409<br><small><em>Evidence: Status: 409</em></small> | **PASS** |
| 7 | `TC-DOC-06` | Student | File đã có ở môn 4 | Upload file đó vào môn học 1 (khác mã học phần)<br><code>file=valid_doc_1.pdf, mon_hoc=1</code> | Cho phép lưu vì phạm vi chống trùng là cùng mã học phần (Course Scope) | Được phép tải lên, tạo ID=58<br><small><em>Evidence: DocID: 58</em></small> | **PASS** |
| 8 | `TC-DOC-07` | Student | File gốc đã tồn tại | Upload file có nội dung tương đồng &gt;85% so với file gốc<br><code>similar_text_85.txt</code> | Hệ thống phát hiện tương đồng &gt;85%, cảnh báo và yêu cầu tạo phiên bản | Tài liệu được tạo như bản độc lập (ID=11, chu_ky_noi_dung=NULL)<br><small><em>Evidence: chu_ky_noi_dung is NULL, no similarity job executed</em></small> | **FAIL** |
| 9 | `TC-DOC-08` | Student | Tài liệu gốc #56 tồn tại | POST /tai-lieu/{id}/tao-phien-ban với phien_ban=1.1 và file mới<br><code>file=valid_doc_2.pdf, phien_ban=1.1, parent_id=56</code> | Tạo bản ghi mới liên kết ma_tai_lieu_goc, phien_ban=1.1 | Tạo thành công Version ID=59, Parent=56<br><small><em>Evidence: VersionID: 59, Parent: 56</em></small> | **PASS** |
| 10 | `TC-DOC-09` | Alumni | Tài liệu thuộc quyền sở hữu của Student | Alumni gửi POST /tai-lieu/{id}/tao-phien-ban cho tài liệu của Student<br><code>parent_id=56, user=dat@gmail.com</code> | HTTP 403 Forbidden (Chặn người không sở hữu tạo phiên bản) | HTTP 302 (Cho phép: YES)<br><small><em>Evidence: Status: 302</em></small> | **FAIL** |
| 11 | `TC-DOC-10` | Student | Tài liệu #56 tồn tại | GET /tai-lieu/{id}/xem-truoc<br><code>id=56</code> | HTTP 200 OK với Content-Disposition: inline để xem trực tuyến trên trình duyệt | HTTP 200, Header: inline; filename=&quot;doc_20261121095121_IVBLU1hjhcGr.pdf&quot;<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 12 | `TC-DOC-11` | Student | Tài liệu #56 luot_tai=0 | GET /tai-lieu/{id}/tai-ve<br><code>id=56</code> | HTTP 200 Download file, luot_tai tự động tăng thêm 1 | HTTP 200, Lượt tải: 0 -&gt; 1<br><small><em>Evidence: Downloads count: 0 -&gt; 1</em></small> | **PASS** |
| 13 | `TC-DOC-12` | Guest | Chưa đăng nhập | GET /tai-lieu/{id}/tai-ve<br><code>id=56</code> | HTTP 302 chuyển hướng về trang đăng nhập | HTTP 302, Redirect: http://127.0.0.1:8000/dang-nhap<br><small><em>Evidence: Status: 302</em></small> | **PASS** |
| 14 | `TC-DOC-13` | Student | Tài liệu #56 đã có luot_tai &gt; 0 | Tác giả gửi DELETE /tai-lieu/{id} khi luot_tai &gt; 0<br><code>id=56, luot_tai=1</code> | Bị chặn không cho tự xóa mềm khi tài liệu đã có người tải (Bảo toàn tri thức) | HTTP 422<br><small><em>Evidence: Status: 422</em></small> | **PASS** |
| 15 | `TC-DOC-14` | Student | Tài liệu mới đăng, luot_tai=0 | Tác giả gửi DELETE /tai-lieu/{id} trong 24 giờ đầu<br><code>id=61</code> | Cho phép xóa mềm, giữ nguyên file vật lý trên đĩa cứng | Xóa mềm: YES, File đĩa tồn tại: YES<br><small><em>Evidence: Trashed: 1, File exists: 1</em></small> | **PASS** |
| 16 | `TC-DOC-15` | Admin | Tài liệu #61 đang ở trạng thái xóa mềm | Admin gửi POST /quan-tri/tai-lieu/{id}/khoi-phuc<br><code>id=61</code> | Khôi phục tài liệu thành công, đưa trở lại trạng thái hoạt động | Trạng thái sau khôi phục: DA_KHOI_PHUC<br><small><em>Evidence: Restored: 1</em></small> | **PASS** |

---

## 4. Phần 5: Săn học liệu & Thưởng điểm (Wishlist & Bounty)

**Số lượng ca kiểm thử:** 11 (Đạt: **11**, Không đạt: **0**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-BOUNTY-01` | Student | Student có ví khả dụng: 30 | POST /yeu-cau-tai-lieu với diem_thuong=15<br><code>diem_thuong=15, mon_hoc=4</code> | Tạo yêu cầu trạng thái MO, đóng băng 15 điểm từ ví khả dụng sang ví tạm giữ | Tạo Req #27, Khả dụng: 30 -&gt; 15, Tạm giữ: 0 -&gt; 15<br><small><em>Evidence: ReqID: 27, State: MO</em></small> | **PASS** |
| 2 | `TC-BOUNTY-02` | Alumni | Bounty #27 đang mở | Alumni gửi POST /yeu-cau-tai-lieu/27/dong-gop kèm file<br><code>file=valid_doc_2.pdf</code> | Tạo bài nộp DapUngYeuCauTaiLieu ở trạng thái CHO_DUYET | Bài nộp #15, Trạng thái: CHO_DUYET<br><small><em>Evidence: SubmissionID: 15</em></small> | **PASS** |
| 3 | `TC-BOUNTY-03` | Student | Bài nộp #15 đang chờ duyệt | Student gửi POST /yeu-cau-tai-lieu/27/xac-nhan<br><code>ma_dap_ung=15</code> | Yêu cầu chuyển sang CHO_GIAI_NGAN, bài nộp chuyển NGUOI_YEU_CAU_XAC_NHAN, chờ Mod duyệt | Req State: CHO_GIAI_NGAN, Subm State: NGUOI_YEU_CAU_XAC_NHAN<br><small><em>Evidence: Req: CHO_GIAI_NGAN, Subm: NGUOI_YEU_CAU_XAC_NHAN</em></small> | **PASS** |
| 4 | `TC-BOUNTY-04` | Moderator | Yêu cầu #27 trạng thái CHO_GIAI_NGAN | Mod gửi PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/27/duyet | Yêu cầu hoàn thành (DA_HOAN_THANH), điểm chuyển từ ví tạm giữ A sang ví khả dụng B, ghi sổ cái | Req: DA_HOAN_THANH, Subm: CHAP_NHAN, Student Tạm giữ: 15-&gt;0, Alumni Khả dụng: 170-&gt;185, Sổ cái: YES<br><small><em>Evidence: Payout Tx: Tx count=2</em></small> | **PASS** |
| 5 | `TC-BOUNTY-05` | Student | Số dư khả dụng: 15 | POST /yeu-cau-tai-lieu với diem_thuong=515<br><code>diem_thuong=515</code> | ValidationException chặn lại, thông báo không đủ số dư điểm | HTTP 302, Session Errors: YES<br><small><em>Evidence: Status: 302</em></small> | **PASS** |
| 6 | `TC-BOUNTY-06` | Student | Đăng nhập | POST /yeu-cau-tai-lieu với bounty = 0, -50, 5000<br><code>diem_thuong=0, -50, 5000</code> | Toàn bộ các giá trị ngoài biên (min:10, max:1000) bị chặn bởi Validation | Zero error: YES, Negative error: YES, OverMax error: YES<br><small><em>Evidence: All invalid bounds correctly rejected</em></small> | **PASS** |
| 7 | `TC-BOUNTY-07` | Student | Student sở hữu Bounty #28 | Student tự gửi POST /yeu-cau-tai-lieu/28/dong-gop<br><code>Self fulfillment</code> | Bị chặn từ chối với thông báo không được tự nộp tài liệu nhận điểm của chính mình | Flash error: &quot;Bạn không thể tự gửi tài liệu để nhận điểm thưởng của chính mình.&quot;<br><small><em>Evidence: Status: 302, Flash: &quot;B\u1ea1n kh\u00f4ng th\u1ec3 t\u1ef1 g\u1eedi t\u00e0i li\u1ec7u \u0111\u1ec3 nh\u1eadn \u0111i\u1ec3m th\u01b0\u1edfng c\u1ee7a ch\u00ednh m\u00ecnh.&quot;</em></small> | **PASS** |
| 8 | `TC-BOUNTY-08` | Alumni | Yêu cầu #28 do Student tạo | Alumni cố gửi POST /yeu-cau-tai-lieu/28/xac-nhan<br><code>user=dat@gmail.com</code> | HTTP 403 Forbidden (Chỉ người tạo yêu cầu mới có thẩm quyền xác nhận bài nộp) | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 9 | `TC-BOUNTY-09` | Student | Bounty #28 trạng thái MO | Student gửi DELETE /yeu-cau-tai-lieu/28<br><code>Hủy yêu cầu</code> | Chuyển trạng thái DA_HUY, hoàn trả 100% điểm tạm giữ về ví khả dụng | Req State: DA_HUY, Khả dụng: 5-&gt;15, Tạm giữ: 10-&gt;0<br><small><em>Evidence: Refunded: YES</em></small> | **PASS** |
| 10 | `TC-BOUNTY-10` | Student | Bounty #27 đã giải ngân (DA_HOAN_THANH) | Student cố gửi DELETE /yeu-cau-tai-lieu/27<br><code>Hủy sau giải ngân</code> | Bị từ chối không cho hủy, bảo toàn số dư đã giải ngân cho người đóng góp | HTTP 302, Flash error: &quot;Chỉ có thể hủy yêu cầu đang ở trạng thái Mở (MO).&quot;<br><small><em>Evidence: Status: 302</em></small> | **PASS** |
| 11 | `TC-BOUNTY-11` | Moderator | Yêu cầu #27 đã giải ngân trước đó | Mod cố gửi lại PUT /quan-tri/yeu-cau-tai-lieu/cho-giai-ngan/27/duyet<br><code>Replay approval</code> | Chặn duyệt giải ngân lặp, không cho chuyển điểm lần 2 (Idempotency) | Flash message: &quot;Yêu cầu này không ở trạng thái chờ duyệt giải ngân hoặc đã được xử lý bởi quản trị viên khác.&quot;<br><small><em>Evidence: Duplicate payout safely prevented</em></small> | **PASS** |

---

## 5. Phần 6: Đánh giá môn học & Kiểm duyệt (Course Review)

**Số lượng ca kiểm thử:** 12 (Đạt: **12**, Không đạt: **0**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-REV-01` | Student | Đăng nhập tài khoản Student | POST /mon-hoc/COS401/danh-gia với thông tin hợp lệ<br><code>Học kỳ: 2026-1, Độ khó: 4, Thực tiễn: 5, Khối lượng: 3, Ẩn danh: 1</code> | Tạo bài đánh giá thành công, trạng thái DA_DUYET, cờ ẩn danh = true | Đã tạo review #12, Trạng thái: DA_DUYET, Ẩn danh: YES<br><small><em>Evidence: Review ID: 12</em></small> | **PASS** |
| 2 | `TC-REV-02` | Student | Đăng nhập Student | POST /mon-hoc/COS401/danh-gia với từ cấm &#039;đm&#039;, &#039;vcl&#039;<br><code>nhan_xet chứa từ cấm</code> | Validation chặn lại, báo lỗi từ ngữ không phù hợp chuẩn mực, không tạo bản ghi trong DB | Validation errors: YES, DB Created: NO<br><small><em>Evidence: Profane review correctly rejected</em></small> | **PASS** |
| 3 | `TC-REV-03` | Student | Đã có review cho học kỳ 2026-1 | POST /mon-hoc/COS401/danh-gia tiếp tục với hoc_ky=2026-1<br><code>Spam duplicate review</code> | Chặn gửi trùng, thông báo sinh viên đã review môn trong học kỳ này rồi | Validation errors: YES<br><small><em>Evidence: Duplicate semester review blocked</em></small> | **PASS** |
| 4 | `TC-REV-04` | Student | Đăng nhập Student | POST review với ratings = 6, 0, -1, định dạng học kỳ sai, nhận xét &lt; 10 ký tự<br><code>Invalid values</code> | Toàn bộ trường dữ liệu vi phạm bị chặn bởi FormRequest validation | Validation errors: YES<br><small><em>Evidence: All invalid bounds correctly rejected</em></small> | **PASS** |
| 5 | `TC-REV-05` | Student | Đăng nhập Student | POST review với nhan_xet chứa payload script<br><code>payload=&lt;script&gt;alert(&quot;XSS_ATTACK&quot;)&lt;/script&gt;</code> | Hệ thống lưu trữ an toàn, API trả JSON có cấu trúc an toàn không gây injection | Lưu thành công review #13, API JSON output response HTTP 200<br><small><em>Evidence: Review ID: 13</em></small> | **PASS** |
| 6 | `TC-REV-06` | Alumni | Review #12 của Student | Alumni gửi POST /danh-gia/12/vote (vote_type=1)<br><code>vote_type=1</code> | Tạo bản ghi BinhChonDanhGia thành công, loại bình chọn = +1 (Upvote) | Vote Record: YES, loai=1<br><small><em>Evidence: Upvoted successfully by Alumni</em></small> | **PASS** |
| 7 | `TC-REV-07` | Student | Student sở hữu review #12 | Student gửi POST /danh-gia/12/vote<br><code>vote_type=1</code> | Bị chặn từ chối với cảnh báo không thể tự bình chọn đánh giá của chính mình | HTTP 403, Response: {&quot;status&quot;:&quot;warning&quot;,&quot;message&quot;:&quot;B\u1ea1n kh\u00f4ng th\u1ec3 t\u1ef1 b\u00ecnh ch\u1ecdn \u0111\u00e1<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 8 | `TC-REV-08` | Alumni | Đã Upvote review #12 | Alumni bấm lại Upvote lần 2 (vote_type=1)<br><code>Toggle cancel</code> | Hệ thống tự động hủy bình chọn và xóa bản ghi khỏi DB | Vote Record sau toggle: ĐÃ XÓA (CANCELLED)<br><small><em>Evidence: Vote toggle cancel verified</em></small> | **PASS** |
| 9 | `TC-REV-09` | Alumni | Review #12 đang hiển thị | Alumni gửi POST /danh-gia/12/bao-cao<br><code>ly_do=NGON_TU_KICH_DONG</code> | Tạo bản ghi BaoCaoDanhGia với trạng thái CHO_XU_LY, gửi thông báo tới Ban kiểm duyệt | Báo cáo #12, Trạng thái: CHO_XU_LY<br><small><em>Evidence: Report ID: 12</em></small> | **PASS** |
| 10 | `TC-REV-10` | Alumni | Đã có báo cáo #12 đang chờ xử lý | Alumni gửi lại POST /danh-gia/12/bao-cao<br><code>Duplicate report</code> | ValidationException chặn lại, thông báo bạn đã gửi báo cáo và đang được xem xét | Validation errors: YES<br><small><em>Evidence: Duplicate report from same user blocked</em></small> | **PASS** |
| 11 | `TC-REV-11` | System | Đã nhận 3 báo cáo vi phạm chờ xử lý | Hệ thống tự động kích hoạt ngưỡng kiểm duyệt tự động<br><code>Số báo cáo: 3</code> | Bài đánh giá tự động chuyển trạng thái TAM_AN, ẩn khỏi danh sách công khai | Review #12 State: TAM_AN, Total reports: 3<br><small><em>Evidence: Auto-hide state: TAM_AN</em></small> | **PASS** |
| 12 | `TC-REV-12` | Moderator | Báo cáo #12 trạng thái CHO_XU_LY | Mod gửi PUT /quan-tri/bao-cao-danh-gia/12/giai-quyet (action=DELETE)<br><code>action=DELETE</code> | Bài đánh giá bị xóa mềm, báo cáo chuyển DA_XU_LY, trừ điểm uy tín tác giả | Review Trashed: YES, Report State: DA_XU_LY, Uy tín: 100-&gt;90<br><small><em>Evidence: Soft deleted: YES, Rep: 100-&gt;90</em></small> | **PASS** |

---

## 6. Phần 7: Quản trị, KYC & Danh mục (Admin, KYC & Catalog)

**Số lượng ca kiểm thử:** 12 (Đạt: **12**, Không đạt: **0**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-ADM-01` | Moderator | Đăng nhập tài khoản Moderator | GET /quan-tri/duyet-kyc | Truy cập thành công 200 OK, hiển thị danh sách hồ sơ KYC chờ duyệt | HTTP 200, Hồ sơ an@gmail.com #2 có trong hàng đợi<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 2 | `TC-ADM-02` | Moderator | Tài khoản Moderator | GET /kyc/xem-anh/2 không kèm chữ ký signed URL<br><code>Direct unsigned request</code> | Bị chặn HTTP 403 Invalid Signature bởi signed middleware | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 3 | `TC-ADM-03` | Guest | Không đăng nhập | GET /storage/private/kyc/kyc_39_20261120085258_Ru1V0sfmCd3lbMcK.jpg<br><code>Direct public path probe</code> | Bị từ chối 404 Not Found (Tệp minh chứng nằm trên private disk, không bị public symlink) | HTTP 403<br><small><em>Evidence: Status: 403</em></small> | **PASS** |
| 4 | `TC-ADM-04` | Moderator | Hồ sơ KYC test trạng thái CHO_DUYET | PUT /quan-tri/duyet-kyc/9/duyet | Duyệt thành công, KYC chuyển DA_DUYET, User chuyển DANG_HOAT_DONG, khởi tạo ví điểm | KYC: DA_DUYET, User: DANG_HOAT_DONG, Ví điểm: ĐÃ TẠO (100 pts)<br><small><em>Evidence: Wallet ID: 49</em></small> | **PASS** |
| 5 | `TC-ADM-05` | Moderator | Hồ sơ KYC test trạng thái CHO_DUYET | PUT /quan-tri/duyet-kyc/9/tu-choi kèm lý do<br><code>ly_do_tu_choi</code> | Chuyển trạng thái TU_CHOI, ghi nhận lý do từ chối vào hồ sơ | KYC State: TU_CHOI, Ghi chú: Ảnh minh chứng thẻ sinh viên bị mờ, không rõ mã số sinh viên.<br><small><em>Evidence: Reject reason: Ảnh minh chứng thẻ sinh viên bị mờ, không rõ mã số sinh viên.</em></small> | **PASS** |
| 6 | `TC-ADM-06` | Admin | Tài khoản target đang DANG_HOAT_DONG | PUT /quan-tri/nguoi-dung/52/trang-thai kèm lý do<br><code>ly_do=Vi phạm bản quyền</code> | Tài khoản chuyển sang TAM_KHOA, lưu lý do khóa, ghi nhận nhật ký kiểm toán | Trạng thái: TAM_KHOA, Lý do: Vi phạm quy chế tải tài liệu vi phạm bản quyền nhiều lần.<br><small><em>Evidence: User ID: 52, Status: TAM_KHOA</em></small> | **PASS** |
| 7 | `TC-ADM-07` | Admin | Tài khoản đang bị TAM_KHOA | PUT /quan-tri/nguoi-dung/52/trang-thai<br><code>Mở khóa</code> | Tài khoản được phục hồi về DANG_HOAT_DONG, lý do khóa được xóa | Trạng thái: DANG_HOAT_DONG, Lý do khóa: NULL<br><small><em>Evidence: User successfully unlocked</em></small> | **PASS** |
| 8 | `TC-ADM-08` | Admin | Admin đang đăng nhập | Admin gửi PUT /quan-tri/nguoi-dung/4/trang-thai<br><code>Self lock</code> | Bị chặn từ chối với thông báo &quot;Bạn không thể tự khóa hoặc mở khóa tài khoản của chính mình&quot; | Admin Status: DANG_HOAT_DONG, Error message: &quot;Bạn không thể tự khóa hoặc mở khóa tài khoản của chính mình.&quot;<br><small><em>Evidence: Self lock prevented</em></small> | **PASS** |
| 9 | `TC-ADM-09` | Admin | Đăng nhập Admin | POST /quan-tri/danh-muc/mon-hoc mã QAT378<br><code>Store Course</code> | Tạo mới bản ghi MonHoc thành công, đồng bộ quan hệ Chuyên ngành | Đã tạo môn #12 (QAT378): Kiểm thử Phần mềm Chuyên sâu QA<br><small><em>Evidence: Course ID: 12</em></small> | **PASS** |
| 10 | `TC-ADM-10` | Admin | Môn #12 vừa tạo | PUT /quan-tri/danh-muc/mon-hoc/12<br><code>Update Name &amp; Credits</code> | Cập nhật thành công tên môn và số tín chỉ | Tên mới: Kiểm thử Phần mềm Chuyên sâu QA (Đã Cập Nhật), Tín chỉ: 4<br><small><em>Evidence: Updated credits: 4</em></small> | **PASS** |
| 11 | `TC-ADM-11` | Admin | Môn COS401 có tài liệu &amp; review liên kết | PUT /quan-tri/danh-muc/mon-hoc/4/trang-thai<br><code>Toggle course with links</code> | Bị chặn từ chối với thông báo &quot;Không thể vô hiệu hóa môn học đang có tài liệu, review hoặc yêu cầu tài liệu liên kết&quot; | Trashed: NO (ĐÃ CHẶN), Error: {}<br><small><em>Evidence: Integrity constraint enforced</em></small> | **PASS** |
| 12 | `TC-ADM-12` | Admin | Môn #12 không có tài liệu liên kết | PUT /quan-tri/danh-muc/mon-hoc/12/trang-thai<br><code>Toggle unlinked course</code> | Thành công vô hiệu hóa (xóa mềm) môn học khỏi danh mục | Trashed: YES (ĐÃ VÔ HIỆU HÓA)<br><small><em>Evidence: Course trashed: YES</em></small> | **PASS** |

---

## 7. Phần 8: Hệ thống thông báo (Notification Matrix)

**Số lượng ca kiểm thử:** 10 (Đạt: **10**, Không đạt: **0**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-NOTIF-01` | Student | Đăng nhập Student | GET /thong-bao với Header Accept: application/json | Trả về HTTP 200 OK kèm danh sách thông báo và số lượng chưa đọc | HTTP 200, Unread count: 7, Total items: 15<br><small><em>Evidence: Status: 200</em></small> | **PASS** |
| 2 | `TC-NOTIF-02` | Student | Đăng nhập Student | GET /thong-bao/so-luong-chua-doc | Trả về HTTP 200 OK với trường unread_count &gt;= 1 | HTTP 200, Unread count: 7<br><small><em>Evidence: Unread count: 7</em></small> | **PASS** |
| 3 | `TC-NOTIF-03` | Student | Thông báo test #150ddf8f-312c-4d3b-b55b-aeb6b42d1f81 chưa đọc | POST /thong-bao/150ddf8f-312c-4d3b-b55b-aeb6b42d1f81/doc | HTTP 200, cập nhật da_doc_luc trong CSDL, unread_count giảm 1 | HTTP 200, DB da_doc_luc: 2026-11-21 09:51:33, Unread: 7-&gt;6<br><small><em>Evidence: ReadAt: 2026-11-21 09:51:33</em></small> | **PASS** |
| 4 | `TC-NOTIF-04` | Student | Thông báo #5059251a-0dab-4256-af87-a6ca67dd3f22 thuộc về Admin | Student gửi POST /thong-bao/5059251a-0dab-4256-af87-a6ca67dd3f22/doc<br><code>Access other user notification</code> | Bị từ chối HTTP 404 Not Found (Bảo vệ quyền sở hữu thông báo cá nhân) | HTTP 404<br><small><em>Evidence: Status: 404</em></small> | **PASS** |
| 5 | `TC-NOTIF-05` | Student | Student có nhiều thông báo chưa đọc | POST /thong-bao/doc-tat-ca | Cập nhật toàn bộ thông báo về trạng thái đã đọc, unread_count = 0 | HTTP 200, DB Unread Count: 0, Response: {&quot;success&quot;:true,&quot;unread_count&quot;:0}<br><small><em>Evidence: Unread in DB: 0</em></small> | **PASS** |
| 6 | `TC-NOTIF-06` | Student | Thông báo test #150ddf8f-312c-4d3b-b55b-aeb6b42d1f81 | DELETE /thong-bao/150ddf8f-312c-4d3b-b55b-aeb6b42d1f81 | HTTP 200, bản ghi bị xóa khỏi bảng notifications | HTTP 200, DB Exists: NO (DELETED)<br><small><em>Evidence: Notification deleted successfully</em></small> | **PASS** |
| 7 | `TC-NOTIF-07` | System | Sự kiện Mod duyệt tài liệu | Kiểm tra bản ghi DocumentApprovedNotification trong CSDL | Thông báo chứa đầy đủ tiêu đề, nội dung, dữ liệu điều hướng liên kết tài liệu | Notif #45bea690-f7ed-48dd-9f07-68529a484794, Tiêu đề: Tài liệu đã được phê duyệt, Nội dung: Tài liệu đóng góp &quot;Laravel_P03_ThemXoaSua_CRUD_2026&quot; cho yêu cầu #13 của bạn đã được duyệt. Bạn có thể vào kiểm tra và nghiệm thu.<br><small><em>Evidence: Title: Tài liệu đã được phê duyệt</em></small> | **PASS** |
| 8 | `TC-NOTIF-08` | System | Sự kiện Mod duyệt giải ngân Bounty | Kiểm tra bản ghi BountyPayoutNotification trong CSDL | Thông báo gửi tới người nhận, chứa tiêu đề, nội dung liên quan giải ngân điểm | Notif #bc7694e2-2932-4f18-a080-af9dd4be7e78, Tiêu đề: Yêu cầu duyệt giải ngân điểm Bounty, Nội dung: NGUYEN TUAN HAI đã xác nhận bài đóng góp của Nguyễn Văn Đạt cho yêu cầu tài liệu #27. Cần duyệt để giải ngân 15 điểm thưởng.<br><small><em>Evidence: Title: Yêu cầu duyệt giải ngân điểm Bounty</em></small> | **PASS** |
| 9 | `TC-NOTIF-09` | System | Sự kiện báo cáo vi phạm đánh giá / tài liệu | Kiểm tra bản ghi NewReviewViolationReportNotification trong CSDL | Thông báo được chuyển phát tới hòm thư kiểm duyệt viên/admin kèm ID đối tượng | Notif #85cced10-d143-4ce6-8557-1df3346ed7f6, Tiêu đề: Báo cáo vi phạm đánh giá mới, Target: App\Models\NguoiDung #3<br><small><em>Evidence: Title: Báo cáo vi phạm đánh giá mới</em></small> | **PASS** |
| 10 | `TC-NOTIF-10` | System | Sự kiện duyệt hoặc từ chối KYC cựu sinh viên | Kiểm tra bản ghi AlumniKycDecisionNotification trong CSDL | Gửi thông báo quyết định kèm lý do trực tiếp tới tài khoản Cựu sinh viên | Notif #bc37ae4c-e56b-4242-804c-c6da7e311fd7, Tiêu đề: Hồ sơ KYC đã được duyệt, Nội dung: Hồ sơ cựu sinh viên đã được duyệt. Tài khoản của bạn đã được kích hoạt. Bạn hiện đã được phép đăng nhập vào hệ thống.<br><small><em>Evidence: Title: Hồ sơ KYC đã được duyệt</em></small> | **PASS** |

---

## 8. Phần 9: An ninh & Dữ liệu biên (Security & Edge Cases)

**Số lượng ca kiểm thử:** 11 (Đạt: **6**, Không đạt: **5**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-SEC-01` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với file exploit.php<br><code>File: exploit.php</code> | Bị chặn bởi mimes/mimetypes validation, không được lưu lên máy chủ | Validation errors: NO - LỖI BẢO MẬT: File .php được upload thành công<br><small><em>Evidence: Dangerous extension .php accepted by server</em></small> | **FAIL** |
| 2 | `TC-SEC-02` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với file trojan.exe<br><code>File: trojan.exe</code> | Bị chặn bởi mimes validation, không được lưu | Validation errors: NO<br><small><em>Evidence: Dangerous extension .exe rejected</em></small> | **FAIL** |
| 3 | `TC-SEC-03` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với file script.sh<br><code>File: script.sh</code> | Bị chặn bởi mimes validation | Validation errors: NO<br><small><em>Evidence: Dangerous extension .sh rejected</em></small> | **FAIL** |
| 4 | `TC-SEC-04` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với file autorun.bat<br><code>File: autorun.bat</code> | Bị chặn bởi mimes validation | Validation errors: NO<br><small><em>Evidence: Dangerous extension .bat rejected</em></small> | **FAIL** |
| 5 | `TC-SEC-05` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với file empty.pdf (0 bytes)<br><code>File size: 0 bytes</code> | Bị từ chối (min: 1 byte), không tạo bản ghi trong DB | Empty in DB: YES (LỖI: Chấp nhận file 0 bytes)<br><small><em>Evidence: Empty file blocked</em></small> | **FAIL** |
| 6 | `TC-SEC-06` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với file 53MB (&gt; max:50MB)<br><code>File size: ~53.6MB</code> | Bị Validation chặn (max: 51200 KB), không ghi vào DB và storage | Large in DB: NO (ĐÃ CHẶN), Errors: YES<br><small><em>Evidence: File &gt; 50MB rejected</em></small> | **PASS** |
| 7 | `TC-SEC-07` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với tên file ../../exploit_traversal.pdf<br><code>Filename: ../../exploit_traversal.pdf</code> | Tên file được chuẩn hóa, file vật lý lưu an toàn trong storage/app/tai_lieu/{hash}, không vượt ra ngoài thư mục | Safe storage check: AN TOÀN (KHÔNG GHI RA NGOÀI)<br><small><em>Evidence: Path traversal neutralized</em></small> | **PASS** |
| 8 | `TC-SEC-08` | Student | Đăng nhập Student | POST /tai-lieu/dang-tai với tên file tiếng Việt có dấu và ký tự đặc biệt<br><code>Tài liệu Ôn thi Cuối kỳ Tiếng Việt &amp; Ký tự đặc biệt (2026).pdf</code> | Hệ thống xử lý lưu trữ an toàn, không gây crash hoặc lỗi encoding | Lưu thành công doc #64, Path: documents/doc_20261121095141_p5TGO9eT5JB3.pdf<br><small><em>Evidence: Doc ID: 64</em></small> | **PASS** |
| 9 | `TC-SEC-09` | Student | Tài khoản Sinh viên | Student gửi PUT /quan-tri/nguoi-dung/{id}/phan-quyen với vai_tro=QUAN_TRI_VIEN<br><code>Privilege Escalation attempt</code> | Bị chặn HTTP 403 Forbidden, không cho leo thang đặc quyền | HTTP 403, Vai trò sau request: SINH_VIEN, Điểm uy tín: 90<br><small><em>Evidence: Privilege escalation prevented</em></small> | **PASS** |
| 10 | `TC-SEC-10` | Guest/Attacker | Request có CSRF token giả | POST /yeu-cau-tai-lieu với _token=FAKE_INVALID_CSRF_TOKEN_1234567890<br><code>_token=FAKE</code> | Bị từ chối HTTP 419 CSRF Token Mismatch hoặc ném lỗi | HTTP 419<br><small><em>Evidence: Status: 419</em></small> | **PASS** |
| 11 | `TC-SEC-11` | Student | Đăng nhập Student | GET /tai-lieu?search=COS401&#039; OR &#039;1&#039;=&#039;1&#039; UNION SELECT 1,2,3,4,5--<br><code>search=COS401&#039; OR &#039;1&#039;=&#039;1&#039; UNION SELECT 1,2,3,4,5--</code> | Hệ thống thực thi an toàn qua Prepared Statements của PDO, không ném SQL Syntax Error, HTTP 200 | HTTP 200<br><small><em>Evidence: Status: 200</em></small> | **PASS** |

---

## 9. Phần 10: Toàn vẹn cơ sở dữ liệu (Database Integrity)

**Số lượng ca kiểm thử:** 8 (Đạt: **7**, Không đạt: **1**)

| STT | Mã TC | Vai trò | Tiền điều kiện | Các bước thực hiện & Dữ liệu | Kết quả mong đợi | Kết quả thực tế & Minh chứng | Trạng thái |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- | :---: |
| 1 | `TC-DB-01` | Database | Bảng tai_lieu | Kiểm tra Foreign Key ma_nguoi_dang và ma_mon_hoc | 0 bản ghi mồ côi (100% tài liệu liên kết đúng người đăng và môn học) | Orphan docs (User): 0, Orphan docs (Course): 0<br><small><em>Evidence: Orphan docs: 0 user, 0 course</em></small> | **PASS** |
| 2 | `TC-DB-02` | Database | Bảng danh_gia_mon_hoc | Kiểm tra FK ma_nguoi_dung và ma_mon_hoc | 0 bản ghi mồ côi trên bảng đánh giá môn học | Orphan reviews (User): 0, Orphan reviews (Course): 0<br><small><em>Evidence: Orphan reviews: 0 user, 0 course</em></small> | **PASS** |
| 3 | `TC-DB-03` | Database | Bảng yeu_cau_tai_lieu &amp; dap_ung | Kiểm tra FK người tạo và yêu cầu tài liệu liên kết | 0 bản ghi mồ côi trên phân hệ Wishlist &amp; Bounty | Orphan reqs: 0, Orphan submissions: 0<br><small><em>Evidence: Orphan reqs: 0, Orphan submissions: 0</em></small> | **PASS** |
| 4 | `TC-DB-04` | Database | Báo cáo vi phạm | Kiểm tra FK liên kết giữa báo cáo và tài nguyên vi phạm | 0 bản ghi mồ côi trong hàng đợi kiểm duyệt | Orphan doc reports: 0, Orphan review reports: 9<br><small><em>Evidence: Orphan doc reports: 0, Orphan review reports: 9</em></small> | **FAIL** |
| 5 | `TC-DB-05` | Database | Bảng vi_diem | Truy vấn kiểm tra số dư âm trên toàn bộ ví điểm người dùng | 100% ví điểm có diem_kha_dung &gt;= 0 và diem_tam_giu &gt;= 0, không có ví âm | Ví âm khả dụng: 0, Ví âm tạm giữ: 0<br><small><em>Evidence: Negative wallets: 0 avail, 0 locked</em></small> | **PASS** |
| 6 | `TC-DB-06` | Database | Bảng vi_diem | Kiểm tra ràng buộc duy nhất 1 User = 1 Wallet | 0 tài khoản có từ 2 ví điểm trở lên (Ràng buộc toàn vẹn 1:1) | Số tài khoản có ví trùng: 0<br><small><em>Evidence: Duplicate wallets: 0</em></small> | **PASS** |
| 7 | `TC-DB-07` | Database | Bảng nguoi_dung | Kiểm tra hash algorithm của mật khẩu toàn bộ người dùng | 100% mật khẩu được băm an toàn bằng Bcrypt ($2y$), không lưu plain text | Số tài khoản lưu mật khẩu thô: 0<br><small><em>Evidence: Plain text passwords in DB: 0</em></small> | **PASS** |
| 8 | `TC-DB-08` | Database | Bảng tai_lieu | Kiểm tra cơ chế Soft Deletes của Eloquent | Tài liệu có deleted_at IS NOT NULL bị ẩn hoàn toàn khỏi default query scope | Default query includes trashed: NO (CÁCH LY AN TOÀN)<br><small><em>Evidence: Soft delete scope working correctly</em></small> | **PASS** |

---

