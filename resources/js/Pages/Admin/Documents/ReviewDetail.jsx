import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    ArrowLeft,
    Download,
    FileText,
    Eye,
    CheckCircle2,
    XCircle,
    ShieldCheck,
    Clock,
    AlertTriangle,
} from 'lucide-react';

const reportActions = [
    { value: 'KEEP', label: 'Giữ nguyên', className: 'bg-slate-700 hover:bg-slate-800' },
    { value: 'RESTORE', label: 'Khôi phục', className: 'bg-emerald-600 hover:bg-emerald-700' },
    { value: 'HIDE', label: 'Ẩn tài liệu', className: 'bg-amber-600 hover:bg-amber-700' },
    { value: 'DELETE', label: 'Xóa mềm', className: 'bg-rose-600 hover:bg-rose-700' },
];

export default function ReviewDetail({ document, reports = [], fileExists }) {
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);

    const isNewPending = document.trang_thai === 'CHO_DUYET';

    // Quyết định cho tài liệu bị báo cáo vi phạm
    const decide = (action) => {
        if (!reason.trim()) {
            window.alert('Vui lòng nhập lý do xử lý.');
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.documents.decide', document.ma_tai_lieu),
            {
                action,
                resolution_reason: reason,
            },
            { onFinish: () => setProcessing(false) }
        );
    };

    // Phê duyệt tài liệu mới đăng
    const handleApproveNew = () => {
        if (!window.confirm(`Xác nhận phê duyệt công khai tài liệu "${document.tieu_de}"?`)) {
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.documents.approve', document.ma_tai_lieu),
            {},
            { onFinish: () => setProcessing(false) }
        );
    };

    // Từ chối tài liệu mới đăng
    const handleRejectNew = () => {
        if (!reason.trim() || reason.trim().length < 10) {
            window.alert('Vui lòng nhập lý do từ chối cụ thể (tối thiểu 10 ký tự) để gửi thông báo cho tác giả.');
            return;
        }

        if (!window.confirm(`Xác nhận từ chối tài liệu "${document.tieu_de}"?`)) {
            return;
        }

        setProcessing(true);
        router.put(
            route('admin.documents.reject', document.ma_tai_lieu),
            { reason },
            { onFinish: () => setProcessing(false) }
        );
    };

    return (
        <AdminLayout title="Chi tiết kiểm tra tài liệu">
            <Head title={`Kiểm tra ${document.tieu_de}`} />

            <div className="space-y-6">
                <div>
                    <Link
                        href={route('admin.documents.review-queue')}
                        className="inline-flex items-center gap-2 text-sm font-semibold text-[#005baa] hover:underline"
                    >
                        <ArrowLeft className="h-4 w-4" /> Quay lại hàng đợi
                    </Link>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(340px,0.6fr)]">
                    {/* Cột trái: Thông tin tài liệu và xem trước file */}
                    <section className="space-y-6">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        {isNewPending ? (
                                            <span className="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-700">
                                                <Clock className="h-3 w-3 text-sky-500" />
                                                Tài liệu mới chờ duyệt
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                                <AlertTriangle className="h-3 w-3 text-amber-500" />
                                                Tài liệu bị gắn cờ kiểm tra
                                            </span>
                                        )}
                                    </div>
                                    <h1 className="mt-2 text-xl font-bold text-slate-900">
                                        {document.tieu_de}
                                    </h1>
                                    <p className="mt-1 text-sm text-slate-500">
                                        {document.mon_hoc?.ma_hoc_phan} · {document.mon_hoc?.ten_mon_hoc} · Người đăng: <strong className="text-slate-700">{document.nguoi_dang?.ho_ten}</strong> (MSSV: {document.nguoi_dang?.mssv || '---'})
                                    </p>
                                </div>
                                <FileText className="h-8 w-8 shrink-0 text-[#005baa]" />
                            </div>

                            <div className="mt-5 grid gap-3 text-sm sm:grid-cols-3">
                                <Info label="Định dạng" value={document.dinh_dang_tep?.toUpperCase()} />
                                <Info label="Dung lượng" value={`${document.dung_luong_kb || 0} KB`} />
                                <Info label="Báo cáo hợp lệ" value={document.so_bao_cao_hop_le ?? 0} />
                            </div>

                            {document.mo_ta && (
                                <div className="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                                    <strong className="block text-slate-500 mb-1">Mô tả của tác giả:</strong>
                                    <p className="whitespace-pre-line">{document.mo_ta}</p>
                                </div>
                            )}
                        </div>

                        {/* Khung xem trước tài liệu trực tiếp */}
                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                                <h2 className="font-bold text-slate-900">Nội dung tệp</h2>
                                {fileExists && (
                                    <a
                                        href={route('admin.documents.preview', document.ma_tai_lieu)}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                    >
                                        <Eye className="h-3.5 w-3.5" /> Mở tab riêng
                                    </a>
                                )}
                            </div>
                            {fileExists && document.dinh_dang_tep?.toLowerCase() === 'pdf' ? (
                                <iframe
                                    title={`Xem trước ${document.tieu_de}`}
                                    src={route('admin.documents.preview', document.ma_tai_lieu)}
                                    className="h-[680px] w-full"
                                />
                            ) : fileExists ? (
                                <div className="p-8 text-center">
                                    <p className="text-sm text-slate-600">
                                        Định dạng này không xem trực tiếp được trong trình duyệt.
                                    </p>
                                    <a
                                        href={route('admin.documents.preview', document.ma_tai_lieu)}
                                        className="mt-3 inline-flex items-center gap-2 rounded-lg bg-[#005baa] px-4 py-2 text-sm font-semibold text-white"
                                    >
                                        <Download className="h-4 w-4" /> Tải tệp về máy để kiểm tra
                                    </a>
                                </div>
                            ) : (
                                <p className="p-8 text-center text-sm text-rose-600">
                                    Không tìm thấy tệp vật lý trên bộ nhớ máy chủ.
                                </p>
                            )}
                        </div>
                    </section>

                    {/* Cột phải: Báo cáo & Quyết định của Moderator */}
                    <aside className="space-y-6">
                        {/* Danh sách báo cáo vi phạm */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h2 className="font-bold text-slate-900">
                                {isNewPending ? 'Trạng thái kiểm duyệt ban đầu' : `Các báo cáo liên quan (${reports.length})`}
                            </h2>
                            {isNewPending ? (
                                <div className="mt-3 rounded-xl bg-sky-50 p-3 text-xs text-sky-800">
                                    <ShieldCheck className="h-5 w-5 text-sky-600 mb-1" />
                                    Tài liệu này mới được nộp và chưa có báo cáo vi phạm. Hãy thẩm định nội dung tệp để phê duyệt công khai hoặc từ chối nếu không đạt tiêu chuẩn.
                                </div>
                            ) : reports.length === 0 ? (
                                <p className="mt-3 text-xs text-slate-400">Không có báo cáo vi phạm chi tiết.</p>
                            ) : (
                                <div className="mt-3 divide-y divide-slate-100">
                                    {reports.map((report) => (
                                        <div key={report.ma_bao_cao} className="py-3 text-sm">
                                            <p className="font-semibold text-rose-700">{report.ly_do}</p>
                                            <p className="mt-1 text-xs text-slate-500">
                                                {report.nguoi_bao_cao?.ho_ten || 'Thành viên'} · {report.chi_tiet_loi || 'Không có mô tả'}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Thẻ Quyết định kiểm duyệt */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h2 className="font-bold text-slate-900">Quyết định kiểm duyệt</h2>

                            {isNewPending ? (
                                /* Form xử lý tài liệu mới đăng */
                                <div className="mt-4 space-y-4">
                                    <p className="text-xs text-slate-500">
                                        Sau khi kiểm tra tệp tin, bạn có thể phê duyệt cho phép tài liệu xuất hiện trên Cổng học thuật hoặc từ chối kèm lý do.
                                    </p>

                                    <textarea
                                        value={reason}
                                        onChange={(event) => setReason(event.target.value)}
                                        rows={3}
                                        maxLength={2000}
                                        placeholder="Nhập lý do từ chối (bắt buộc nếu từ chối duyệt)..."
                                        className="w-full rounded-xl border border-slate-300 p-3 text-xs focus:border-[#005baa] focus:ring-1 focus:ring-blue-100 outline-none"
                                    />

                                    <div className="flex flex-col gap-2">
                                        <button
                                            type="button"
                                            disabled={processing}
                                            onClick={handleApproveNew}
                                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 disabled:opacity-50 transition"
                                        >
                                            <CheckCircle2 className="h-4 w-4" />
                                            Phê duyệt công khai tài liệu
                                        </button>

                                        <button
                                            type="button"
                                            disabled={processing}
                                            onClick={handleRejectNew}
                                            className="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-50 transition"
                                        >
                                            <XCircle className="h-4 w-4" />
                                            Từ chối duyệt tài liệu
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                /* Form xử lý tài liệu bị báo cáo vi phạm */
                                <div className="mt-4 space-y-3">
                                    <textarea
                                        value={reason}
                                        onChange={(event) => setReason(event.target.value)}
                                        rows={4}
                                        maxLength={2000}
                                        placeholder="Nhập căn cứ xử lý sau khi đã kiểm tra nội dung..."
                                        className="w-full rounded-xl border border-slate-300 p-3 text-xs focus:border-[#005baa] focus:ring-1 focus:ring-blue-100 outline-none"
                                    />
                                    <div className="grid grid-cols-2 gap-2">
                                        {reportActions.map((action) => (
                                            <button
                                                key={action.value}
                                                type="button"
                                                disabled={processing}
                                                onClick={() => decide(action.value)}
                                                className={`rounded-lg px-3 py-2 text-xs font-semibold text-white ${action.className} disabled:opacity-50`}
                                            >
                                                {action.label}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </aside>
                </div>
            </div>
        </AdminLayout>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-xl bg-slate-50 p-3">
            <p className="text-xs text-slate-500">{label}</p>
            <p className="mt-1 font-semibold text-slate-900">{value || '---'}</p>
        </div>
    );
}
