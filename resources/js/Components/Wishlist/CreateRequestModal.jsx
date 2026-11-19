import React, { useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { X, Coins, AlertCircle, Lock } from 'lucide-react';

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
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div className="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200/80 transition-all">
                {/* Header */}
                <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div className="flex items-center gap-2.5">
                        <div className="p-2 bg-amber-50 text-amber-700 rounded-lg border border-amber-200/60">
                            <Coins className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-semibold text-slate-900">Tạo Yêu Cầu Tìm Tài Liệu</h3>
                            <p className="text-xs text-slate-500">Treo thưởng Bounty để nhận hỗ trợ từ cộng đồng sinh viên AGU</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                        aria-label="Đóng"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="mt-4 space-y-4">
                    {/* Chọn môn học (Tự động khóa nếu có defaultCourse) */}
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">
                            Môn học <span className="text-rose-500">*</span>
                        </label>
                        {defaultCourse ? (
                            <div className="p-3 bg-blue-50/70 border border-blue-200/80 rounded-lg flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <span className="font-mono text-xs font-bold text-[#005BAA] bg-white px-2 py-0.5 rounded border border-blue-200">
                                        {defaultCourse.ma_hoc_phan || defaultCourse.course_code}
                                    </span>
                                    <span className="text-xs font-semibold text-slate-800 truncate max-w-[240px]">
                                        {defaultCourse.ten_mon_hoc || defaultCourse.name}
                                    </span>
                                </div>
                                <span className="inline-flex items-center gap-1 text-[11px] text-[#005BAA] font-medium bg-blue-100/70 px-2 py-0.5 rounded-full border border-blue-200/60 shrink-0">
                                    <Lock size={11} />
                                    <span>Đã khóa môn này</span>
                                </span>
                            </div>
                        ) : (
                            <select
                                value={data.ma_mon_hoc}
                                onChange={(e) => setData('ma_mon_hoc', e.target.value)}
                                className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors cursor-pointer"
                            >
                                <option value="">-- Chọn môn học --</option>
                                {courses.map((course) => (
                                    <option key={course.ma_mon_hoc} value={course.ma_mon_hoc}>
                                        [{course.ma_hoc_phan}] {course.ten_mon_hoc}
                                    </option>
                                ))}
                            </select>
                        )}
                        {errors.ma_mon_hoc && <p className="text-rose-500 text-xs mt-1">{errors.ma_mon_hoc}</p>}
                    </div>

                    {/* Tiêu đề yêu cầu */}
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">
                            Tiêu đề yêu cầu <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Ví dụ: Cần tìm đề thi cuối kỳ môn Lập trình Web năm 2024"
                            value={data.tieu_de}
                            onChange={(e) => setData('tieu_de', e.target.value)}
                            className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors"
                        />
                        {errors.tieu_de && <p className="text-rose-500 text-xs mt-1">{errors.tieu_de}</p>}
                    </div>

                    {/* Giảng viên & Phân loại */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-slate-700 mb-1">
                                Giảng viên (nếu có)
                            </label>
                            <input
                                type="text"
                                placeholder="Ví dụ: ThS. Nguyễn Văn A"
                                value={data.ten_giang_vien}
                                onChange={(e) => setData('ten_giang_vien', e.target.value)}
                                className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors"
                            />
                            {errors.ten_giang_vien && <p className="text-rose-500 text-xs mt-1">{errors.ten_giang_vien}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-700 mb-1">
                                Phân loại tài liệu <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={data.phan_loai}
                                onChange={(e) => setData('phan_loai', e.target.value)}
                                className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors cursor-pointer"
                            >
                                <option value="DE_THI">Đề thi / Kiểm tra</option>
                                <option value="BAI_GIANG">Slide / Bài giảng</option>
                                <option value="DO_AN">Đồ án / Source code</option>
                                <option value="BAI_TAP">Bài tập / Lời giải</option>
                                <option value="KHAC">Tài liệu tham khảo</option>
                            </select>
                            {errors.phan_loai && <p className="text-rose-500 text-xs mt-1">{errors.phan_loai}</p>}
                        </div>
                    </div>

                    {/* Mô tả chi tiết */}
                    <div>
                        <label className="block text-xs font-medium text-slate-700 mb-1">
                            Mô tả cụ thể yêu cầu
                        </label>
                        <textarea
                            rows={3}
                            placeholder="Mô tả kỹ nội dung tài liệu, phạm vi kiến thức hoặc định dạng file mong muốn..."
                            value={data.mo_ta}
                            onChange={(e) => setData('mo_ta', e.target.value)}
                            className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors"
                        />
                    </div>

                    {/* Điểm treo thưởng Bounty */}
                    <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
                        <div className="flex items-center justify-between">
                            <label className="text-xs font-medium text-slate-800 flex items-center gap-1.5">
                                <Coins size={14} className="text-amber-600" />
                                <span>Điểm treo thưởng (Bounty Points)</span>
                                <span className="text-rose-500">*</span>
                            </label>
                            <span className="text-xs text-slate-500">
                                Ví khả dụng: <strong className="font-mono text-amber-700 font-semibold">{availablePoints}</strong> pts
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
                                    className="w-28 text-sm font-mono font-semibold text-slate-900 rounded-lg border border-slate-300 bg-white pl-3 pr-8 py-2 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA]"
                                />
                                <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-400 pointer-events-none">
                                    pts
                                </span>
                            </div>

                            {/* Quick Presets */}
                            <div className="flex items-center gap-1.5 flex-wrap">
                                {[20, 30, 50, 100].map((preset) => (
                                    <button
                                        key={preset}
                                        type="button"
                                        onClick={() => setData('diem_thuong', preset)}
                                        className={`px-2.5 py-1 text-xs font-mono font-medium rounded-md border transition-colors cursor-pointer ${
                                            data.diem_thuong === preset
                                                ? 'bg-[#005BAA] text-white border-[#005BAA]'
                                                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                                        }`}
                                    >
                                        +{preset}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <p className="text-[11px] text-slate-500">
                            Điểm thưởng sẽ được hệ thống <strong>tạm giữ ký quỹ</strong> an toàn và chỉ chuyển giao khi bạn xác nhận duyệt bài đóng góp.
                        </p>

                        {/* Bảng minh họa biến động số dư ví điểm (Academic Ledger) */}
                        <div className="grid grid-cols-3 gap-2 p-2.5 bg-white rounded-lg border border-slate-200 text-center">
                            <div>
                                <span className="block text-[10px] text-slate-500 font-medium">Số dư hiện tại</span>
                                <span className="font-mono text-xs font-semibold text-slate-800 tabular-nums">{availablePoints} pts</span>
                            </div>
                            <div className="border-x border-slate-100">
                                <span className="block text-[10px] text-amber-700 font-medium">Tạm giữ ký quỹ</span>
                                <span className="font-mono text-xs font-semibold text-amber-700 tabular-nums">-{Number(data.diem_thuong) || 0} pts</span>
                            </div>
                            <div>
                                <span className="block text-[10px] text-slate-500 font-medium">Dư còn lại</span>
                                <span className={`font-mono text-xs font-semibold tabular-nums ${(availablePoints - (Number(data.diem_thuong) || 0) < 0) ? 'text-rose-600' : 'text-emerald-700'}`}>
                                    {availablePoints - (Number(data.diem_thuong) || 0)} pts
                                </span>
                            </div>
                        </div>

                        {data.diem_thuong !== '' && Number(data.diem_thuong) < 10 && (
                            <p className="text-rose-600 text-xs mt-2 flex items-center gap-1">
                                <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                Điểm treo thưởng tối thiểu là 10 điểm.
                            </p>
                        )}
                        {data.diem_thuong !== '' && Number(data.diem_thuong) > availablePoints && (
                            <p className="text-rose-600 text-xs mt-2 flex items-center gap-1">
                                <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                Số dư khả dụng không đủ ({availablePoints} điểm). Hãy đóng góp tài liệu để nhận thêm điểm thưởng.
                            </p>
                        )}
                        {errors.diem_thuong && <p className="text-rose-600 text-xs mt-1">{errors.diem_thuong}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-3.5 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
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
                            className="px-4 py-2 text-xs font-medium text-white bg-[#005BAA] hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed rounded-lg shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            {processing ? 'Đang tạo yêu cầu...' : 'Đăng yêu cầu'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
