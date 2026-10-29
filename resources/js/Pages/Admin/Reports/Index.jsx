import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import { CheckCircle2, Eye, Flag, Search, Trash2 } from 'lucide-react';

const reasonLabels = {
    TRUNG_LAP: 'Trùng lặp',
    SAI_NOI_DUNG: 'Sai nội dung',
    MA_DOC: 'Mã độc',
    BAN_QUYEN: 'Bản quyền',
    KHAC: 'Khác',
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

export default function ReportsIndex({ reports, filters = {}, reasons = [] }) {
    const items = reports?.data || [];
    const [query, setQuery] = useState({
        status: filters.status || '',
        reason: filters.reason || '',
        from: filters.from || '',
        to: filters.to || '',
    });

    const submitFilters = (event) => {
        event.preventDefault();
        router.get(route('admin.reports.index'), query, { preserveState: true, preserveScroll: true });
    };

    const archiveReport = (report) => {
        if (!window.confirm('Báo cáo này đã có kết quả xử lý. Bạn có chắc muốn lưu trữ báo cáo khỏi hàng đợi không?')) {
            return;
        }

        router.delete(route('admin.reports.archive', report.ma_bao_cao), {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Hàng đợi Báo cáo vi phạm">
            <Head title="Hàng đợi Báo cáo - AGU Hub Admin" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="rounded-xl border border-rose-100 bg-rose-50 p-2 text-rose-600"><Flag className="h-5 w-5" /></span>
                            <h1 className="text-xl font-bold text-slate-900">Hàng đợi Xử lý Báo cáo Vi phạm</h1>
                        </div>
                        <p className="ml-9 mt-1.5 text-xs text-slate-500">Kiểm tra nội dung và ghi nhận quyết định xử lý báo cáo.</p>
                    </div>
                    <div className="self-start rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 sm:self-auto">
                        Tổng báo cáo: <strong className="text-slate-900">{reports?.total || items.length}</strong>
                    </div>
                </div>

                <form onSubmit={submitFilters} className="grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-2 lg:grid-cols-5">
                    <select value={query.status} onChange={(e) => setQuery({ ...query, status: e.target.value })} className="rounded-lg border-slate-200 text-sm">
                        <option value="">Mọi trạng thái</option>
                        {Object.entries(statusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                    </select>
                    <select value={query.reason} onChange={(e) => setQuery({ ...query, reason: e.target.value })} className="rounded-lg border-slate-200 text-sm">
                        <option value="">Mọi lý do</option>
                        {reasons.map((reason) => <option key={reason} value={reason}>{reasonLabels[reason] || reason}</option>)}
                    </select>
                    <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm text-slate-600 focus-within:border-[#005baa] focus-within:ring-1 focus-within:ring-[#005baa]">
                        <span className="shrink-0 font-medium">Từ ngày</span>
                        <input type="date" value={query.from} onChange={(e) => setQuery({ ...query, from: e.target.value })} className="min-w-0 flex-1 border-0 p-0 text-sm text-slate-900 focus:ring-0" aria-label="Từ ngày" />
                    </label>
                    <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm text-slate-600 focus-within:border-[#005baa] focus-within:ring-1 focus-within:ring-[#005baa]">
                        <span className="shrink-0 font-medium">Đến ngày</span>
                        <input type="date" value={query.to} onChange={(e) => setQuery({ ...query, to: e.target.value })} className="min-w-0 flex-1 border-0 p-0 text-sm text-slate-900 focus:ring-0" aria-label="Đến ngày" />
                    </label>
                    <button className="inline-flex items-center justify-center gap-2 rounded-lg bg-[#005baa] px-4 py-2 text-sm font-semibold text-white hover:bg-[#004b8d]"><Search className="h-4 w-4" />Lọc</button>
                </form>

                <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    {items.length === 0 ? (
                        <div className="p-12 text-center">
                            <CheckCircle2 className="mx-auto mb-4 h-10 w-10 text-emerald-600" />
                            <h3 className="text-base font-bold text-slate-900">Không có báo cáo phù hợp</h3>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="border-b border-slate-200/80 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr><th className="px-6 py-4">Tài liệu</th><th className="px-6 py-4">Người báo cáo</th><th className="px-6 py-4">Lý do</th><th className="px-6 py-4">Trạng thái</th><th className="px-6 py-4 text-right">Thao tác</th></tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((report) => (
                                        <tr key={report.ma_bao_cao} className="hover:bg-slate-50/50">
                                            <td className="px-6 py-4 font-semibold text-slate-900">{report.tai_lieu?.tieu_de || `Tài liệu #${report.ma_tai_lieu}`}</td>
                                            <td className="px-6 py-4 text-xs">{report.nguoi_bao_cao?.ho_ten || 'Thành viên'}</td>
                                            <td className="px-6 py-4 text-xs text-rose-600">{reasonLabels[report.ly_do] || report.ly_do}</td>
                                            <td className="px-6 py-4 text-xs"><span className={`rounded-full border px-2 py-0.5 ${statusStyles[report.trang_thai_xu_ly] || 'border-slate-200 bg-slate-50 text-slate-600'}`}>{statusLabels[report.trang_thai_xu_ly] || report.trang_thai_xu_ly}</span></td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="inline-flex items-center gap-3">
                                                    <Link href={route('admin.reports.show', report.ma_bao_cao)} className="inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"><Eye className="h-4 w-4" />Xem chi tiết</Link>
                                                    {['DA_XU_LY', 'BAC_BO'].includes(report.trang_thai_xu_ly) && (
                                                        <button
                                                            type="button"
                                                            onClick={() => archiveReport(report)}
                                                            title="Lưu trữ báo cáo"
                                                            aria-label={`Lưu trữ báo cáo #${report.ma_bao_cao}`}
                                                            className="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-200"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={reports?.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
