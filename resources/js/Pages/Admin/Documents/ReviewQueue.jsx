import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import { ExternalLink, Eye, FileWarning, ShieldCheck } from 'lucide-react';

const actions = [
    { value: 'KEEP', label: 'Giữ nguyên', className: 'bg-slate-700 hover:bg-slate-800' },
    { value: 'RESTORE', label: 'Khôi phục', className: 'bg-emerald-600 hover:bg-emerald-700' },
    { value: 'HIDE', label: 'Ẩn tài liệu', className: 'bg-amber-600 hover:bg-amber-700' },
    { value: 'DELETE', label: 'Xóa mềm', className: 'bg-rose-600 hover:bg-rose-700' },
];

export default function ReviewQueue({ documents, trustedReputation, reviewThreshold, autoHideThreshold }) {
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);
    const items = documents?.data || [];

    const resizeReasonInput = (event) => {
        const input = event.currentTarget;
        input.style.height = 'auto';
        input.style.height = `${input.scrollHeight}px`;
        setReason(input.value);
    };

    const decide = (document, action) => {
        if (!reason.trim()) {
            window.alert('Vui lòng nhập lý do xử lý trước khi quyết định.');
            return;
        }
        setProcessing(true);
        router.put(route('admin.documents.decide', document.ma_tai_lieu), {
            action,
            resolution_reason: reason,
        }, { preserveScroll: true, onFinish: () => setProcessing(false) });
    };

    return (
        <AdminLayout title="Hàng đợi kiểm tra tài liệu">
            <Head title="Hàng đợi kiểm tra tài liệu - AGU Hub Admin" />
            <div className="space-y-6">
                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <div className="flex items-center gap-2">
                        <span className="rounded-xl border border-amber-100 bg-amber-50 p-2 text-amber-600"><FileWarning className="h-5 w-5" /></span>
                        <h1 className="text-xl font-bold text-slate-900">Hàng đợi kiểm tra tài liệu</h1>
                    </div>
                    <p className="ml-9 mt-1.5 text-xs text-slate-500">
                        Tài khoản uy tín từ {trustedReputation} điểm; chuyển kiểm tra từ {reviewThreshold} báo cáo, tự động ẩn từ {autoHideThreshold} báo cáo.
                    </p>
                </div>

                <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    {items.length === 0 ? (
                        <div className="p-12 text-center"><ShieldCheck className="mx-auto mb-3 h-10 w-10 text-emerald-600" /><p className="font-semibold text-slate-900">Hàng đợi đang trống</p></div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-[1180px] w-full text-left text-sm text-slate-600">
                                <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                                    <tr><th className="px-6 py-4">Tài liệu</th><th className="px-6 py-4">Người đăng</th><th className="px-6 py-4">Báo cáo hợp lệ</th><th className="px-6 py-4">Lý do tự động</th><th className="px-6 py-4">Quyết định</th></tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((document) => (
                                        <tr key={document.ma_tai_lieu}>
                                            <td className="px-6 py-4 font-semibold text-slate-900">
                                                <Link href={route('admin.documents.review-detail', document.ma_tai_lieu)} className="hover:text-[#005baa] hover:underline">
                                                    {document.tieu_de}
                                                </Link>
                                                <div className="mt-1 flex flex-wrap gap-2 text-[11px] font-normal text-slate-500">
                                                    <span>{document.mon_hoc?.ma_hoc_phan || '---'}</span>
                                                    <span>· {document.dinh_dang_tep?.toUpperCase() || 'Tệp'}</span>
                                                    <span>· {document.dung_luong_kb || 0} KB</span>
                                                </div>
                                                <Link href={route('admin.documents.review-detail', document.ma_tai_lieu)} className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa]">
                                                    <Eye className="h-3.5 w-3.5" /> Xem chi tiết
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4 text-xs">{document.nguoi_dang?.ho_ten || '---'}</td>
                                            <td className="px-6 py-4 font-bold text-rose-600">{document.bao_cao_hop_le_count ?? document.so_bao_cao_hop_le}</td>
                                            <td className="max-w-sm px-6 py-4 text-xs">
                                                <p>{document.ly_do_trang_thai || '---'}</p>
                                                <Link href={route('admin.documents.review-detail', document.ma_tai_lieu)} className="mt-2 inline-flex items-center gap-1 font-semibold text-[#005baa]">
                                                    <ExternalLink className="h-3.5 w-3.5" /> Xem báo cáo và tệp
                                                </Link>
                                            </td>
                                            <td className="min-w-[360px] px-6 py-4 align-top">
                                                <textarea
                                                    value={reason}
                                                    onInput={resizeReasonInput}
                                                    maxLength={2000}
                                                    rows={2}
                                                    placeholder="Nhập lý do và căn cứ xử lý..."
                                                    aria-label={`Lý do quyết định cho tài liệu ${document.tieu_de}`}
                                                    className="mb-3 block min-h-[72px] w-full resize-none overflow-hidden rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-6 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-[#005baa] focus:ring-2 focus:ring-blue-100"
                                                />
                                                <div className="flex flex-wrap gap-2">
                                                    {actions.map((action) => <button key={action.value} type="button" disabled={processing} onClick={() => decide(document, action.value)} className={`inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white ${action.className}`}><Eye className="h-3.5 w-3.5" />{action.label}</button>)}
                                                </div>
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
