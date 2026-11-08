import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    ExternalLink,
    Eye,
    FileWarning,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Coins,
    FileText,
    Clock,
    AlertTriangle,
} from 'lucide-react';

const reportActions = [
    { value: 'KEEP', label: 'Giữ nguyên', className: 'bg-slate-700 hover:bg-slate-800' },
    { value: 'RESTORE', label: 'Khôi phục', className: 'bg-emerald-600 hover:bg-emerald-700' },
    { value: 'HIDE', label: 'Ẩn tài liệu', className: 'bg-amber-600 hover:bg-amber-700' },
    { value: 'DELETE', label: 'Xóa mềm', className: 'bg-rose-600 hover:bg-rose-700' },
];

export default function ReviewQueue({
    documents,
    counts = {},
    currentTab = 'all',
    trustedReputation = 100,
    reviewThreshold = 3,
    autoHideThreshold = 5,
}) {
    const [reason, setReason] = useState('');
    const [rejectingId, setRejectingId] = useState(null);
    const [rejectReason, setRejectReason] = useState('');
    const [processing, setProcessing] = useState(false);

    const items = documents?.data || [];

    // Chuyển tab lọc trạng thái
    const handleTabChange = (tabKey) => {
        router.get(
            route('admin.documents.review-queue'),
            { tab: tabKey },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Phê duyệt tài liệu mới đăng (CHO_DUYET -> DA_DUYET)
    const handleApproveNew = (document) => {
        if (!window.confirm(`Xác nhận phê duyệt công khai tài liệu "${document.tieu_de}"?`)) {
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.documents.approve', document.ma_tai_lieu),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            }
        );
    };

    // Từ chối tài liệu mới đăng (CHO_DUYET -> TU_CHOI)
    const handleRejectNew = (document) => {
        if (!rejectReason.trim() || rejectReason.trim().length < 10) {
            window.alert('Vui lòng nhập lý do từ chối cụ thể (tối thiểu 10 ký tự) để gửi thông báo cho tác giả.');
            return;
        }

        if (!window.confirm(`Xác nhận từ chối tài liệu "${document.tieu_de}"? Lý do sẽ được gửi tới tác giả.`)) {
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.documents.reject', document.ma_tai_lieu),
            { reason: rejectReason },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setRejectingId(null);
                    setRejectReason('');
                },
            }
        );
    };

    // Quyết định xử lý cho tài liệu bị báo cáo vi phạm
    const decide = (document, action) => {
        if (!reason.trim()) {
            window.alert('Vui lòng nhập lý do xử lý trước khi quyết định.');
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.documents.decide', document.ma_tai_lieu),
            {
                action,
                resolution_reason: reason,
            },
            { preserveScroll: true, onFinish: () => setProcessing(false) }
        );
    };

    const tabs = [
        { key: 'all', label: 'Tất cả cần duyệt', count: counts.all || 0 },
        { key: 'CHO_DUYET', label: 'Tài liệu mới chờ duyệt', count: counts.CHO_DUYET || 0, isNew: true },
        { key: 'CHO_KIEM_TRA', label: 'Bị báo cáo vi phạm (≥3)', count: counts.CHO_KIEM_TRA || 0 },
        { key: 'TAM_AN_KIEM_TRA', label: 'Tự động tạm ẩn (≥5)', count: counts.TAM_AN_KIEM_TRA || 0 },
    ];

    return (
        <AdminLayout title="Hàng đợi kiểm tra tài liệu">
            <Head title="Hàng đợi kiểm tra tài liệu - AGU Hub Admin" />

            <div className="space-y-6">
                {/* Banner thông tin hàng đợi */}
                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <div className="flex items-center gap-3">
                        <span className="rounded-xl border border-blue-200 bg-blue-50 p-2.5 text-blue-600">
                            <FileWarning className="h-6 w-6" />
                        </span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900">
                                Hàng đợi thẩm định & kiểm tra tài liệu
                            </h1>
                            <p className="mt-1 text-xs text-slate-500">
                                Bao gồm tài liệu mới tải lên chờ duyệt ban đầu và các tài liệu bị gắn cờ do đạt ngưỡng báo cáo vi phạm (≥{reviewThreshold} báo cáo kiểm tra, ≥{autoHideThreshold} báo cáo tự ẩn).
                            </p>
                        </div>
                    </div>

                    {/* Bộ lọc Tabs trạng thái */}
                    <div className="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                        {tabs.map((tab) => {
                            const active = currentTab === tab.key;
                            return (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => handleTabChange(tab.key)}
                                    className={`inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition ${
                                        active
                                            ? 'bg-slate-900 text-white shadow-xs'
                                            : 'border border-slate-200 bg-slate-50/70 text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    }`}
                                >
                                    {tab.isNew && tab.count > 0 && (
                                        <span className="h-2 w-2 rounded-full bg-blue-400 animate-pulse" />
                                    )}
                                    {tab.label}
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-[11px] font-bold ${
                                            active
                                                ? 'bg-white/20 text-white'
                                                : 'bg-slate-200 text-slate-700'
                                        }`}
                                    >
                                        {tab.count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Bảng danh sách hàng đợi */}
                <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    {items.length === 0 ? (
                        <div className="p-16 text-center">
                            <ShieldCheck className="mx-auto mb-3 h-12 w-12 text-emerald-600" />
                            <p className="text-base font-semibold text-slate-900">
                                Hàng đợi hiện đang trống
                            </p>
                            <p className="mt-1 text-xs text-slate-500">
                                Không có tài liệu nào trong danh mục này cần xử lý.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-[1200px] w-full text-left text-sm text-slate-600">
                                <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                                    <tr>
                                        <th className="px-6 py-4">Tài liệu & Phân loại</th>
                                        <th className="px-6 py-4">Người đăng</th>
                                        <th className="px-6 py-4">Trạng thái / Báo cáo</th>
                                        <th className="px-6 py-4">Căn cứ chuyển duyệt</th>
                                        <th className="px-6 py-4 min-w-[360px]">Quyết định kiểm duyệt</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((document) => {
                                        const isNewPending = document.trang_thai === 'CHO_DUYET';
                                        const isBounty = document.dap_ung_yeu_caus && document.dap_ung_yeu_caus.length > 0;
                                        const isRejecting = rejectingId === document.ma_tai_lieu;

                                        return (
                                            <tr key={document.ma_tai_lieu} className="hover:bg-slate-50/60 transition">
                                                {/* Cột 1: Thông tin tài liệu */}
                                                <td className="px-6 py-4 font-semibold text-slate-900 max-w-sm">
                                                    <div className="flex items-start gap-2">
                                                        <FileText className="h-4 w-4 shrink-0 text-blue-600 mt-0.5" />
                                                        <div>
                                                            <Link
                                                                href={route('admin.documents.review-detail', document.ma_tai_lieu)}
                                                                className="hover:text-[#005baa] hover:underline line-clamp-2"
                                                            >
                                                                {document.tieu_de}
                                                            </Link>
                                                            <div className="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] font-normal text-slate-500">
                                                                <span className="font-medium text-slate-700">
                                                                    {document.mon_hoc?.ma_hoc_phan || '---'}
                                                                </span>
                                                                <span>· {document.dinh_dang_tep?.toUpperCase() || 'Tệp'}</span>
                                                                <span>· {document.dung_luong_kb || 0} KB</span>
                                                            </div>
                                                            <div className="mt-2 flex flex-wrap gap-1.5">
                                                                {/* Huy hiệu Bounty nếu có */}
                                                                {isBounty && (
                                                                    <span className="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                                                        <Coins className="h-3 w-3 text-amber-500" />
                                                                        Đóng góp Bounty ({document.dap_ung_yeu_caus[0]?.yeu_cau?.diem_thuong || 0} đ)
                                                                    </span>
                                                                )}
                                                                <Link
                                                                    href={route('admin.documents.review-detail', document.ma_tai_lieu)}
                                                                    className="inline-flex items-center gap-1 text-[11px] font-semibold text-[#005baa] hover:underline"
                                                                >
                                                                    <Eye className="h-3.5 w-3.5" /> Xem chi tiết & tệp
                                                                </Link>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Cột 2: Người đăng */}
                                                <td className="px-6 py-4 text-xs whitespace-nowrap">
                                                    <div className="font-semibold text-slate-800">
                                                        {document.nguoi_dang?.ho_ten || '---'}
                                                    </div>
                                                    <div className="text-slate-500">
                                                        MSSV: {document.nguoi_dang?.mssv || '---'}
                                                    </div>
                                                    <div className="mt-1 text-[11px] text-slate-400">
                                                        Tải lên: {document.created_at ? new Date(document.created_at).toLocaleDateString('vi-VN') : '---'}
                                                    </div>
                                                </td>

                                                {/* Cột 3: Trạng thái & Báo cáo */}
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {isNewPending ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                                            <Clock className="h-3 w-3 text-sky-500" />
                                                            Chờ duyệt mới
                                                        </span>
                                                    ) : (
                                                        <div>
                                                            <span className="font-bold text-rose-600 text-sm">
                                                                {document.bao_cao_hop_le_count ?? document.so_bao_cao_hop_le ?? 0}
                                                            </span>
                                                            <span className="text-xs text-slate-500 ml-1">báo cáo hợp lệ</span>
                                                            {document.trang_thai === 'TAM_AN_KIEM_TRA' && (
                                                                <div className="mt-1 inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                                                    <AlertTriangle className="h-3 w-3 text-amber-500" />
                                                                    Tạm ẩn tự động
                                                                </div>
                                                            )}
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Cột 4: Căn cứ chuyển duyệt */}
                                                <td className="max-w-xs px-6 py-4 text-xs text-slate-600">
                                                    <p className="line-clamp-2">
                                                        {document.ly_do_trang_thai || (isNewPending ? 'Tài liệu mới tải lên chờ thẩm định nội dung.' : '---')}
                                                    </p>
                                                    <a
                                                        href={route('admin.documents.preview', document.ma_tai_lieu)}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="mt-1.5 inline-flex items-center gap-1 text-[11px] font-semibold text-[#005baa] hover:underline"
                                                    >
                                                        <ExternalLink className="h-3 w-3" /> Mở xem trực tiếp tệp
                                                    </a>
                                                </td>

                                                {/* Cột 5: Quyết định kiểm duyệt */}
                                                <td className="px-6 py-4 align-top">
                                                    {isNewPending ? (
                                                        /* --- XỬ LÝ TÀI LIỆU MỚI ĐĂNG (CHO_DUYET) --- */
                                                        !isRejecting ? (
                                                            <div className="space-y-2">
                                                                <p className="text-[11px] text-slate-500 font-medium">
                                                                    Thẩm định file & quyết định phê duyệt:
                                                                </p>
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    {/* Nút phê duyệt công khai */}
                                                                    <button
                                                                        type="button"
                                                                        disabled={processing}
                                                                        onClick={() => handleApproveNew(document)}
                                                                        className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 disabled:opacity-50 transition"
                                                                    >
                                                                        <CheckCircle2 className="h-4 w-4" />
                                                                        Phê duyệt công khai
                                                                    </button>

                                                                    {/* Nút từ chối */}
                                                                    <button
                                                                        type="button"
                                                                        disabled={processing}
                                                                        onClick={() => setRejectingId(document.ma_tai_lieu)}
                                                                        className="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-50 transition"
                                                                    >
                                                                        <XCircle className="h-4 w-4" />
                                                                        Từ chối...
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            /* Form nhập lý do từ chối tài liệu mới */
                                                            <div className="space-y-2 rounded-xl border border-rose-200 bg-rose-50/50 p-3">
                                                                <label className="block text-xs font-medium text-rose-800">
                                                                    Lý do từ chối (sẽ gửi thông báo cho tác giả):
                                                                </label>
                                                                <textarea
                                                                    rows={2}
                                                                    value={rejectReason}
                                                                    onChange={(e) => setRejectReason(e.target.value)}
                                                                    placeholder="Ví dụ: Tài liệu mờ không đọc được, tài liệu thương mại có bản quyền..."
                                                                    className="block w-full rounded-lg border border-rose-300 bg-white p-2 text-xs text-slate-800 outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                                                                />
                                                                <div className="flex items-center gap-2">
                                                                    <button
                                                                        type="button"
                                                                        disabled={processing}
                                                                        onClick={() => handleRejectNew(document)}
                                                                        className="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                                                                    >
                                                                        Xác nhận từ chối
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => {
                                                                            setRejectingId(null);
                                                                            setRejectReason('');
                                                                        }}
                                                                        className="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                                                    >
                                                                        Hủy
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )
                                                    ) : (
                                                        /* --- XỬ LÝ TÀI LIỆU BỊ BÁO CÁO VI PHẠM (CHO_KIEM_TRA, TAM_AN_KIEM_TRA) --- */
                                                        <div>
                                                            <textarea
                                                                value={reason}
                                                                onChange={(e) => setReason(e.target.value)}
                                                                maxLength={2000}
                                                                rows={2}
                                                                placeholder="Nhập căn cứ xử lý vi phạm..."
                                                                className="mb-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 shadow-2xs outline-none focus:border-[#005baa] focus:ring-1 focus:ring-blue-100"
                                                            />
                                                            <div className="flex flex-wrap gap-1.5">
                                                                {reportActions.map((action) => (
                                                                    <button
                                                                        key={action.value}
                                                                        type="button"
                                                                        disabled={processing}
                                                                        onClick={() => decide(document, action.value)}
                                                                        className={`inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white ${action.className} disabled:opacity-50`}
                                                                    >
                                                                        <Eye className="h-3.5 w-3.5" />
                                                                        {action.label}
                                                                    </button>
                                                                ))}
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
                    {documents?.links && (
                        <div className="border-t border-slate-100 p-4">
                            <Pagination links={documents.links} />
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
