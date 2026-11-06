import React from 'react';
import { useForm } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import { AlertTriangle, X, ShieldAlert, Send } from 'lucide-react';

export default function DocumentReportModal({
    isOpen = false,
    onClose = () => {},
    document = null,
}) {
    const { data, setData, post, processing, reset, errors, clearErrors } = useForm({
        ma_tai_lieu: document?.ma_tai_lieu || '',
        ly_do: 'BAN_QUYEN',
        chi_tiet_loi: '',
    });

    React.useEffect(() => {
        if (document) {
            setData((prev) => ({
                ...prev,
                ma_tai_lieu: document.ma_tai_lieu,
            }));
        }
    }, [document]);

    const handleClose = () => {
        reset();
        clearErrors();
        onClose();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('reports.document.store'), {
            preserveScroll: true,
            onSuccess: () => {
                handleClose();
            },
        });
    };

    const reportReasons = [
        {
            value: 'BAN_QUYEN',
            label: 'Vi phạm bản quyền tác giả / Độc quyền',
            desc: 'Tài liệu cá nhân của giảng viên, tài liệu mật hoặc chưa được phép lưu hành công khai.',
        },
        {
            value: 'SAI_NOI_DUNG',
            label: 'Sai môn học / Nội dung không chính xác',
            desc: 'Tài liệu bị gán nhầm học phần, nội dung xuyên tạc hoặc sai lệch kiến thức chuyên môn.',
        },
        {
            value: 'TRUNG_LAP',
            label: 'Tài liệu trùng lặp',
            desc: 'Tài liệu đã tồn tại trong kho học tập của môn học này.',
        },
        {
            value: 'MA_DOC',
            label: 'Chứa mã độc / Tệp nguy hiểm',
            desc: 'Tệp chứa virus, macro độc hại hoặc đường dẫn phishing gây hại hệ thống.',
        },
        {
            value: 'KHAC',
            label: 'Lý do khác',
            desc: 'Các hành vi vi phạm khác vi phạm quy chuẩn cộng đồng AGU Academic Hub.',
        },
    ];

    if (!document) return null;

    return (
        <Modal show={isOpen} onClose={handleClose} maxWidth="lg">
            <div className="p-6">
                {/* Header */}
                <div className="flex items-start justify-between pb-4 border-b border-border-default">
                    <div className="flex items-center gap-2 text-semantic-danger">
                        <span className="p-2 bg-semantic-danger/10 rounded-lg">
                            <ShieldAlert size={22} className="text-semantic-danger" />
                        </span>
                        <div>
                            <h3 className="text-lg font-bold text-text-primary">
                                Báo cáo vi phạm tài liệu
                            </h3>
                            <p className="text-xs text-text-secondary line-clamp-1 mt-0.5">
                                {document.tieu_de}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={handleClose}
                        className="p-1 rounded-md text-text-secondary hover:text-text-primary hover:bg-surface-page transition-colors"
                        aria-label="Đóng"
                    >
                        <X size={20} />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="mt-4 space-y-4">
                    {/* Reason Select */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">
                            Lý do báo cáo vi phạm <span className="text-semantic-danger">*</span>
                        </label>
                        <div className="space-y-2">
                            {reportReasons.map((item) => (
                                <label
                                    key={item.value}
                                    className={`flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all ${
                                        data.ly_do === item.value
                                            ? 'border-brand-primary bg-brand-primary/5 ring-1 ring-brand-primary/30'
                                            : 'border-border-default hover:bg-surface-page'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="ly_do"
                                        value={item.value}
                                        checked={data.ly_do === item.value}
                                        onChange={(e) => setData('ly_do', e.target.value)}
                                        className="mt-0.5 text-brand-primary focus:ring-brand-primary h-4 w-4"
                                    />
                                    <div className="flex-1">
                                        <div className="text-sm font-semibold text-text-primary">
                                            {item.label}
                                        </div>
                                        <div className="text-xs text-text-secondary mt-0.5">
                                            {item.desc}
                                        </div>
                                    </div>
                                </label>
                            ))}
                        </div>
                        {errors.ly_do && (
                            <p className="mt-1 text-xs text-semantic-danger">{errors.ly_do}</p>
                        )}
                    </div>

                    {/* Detailed Reason */}
                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">
                            Mô tả chi tiết bằng chứng <span className="text-text-muted font-normal">(Tùy chọn)</span>
                        </label>
                        <textarea
                            value={data.chi_tiet_loi}
                            onChange={(e) => setData('chi_tiet_loi', e.target.value)}
                            rows={3}
                            maxLength={2000}
                            placeholder="Mô tả cụ thể trang vi phạm hoặc cung cấp thông tin đối chiếu để hỗ trợ Ban Kiểm duyệt xử lý nhanh chóng..."
                            className="w-full text-sm bg-surface-page border border-border-default rounded-lg p-2.5 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                        />
                        {errors.chi_tiet_loi && (
                            <p className="mt-1 text-xs text-semantic-danger">{errors.chi_tiet_loi}</p>
                        )}
                    </div>

                    {/* Academic Integrity Warning */}
                    <div className="flex items-start gap-2.5 p-3 rounded-lg bg-semantic-warning/10 border border-semantic-warning/20 text-xs text-text-secondary">
                        <AlertTriangle size={18} className="text-semantic-warning shrink-0 mt-0.5" />
                        <div>
                            <span className="font-semibold text-text-primary">Cảnh báo liêm chính:</span>{' '}
                            Báo cáo sai sự thật hoặc lạm dụng tính năng báo cáo có thể khiến tài khoản của bạn bị trừ điểm uy tín học thuật theo Quy chế AGU.
                        </div>
                    </div>

                    {/* Modal Footer Actions */}
                    <div className="flex items-center justify-end gap-3 pt-3 border-t border-border-default">
                        <button
                            type="button"
                            onClick={handleClose}
                            className="px-4 py-2 text-sm font-medium text-text-secondary hover:text-text-primary hover:bg-surface-page rounded-md transition-colors"
                        >
                            Hủy bỏ
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-semantic-danger hover:bg-red-700 disabled:opacity-50 rounded-md shadow-xs transition-all"
                        >
                            <Send size={15} />
                            <span>{processing ? 'Đang gửi...' : 'Gửi báo cáo'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

