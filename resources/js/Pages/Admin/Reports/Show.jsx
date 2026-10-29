import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, ExternalLink, FileText, User } from 'lucide-react';

const actions = [
    {
        value: 'HIDE',
        label: 'Tạm ẩn tài liệu để kiểm tra',
        className: 'bg-amber-600 hover:bg-amber-700',
    },
    {
        value: 'DELETE',
        label: 'Gỡ tài liệu khỏi hệ thống',
        className: 'bg-rose-600 hover:bg-rose-700',
    },
    {
        value: 'DISMISS',
        label: 'Bác bỏ báo cáo, giữ tài liệu',
        className: 'bg-slate-700 hover:bg-slate-800',
    },
];

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

export default function ReportShow({ report, relatedReports = [] }) {
    const [reason, setReason] = useState('');
    const [reasonError, setReasonError] = useState('');
    const [processing, setProcessing] = useState(false);

    const resolve = (action) => {
        if (!reason.trim()) {
            setReasonError('Vui lòng nhập lý do và căn cứ xử lý.');
            return;
        }
        setReasonError('');
        setProcessing(true);
        router.put(route('admin.reports.resolve', report.ma_bao_cao), { action, resolution_reason: reason }, {
            preserveScroll: true,
            onError: (errors) => {
                setReasonError(errors.resolution_reason || 'Không thể ghi nhận xử lý. Vui lòng kiểm tra lại thông tin.');
            },
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AdminLayout title="Chi tiết báo cáo vi phạm">
            <Head title={`Báo cáo #${report.ma_bao_cao} - AGU Hub Admin`} />
            <div className="space-y-6">
                <Link href={route('admin.reports.index')} className="inline-flex items-center gap-2 text-sm font-semibold text-[#005baa] hover:underline"><ArrowLeft className="h-4 w-4" />Quay lại hàng đợi</Link>
                <div className="grid gap-6 lg:grid-cols-3">
                    <section className="space-y-6 lg:col-span-2">
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h1 className="mb-5 text-xl font-bold text-slate-900">Báo cáo #{report.ma_bao_cao}</h1>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Info icon={<FileText />} label="Tài liệu" value={report.tai_lieu?.tieu_de || `#${report.ma_tai_lieu}`} />
                                <Info icon={<User />} label="Người đăng" value={report.tai_lieu?.nguoi_dang?.ho_ten || 'Không xác định'} />
                                <Info icon={<User />} label="Người báo cáo" value={report.nguoi_bao_cao?.ho_ten || 'Không xác định'} />
                                <Info label="Lý do" value={report.ly_do} />
                            </div>
                            <div className="mt-5 rounded-xl bg-rose-50 p-4"><p className="text-xs font-semibold text-rose-700">Nội dung chi tiết vi phạm</p><p className="mt-2 whitespace-pre-wrap text-sm text-slate-700">{report.chi_tiet_loi || 'Không có mô tả thêm.'}</p></div>
                            {report.tai_lieu?.duong_dan_tep && <a href={report.tai_lieu.duong_dan_tep} target="_blank" rel="noreferrer" className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-[#005baa] hover:underline"><ExternalLink className="h-4 w-4" />Xem tài liệu bị báo cáo</a>}
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                            <h2 className="mb-4 text-base font-bold text-slate-900">Các báo cáo khác cùng tài liệu ({relatedReports.length})</h2>
                            <div className="divide-y divide-slate-100">{relatedReports.map((item) => <div key={item.ma_bao_cao} className="flex justify-between gap-4 py-3 text-sm"><span>{item.nguoi_bao_cao?.ho_ten || 'Thành viên'} · {item.ly_do}</span><span className={`rounded-full border px-2 py-0.5 text-xs ${statusStyles[item.trang_thai_xu_ly] || 'border-slate-200 bg-slate-50 text-slate-600'}`}>{statusLabels[item.trang_thai_xu_ly] || item.trang_thai_xu_ly}</span></div>)}</div>
                        </div>
                    </section>
                    <section className="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                        <h2 className="text-base font-bold text-slate-900">Ghi nhận xử lý</h2>
                        {report.trang_thai_xu_ly !== 'CHO_XU_LY' && (
                            <p className="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
                                Báo cáo đã có kết quả. Thao tác mới sẽ thay thế quyết định trước đó.
                            </p>
                        )}
                        <div className="mt-4">
                            <label htmlFor="resolution-reason" className="block text-sm font-semibold text-slate-800">
                                Lý do xử lý <span className="text-rose-600" aria-hidden="true">*</span>
                            </label>
                            <p id="resolution-reason-hint" className="mt-1 text-xs leading-5 text-slate-500">
                                Nêu căn cứ cho quyết định và thông tin cần lưu lại để đối chiếu sau này.
                            </p>
                            <textarea
                                id="resolution-reason"
                                value={reason}
                                onChange={(e) => {
                                    setReason(e.target.value);
                                    if (reasonError) setReasonError('');
                                }}
                                rows={6}
                                maxLength={2000}
                                placeholder="Ví dụ: Đã kiểm tra nội dung và xác định tài liệu vi phạm bản quyền..."
                                aria-describedby="resolution-reason-hint resolution-reason-count"
                                aria-invalid={Boolean(reasonError)}
                                className={`mt-3 min-h-[144px] w-full resize-y rounded-xl border px-3 py-3 text-sm leading-6 text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:ring-2 ${reasonError
                                    ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-100'
                                    : 'border-slate-200 focus:border-[#005baa] focus:ring-blue-100'
                                }`}
                            />
                            <div className="mt-1 flex items-start justify-between gap-3">
                                <p className="text-xs text-slate-500">Bắt buộc, tối đa 2.000 ký tự.</p>
                                <p id="resolution-reason-count" className={`text-xs ${reason.length > 1800 ? 'font-semibold text-amber-600' : 'text-slate-400'}`}>
                                    {reason.length}/2000
                                </p>
                            </div>
                            {reasonError && <p className="mt-2 text-xs font-medium text-rose-600" role="alert">{reasonError}</p>}
                        </div>
                        <div className="mt-5 space-y-3">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Chọn kết quả xử lý</p>
                            {actions.map((action) => (
                                <button
                                    key={action.value}
                                    disabled={processing}
                                    onClick={() => resolve(action.value)}
                                    className={`w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-50 ${action.className}`}
                                >
                                    {action.label}
                                </button>
                            ))}
                        </div>
                        {report.nguoi_xu_ly && <p className="mt-4 text-xs text-slate-500">Đã xử lý bởi {report.nguoi_xu_ly.ho_ten} lúc {report.xu_ly_luc}</p>}
                    </section>
                </div>
            </div>
        </AdminLayout>
    );
}

function Info({ icon, label, value }) {
    return <div className="flex items-start gap-3"><span className="text-slate-400 [&>svg]:h-4 [&>svg]:w-4">{icon}</span><div><p className="text-xs text-slate-500">{label}</p><p className="mt-1 text-sm font-semibold text-slate-900">{value}</p></div></div>;
}
