import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import { Trash2, RotateCcw, FileText, CheckCircle2 } from 'lucide-react';

export default function DeletedDocumentsIndex({ documents }) {
    const items = documents?.data || [];

    const handleRestore = (id, title) => {
        if (!confirm(`Xác nhận khôi phục tài liệu "${title}"?`)) return;
        router.post(route('admin.documents.restore', id), {}, {
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
                            Danh sách các tài liệu đã bị xóa mềm (Soft Deleted) trên hệ thống, có thể khôi phục lại bất cứ lúc nào.
                        </p>
                    </div>

                    <div className="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 self-start sm:self-auto">
                        Đã xóa: <strong className="text-slate-900">{documents?.total || items.length}</strong> tài liệu
                    </div>
                </div>

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
                                            <td className="px-6 py-4 text-xs text-slate-500">
                                                {doc.ngay_xoa_tam}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => handleRestore(doc.ma_tai_lieu, doc.tieu_de)}
                                                    className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                                >
                                                    <RotateCcw className="w-3.5 h-3.5" />
                                                    Khôi phục
                                                </button>
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
