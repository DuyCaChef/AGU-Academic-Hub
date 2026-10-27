import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Eye,
    RotateCw,
    ZoomIn,
    ZoomOut,
    ExternalLink,
    AlertCircle,
    FileCheck2,
    Clock,
    User,
    GraduationCap,
    School,
    X,
    Loader2
} from 'lucide-react';

export default function KycIndex({ kycQueue }) {
    const { flash } = usePage().props;
    const items = kycQueue?.data || [];

    // State phục vụ Modal Xem ảnh Signed URL
    const [previewImage, setPreviewImage] = useState(null);
    const [zoomLevel, setZoomLevel] = useState(1);
    const [rotation, setRotation] = useState(0);

    // State phục vụ Modal Từ chối
    const [rejectItem, setRejectItem] = useState(null);
    const [rejectReason, setRejectReason] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [rejectError, setRejectError] = useState('');

    // Xử lý mở Modal Xem ảnh
    const handleOpenPreview = (item) => {
        setPreviewImage(item);
        setZoomLevel(1);
        setRotation(0);
    };

    const handleClosePreview = () => {
        setPreviewImage(null);
        setZoomLevel(1);
        setRotation(0);
    };

    // Thao tác duyệt hồ sơ
    const handleApprove = (item) => {
        if (!confirm(`Xác nhận phê duyệt hồ sơ KYC của sinh viên/cựu sinh viên "${item.ho_ten}"?`)) {
            return;
        }

        setIsSubmitting(true);
        router.put(route('admin.kyc.approve', item.ma_xac_thuc), {}, {
            preserveScroll: true,
            onFinish: () => setIsSubmitting(false),
        });
    };

    // Mở Modal từ chối
    const handleOpenRejectModal = (item) => {
        setRejectItem(item);
        setRejectReason('');
        setRejectError('');
    };

    // Submit từ chối
    const handleSubmitReject = (e) => {
        e.preventDefault();
        if (!rejectReason || rejectReason.trim().length < 6) {
            setRejectError('Vui lòng nhập lý do từ chối rõ ràng (tối thiểu 6 ký tự).');
            return;
        }

        setIsSubmitting(true);
        router.put(
            route('admin.kyc.reject', rejectItem.ma_xac_thuc),
            { ly_do_tu_choi: rejectReason.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRejectItem(null);
                    setRejectReason('');
                },
                onError: (errors) => {
                    setRejectError(errors.ly_do_tu_choi || 'Đã có lỗi xảy ra.');
                },
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Format tên loại minh chứng
    const getVerificationTypeLabel = (type) => {
        switch (type) {
            case 'THE_SINH_VIEN':
            case 'STUDENT_CARD':
                return { text: 'Thẻ sinh viên', badge: 'bg-blue-50 text-blue-700 border-blue-200' };
            case 'BANG_TOT_NGHIEP':
            case 'DIPLOMA':
                return { text: 'Bằng tốt nghiệp', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
            case 'GIAY_CHUNG_NHAN':
            case 'GRADUATION_CERT':
                return { text: 'Giấy chứng nhận tốt nghiệp', badge: 'bg-purple-50 text-purple-700 border-purple-200' };
            default:
                return { text: type || 'Minh chứng', badge: 'bg-slate-50 text-slate-700 border-slate-200' };
        }
    };

    return (
        <AdminLayout title="Hàng đợi duyệt xác thực KYC">
            <Head title="Hàng đợi duyệt xác thực KYC - AGU Hub Admin" />

            <div className="space-y-6">
                {/* Header trang */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 bg-blue-50 text-[#005baa] rounded-xl border border-blue-100">
                                <ShieldCheck className="w-5 h-5" />
                            </span>
                            <h1 className="text-xl font-bold text-slate-900">
                                Hàng đợi xét duyệt hồ sơ định danh (KYC)
                            </h1>
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5 ml-9">
                            Đối soát thẻ sinh viên và văn bằng cựu sinh viên qua liên kết ảnh có chữ ký số bảo mật (Signed URL 30 phút).
                        </p>
                    </div>

                    <div className="flex items-center gap-2 self-start sm:self-auto">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <Clock className="w-3.5 h-3.5 animate-pulse" />
                            Đang chờ duyệt: <strong>{kycQueue?.total || 0}</strong>
                        </span>
                    </div>
                </div>

                {/* Bảng danh sách hàng đợi KYC */}
                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    {items.length === 0 ? (
                        <div className="p-12 text-center">
                            <div className="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                                <FileCheck2 className="w-8 h-8" />
                            </div>
                            <h3 className="text-base font-bold text-slate-900">Hàng đợi KYC đang trống</h3>
                            <p className="text-xs text-slate-500 max-w-md mx-auto mt-1">
                                Tất cả hồ sơ xác thực định danh của sinh viên và cựu sinh viên đã được xử lý hoàn tất.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="bg-slate-50/75 border-b border-slate-200/80 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <tr>
                                        <th className="px-6 py-4">Sinh viên / Ứng viên</th>
                                        <th className="px-6 py-4">MSSV / Khóa học</th>
                                        <th className="px-6 py-4">Loại minh chứng</th>
                                        <th className="px-6 py-4">Thời gian nộp</th>
                                        <th className="px-6 py-4 text-center">Bằng chứng (Signed URL)</th>
                                        <th className="px-6 py-4 text-right">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((item) => {
                                        const typeInfo = getVerificationTypeLabel(item.loai_minh_chung);

                                        return (
                                            <tr key={item.ma_xac_thuc} className="hover:bg-slate-50/50 transition-colors">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 font-bold flex items-center justify-center border border-slate-200 uppercase text-xs">
                                                            {item.ho_ten ? item.ho_ten.charAt(0) : 'U'}
                                                        </div>
                                                        <div>
                                                            <div className="font-semibold text-slate-900 text-sm">
                                                                {item.ho_ten || 'Chưa cập nhật'}
                                                            </div>
                                                            <div className="text-xs text-slate-500">
                                                                {item.email}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <div className="font-mono text-xs font-bold text-[#005baa] bg-blue-50 px-2 py-0.5 rounded-md inline-block border border-blue-100">
                                                        {item.mssv || '---'}
                                                    </div>
                                                    {item.nam_tot_nghiep && (
                                                        <div className="text-xs text-slate-500 flex items-center gap-1 mt-1">
                                                            <GraduationCap className="w-3.5 h-3.5 text-slate-400" />
                                                            Tốt nghiệp: {item.nam_tot_nghiep}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border ${typeInfo.badge}`}>
                                                        {typeInfo.text}
                                                    </span>
                                                </td>

                                                <td className="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                                    {item.ngay_nop}
                                                </td>

                                                <td className="px-6 py-4 text-center">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenPreview(item)}
                                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors"
                                                    >
                                                        <Eye className="w-3.5 h-3.5 text-[#005baa]" />
                                                        Xem ảnh thẻ
                                                    </button>
                                                </td>

                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            disabled={isSubmitting}
                                                            onClick={() => handleApprove(item)}
                                                            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:scale-95 transition-all shadow-xs disabled:opacity-50"
                                                        >
                                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                                            Duyệt
                                                        </button>

                                                        <button
                                                            type="button"
                                                            disabled={isSubmitting}
                                                            onClick={() => handleOpenRejectModal(item)}
                                                            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 active:scale-95 transition-all disabled:opacity-50"
                                                        >
                                                            <XCircle className="w-3.5 h-3.5" />
                                                            Từ chối
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Phân trang */}
                    <Pagination links={kycQueue?.links} />
                </div>
            </div>

            {/* MODAL 1: XEM ẢNH MINH CHỨNG QUA SIGNED URL */}
            {previewImage && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-in fade-in duration-200">
                    <div className="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                        {/* Header Modal */}
                        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <div>
                                <h3 className="text-base font-bold text-slate-900">
                                    Minh chứng xác thực: {previewImage.ho_ten}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    MSSV: {previewImage.mssv} • {getVerificationTypeLabel(previewImage.loai_minh_chung).text}
                                </p>
                            </div>

                            <div className="flex items-center gap-2">
                                {/* Công cụ xoay & zoom */}
                                <button
                                    type="button"
                                    onClick={() => setZoomLevel((prev) => Math.min(prev + 0.25, 2.5))}
                                    className="p-1.5 text-slate-600 hover:bg-slate-200 rounded-lg transition-colors"
                                    title="Phóng to"
                                >
                                    <ZoomIn className="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setZoomLevel((prev) => Math.max(prev - 0.25, 0.5))}
                                    className="p-1.5 text-slate-600 hover:bg-slate-200 rounded-lg transition-colors"
                                    title="Thu nhỏ"
                                >
                                    <ZoomOut className="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setRotation((prev) => (prev + 90) % 360)}
                                    className="p-1.5 text-slate-600 hover:bg-slate-200 rounded-lg transition-colors"
                                    title="Xoay ảnh 90 độ"
                                >
                                    <RotateCw className="w-4 h-4" />
                                </button>
                                <a
                                    href={previewImage.url_xem_anh}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="p-1.5 text-[#005baa] hover:bg-blue-50 rounded-lg transition-colors ml-1"
                                    title="Mở tab mới"
                                >
                                    <ExternalLink className="w-4 h-4" />
                                </a>

                                <div className="h-4 w-px bg-slate-300 mx-1" />

                                <button
                                    type="button"
                                    onClick={handleClosePreview}
                                    className="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded-lg transition-colors"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        {/* Vùng hiển thị ảnh */}
                        <div className="flex-1 overflow-auto p-6 bg-slate-900/5 flex items-center justify-center min-h-[400px]">
                            <img
                                src={previewImage.url_xem_anh}
                                alt="Ảnh minh chứng KYC"
                                style={{
                                    transform: `scale(${zoomLevel}) rotate(${rotation}deg)`,
                                    transition: 'transform 0.2s ease-in-out',
                                }}
                                className="max-w-full max-h-[60vh] object-contain rounded-lg shadow-md border border-slate-200"
                                onError={(e) => {
                                    e.target.onerror = null;
                                    e.target.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><text y="50%" x="50%" text-anchor="middle" fill="red">Signed URL đã hết hạn</text></svg>';
                                }}
                            />
                        </div>

                        {/* Footer hành động trong Modal */}
                        <div className="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                            <span className="text-xs text-amber-600 flex items-center gap-1.5">
                                <AlertCircle className="w-4 h-4 shrink-0" />
                                Liên kết có thời hạn 30 phút nhằm bảo mật dữ liệu cá nhân (PII).
                            </span>

                            <div className="flex items-center gap-3">
                                <button
                                    type="button"
                                    onClick={() => {
                                        handleClosePreview();
                                        handleOpenRejectModal(previewImage);
                                    }}
                                    className="px-4 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-colors"
                                >
                                    Từ chối hồ sơ
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        handleClosePreview();
                                        handleApprove(previewImage);
                                    }}
                                    className="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition-colors"
                                >
                                    Xác nhận phê duyệt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* MODAL 2: TỪ CHỐI HỒ SƠ VÀ NHẬP LÝ DO */}
            {rejectItem && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-in fade-in duration-200">
                    <div className="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div className="flex items-center gap-2 text-rose-600 font-bold text-base">
                                <XCircle className="w-5 h-5" />
                                Từ chối hồ sơ xác thực
                            </div>
                            <button
                                type="button"
                                onClick={() => setRejectItem(null)}
                                className="text-slate-400 hover:text-slate-600"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitReject} className="mt-4 space-y-4">
                            <div>
                                <p className="text-xs text-slate-600 mb-2">
                                    Người dùng: <strong>{rejectItem.ho_ten}</strong> ({rejectItem.email})
                                </p>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Lý do từ chối <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    rows="4"
                                    required
                                    value={rejectReason}
                                    onChange={(e) => {
                                        setRejectReason(e.target.value);
                                        setRejectError('');
                                    }}
                                    placeholder="Ví dụ: Ảnh chụp thẻ SV bị mờ không thấy rõ MSSV; Năm tốt nghiệp không khớp với văn bằng đính kèm..."
                                    className="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring-rose-500 p-3"
                                />
                                {rejectError && (
                                    <p className="text-xs text-rose-600 mt-1">{rejectError}</p>
                                )}
                            </div>

                            <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    disabled={isSubmitting}
                                    onClick={() => setRejectItem(null)}
                                    className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                                >
                                    Hủy bỏ
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors shadow-xs disabled:opacity-50"
                                >
                                    {isSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                    Gửi quyết định từ chối
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}