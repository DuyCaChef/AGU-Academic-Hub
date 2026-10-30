import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import { Trash2, RotateCcw, FileText, CheckCircle2 } from 'lucide-react';

export default function DeletedDocumentsIndex({ documents, filters = {}, actors = [], sources = [], statuses = [], canPermanentlyDelete = false, adminDeleteAfterDays = 60, autoPurgeAfterDays = 90 }) {
    const items = documents?.data || [];

    const handleRestore = (doc) => {
        const hasReports = doc.bao_cao_vi_phams?.some((report) => report.trang_thai_xu_ly === 'CHO_XU_LY');
        if (!confirm(`Xác nhận khôi phục tài liệu "${doc.tieu_de}"${hasReports ? ' và chuyển vào hàng đợi kiểm tra vi phạm' : ''}?`)) return;
        router.post(route('admin.documents.restore', doc.ma_tai_lieu), {
            confirm_violation: hasReports,
        }, {
            preserveScroll: true,
        });
    };

    const applyFilters = (event) => {
        event.preventDefault();
        router.get(route('admin.documents.deleted'), Object.fromEntries(new FormData(event.currentTarget)), {
            preserveState: true,
            replace: true,
        });
    };

    const handlePermanentDelete = (doc) => {
        if (!confirm(`Xóa vĩnh viễn tài liệu "${doc.tieu_de}" và tệp vật lý? Thao tác này không thể hoàn tác.`)) return;
        router.delete(route('admin.documents.permanent-delete', doc.ma_tai_lieu), {
            data: { reason: 'Xóa vĩnh viễn sau thời hạn lưu trữ.' },
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title="Thùng rác Tài liệu">
            <Head title="Thùng rác Tài liệu - AGU Hub Admin" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 bg-slate-100 text-slate-700 rounded-xl border border-slate-200">
                                <Trash2 className="w-5 h-5" />
                            </span>
                            <h1 className="text-xl font-bold text-slate-900">
                                Thùng rác & Khôi phục Tài liệu
                            </h1>
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5 ml-9">
                            Tài liệu được giữ tối thiểu {autoPurgeAfterDays} ngày; Admin có thể xóa vĩnh viễn từ ngày thứ {adminDeleteAfterDays}.
                        </p>
                    </div>

                    <div className="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 self-start sm:self-auto">
                        Đã xóa: <strong className="text-slate-900">{documents?.total || items.length}</strong> tài liệu
                    </div>
                </div>

                <form onSubmit={applyFilters} className="flex flex-wrap items-end gap-3 bg-white p-4 rounded-2xl border border-slate-200/80">
                    <label className="text-xs font-semibold">Từ ngày<input name="from" type="date" defaultValue={filters.from || ''} className="block mt-1 rounded-lg border-slate-200 text-sm" /></label>
                    <label className="text-xs font-semibold">Đến ngày<input name="to" type="date" defaultValue={filters.to || ''} className="block mt-1 rounded-lg border-slate-200 text-sm" /></label>
                    <label className="text-xs font-semibold">Người xóa<select name="deleted_by" defaultValue={filters.deleted_by || ''} className="block mt-1 rounded-lg border-slate-200 text-sm"><option value="">Tất cả</option>{actors.map((actor) => <option key={actor.ma_nguoi_dung} value={actor.ma_nguoi_dung}>{actor.ho_ten}</option>)}</select></label>
                    <label className="text-xs font-semibold">Nguồn xóa<select name="source" defaultValue={filters.source || ''} className="block mt-1 rounded-lg border-slate-200 text-sm"><option value="">Tất cả</option>{sources.map((source) => <option key={source} value={source}>{source}</option>)}</select></label>
                    <label className="text-xs font-semibold">Trạng thái<select name="status" defaultValue={filters.status || ''} className="block mt-1 rounded-lg border-slate-200 text-sm"><option value="">Tất cả</option>{statuses.map((status) => <option key={status} value={status}>{status}</option>)}</select></label>
                    <button className="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold">Lọc</button>
                </form>

                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    {items.length === 0 ? (
                        <div className="p-12 text-center">
                            <div className="w-16 h-16 bg-slate-100 text-slate-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-slate-200">
                                <FileText className="w-8 h-8" />
                            </div>
                            <h3 className="text-base font-bold text-slate-900">Thùng rác đang trống</h3>
                            <p className="text-xs text-slate-500 max-w-md mx-auto mt-1">
                                Không có tài liệu nào bị xóa mềm trong hệ thống.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="bg-slate-50/75 border-b border-slate-200/80 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <tr>
                                        <th className="px-6 py-4">Tài liệu</th>
                                        <th className="px-6 py-4">Môn học</th>
                                        <th className="px-6 py-4">Người đăng</th>
                                        <th className="px-6 py-4">Người xóa / lý do</th>
                                        <th className="px-6 py-4">Thời gian xóa</th>
                                        <th className="px-6 py-4 text-right">Khôi phục</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((doc) => (
                                        <tr key={doc.ma_tai_lieu} className="hover:bg-slate-50/50 transition-colors">
                                            <td className="px-6 py-4 font-semibold text-slate-900">
                                                {doc.tieu_de}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                {doc.mon_hoc?.ten_mon_hoc || '---'}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                {doc.nguoi_dang?.ho_ten || '---'}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                <div>{doc.nguoi_xoa?.ho_ten || 'Hệ thống'} ({doc.nguon_xoa || '---'})</div>
                                                <div className="text-slate-400 mt-1 max-w-xs">{doc.ly_do_xoa || 'Không có lý do'}</div>
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-500">
                                                {doc.ngay_xoa_tam}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => handleRestore(doc)}
                                                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                                >
                                                    <RotateCcw className="w-3.5 h-3.5" /> Khôi phục
                                                </button>
                                                {canPermanentlyDelete && doc.co_the_xoa_vinh_vien && (
                                                    <button type="button" onClick={() => handlePermanentDelete(doc)} className="ml-2 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200">
                                                        Xóa vĩnh viễn
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={documents?.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
