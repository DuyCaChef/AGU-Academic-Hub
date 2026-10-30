import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Download, FileText, Eye } from 'lucide-react';

const actions = [
    { value: 'KEEP', label: 'Giữ nguyên', className: 'bg-slate-700 hover:bg-slate-800' },
    { value: 'RESTORE', label: 'Khôi phục', className: 'bg-emerald-600 hover:bg-emerald-700' },
    { value: 'HIDE', label: 'Ẩn tài liệu', className: 'bg-amber-600 hover:bg-amber-700' },
    { value: 'DELETE', label: 'Xóa mềm', className: 'bg-rose-600 hover:bg-rose-700' },
];

export default function ReviewDetail({ document, reports = [], fileExists }) {
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);

    const decide = (action) => {
        if (!reason.trim()) {
            window.alert('Vui lòng nhập lý do xử lý.');
            return;
        }

        setProcessing(true);
        router.put(route('admin.documents.decide', document.ma_tai_lieu), {
            action,
            resolution_reason: reason,
        }, { onFinish: () => setProcessing(false) });
    };

    return (
        <AdminLayout title="Chi tiết kiểm tra tài liệu">
            <Head title={`Kiểm tra ${document.tieu_de}`} />
            <div className="space-y-6">
                <Link href={route('admin.documents.review-queue')} className="inline-flex items-center gap-2 text-sm font-semibold text-[#005baa] hover:underline">
                    <ArrowLeft className="h-4 w-4" /> Quay lại hàng đợi
                </Link>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
                    <section className="space-y-6">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Tài liệu cần kiểm tra</p>
                                    <h1 className="mt-1 text-2xl font-bold text-slate-900">{document.tieu_de}</h1>
                                    <p className="mt-2 text-sm text-slate-500">{document.mon_hoc?.ma_hoc_phan} · {document.mon_hoc?.ten_mon_hoc} · Người đăng: {document.nguoi_dang?.ho_ten}</p>
                                </div>
                                <FileText className="h-8 w-8 shrink-0 text-[#005baa]" />
                            </div>
                            <div className="mt-5 grid gap-3 text-sm sm:grid-cols-3">
                                <Info label="Định dạng" value={document.dinh_dang_tep?.toUpperCase()} />
                                <Info label="Dung lượng" value={`${document.dung_luong_kb || 0} KB`} />
                                <Info label="Báo cáo hợp lệ" value={document.so_bao_cao_hop_le} />
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                                <h2 className="font-bold text-slate-900">Nội dung tệp</h2>
                                {fileExists && <a href={route('admin.documents.preview', document.ma_tai_lieu)} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-sm font-semibold text-[#005baa]"><Eye className="h-4 w-4" /> Mở xem</a>}
                            </div>
                            {fileExists && document.dinh_dang_tep?.toLowerCase() === 'pdf' ? (
                                <iframe title={`Xem trước ${document.tieu_de}`} src={route('admin.documents.preview', document.ma_tai_lieu)} className="h-[680px] w-full" />
                            ) : fileExists ? (
                                <div className="p-8 text-center"><p className="text-sm text-slate-600">Định dạng này không xem trực tiếp được trong trình duyệt.</p><a href={route('admin.documents.preview', document.ma_tai_lieu)} className="mt-3 inline-flex items-center gap-2 rounded-lg bg-[#005baa] px-4 py-2 text-sm font-semibold text-white"><Download className="h-4 w-4" /> Tải tệp để kiểm tra</a></div>
                            ) : (
                                <p className="p-8 text-center text-sm text-rose-600">Không tìm thấy tệp vật lý trên bộ nhớ máy chủ.</p>
                            )}
                        </div>
                    </section>

                    <aside className="space-y-6">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h2 className="font-bold text-slate-900">Các báo cáo liên quan ({reports.length})</h2>
                            <div className="mt-3 divide-y divide-slate-100">{reports.map((report) => <div key={report.ma_bao_cao} className="py-3 text-sm"><p className="font-semibold text-rose-700">{report.ly_do}</p><p className="mt-1 text-xs text-slate-500">{report.nguoi_bao_cao?.ho_ten || 'Thành viên'} · {report.chi_tiet_loi || 'Không có mô tả'}</p></div>)}</div>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h2 className="font-bold text-slate-900">Quyết định kiểm duyệt</h2>
                            <textarea value={reason} onChange={(event) => setReason(event.target.value)} rows={5} maxLength={2000} placeholder="Nhập căn cứ sau khi đã kiểm tra nội dung..." className="mt-3 w-full rounded-xl border-slate-300 text-sm focus:border-[#005baa] focus:ring-blue-100" />
                            <div className="mt-3 grid grid-cols-2 gap-2">{actions.map((action) => <button key={action.value} type="button" disabled={processing} onClick={() => decide(action.value)} className={`rounded-lg px-3 py-2 text-xs font-semibold text-white ${action.className}`}>{action.label}</button>)}</div>
                        </div>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    );
}

function Info({ label, value }) {
    return <div className="rounded-xl bg-slate-50 p-3"><p className="text-xs text-slate-500">{label}</p><p className="mt-1 font-semibold text-slate-900">{value || '---'}</p></div>;
}
