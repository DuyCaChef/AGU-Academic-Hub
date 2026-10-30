import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import { CheckCircle2, Eye, FileWarning, Search } from 'lucide-react';

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

export default function RemovalRequestsIndex({ requests, filters = {}, communityOwnershipThreshold }) {
    const items = requests?.data || [];
    const [status, setStatus] = useState(filters.status || '');

    const submit = (event) => {
        event.preventDefault();
        router.get(route('admin.documents.removal-requests.index'), { status }, { preserveState: true, preserveScroll: true });
    };

    return (
        <AdminLayout title="Yêu cầu gỡ tài liệu">
            <Head title="Yêu cầu gỡ tài liệu - AGU Hub Admin" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <span className="rounded-xl border border-amber-100 bg-amber-50 p-2 text-amber-600"><FileWarning className="h-5 w-5" /></span>
                        <div>
                            <h1 className="text-xl font-bold text-slate-900">Yêu cầu gỡ tài liệu</h1>
                            <p className="mt-1 text-xs text-slate-500">Chỉ xử lý tài liệu đã đạt {communityOwnershipThreshold} lượt tải trở lên.</p>
                        </div>
                    </div>
                    <strong className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">{requests?.total || items.length} yêu cầu</strong>
                </div>

                <form onSubmit={submit} className="flex gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                    <select value={status} onChange={(event) => setStatus(event.target.value)} className="rounded-lg border-slate-200 text-sm">
                        <option value="">Mọi trạng thái</option>
                        {Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                    </select>
                    <button className="inline-flex items-center gap-2 rounded-lg bg-[#005baa] px-4 py-2 text-sm font-semibold text-white"><Search className="h-4 w-4" />Lọc</button>
                </form>

                <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    {items.length === 0 ? (
                        <div className="p-12 text-center"><CheckCircle2 className="mx-auto mb-4 h-10 w-10 text-emerald-600" /><h3 className="font-bold text-slate-900">Không có yêu cầu</h3></div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="border-b border-slate-200/80 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                                    <tr><th className="px-6 py-4">Tài liệu</th><th className="px-6 py-4">Tác giả</th><th className="px-6 py-4">Lượt tải</th><th className="px-6 py-4">Lý do</th><th className="px-6 py-4">Trạng thái</th><th className="px-6 py-4 text-right">Thao tác</th></tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((item) => (
                                        <tr key={item.ma_yeu_cau_go_bo} className="hover:bg-slate-50/50">
                                            <td className="px-6 py-4 font-semibold text-slate-900">{item.tai_lieu?.tieu_de || `Tài liệu #${item.ma_tai_lieu}`}</td>
                                            <td className="px-6 py-4 text-xs">{item.tai_lieu?.nguoi_dang?.ho_ten || item.nguoi_yeu_cau?.ho_ten || '---'}</td>
                                            <td className="px-6 py-4 text-xs font-semibold">{item.tai_lieu?.luot_tai ?? 0}</td>
                                            <td className="max-w-xs truncate px-6 py-4 text-xs">{item.ly_do}</td>
                                            <td className="px-6 py-4 text-xs">
                                                <span className={`inline-flex rounded-full border px-2.5 py-1 font-semibold ${statusStyles[item.trang_thai] || 'border-slate-200 bg-slate-50 text-slate-600'}`}>
                                                    {statuses[item.trang_thai] || item.trang_thai}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right"><Link href={route('admin.documents.removal-requests.show', item.ma_yeu_cau_go_bo)} className="inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"><Eye className="h-4 w-4" />Xem và xử lý</Link></td>
                                        </tr>
                                    ))}
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
