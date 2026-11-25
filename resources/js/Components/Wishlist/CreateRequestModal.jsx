import React, { useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { X, Coins, AlertCircle, Lock, BookOpen, Send, ShieldCheck } from 'lucide-react';

export default function CreateRequestModal({ isOpen, onClose, courses = [], defaultCourse = null }) {
    const { auth } = usePage().props;
    const availablePoints = auth?.user?.vi_diem?.diem_kha_dung || 0;

    const { data, setData, post, processing, errors, reset } = useForm({
        ma_mon_hoc: defaultCourse?.ma_mon_hoc || '',
        tieu_de: '',
        mo_ta: '',
        ten_giang_vien: '',
        phan_loai: 'DE_THI',
        diem_thuong: 30,
        redirect_to: defaultCourse ? 'back' : undefined,
    });

    useEffect(() => {
        if (defaultCourse) {
            setData((prev) => ({
                ...prev,
                ma_mon_hoc: defaultCourse.ma_mon_hoc,
                redirect_to: 'back',
            }));
        }
    }, [defaultCourse, isOpen]);

    useEffect(() => {
        if (!isOpen) return;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    const handleSubmit = (e) => {
        e.preventDefault();
        const payload = {
            ...data,
            ma_mon_hoc: defaultCourse ? defaultCourse.ma_mon_hoc : data.ma_mon_hoc,
            redirect_to: defaultCourse ? 'back' : undefined,
        };
        post(route('document-requests.store'), {
            data: payload,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div
            className="fixed inset-0 z-[110] flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs sm:items-center"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-request-title"
                className="relative my-auto w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xl transition-all"
            >
                {/* Header Banner đồng bộ tông AGU */}
                <div className="relative overflow-hidden bg-gradient-to-br from-[#005baa] via-[#004e95] to-blue-900 px-6 py-5 text-white">
                    <div className="pointer-events-none absolute -right-8 -bottom-10 h-36 w-36 rounded-full bg-white/10 blur-2xl" />
                    <div className="pointer-events-none absolute -left-8 -top-10 h-28 w-28 rounded-full bg-[#FFC72C]/15 blur-2xl" />

                    <div className="relative z-10 flex items-start justify-between gap-4">
                        <div className="flex items-center gap-3.5">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-[#FFC72C]/40 bg-[#FFC72C]/20 text-[#FFC72C] shadow-inner">
                                <Coins className="h-5 w-5" />
                            </div>
                            <div>
                                <h3 id="create-request-title" className="text-lg font-bold tracking-tight text-white">
                                    Tạo Yêu Cầu Tìm Tài Liệu
                                </h3>
                                <p className="mt-0.5 text-xs text-blue-100/90">
                                    Treo thưởng Bounty để nhận hỗ trợ nhanh từ cộng đồng sinh viên AGU
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={onClose}
                            className="cursor-pointer rounded-xl p-1.5 text-white/80 transition-colors hover:bg-white/15 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40"
                            aria-label="Đóng"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 p-6">
                        {/* Chọn môn học (Tự động khóa nếu có defaultCourse) */}
                        <div>
                            <label className="mb-1.5 block text-xs font-semibold text-slate-700">
                                Môn học <span className="text-rose-500">*</span>
                            </label>
                            {defaultCourse ? (
                                <div className="flex items-center justify-between gap-2 rounded-xl border border-blue-200/80 bg-blue-50/70 p-3">
                                    <div className="flex min-w-0 items-center gap-2">
                                        <span className="shrink-0 rounded-md border border-blue-200 bg-white px-2 py-0.5 font-mono text-xs font-bold text-[#005BAA]">
                                            {defaultCourse.ma_hoc_phan || defaultCourse.course_code}
                                        </span>
                                        <span className="truncate text-sm font-semibold text-slate-800">
                                            {defaultCourse.ten_mon_hoc || defaultCourse.name}
                                        </span>
                                    </div>
                                    <span className="inline-flex shrink-0 items-center gap-1 rounded-full border border-blue-200/60 bg-blue-100/70 px-2.5 py-0.5 text-[11px] font-medium text-[#005BAA]">
                                        <Lock size={11} />
                                        <span>Đã khóa môn này</span>
                                    </span>
                                </div>
                            ) : (
                                <div className="relative">
                                    <BookOpen
                                        size={16}
                                        className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                                    />
                                    <select
                                        value={data.ma_mon_hoc}
                                        onChange={(e) => setData('ma_mon_hoc', e.target.value)}
                                        className="w-full cursor-pointer rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm font-medium text-slate-800 shadow-2xs transition-colors focus:border-[#005BAA] focus:outline-none focus:ring-1 focus:ring-[#005BAA]"
                                    >
                                        <option value="">-- Chọn môn học --</option>
                                        {courses.map((course) => (
                                            <option key={course.ma_mon_hoc} value={course.ma_mon_hoc}>
                                                [{course.ma_hoc_phan}] {course.ten_mon_hoc}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}
                            {errors.ma_mon_hoc && <p className="mt-1 text-xs text-rose-500">{errors.ma_mon_hoc}</p>}
                        </div>

                        {/* Tiêu đề yêu cầu */}
                        <div>
                            <label className="mb-1.5 block text-xs font-semibold text-slate-700">
                                Tiêu đề yêu cầu <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                placeholder="Ví dụ: Cần tìm đề thi cuối kỳ môn Lập trình Web năm 2024"
                                value={data.tieu_de}
                                onChange={(e) => setData('tieu_de', e.target.value)}
                                className="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 shadow-2xs transition-colors placeholder:text-slate-400 focus:border-[#005BAA] focus:outline-none focus:ring-1 focus:ring-[#005BAA]"
                            />
                            {errors.tieu_de && <p className="mt-1 text-xs text-rose-500">{errors.tieu_de}</p>}
                        </div>

                        {/* Giảng viên & Phân loại */}
                        <div className="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            <div>
                                <label className="mb-1.5 block text-xs font-semibold text-slate-700">
                                    Giảng viên (nếu có)
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ví dụ: ThS. Nguyễn Văn A"
                                    value={data.ten_giang_vien}
                                    onChange={(e) => setData('ten_giang_vien', e.target.value)}
                                    className="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 shadow-2xs transition-colors placeholder:text-slate-400 focus:border-[#005BAA] focus:outline-none focus:ring-1 focus:ring-[#005BAA]"
                                />
                                {errors.ten_giang_vien && <p className="mt-1 text-xs text-rose-500">{errors.ten_giang_vien}</p>}
                            </div>

                            <div>
                                <label className="mb-1.5 block text-xs font-semibold text-slate-700">
                                    Phân loại tài liệu <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={data.phan_loai}
                                    onChange={(e) => setData('phan_loai', e.target.value)}
                                    className="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 shadow-2xs transition-colors focus:border-[#005BAA] focus:outline-none focus:ring-1 focus:ring-[#005BAA]"
                                >
                                    <option value="DE_THI">Đề thi / Kiểm tra</option>
                                    <option value="BAI_GIANG">Slide / Bài giảng</option>
                                    <option value="DO_AN">Đồ án / Source code</option>
                                    <option value="BAI_TAP">Bài tập / Lời giải</option>
                                    <option value="KHAC">Tài liệu tham khảo</option>
                                </select>
                                {errors.phan_loai && <p className="mt-1 text-xs text-rose-500">{errors.phan_loai}</p>}
                            </div>
                        </div>

                        {/* Mô tả chi tiết */}
                        <div>
                            <label className="mb-1.5 block text-xs font-semibold text-slate-700">
                                Mô tả cụ thể yêu cầu
                            </label>
                            <textarea
                                rows={3}
                                placeholder="Mô tả kỹ nội dung tài liệu, phạm vi kiến thức hoặc định dạng file mong muốn..."
                                value={data.mo_ta}
                                onChange={(e) => setData('mo_ta', e.target.value)}
                                className="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 shadow-2xs transition-colors placeholder:text-slate-400 focus:border-[#005BAA] focus:outline-none focus:ring-1 focus:ring-[#005BAA]"
                            />
                        </div>

                        {/* Điểm treo thưởng Bounty */}
                        <div className="space-y-3 rounded-xl border border-amber-200/80 bg-gradient-to-br from-amber-50/70 via-white to-slate-50 p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <label className="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                    <Coins size={15} className="text-amber-600" />
                                    <span>Điểm treo thưởng (Bounty Points)</span>
                                    <span className="text-rose-500">*</span>
                                </label>
                                <span className="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs text-amber-800">
                                    Ví khả dụng: <strong className="font-mono font-bold">{availablePoints}</strong> pts
                                </span>
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <div className="relative">
                                    <input
                                        type="number"
                                        min="10"
                                        max={Math.max(10, availablePoints)}
                                        step="5"
                                        placeholder="Tối thiểu 10"
                                        value={data.diem_thuong}
                                        onChange={(e) => {
                                            const val = e.target.value;
                                            if (val === '') {
                                                setData('diem_thuong', '');
                                            } else {
                                                const parsed = parseInt(val, 10);
                                                setData('diem_thuong', isNaN(parsed) ? '' : parsed);
                                            }
                                        }}
                                        className="w-32 rounded-xl border border-slate-300 bg-white py-2 pl-3.5 pr-9 font-mono text-sm font-bold text-slate-900 shadow-2xs focus:border-[#005BAA] focus:outline-none focus:ring-1 focus:ring-[#005BAA]"
                                    />
                                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400">
                                        pts
                                    </span>
                                </div>

                                {/* Quick Presets */}
                                <div className="flex flex-wrap items-center gap-1.5">
                                    {[20, 30, 50, 100].map((preset) => (
                                        <button
                                            key={preset}
                                            type="button"
                                            onClick={() => setData('diem_thuong', preset)}
                                            className={`cursor-pointer rounded-lg border px-3 py-1.5 font-mono text-xs font-semibold transition-all ${
                                                data.diem_thuong === preset
                                                    ? 'border-[#005BAA] bg-[#005BAA] text-white shadow-xs'
                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-blue-50/50 hover:text-[#005BAA]'
                                            }`}
                                        >
                                            +{preset}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <p className="flex items-start gap-1.5 text-[11px] leading-relaxed text-slate-500">
                                <ShieldCheck size={14} className="mt-0.5 shrink-0 text-emerald-600" />
                                <span>
                                    Điểm thưởng sẽ được hệ thống <strong>tạm giữ ký quỹ</strong> an toàn và chỉ chuyển giao khi bạn xác nhận duyệt bài đóng góp.
                                </span>
                            </p>

                            {/* Bảng minh họa biến động số dư ví điểm (Academic Ledger) */}
                            <div className="grid grid-cols-3 gap-2 rounded-xl border border-slate-200/80 bg-white p-3 text-center shadow-2xs">
                                <div>
                                    <span className="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Số dư hiện tại</span>
                                    <span className="mt-0.5 block font-mono text-xs font-bold tabular-nums text-slate-800">{availablePoints} pts</span>
                                </div>
                                <div className="border-x border-slate-100">
                                    <span className="block text-[10px] font-semibold uppercase tracking-wider text-amber-600">Tạm giữ ký quỹ</span>
                                    <span className="mt-0.5 block font-mono text-xs font-bold tabular-nums text-amber-700">-{Number(data.diem_thuong) || 0} pts</span>
                                </div>
                                <div>
                                    <span className="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Dư còn lại</span>
                                    <span className={`mt-0.5 block font-mono text-xs font-bold tabular-nums ${(availablePoints - (Number(data.diem_thuong) || 0) < 0) ? 'text-rose-600' : 'text-emerald-700'}`}>
                                        {availablePoints - (Number(data.diem_thuong) || 0)} pts
                                    </span>
                                </div>
                            </div>

                            {data.diem_thuong !== '' && Number(data.diem_thuong) < 10 && (
                                <p className="mt-2 flex items-center gap-1 text-xs text-rose-600">
                                    <AlertCircle className="h-3.5 w-3.5 shrink-0" />
                                    Điểm treo thưởng tối thiểu là 10 điểm.
                                </p>
                            )}
                            {data.diem_thuong !== '' && Number(data.diem_thuong) > availablePoints && (
                                <p className="mt-2 flex items-center gap-1 text-xs text-rose-600">
                                    <AlertCircle className="h-3.5 w-3.5 shrink-0" />
                                    Số dư khả dụng không đủ ({availablePoints} điểm). Hãy đóng góp tài liệu để nhận thêm điểm thưởng.
                                </p>
                            )}
                            {errors.diem_thuong && <p className="mt-1 text-xs text-rose-600">{errors.diem_thuong}</p>}
                        </div>
                    </div>

                    {/* Footer Actions */}
                    <div className="flex flex-col-reverse gap-2.5 border-t border-slate-200/80 bg-slate-50/80 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
                        <button
                            type="button"
                            onClick={onClose}
                            className="w-full cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 sm:w-auto"
                        >
                            Hủy bỏ
                        </button>
                        <button
                            type="submit"
                            disabled={
                                processing ||
                                !data.diem_thuong ||
                                Number(data.diem_thuong) < 10 ||
                                Number(data.diem_thuong) > availablePoints ||
                                !(defaultCourse ? defaultCourse.ma_mon_hoc : data.ma_mon_hoc) ||
                                !data.tieu_de?.trim()
                            }
                            className="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-[#005BAA] px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 sm:w-auto"
                        >
                            <Send size={15} />
                            <span>{processing ? 'Đang tạo yêu cầu...' : 'Đăng yêu cầu'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
