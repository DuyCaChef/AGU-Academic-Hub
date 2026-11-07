import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    ArrowLeft,
    BookOpen,
    CheckCircle2,
    Clock,
    ExternalLink,
    EyeOff,
    FileText,
    Flag,
    GraduationCap,
    Info,
    MessageSquare,
    ShieldAlert,
    Trash2,
    User,
    XCircle,
} from 'lucide-react';

const reasonLabels = {
    NGON_TU_KICH_DONG: 'Ngôn từ kích động / xúc phạm',
    BOI_NHO: 'Bôi nhọ / Vu khống',
    QUAY_ROI: 'Quấy rối sinh viên khác',
    SPAM: 'Spam / Rao vặt',
    KHAC: 'Lý do khác',
};

const reasonStyles = {
    NGON_TU_KICH_DONG: 'border-rose-200 bg-rose-50 text-rose-700',
    BOI_NHO: 'border-purple-200 bg-purple-50 text-purple-700',
    QUAY_ROI: 'border-amber-200 bg-amber-50 text-amber-700',
    SPAM: 'border-blue-200 bg-blue-50 text-blue-700',
    KHAC: 'border-slate-200 bg-slate-100 text-slate-700',
};

const statusLabels = {
    CHO_XU_LY: 'Chờ xử lý',
    DA_XU_LY: 'Đã xử lý',
    BAC_BO: 'Bác bỏ',
};

const statusStyles = {
    CHO_XU_LY: 'border-amber-200 bg-amber-50 text-amber-700',
    DA_XU_LY: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    BAC_BO: 'border-slate-200 bg-slate-100 text-slate-600',
};

export default function ReviewReportShow({ report, relatedReports = [] }) {
    const [reason, setReason] = useState('');
    const [reasonError, setReasonError] = useState('');
    const [processing, setProcessing] = useState(false);

    const review = report?.danh_gia;
    const course = review?.monHoc;
    const author = review?.nguoiDung;
    const reporter = report?.nguoi_bao_cao;

    const resolve = (action) => {
        if (!reason.trim()) {
            setReasonError('Vui lòng nhập lý do và căn cứ giải quyết báo cáo.');
            return;
        }

        const actionText = action === 'HIDE' ? 'Tạm ẩn' : action === 'DELETE' ? 'Xóa bỏ' : 'Bác bỏ';
        if (!window.confirm(`Xác nhận thực hiện "${actionText}" đối với bài đánh giá này?`)) {
            return;
        }

        setReasonError('');
        setProcessing(true);

        router.put(
            route('admin.review-reports.resolve', report.ma_bao_cao),
            { action, resolution_reason: reason },
            {
                preserveScroll: true,
                onError: (errors) => {
                    setReasonError(errors.resolution_reason || 'Không thể ghi nhận kết quả. Vui lòng kiểm tra lại.');
                },
                onFinish: () => setProcessing(false),
            }
        );
    };

    const formatDate = (dateString) => {
        if (!dateString) return '—';
        return new Date(dateString).toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AdminLayout title={`Chi tiết báo cáo đánh giá #${report.ma_bao_cao}`}>
            <Head title={`Báo cáo Đánh giá #${report.ma_bao_cao} - AGU Hub Admin`} />

            <div className="space-y-6">
                {/* Back button */}
                <div>
                    <Link
                        href={route('admin.review-reports.index')}
                        className="inline-flex items-center gap-2 text-sm font-semibold text-brand-primary hover:underline"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        <span>Quay lại hàng đợi báo cáo đánh giá</span>
                    </Link>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Cột trái: Chi tiết bài viết & báo cáo (2 cột) */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* 1. Chi tiết bài đánh giá bị gắn cờ */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                                <div>
                                    <div className="flex items-center gap-2 mb-1">
                                        <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                            Bài đánh giá bị báo cáo
                                        </span>
                                        {report.loai_doi_tuong === 'GIANG_VIEN' || report.ly_do === 'BOI_NHO' ? (
                                            <span className="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-800">
                                                <GraduationCap size={12} />
                                                <span>Đánh giá Giảng viên</span>
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-bold text-indigo-700">
                                                <BookOpen size={12} />
                                                <span>Đánh giá Môn học</span>
                                            </span>
                                        )}
                                    </div>
                                    <h2 className="text-lg font-bold text-slate-900 mt-0.5">
                                        {course ? `${course.ma_mon_hoc} - ${course.ten_mon_hoc}` : `Môn học #${review?.ma_mon_hoc}`}
                                    </h2>
                                    {report.ten_giang_vien && (
                                        <div className="text-xs text-amber-800 font-semibold mt-1 flex items-center gap-1">
                                            <User size={12} />
                                            <span>Giảng viên bị phản ánh: {report.ten_giang_vien}</span>
                                        </div>
                                    )}
                                </div>

                                <div className="flex items-center gap-2">
                                    <span className="text-xs text-slate-500">
                                        Học kỳ: <strong className="text-slate-800">{review?.hoc_ky || '—'}</strong>
                                    </span>
                                    {course && (
                                        <Link
                                            href={route('courses.reviews.index', course.ma_mon_hoc)}
                                            target="_blank"
                                            className="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                        >
                                            <span>Xem trang môn</span>
                                            <ExternalLink size={12} />
                                        </Link>
                                    )}
                                </div>
                            </div>

                            {/* Tiêu chí điểm */}
                            <div className="mt-4 grid grid-cols-3 gap-3 rounded-xl bg-slate-50 p-3 text-center text-xs">
                                <div>
                                    <div className="text-slate-400">Độ khó</div>
                                    <div className="mt-1 font-bold text-slate-800">{review?.diem_do_kho || 0} / 5 ⭐</div>
                                </div>
                                <div className="border-x border-slate-200">
                                    <div className="text-slate-400">Thực tiễn</div>
                                    <div className="mt-1 font-bold text-slate-800">{review?.diem_thuc_tien || 0} / 5 ⭐</div>
                                </div>
                                <div>
                                    <div className="text-slate-400">Khối lượng bài tập</div>
                                    <div className="mt-1 font-bold text-slate-800">{review?.diem_khoi_luong || 0} / 5 ⭐</div>
                                </div>
                            </div>

                            {/* Nội dung bài đánh giá */}
                            <div className="mt-5">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Nội dung nhận xét nguyên bản:
                                </label>
                                <div className="mt-2 rounded-xl border border-rose-100 bg-rose-50/50 p-4 text-sm leading-relaxed text-slate-800 whitespace-pre-wrap font-sans">
                                    {review?.noi_dung || review?.nhan_xet || <span className="text-slate-400 italic">Không có nhận xét dạng văn bản.</span>}
                                </div>
                            </div>

                            {/* Thông tin tác giả bài đánh giá (Admin xem được đầy đủ danh tính) */}
                            <div className="mt-5 rounded-xl border border-slate-100 bg-slate-50/75 p-4 text-xs">
                                <div className="flex items-center gap-2 font-bold text-slate-700">
                                    <User size={14} className="text-brand-primary" />
                                    <span>Thông tin tác giả đánh giá (Đối soát quản trị viên):</span>
                                </div>
                                <div className="mt-2.5 grid gap-2 sm:grid-cols-2 text-slate-600">
                                    <div>
                                        Họ tên: <strong className="text-slate-800">{author?.ho_ten || 'Không xác định'}</strong>
                                        {review?.an_danh && (
                                            <span className="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">
                                                Hiển thị ẩn danh với SV
                                            </span>
                                        )}
                                    </div>
                                    <div>Email: <strong className="text-slate-800">{author?.email || '—'}</strong></div>
                                    <div>MSSV: <strong className="text-slate-800">{author?.mssv || '—'}</strong></div>
                                    <div>Thời gian đăng: <strong className="text-slate-800">{formatDate(review?.created_at)}</strong></div>
                                </div>
                            </div>
                        </div>

                        {/* 2. Chi tiết báo cáo vi phạm */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex items-center gap-2 text-slate-900 border-b border-slate-100 pb-4">
                                <ShieldAlert className="h-5 w-5 text-rose-600" />
                                <h3 className="text-base font-bold">Nội dung báo cáo vi phạm #{report.ma_bao_cao}</h3>
                            </div>

                            <div className="mt-4 grid gap-4 sm:grid-cols-2 text-xs">
                                <div className="rounded-xl border border-slate-100 bg-slate-50/75 p-3">
                                    <div className="text-slate-400">Người gửi báo cáo:</div>
                                    <div className="mt-1 font-bold text-slate-900">{reporter?.ho_ten || 'Thành viên'}</div>
                                    <div className="text-slate-500 mt-0.5">{reporter?.email || '—'}</div>
                                </div>

                                <div className="rounded-xl border border-slate-100 bg-slate-50/75 p-3">
                                    <div className="text-slate-400">Lý do báo cáo:</div>
                                    <div className="mt-1">
                                        <span className={`inline-block rounded-full border px-2.5 py-0.5 font-semibold ${reasonStyles[report.ly_do] || 'border-slate-200 bg-slate-50 text-slate-700'}`}>
                                            {reasonLabels[report.ly_do] || report.ly_do}
                                        </span>
                                    </div>
                                    <div className="text-slate-400 mt-1 flex items-center gap-1">
                                        <Clock size={11} />
                                        <span>Gửi lúc: {formatDate(report.created_at)}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-4">
                                <label className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Mô tả chi tiết vi phạm từ người báo cáo:
                                </label>
                                <div className="mt-2 rounded-xl bg-slate-50 p-3.5 text-xs text-slate-700 italic border border-slate-100">
                                    "{report.chi_tiet || 'Không có mô tả chi tiết kèm theo.'}"
                                </div>
                            </div>
                        </div>

                        {/* 3. Các báo cáo khác cùng hướng tới bài viết này */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <h3 className="text-base font-bold text-slate-900 mb-3">
                                Các báo cáo khác cùng bài đánh giá này ({relatedReports.length})
                            </h3>
                            {relatedReports.length === 0 ? (
                                <p className="text-xs text-slate-500">
                                    Hiện chưa có sinh viên nào khác gửi báo cáo cho bài đánh giá này.
                                </p>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {relatedReports.map((item) => (
                                        <div key={item.ma_bao_cao} className="flex items-center justify-between py-3 text-xs">
                                            <div>
                                                <span className="font-semibold text-slate-800">
                                                    {item.nguoi_bao_cao?.ho_ten || 'Thành viên'}
                                                </span>
                                                <span className="text-slate-400 ml-2">
                                                    · Lý do: <strong className="text-slate-700">{reasonLabels[item.ly_do] || item.ly_do}</strong>
                                                </span>
                                                <span className="text-slate-400 ml-2">({formatDate(item.created_at)})</span>
                                            </div>
                                            <span className={`rounded-full border px-2 py-0.5 font-medium ${statusStyles[item.trang_thai] || 'border-slate-200 bg-slate-50 text-slate-600'}`}>
                                                {statusLabels[item.trang_thai] || item.trang_thai}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Cột phải: Panel Ghi nhận quyết định xử lý */}
                    <div className="space-y-6">
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                                <h3 className="text-base font-bold text-slate-900">
                                    Quyết định xử lý
                                </h3>
                                <span className={`rounded-full border px-2.5 py-0.5 text-xs font-semibold ${statusStyles[report.trang_thai] || 'border-slate-200 bg-slate-50 text-slate-600'}`}>
                                    {statusLabels[report.trang_thai] || report.trang_thai}
                                </span>
                            </div>

                            {/* Thông tin nếu đã xử lý trước đó */}
                            {report.trang_thai !== 'CHO_XU_LY' && (
                                <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50/70 p-3.5 text-xs text-amber-900 space-y-2">
                                    <div className="font-bold flex items-center gap-1.5 text-amber-950">
                                        <Info size={15} className="text-amber-700" />
                                        <span>Báo cáo này đã có kết quả xử lý</span>
                                    </div>
                                    <div>Người xử lý: <strong>{report.nguoi_xu_ly?.ho_ten || 'Ban Kiểm duyệt'}</strong></div>
                                    <div>Thời gian: <strong>{formatDate(report.thoi_gian_xu_ly)}</strong></div>
                                    {report.ghi_chu_xu_ly && (
                                        <div className="mt-1.5 rounded-lg bg-white/80 p-2.5 border border-amber-200">
                                            <span className="font-semibold text-slate-600 block text-[11px] uppercase">Ghi chú & Căn cứ xử lý:</span>
                                            <span className="italic text-slate-800 text-xs mt-0.5 block whitespace-pre-wrap">
                                                "{report.ghi_chu_xu_ly}"
                                            </span>
                                        </div>
                                    )}
                                    <p className="pt-1 text-[11px] text-amber-700">
                                        Bạn có thể đưa ra quyết định mới bên dưới để cập nhật lại trạng thái bài đánh giá.
                                    </p>
                                </div>
                            )}

                            {/* Nhập lý do / căn cứ xử lý */}
                            <div className="mt-4">
                                <label htmlFor="resolution_reason" className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Căn cứ & Lý do xử lý <span className="text-rose-600">*</span>
                                </label>
                                <p className="mt-1 text-[11px] text-slate-500">
                                    Nêu rõ điều khoản vi phạm tiêu chuẩn cộng đồng để lưu lại nhật ký kiểm toán và thông báo cho sinh viên.
                                </p>
                                <textarea
                                    id="resolution_reason"
                                    rows={4}
                                    value={reason}
                                    onChange={(e) => {
                                        setReason(e.target.value);
                                        if (reasonError) setReasonError('');
                                    }}
                                    placeholder="VD: Nhận xét chứa ngôn từ công kích giảng viên vô căn cứ, vi phạm quy tắc số 2..."
                                    className={`mt-2 w-full rounded-xl border p-3 text-xs leading-relaxed focus:ring-1 ${
                                        reasonError
                                            ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-200'
                                            : 'border-slate-200 focus:border-brand-primary focus:ring-brand-primary'
                                    }`}
                                />
                                {reasonError && (
                                    <p className="mt-1 text-xs text-rose-600">{reasonError}</p>
                                )}
                            </div>

                            {/* 3 nút hành động */}
                            <div className="mt-5 space-y-2.5">
                                {/* Nút 1: Tạm ẩn bài viết */}
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={() => resolve('HIDE')}
                                    className="w-full flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-amber-600 disabled:opacity-50"
                                >
                                    <EyeOff size={14} />
                                    <span>Tạm ẩn đánh giá (Để kiểm tra thêm)</span>
                                </button>

                                {/* Nút 2: Xóa vĩnh viễn bài review */}
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={() => resolve('DELETE')}
                                    className="w-full flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-rose-700 disabled:opacity-50"
                                >
                                    <Trash2 size={14} />
                                    <span>Xóa bỏ bài đánh giá (Vi phạm nghiêm trọng)</span>
                                </button>

                                {/* Nút 3: Bác bỏ báo cáo */}
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={() => resolve('DISMISS')}
                                    className="w-full flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900 disabled:opacity-50"
                                >
                                    <CheckCircle2 size={14} className="text-emerald-600" />
                                    <span>Bác bỏ báo cáo (Đánh giá hợp lệ)</span>
                                </button>
                            </div>

                            {/* Ghi chú hướng dẫn cho Moderator */}
                            <div className="mt-5 rounded-xl bg-slate-50 p-3 text-[11px] text-slate-500 leading-relaxed">
                                <strong className="text-slate-700">Quy tắc ứng xử:</strong>
                                <ul className="list-disc pl-4 mt-1 space-y-0.5">
                                    <li>Tạm ẩn: Bài viết không hiển thị cho sinh viên khác nhưng chưa xóa khỏi CSDL.</li>
                                    <li>Xóa bỏ: Gỡ mềm bài viết và giải quyết toàn bộ báo cáo đang chờ.</li>
                                    <li>Bác bỏ: Giữ nguyên hoặc khôi phục hiển thị đánh giá công khai.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

