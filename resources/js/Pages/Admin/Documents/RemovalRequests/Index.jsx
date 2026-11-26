import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    CheckCircle2,
    Download,
    Eye,
    FileText,
    FileWarning,
    RotateCcw,
    Search,
    Shield,
    X,
} from 'lucide-react';

const statuses = {
    CHO_XU_LY: 'Chờ xử lý',
    DA_DUYET: 'Đã duyệt',
    TU_CHOI: 'Từ chối',
};

const statusStyles = {
    CHO_XU_LY: 'border-amber-200 bg-amber-50 text-amber-700',
    DA_DUYET: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    TU_CHOI: 'border-rose-200 bg-rose-50 text-rose-700',
};

const statusDotStyles = {
    CHO_XU_LY: 'bg-amber-500',
    DA_DUYET: 'bg-emerald-500',
    TU_CHOI: 'bg-rose-500',
};

const reasonLabels = {
    Mac_dinh: 'Yêu cầu gỡ mặc định',
    MAC_DINH: 'Yêu cầu gỡ mặc định',
    BAN_QUYEN: 'Vi phạm bản quyền',
    NOI_DUNG_CU: 'Nội dung lỗi thời',
    SAI_THONG_TIN: 'Sai lệch thông tin',
    KHAC: 'Lý do khác',
};

export default function RemovalRequestsIndex({ requests, filters = {}, communityOwnershipThreshold }) {
    const items = requests?.data || [];
    const [status, setStatus] = useState(filters.status || '');
    const [keyword, setKeyword] = useState('');

    const handleStatusFilter = (nextStatus) => {
        setStatus(nextStatus);
        router.get(
            route('admin.documents.removal-requests.index'),
            nextStatus ? { status: nextStatus } : {},
            { preserveState: true, preserveScroll: true }
        );
    };

    const submit = (event) => {
        event.preventDefault();
        router.get(
            route('admin.documents.removal-requests.index'),
            status ? { status } : {},
            { preserveState: true, preserveScroll: true }
        );
    };

    const filteredItems = items.filter((item) => {
        const q = keyword.trim().toLowerCase();
        if (!q) return true;
        const title = (item.tai_lieu?.tieu_de || '').toLowerCase();
        const author = (item.tai_lieu?.nguoi_dang?.ho_ten || item.nguoi_yeu_cau?.ho_ten || '').toLowerCase();
        const reason = (reasonLabels[item.ly_do] || item.ly_do || '').toLowerCase();
        return title.includes(q) || author.includes(q) || reason.includes(q);
    });

    const totalCount = requests?.total ?? items.length;

    return (
        <AdminLayout title="Yêu cầu gỡ tài liệu">
            <Head title="Yêu cầu gỡ tài liệu - AGU Hub Admin" />
            <div className="space-y-6">
                {/* 1. Header Card */}
                <div className="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-start gap-3.5">
                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-200/80 bg-amber-50 text-amber-600">
                            <FileWarning className="h-5 w-5" />
                        </span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900">Yêu cầu gỡ tài liệu</h1>
                            <p className="mt-1 text-xs text-slate-500">
                                Kiểm duyệt các yêu cầu gỡ bỏ từ tác giả đối với tài liệu thuộc sở hữu cộng đồng.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 shrink-0">
                        <span className="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-[#005baa]">
                            <Shield className="h-3.5 w-3.5" />
                            <span>Ngưỡng cộng đồng: ≥ {communityOwnershipThreshold} lượt tải</span>
                        </span>
                        <span className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                            Tổng: <strong className="font-mono text-slate-900">{totalCount}</strong> yêu cầu
                        </span>
                    </div>
                </div>

                {/* 2. Thanh lọc trạng thái & tìm nhanh */}
                <div className="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs lg:flex-row lg:items-center">
                    {/* Quick Status Tabs */}
                    <div className="flex flex-wrap items-center gap-1.5">
                        {[
                            { value: '', label: 'Tất cả' },
                            { value: 'CHO_XU_LY', label: 'Chờ xử lý' },
                            { value: 'DA_DUYET', label: 'Đã duyệt' },
                            { value: 'TU_CHOI', label: 'Từ chối' },
                        ].map((tab) => {
                            const active = status === tab.value;
                            return (
                                <button
                                    key={tab.value || 'ALL'}
                                    type="button"
                                    onClick={() => handleStatusFilter(tab.value)}
                                    className={`cursor-pointer rounded-xl px-3.5 py-2 text-xs font-semibold transition-all ${
                                        active
                                            ? 'bg-[#005baa] text-white shadow-xs'
                                            : 'border border-slate-200 bg-slate-50/70 text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            );
                        })}
                    </div>

                    {/* Quick search + Refresh */}
                    <form onSubmit={submit} className="flex flex-1 items-center gap-2 lg:max-w-sm">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                value={keyword}
                                onChange={(e) => setKeyword(e.target.value)}
                                placeholder="Tìm nhanh tài liệu, tác giả, lý do..."
                                className="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 pl-9 pr-8 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:border-[#005baa] focus:bg-white focus:ring-1 focus:ring-[#005baa]"
                            />
                            {keyword && (
                                <button
                                    type="button"
                                    onClick={() => setKeyword('')}
                                    className="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600"
                                    title="Xóa từ khóa"
                                >
                                    <X className="h-3.5 w-3.5" />
                                </button>
                            )}
                        </div>
                        {(status || keyword) && (
                            <button
                                type="button"
                                onClick={() => {
                                    setKeyword('');
                                    handleStatusFilter('');
                                }}
                                title="Đặt lại bộ lọc"
                                className="inline-flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                            >
                                <RotateCcw className="h-4 w-4" />
                            </button>
                        )}
                    </form>
                </div>

                {/* 3. Bảng danh sách yêu cầu */}
                <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    {filteredItems.length === 0 ? (
                        <div className="p-12 text-center">
                            <CheckCircle2 className="mx-auto mb-3 h-10 w-10 text-emerald-600" />
                            <h3 className="text-base font-bold text-slate-900">Không có yêu cầu nào phù hợp</h3>
                            <p className="mt-1 text-xs text-slate-500">
                                Hiện tại không có yêu cầu gỡ tài liệu nào khớp với bộ lọc đang chọn.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="border-b border-slate-200/80 bg-slate-50/90 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th className="min-w-[240px] py-3.5 pl-6 pr-4 whitespace-nowrap">Tài liệu</th>
                                        <th className="px-4 py-3.5 whitespace-nowrap">Tác giả / Người gửi</th>
                                        <th className="px-4 py-3.5 whitespace-nowrap">Lượt tải</th>
                                        <th className="px-4 py-3.5 whitespace-nowrap">Lý do yêu cầu</th>
                                        <th className="px-4 py-3.5 whitespace-nowrap">Trạng thái</th>
                                        <th className="py-3.5 pl-4 pr-6 text-right whitespace-nowrap">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {filteredItems.map((item) => {
                                        const authorName =
                                            item.tai_lieu?.nguoi_dang?.ho_ten ||
                                            item.nguoi_yeu_cau?.ho_ten ||
                                            'Thành viên AGU';
                                        const downloads = item.tai_lieu?.luot_tai ?? 0;
                                        const reasonText = reasonLabels[item.ly_do] || item.ly_do || 'Không nêu lý do';

                                        return (
                                            <tr
                                                key={item.ma_yeu_cau_go_bo}
                                                className="transition-colors hover:bg-blue-50/25"
                                            >
                                                {/* Tài liệu */}
                                                <td className="py-4 pl-6 pr-4 align-middle">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-[#005baa]">
                                                            <FileText className="h-5 w-5" />
                                                        </div>
                                                        <div className="min-w-0">
                                                            <p className="line-clamp-1 text-sm font-bold text-slate-900" title={item.tai_lieu?.tieu_de}>
                                                                {item.tai_lieu?.tieu_de || `Tài liệu #${item.ma_tai_lieu}`}
                                                            </p>
                                                            <div className="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-400">
                                                                <span className="font-mono font-medium text-slate-500">
                                                                    Yêu cầu #{item.ma_yeu_cau_go_bo}
                                                                </span>
                                                                {item.tai_lieu?.mon_hoc?.ma_hoc_phan && (
                                                                    <span className="rounded bg-slate-100 px-1.5 py-0.5 font-mono font-semibold text-slate-600">
                                                                        {item.tai_lieu.mon_hoc.ma_hoc_phan}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Tác giả */}
                                                <td className="px-4 py-4 align-middle whitespace-nowrap">
                                                    <div className="flex items-center gap-2.5">
                                                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-[11px] font-bold text-slate-700">
                                                            {authorName.charAt(0).toUpperCase()}
                                                        </span>
                                                        <div className="text-xs">
                                                            <p className="font-semibold text-slate-800">{authorName}</p>
                                                            {item.nguoi_yeu_cau?.mssv && (
                                                                <p className="font-mono text-[11px] text-slate-400">
                                                                    {item.nguoi_yeu_cau.mssv}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Lượt tải */}
                                                <td className="px-4 py-4 align-middle whitespace-nowrap">
                                                    <span className="inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50/70 px-2.5 py-1 font-mono text-xs font-bold text-[#005baa]">
                                                        <Download className="h-3.5 w-3.5" />
                                                        <span>{downloads}</span>
                                                    </span>
                                                </td>

                                                {/* Lý do */}
                                                <td className="max-w-xs px-4 py-4 align-middle">
                                                    <span className="inline-block rounded-lg border border-slate-200/80 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 line-clamp-1" title={reasonText}>
                                                        {reasonText}
                                                    </span>
                                                </td>

                                                {/* Trạng thái */}
                                                <td className="px-4 py-4 align-middle whitespace-nowrap text-xs">
                                                    <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 font-semibold ${statusStyles[item.trang_thai] || 'border-slate-200 bg-slate-50 text-slate-600'}`}>
                                                        <span className={`h-1.5 w-1.5 rounded-full ${statusDotStyles[item.trang_thai] || 'bg-slate-400'}`} />
                                                        {statuses[item.trang_thai] || item.trang_thai}
                                                    </span>
                                                </td>

                                                {/* Thao tác */}
                                                <td className="py-4 pl-4 pr-6 text-right align-middle whitespace-nowrap">
                                                    <Link
                                                        href={route('admin.documents.removal-requests.show', item.ma_yeu_cau_go_bo)}
                                                        className="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50/80 px-3 py-1.5 text-xs font-semibold text-[#005baa] shadow-2xs transition-all hover:border-[#005baa] hover:bg-[#005baa] hover:text-white"
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                        <span>Xem & xử lý</span>
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={requests?.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
