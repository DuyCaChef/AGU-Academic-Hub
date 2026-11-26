import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, Check, FileText, X } from 'lucide-react';

export default function RemovalRequestShow({ removalRequest, communityOwnershipThreshold }) {
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);
    const document = removalRequest.tai_lieu;
    const processed = removalRequest.trang_thai !== 'CHO_XU_LY';

    const decide = (action) => {
        if (action === 'REJECT' && !reason.trim()) {
            window.alert('Vui lòng nhập lý do từ chối.');
            return;
        }
        if (!window.confirm(action === 'APPROVE' ? 'Duyệt và gỡ mềm tài liệu này?' : 'Từ chối yêu cầu gỡ này?')) return;
        setProcessing(true);
        router.put(route('admin.documents.removal-requests.decide', removalRequest.ma_yeu_cau_go_bo), {
            action,
            decision_reason: reason,
        }, { preserveScroll: true, onFinish: () => setProcessing(false) });
    };

    return (
        <AdminLayout title="Chi tiết yêu cầu gỡ">
            <Head title="Chi tiết yêu cầu gỡ - AGU Hub Admin" />
            <div className="mx-auto max-w-4xl space-y-6">
                <Link href={route('admin.documents.removal-requests.index')} className="inline-flex items-center gap-2 text-sm font-semibold text-[#005baa]"><ArrowLeft className="h-4 w-4" />Quay lại hàng đợi</Link>
                <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <div className="mb-6 flex items-start gap-3"><span className="rounded-xl bg-slate-100 p-2 text-slate-700"><FileText className="h-5 w-5" /></span><div><h1 className="text-xl font-bold text-slate-900">{document?.tieu_de}</h1><p className="text-sm text-slate-500">{document?.mon_hoc?.ten_mon_hoc || '---'}</p></div></div>
                    <div className="grid gap-4 text-sm sm:grid-cols-3">
                        <div><p className="text-xs text-slate-500">Tác giả</p><p className="font-semibold">{document?.nguoi_dang?.ho_ten || removalRequest.nguoi_yeu_cau?.ho_ten}</p></div>
                        <div><p className="text-xs text-slate-500">Số lượt tải</p><p className="font-semibold">{document?.luot_tai ?? 0} / tối thiểu {communityOwnershipThreshold}</p></div>
                        <div><p className="text-xs text-slate-500">Người gửi</p><p className="font-semibold">{removalRequest.nguoi_yeu_cau?.email || '---'}</p></div>
                    </div>
                    <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4"><p className="text-xs font-semibold text-amber-800">Lý do tác giả yêu cầu gỡ</p><p className="mt-2 whitespace-pre-wrap text-sm text-amber-950">{removalRequest.ly_do}</p></div>
                </section>
                <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <h2 className="font-bold text-slate-900">Quyết định xử lý</h2>
                    {processed ? <p className="mt-3 text-sm text-slate-600">Yêu cầu đã được {removalRequest.trang_thai === 'DA_DUYET' ? 'duyệt' : 'từ chối'} lúc {removalRequest.xu_ly_luc}.</p> : (
                        <>
                            <textarea
                                value={reason}
                                onChange={(event) => setReason(event.target.value)}
                                rows={4}
                                maxLength={2000}
                                placeholder="Nhập lý do duyệt hoặc từ chối (bắt buộc)..."
                                className="mt-4 w-full resize-y rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 caret-slate-900 outline-none transition focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20"
                            />
                            <div className="mt-4 flex flex-wrap gap-3">
                                <button type="button" disabled={processing} onClick={() => decide('APPROVE')} className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><Check className="h-4 w-4" />Duyệt và gỡ</button>
                                <button type="button" disabled={processing} onClick={() => decide('REJECT')} className="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><X className="h-4 w-4" />Từ chối</button>
                            </div>
                        </>
                    )}
                </section>
            </div>
        </AdminLayout>
    );
}
