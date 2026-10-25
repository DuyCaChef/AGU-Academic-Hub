import React, { useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { X, AlertCircle, ShieldCheck, Send, Calendar } from 'lucide-react';
import RatingStarInput from '@/Components/RatingStarInput';
import { detectProfanity, parseHighlightedText } from '@/Utils/profanityFilter';

export default function CreateReviewModal({ isOpen, onClose, course }) {
    if (!isOpen) return null;

    // Chuẩn hóa mã học phần và ID môn học từ Backend
    const courseCode = course?.course_code || course?.ma_hoc_phan || '';
    const courseName = course?.name || course?.ten_mon_hoc || 'Môn học';

    // Khởi tạo Inertia Form Hook hỗ trợ cả schema tiếng Anh và CSDL tiếng Việt
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        difficulty_score: 3,
        practicality_score: 4,
        workload_score: 3,
        academic_term: '2025-2',
        content: '',
        is_anonymous: true,
        diem_do_kho: 3,
        diem_thuc_tien: 4,
        diem_ap_luc_bai_tap: 3,
        diem_khoi_luong: 3,
        hoc_ky: '2025-2',
        nhan_xet: '',
        noi_dung: '',
        la_an_danh: true,
        an_danh: true,
    });

    // Phát hiện từ cấm theo thời gian thực (Real-time Profanity Check)
    const violations = useMemo(() => {
        return detectProfanity(data.content || data.nhan_xet);
    }, [data.content, data.nhan_xet]);

    const hasViolations = violations.length > 0;

    // Đồng bộ thay đổi nội dung nhận xét
    const handleContentChange = (e) => {
        const val = e.target.value;
        setData((prev) => ({
            ...prev,
            content: val,
            nhan_xet: val,
            noi_dung: val,
        }));
    };

    // Đóng bằng phím ESC (Accessibility)
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && !processing) {
                onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [processing, onClose]);

    // Submit form lên Controller
    const handleSubmit = (e) => {
        e.preventDefault();
        if (hasViolations) return;

        // Ưu tiên endpoint chuẩn đã đăng ký tại routes/community.php
        const submitUrl = typeof route === 'function' && route().has('courses.reviews.store')
            ? route('courses.reviews.store', courseCode)
            : `/mon-hoc/${courseCode}/danh-gia`;

        post(submitUrl, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                clearErrors();
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all animate-fade-in my-6">

                {/* Header Modal */}
                <div className="px-6 py-4 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="px-2 py-0.5 rounded text-xs font-mono font-bold bg-[#005baa]/10 text-[#005baa]">
                                {courseCode}
                            </span>
                            <h3 className="text-base sm:text-lg font-bold text-[#0f172a]">
                                Đánh giá môn học: {courseName}
                            </h3>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Chia sẻ trải nghiệm khách quan giúp cộng đồng sinh viên AGU học tập tốt hơn.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        disabled={processing}
                        className="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                        title="Đóng modal"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Body Form */}
                <form onSubmit={handleSubmit} className="p-6 space-y-5 max-h-[calc(90vh-140px)] overflow-y-auto">

                    {/* Học kỳ & Tùy chọn Ẩn danh */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pb-4 border-b border-slate-100">
                        <div className="p-3 bg-slate-50/80 rounded-xl border border-slate-200/80 flex flex-col justify-between">
                            <label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5 mb-1.5">
                                <Calendar className="w-3.5 h-3.5 text-[#005baa]" />
                                <span>Học kỳ bạn đã học môn này</span>
                                <span className="text-[#e63946]">*</span>
                            </label>
                            <select
                                value={data.academic_term || data.hoc_ky}
                                onChange={(e) => {
                                    const val = e.target.value;
                                    setData((prev) => ({
                                        ...prev,
                                        academic_term: val,
                                        hoc_ky: val,
                                    }));
                                }}
                                className="w-full text-xs sm:text-sm font-medium rounded-lg border-slate-200 bg-white py-1.5 px-3 focus:border-[#005baa] focus:ring focus:ring-[#005baa]/20 transition-colors shadow-sm"
                            >
                                <option value="2026-1">Học kỳ 1 (2025 - 2026)</option>
                                <option value="2025-2">Học kỳ 2 (2024 - 2025)</option>
                                <option value="2025-1">Học kỳ 1 (2024 - 2025)</option>
                                <option value="2024-2">Học kỳ 2 (2023 - 2024)</option>
                                <option value="2024-1">Học kỳ 1 (2023 - 2024)</option>
                            </select>
                            {(errors.academic_term || errors.hoc_ky) && (
                                <p className="text-xs text-[#e63946] mt-1 font-medium">{errors.academic_term || errors.hoc_ky}</p>
                            )}
                        </div>

                        <label className="p-3 bg-slate-50/80 hover:bg-slate-100/80 rounded-xl border border-slate-200/80 flex items-center gap-3 cursor-pointer transition-colors">
                            <input
                                type="checkbox"
                                checked={data.is_anonymous}
                                onChange={(e) => {
                                    const checked = e.target.checked;
                                    setData((prev) => ({
                                        ...prev,
                                        is_anonymous: checked,
                                        la_an_danh: checked,
                                        an_danh: checked,
                                    }));
                                }}
                                className="w-4 h-4 text-[#005baa] rounded border-slate-300 focus:ring-[#005baa]"
                            />
                            <div className="flex-1">
                                <span className="text-xs font-semibold text-slate-800 flex items-center gap-1.5">
                                    <ShieldCheck className="w-4 h-4 text-[#00a896]" />
                                    <span>Đánh giá ẩn danh</span>
                                </span>
                                <p className="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                    Che giấu tên thật và MSSV với các sinh viên khác
                                </p>
                            </div>
                        </label>
                    </div>

                    {/* 3 Tiêu chí chấm điểm (Xếp dạng dòng ngang thoáng đẹp) */}
                    <div className="space-y-2.5 p-4 bg-slate-50/70 rounded-2xl border border-slate-200/70">
                        <div className="flex items-center justify-between pb-0.5">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-600">
                                Đánh giá theo 3 tiêu chí trọng tâm
                            </h4>
                            <span className="text-[11px] text-slate-400 font-medium">Bấm vào sao để chọn (1-5)</span>
                        </div>

                        <RatingStarInput
                            label="Độ khó môn học"
                            description="Kiến thức lý thuyết, bài thi"
                            value={data.difficulty_score || data.diem_do_kho}
                            customLabels={{
                                1: 'Rất dễ hiểu',
                                2: 'Khá dễ',
                                3: 'Vừa phải',
                                4: 'Khá khó',
                                5: 'Rất khó / Nặng',
                            }}
                            onChange={(val) => {
                                setData((prev) => ({
                                    ...prev,
                                    difficulty_score: val,
                                    diem_do_kho: val,
                                }));
                            }}
                        />

                        <RatingStarInput
                            label="Tính thực tiễn"
                            description="Khả năng ứng dụng thực tế, đồ án"
                            value={data.practicality_score || data.diem_thuc_tien}
                            customLabels={{
                                1: 'Hàn lâm ít dùng',
                                2: 'Ít ứng dụng',
                                3: 'Khá hữu ích',
                                4: 'Ứng dụng tốt',
                                5: 'Rất thực tiễn',
                            }}
                            onChange={(val) => {
                                setData((prev) => ({
                                    ...prev,
                                    practicality_score: val,
                                    diem_thuc_tien: val,
                                }));
                            }}
                        />

                        <RatingStarInput
                            label="Khối lượng bài tập"
                            description="Bài tập lớn, lab thực hành, deadline"
                            value={data.workload_score || data.diem_ap_luc_bai_tap}
                            customLabels={{
                                1: 'Rất nhẹ nhàng',
                                2: 'Ít bài tập',
                                3: 'Vừa sức',
                                4: 'Nhiều bài tập',
                                5: 'Deadline dày đặc',
                            }}
                            onChange={(val) => {
                                setData((prev) => ({
                                    ...prev,
                                    workload_score: val,
                                    diem_ap_luc_bai_tap: val,
                                    diem_khoi_luong: val,
                                }));
                            }}
                        />
                    </div>

                    {/* Vùng nhập nhận xét & Highlight từ cấm realtime */}
                    <div className="space-y-1.5">
                        <div className="flex justify-between items-center">
                            <label className="text-xs font-semibold text-slate-800">
                                Nhận xét chi tiết <span className="text-[#e63946]">*</span>
                            </label>
                            <span className="text-[11px] text-slate-400">
                                {(data.content || data.nhan_xet || '').length} / 2000 ký tự (tối thiểu 10 ký tự)
                            </span>
                        </div>

                        <div className="relative">
                            <textarea
                                rows={4}
                                value={data.content || data.nhan_xet}
                                onChange={handleContentChange}
                                placeholder="Chia sẻ kinh nghiệm: phương pháp học hiệu quả, đề thi thường tập trung phần nào, lưu ý khi làm bài tập lớn..."
                                className={`w-full text-sm rounded-xl transition-colors leading-relaxed p-3.5 ${hasViolations
                                        ? 'border-[#e63946] focus:border-[#e63946] focus:ring-[#e63946]/20 bg-rose-50/30'
                                        : 'border-slate-200 focus:border-[#005baa] focus:ring-[#005baa]/20'
                                    }`}
                            />
                        </div>

                        {/* Cảnh báo từ cấm Realtime */}
                        {hasViolations && (
                            <div className="p-3 bg-rose-50 border border-rose-200 rounded-xl space-y-1.5 animate-shake">
                                <div className="flex items-center gap-1.5 text-xs font-semibold text-[#e63946]">
                                    <AlertCircle className="w-4 h-4 flex-shrink-0" />
                                    <span>Phát hiện từ ngữ không phù hợp chuẩn mực học thuật:</span>
                                </div>
                                <div className="flex flex-wrap gap-1.5">
                                    {violations.map((w, idx) => (
                                        <span
                                            key={idx}
                                            className="px-2 py-0.5 text-xs bg-rose-200 text-rose-800 rounded font-medium line-through"
                                        >
                                            {w}
                                        </span>
                                    ))}
                                </div>
                                <div className="mt-2 text-xs text-slate-600 bg-white p-2.5 rounded-lg border border-rose-100">
                                    <span className="font-semibold text-slate-500 block mb-1">
                                        Xem trước văn bản được phát hiện:
                                    </span>
                                    <p className="leading-relaxed">
                                        {parseHighlightedText(data.content || data.nhan_xet, violations).map((part, i) => (
                                            <span
                                                key={i}
                                                className={
                                                    part.isBad
                                                        ? 'bg-rose-200 text-rose-800 px-1 py-0.5 rounded font-bold underline decoration-rose-500'
                                                        : ''
                                                }
                                            >
                                                {part.text}
                                            </span>
                                        ))}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* Lỗi trả về từ Backend validation */}
                        {(errors.content || errors.nhan_xet || errors.noi_dung) && (
                            <p className="text-xs text-[#e63946] mt-1 font-medium">
                                {errors.content || errors.nhan_xet || errors.noi_dung}
                            </p>
                        )}
                    </div>

                    {/* Footer Actions */}
                    <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={processing}
                            className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors"
                        >
                            Hủy bỏ
                        </button>

                        <button
                            type="submit"
                            disabled={processing || hasViolations || (data.content || data.nhan_xet || '').trim().length < 10}
                            className="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-[#005baa] hover:bg-blue-800 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100 rounded-xl shadow-sm transition-all"
                        >
                            {processing ? (
                                <>
                                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                    <span>Đang đăng tải...</span>
                                </>
                            ) : (
                                <>
                                    <Send className="w-4 h-4" />
                                    <span>Đăng đánh giá</span>
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
