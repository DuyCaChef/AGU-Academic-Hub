import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import { Flag, CheckCircle2, AlertCircle, FileText, User } from 'lucide-react';

export default function ReportsIndex({ reports }) {
    const items = reports?.data || [];

    return (
        <AdminLayout title="Hàng đợi Báo cáo vi phạm">
            <Head title="Hàng đợi Báo cáo - AGU Hub Admin" />

            <div className="space-y-6">
                {/* Header trang */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 bg-rose-50 text-rose-600 rounded-xl border border-rose-100">
                                <Flag className="w-5 h-5" />
                            </span>
                            <h1 className="text-xl font-bold text-slate-900">
                                Hàng đợi Xử lý Báo cáo Vi phạm
                            </h1>
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5 ml-9">
                            Kiểm duyệt và xử lý các báo cáo vi phạm tiêu chuẩn cộng đồng, tài liệu vi phạm bản quyền hoặc nội dung không phù hợp.
                        </p>
                    </div>

                    <div className="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 self-start sm:self-auto">
                        Đang chờ xử lý: <strong className="text-slate-900">{reports?.total || items.length}</strong>
                    </div>
                </div>

                {/* Danh sách báo cáo */}
                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    {items.length === 0 ? (
                        <div className="p-12 text-center">
                            <div className="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-100">
                                <CheckCircle2 className="w-8 h-8" />
                            </div>
                            <h3 className="text-base font-bold text-slate-900">Hàng đợi Báo cáo đang trống</h3>
                            <p className="text-xs text-slate-500 max-w-md mx-auto mt-1">
                                Hiện không có báo cáo vi phạm nào cần kiểm duyệt. Toàn bộ tài liệu trên hệ thống đều tuân thủ tốt tiêu chuẩn cộng đồng.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="bg-slate-50/75 border-b border-slate-200/80 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <tr>
                                        <th className="px-6 py-4">Tài liệu bị báo cáo</th>
                                        <th className="px-6 py-4">Người báo cáo</th>
                                        <th className="px-6 py-4">Lý do vi phạm</th>
                                        <th className="px-6 py-4">Trạng thái</th>
                                        <th className="px-6 py-4 text-right">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((r) => (
                                        <tr key={r.ma_bao_cao} className="hover:bg-slate-50/50 transition-colors">
                                            <td className="px-6 py-4 font-semibold text-slate-900">
                                                {r.tai_lieu?.tieu_de || `Tài liệu #${r.ma_tai_lieu}`}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                {r.nguoi_bao_cao?.ho_ten || 'Thành viên'}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-rose-600">
                                                {r.ly_do}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                <span className="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                                    {r.trang_thai_xu_ly || 'Chờ xử lý'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <span className="text-xs text-slate-400">Xem chi tiết</span>
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
