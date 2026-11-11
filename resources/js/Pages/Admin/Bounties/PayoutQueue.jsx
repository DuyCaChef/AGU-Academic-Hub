import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    Coins,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    ExternalLink,
    Eye,
    Download,
    FileText,
    User,
    AlertCircle,
} from 'lucide-react';

// Trang Hàng đợi duyệt giải ngân điểm thưởng Bounty dành cho Admin và Kiểm duyệt viên
export default function PayoutQueue({ queue }) {
    const [rejectionReasons, setRejectionReasons] = useState({});
    const [rejectingId, setRejectingId] = useState(null);
    const [processingId, setProcessingId] = useState(null);

    const items = queue?.data || [];

    // Cập nhật lý do từ chối cho từng yêu cầu
    const handleReasonChange = (maYeuCau, value) => {
        setRejectionReasons((prev) => ({
            ...prev,
            [maYeuCau]: value,
        }));
    };

    // Phê duyệt giải ngân điểm thưởng
    const handleApprove = (item) => {
        if (!window.confirm(`Xác nhận duyệt giải ngân ${item.diem_thuong} điểm thưởng cho sinh viên ${item.dap_ung_duoc_chon?.nguoi_dong_gop?.ho_ten || 'đóng góp'}?`)) {
            return;
        }

        setProcessingId(item.ma_yeu_cau);
        router.put(
            route('admin.bounties.approve-payout', item.ma_yeu_cau),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            }
        );
    };

    // Từ chối giải ngân và hoàn điểm cho người yêu cầu
    const handleReject = (item) => {
        const reason = (rejectionReasons[item.ma_yeu_cau] || '').trim();
        if (!reason || reason.length < 10) {
            window.alert('Vui lòng nhập lý do từ chối cụ thể (tối thiểu 10 ký tự) để gửi thông báo cho sinh viên.');
            return;
        }

        if (!window.confirm(`Xác nhận từ chối giải ngân? Hệ thống sẽ hoàn trả ${item.diem_thuong} điểm về ví của sinh viên ${item.nguoi_tao?.ho_ten || 'yêu cầu'}.`)) {
            return;
        }

        setProcessingId(item.ma_yeu_cau);
        router.put(
            route('admin.bounties.reject-payout', item.ma_yeu_cau),
            { ly_do: reason },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessingId(null);
                    setRejectingId(null);
                },
            }
        );
    };

    return (
        <AdminLayout title="Kiểm duyệt Bounty">
            <Head title="Hàng đợi kiểm duyệt tài liệu Bounty - AGU Hub Admin" />

            <div className="space-y-6">
                {/* Banner tiêu đề trang */}
                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <div className="flex items-center gap-3">
                        <span className="rounded-xl border border-amber-200 bg-amber-50 p-2.5 text-amber-600">
                            <Coins className="h-6 w-6" />
                        </span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900">
                                Hàng đợi kiểm duyệt tài liệu Bounty
                            </h1>
                            <p className="mt-1 text-xs text-slate-500">
                                Kiểm tra an toàn tài liệu (mã độc, spam, đúng môn học) và phê duyệt để giải ngân ngay điểm thưởng cho người đóng góp.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Bảng danh sách hàng đợi */}
                <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    {items.length === 0 ? (
                        <div className="p-16 text-center">
                            <ShieldCheck className="mx-auto mb-3 h-12 w-12 text-emerald-600" />
                            <p className="text-base font-semibold text-slate-900">
                                Không có yêu cầu nào đang chờ giải ngân
                            </p>
                            <p className="mt-1 text-xs text-slate-500">
                                Mọi xác nhận tài liệu từ sinh viên đã được kiểm tra và xử lý xong.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-[1200px] w-full text-left text-sm text-slate-600">
                                <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                                    <tr>
                                        <th className="px-6 py-4">Yêu cầu & Môn học</th>
                                        <th className="px-6 py-4">Người yêu cầu (SV A)</th>
                                        <th className="px-6 py-4">Tài liệu nộp (SV B)</th>
                                        <th className="px-6 py-4">Điểm thưởng</th>
                                        <th className="px-6 py-4 text-center">Trạng thái</th>
                                        <th className="px-6 py-4 min-w-[340px]">Quyết định xử lý</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((item) => {
                                        const submission = item.dap_ung_duoc_chon;
                                        const doc = submission?.tai_lieu;
                                        const isRejecting = rejectingId === item.ma_yeu_cau;
                                        const isBusy = processingId === item.ma_yeu_cau;

                                        return (
                                            <tr key={item.ma_yeu_cau} className="hover:bg-slate-50/60 transition">
                                                {/* Yêu cầu & Môn học */}
                                                <td className="px-6 py-4 font-semibold text-slate-900 max-w-xs">
                                                    <p className="line-clamp-2 text-slate-900">
                                                        {item.tieu_de}
                                                    </p>
                                                    <div className="mt-1 flex flex-wrap gap-2 text-[11px] font-normal text-slate-500">
                                                        <span className="font-medium text-blue-700">
                                                            {item.mon_hoc?.ten_mon_hoc || item.mon_hoc?.ma_hoc_phan || 'Môn học'}
                                                        </span>
                                                        <span>· Phân loại: {item.phan_loai || 'Tài liệu'}</span>
                                                    </div>
                                                </td>

                                                {/* Người yêu cầu (SV A) */}
                                                <td className="px-6 py-4 text-xs">
                                                    <div className="font-semibold text-slate-800">
                                                        {item.nguoi_tao?.ho_ten || '---'}
                                                    </div>
                                                    <div className="text-slate-500">
                                                        MSSV: {item.nguoi_tao?.mssv || '---'}
                                                    </div>
                                                    <div className="mt-1 text-[11px] text-slate-400">
                                                        Xác nhận: {item.thoi_gian_nguoi_yeu_cau_xac_nhan ? new Date(item.thoi_gian_nguoi_yeu_cau_xac_nhan).toLocaleString('vi-VN') : '---'}
                                                    </div>
                                                </td>

                                                {/* Tài liệu nộp bởi SV B */}
                                                <td className="px-6 py-4 max-w-xs">
                                                    {doc ? (
                                                        <div>
                                                            <div className="flex items-center gap-1.5 font-medium text-slate-900">
                                                                <FileText className="h-4 w-4 shrink-0 text-blue-600" />
                                                                <span className="truncate">{doc.tieu_de}</span>
                                                            </div>
                                                            <div className="mt-1 text-[11px] text-slate-500">
                                                                Đăng bởi: <span className="font-medium text-slate-700">{submission?.nguoi_dong_gop?.ho_ten}</span>
                                                                <span> ({doc.dinh_dang_tep?.toUpperCase()} · {doc.dung_luong_kb || 0} KB)</span>
                                                            </div>
                                                            {doc.duong_dan_tep && (
                                                                <a
                                                                    href={`/storage/${doc.duong_dan_tep}`}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                                                >
                                                                    <Download className="h-3.5 w-3.5" /> Tải về kiểm tra file
                                                                </a>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-slate-400">Chưa liên kết tài liệu</span>
                                                    )}
                                                </td>

                                                {/* Điểm thưởng */}
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">
                                                        <Coins className="h-3.5 w-3.5 text-amber-500" />
                                                        {item.diem_thuong} điểm
                                                    </span>
                                                </td>

                                                {/* Trạng thái */}
                                                <td className="px-6 py-4 text-center whitespace-nowrap">
                                                    <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-100/70 px-2.5 py-1 text-[11px] font-semibold text-amber-800">
                                                        <AlertCircle className="h-3 w-3 text-amber-600" />
                                                        Chờ duyệt giải ngân
                                                    </span>
                                                </td>

                                                {/* Quyết định của Admin/Mod */}
                                                <td className="px-6 py-4 align-top">
                                                    {!isRejecting ? (
                                                        <div className="flex items-center gap-2">
                                                            {/* Nút chấp nhận giải ngân */}
                                                            <button
                                                                type="button"
                                                                disabled={isBusy}
                                                                onClick={() => handleApprove(item)}
                                                                className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 disabled:opacity-50 transition"
                                                            >
                                                                <CheckCircle2 className="h-4 w-4" />
                                                                Duyệt giải ngân
                                                            </button>

                                                            {/* Nút mở ô nhập lý do từ chối */}
                                                            <button
                                                                type="button"
                                                                disabled={isBusy}
                                                                onClick={() => setRejectingId(item.ma_yeu_cau)}
                                                                className="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-50 transition"
                                                            >
                                                                <XCircle className="h-4 w-4" />
                                                                Từ chối...
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <div className="space-y-2 rounded-xl border border-rose-200 bg-rose-50/50 p-3">
                                                            <label className="block text-xs font-medium text-rose-800">
                                                                Lý do từ chối giải ngân (hoàn điểm lại cho SV A):
                                                            </label>
                                                            <textarea
                                                                rows={2}
                                                                value={rejectionReasons[item.ma_yeu_cau] || ''}
                                                                onChange={(e) => handleReasonChange(item.ma_yeu_cau, e.target.value)}
                                                                placeholder="Nhập lý do: file rác, chứa mã độc, gian lận điểm thưởng..."
                                                                className="block w-full rounded-lg border border-rose-300 bg-white p-2 text-xs text-slate-800 outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                                            />
                                                            <div className="flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    disabled={isBusy}
                                                                    onClick={() => handleReject(item)}
                                                                    className="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                                                                >
                                                                    Xác nhận từ chối & Hoàn điểm
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setRejectingId(null)}
                                                                    className="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                                                >
                                                                    Hủy
                                                                </button>
                                                            </div>
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Phân trang */}
                    {queue?.links && (
                        <div className="border-t border-slate-100 p-4">
                            <Pagination links={queue.links} />
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}

