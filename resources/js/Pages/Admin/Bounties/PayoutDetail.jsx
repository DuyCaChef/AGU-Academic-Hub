import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    Coins,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    ArrowLeft,
    Download,
    FileText,
    User,
    Calendar,
    BookOpen,
    AlertTriangle,
} from 'lucide-react';

// Trang xem chi tiết yêu cầu tài liệu chờ duyệt giải ngân
export default function PayoutDetail({ yeuCau }) {
    const [reason, setReason] = useState('');
    const [isRejecting, setIsRejecting] = useState(false);
    const [processing, setProcessing] = useState(false);

    const submission = yeuCau?.dap_ung_duoc_chon;
    const doc = submission?.tai_lieu;

    const handleApprove = () => {
        if (!window.confirm(`Xác nhận duyệt giải ngân ${yeuCau.diem_thuong} điểm thưởng cho người đóng góp?`)) {
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.bounties.approve-payout', yeuCau.ma_yeu_cau),
            {},
            {
                onFinish: () => setProcessing(false),
            }
        );
    };

    const handleReject = () => {
        if (!reason.trim() || reason.trim().length < 10) {
            window.alert('Vui lòng nhập lý do từ chối cụ thể (tối thiểu 10 ký tự).');
            return;
        }

        if (!window.confirm(`Xác nhận từ chối giải ngân? Hệ thống sẽ hoàn lại ${yeuCau.diem_thuong} điểm cho người yêu cầu.`)) {
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.bounties.reject-payout', yeuCau.ma_yeu_cau),
            { ly_do: reason },
            {
                onFinish: () => setProcessing(false),
            }
        );
    };

    return (
        <AdminLayout title={`Chi tiết duyệt giải ngân #${yeuCau.ma_yeu_cau}`}>
            <Head title={`Chi tiết yêu cầu #${yeuCau.ma_yeu_cau} - AGU Hub Admin`} />

            <div className="max-w-5xl mx-auto space-y-6">
                {/* Nút quay lại */}
                <div>
                    <Link
                        href={route('admin.bounties.payout-queue')}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition"
                    >
                        <ArrowLeft className="h-4 w-4" /> Quay lại hàng đợi giải ngân
                    </Link>
                </div>

                {/* Thẻ thông tin tổng quan yêu cầu */}
                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                Yêu cầu #{yeuCau.ma_yeu_cau} · Chờ duyệt giải ngân
                            </span>
                            <h1 className="mt-2 text-xl font-bold text-slate-900">
                                {yeuCau.tieu_de}
                            </h1>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-right">
                            <span className="block text-xs text-amber-700 font-medium">Điểm thưởng treo</span>
                            <span className="inline-flex items-center gap-1.5 text-lg font-bold text-amber-800">
                                <Coins className="h-5 w-5 text-amber-500" />
                                {yeuCau.diem_thuong} điểm
                            </span>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-600">
                        <div>
                            <span className="font-semibold text-slate-500 block mb-1">Môn học:</span>
                            <span className="font-medium text-slate-800 text-sm">
                                {yeuCau.mon_hoc?.ten_mon_hoc} ({yeuCau.mon_hoc?.ma_hoc_phan})
                            </span>
                        </div>
                        <div>
                            <span className="font-semibold text-slate-500 block mb-1">Sinh viên yêu cầu (SV A):</span>
                            <span className="font-medium text-slate-800 text-sm">
                                {yeuCau.nguoi_tao?.ho_ten} (MSSV: {yeuCau.nguoi_tao?.mssv})
                            </span>
                        </div>
                        <div>
                            <span className="font-semibold text-slate-500 block mb-1">Thời gian SV A xác nhận:</span>
                            <span className="font-medium text-slate-800 text-sm">
                                {yeuCau.thoi_gian_nguoi_yeu_cau_xac_nhan ? new Date(yeuCau.thoi_gian_nguoi_yeu_cau_xac_nhan).toLocaleString('vi-VN') : '---'}
                            </span>
                        </div>
                    </div>

                    {yeuCau.mo_ta && (
                        <div className="rounded-xl bg-slate-50 p-4 text-xs text-slate-700">
                            <span className="font-semibold text-slate-500 block mb-1">Nội dung chi tiết yêu cầu:</span>
                            <p className="whitespace-pre-line">{yeuCau.mo_ta}</p>
                        </div>
                    )}
                </div>

                {/* Thẻ tài liệu nộp cần kiểm duyệt */}
                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-4">
                    <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
                        <FileText className="h-5 w-5 text-blue-600" />
                        Tài liệu đóng góp được chọn giải ngân
                    </h2>

                    {doc ? (
                        <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 className="font-bold text-slate-900 text-sm">{doc.tieu_de}</h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Định dạng: {doc.dinh_dang_tep?.toUpperCase()} · Dung lượng: {doc.dung_luong_kb} KB · Đăng bởi: <span className="font-semibold text-slate-700">{submission?.nguoi_dong_gop?.ho_ten}</span> (MSSV: {submission?.nguoi_dong_gop?.mssv})
                                    </p>
                                </div>
                                {doc.duong_dan_tep && (
                                    <a
                                        href={`/storage/${doc.duong_dan_tep}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50"
                                    >
                                        <Download className="h-4 w-4 text-blue-600" /> Tải về kiểm tra file
                                    </a>
                                )}
                            </div>

                            {submission?.ghi_chu_dong_gop && (
                                <div className="text-xs text-slate-600 border-t border-slate-200/60 pt-2">
                                    <span className="font-semibold text-slate-500">Lời nhắn từ người đóng góp:</span> {submission.ghi_chu_dong_gop}
                                </div>
                            )}
                        </div>
                    ) : (
                        <p className="text-xs text-slate-400">Không tìm thấy thông tin tài liệu.</p>
                    )}

                    {/* Vùng quyết định của Admin */}
                    <div className="border-t border-slate-100 pt-6">
                        <h3 className="text-sm font-bold text-slate-900 mb-3">Quyết định của Ban Quản Trị:</h3>

                        {!isRejecting ? (
                            <div className="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={handleApprove}
                                    className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-700 disabled:opacity-50 transition"
                                >
                                    <CheckCircle2 className="h-5 w-5" />
                                    Duyệt giải ngân ({yeuCau.diem_thuong} điểm)
                                </button>
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={() => setIsRejecting(true)}
                                    className="inline-flex items-center gap-2 rounded-xl border border-rose-300 bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-50 transition"
                                >
                                    <XCircle className="h-5 w-5" />
                                    Từ chối giải ngân & Hoàn điểm
                                </button>
                            </div>
                        ) : (
                            <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 space-y-3">
                                <label className="block text-xs font-semibold text-rose-900">
                                    Nhập lý do từ chối giải ngân (hệ thống sẽ hoàn trả {yeuCau.diem_thuong} điểm về ví của {yeuCau.nguoi_tao?.ho_ten}):
                                </label>
                                <textarea
                                    rows={3}
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="Ví dụ: Tài liệu tải lên không đúng môn học, tệp tin bị hỏng hoặc chứa mã độc nguy hiểm..."
                                    className="w-full rounded-lg border border-rose-300 bg-white p-3 text-xs text-slate-900 outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                />
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        disabled={processing}
                                        onClick={handleReject}
                                        className="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                                    >
                                        Xác nhận từ chối & Hoàn trả điểm
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setIsRejecting(false)}
                                        className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        Hủy
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

