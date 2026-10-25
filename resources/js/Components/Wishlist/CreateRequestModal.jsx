import React from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { X, Coins, AlertCircle, BookOpen, User, Tag, HelpCircle } from 'lucide-react';

export default function CreateRequestModal({ isOpen, onClose, courses = [] }) {
    const { auth } = usePage().props;
    const availablePoints = auth?.user?.vi_diem?.diem_kha_dung || 0;

    const { data, setData, post, processing, errors, reset } = useForm({
        ma_mon_hoc: '',
        tieu_de: '',
        mo_ta: '',
        ten_giang_vien: '',
        phan_loai: 'DE_THI',
        diem_thuong: 30,
    });

    if (!isOpen) return null;

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('document-requests.store'), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div className="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 transition-all">
                {/* Header */}
                <div className="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <div className="p-2 bg-amber-50 text-amber-600 rounded-xl">
                            <Coins className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-lg font-bold text-slate-800">Tạo Yêu Cầu Tìm Tài Liệu</h3>
                            <p className="text-xs text-slate-500">Treo thưởng Bounty để cộng đồng hỗ trợ nhanh nhất</p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="mt-4 space-y-4">
                    {/* Chọn môn học */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Môn học <span className="text-rose-500">*</span>
                        </label>
                        <select
                            value={data.ma_mon_hoc}
                            onChange={(e) => setData('ma_mon_hoc', e.target.value)}
                            className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors shadow-2xs cursor-pointer"
                        >
                            <option value="">-- Chọn môn học --</option>
                            {courses.map((course) => (
                                <option key={course.ma_mon_hoc} value={course.ma_mon_hoc}>
                                    [{course.ma_hoc_phan}] {course.ten_mon_hoc}
                                </option>
                            ))}
                        </select>
                        {errors.ma_mon_hoc && <p className="text-rose-500 text-xs mt-1">{errors.ma_mon_hoc}</p>}
                    </div>

                    {/* Tiêu đề yêu cầu */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Tiêu đề yêu cầu <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            placeholder="VD: Cần tìm đề thi cuối kỳ môn Lập trình Web năm 2024"
                            value={data.tieu_de}
                            onChange={(e) => setData('tieu_de', e.target.value)}
                            className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 cursor-text caret-[#005baa] focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors shadow-2xs"
                        />
                        {errors.tieu_de && <p className="text-rose-500 text-xs mt-1">{errors.tieu_de}</p>}
                    </div>

                    {/* Giảng viên & Phân loại */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Giảng viên (nếu có)</label>
                            <input
                                type="text"
                                placeholder="VD: ThS. Nguyễn Văn A"
                                value={data.ten_giang_vien}
                                onChange={(e) => setData('ten_giang_vien', e.target.value)}
                                className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 cursor-text caret-[#005baa] focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors shadow-2xs"
                            />
                            {errors.ten_giang_vien && <p className="text-rose-500 text-xs mt-1">{errors.ten_giang_vien}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Phân loại <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={data.phan_loai}
                                onChange={(e) => setData('phan_loai', e.target.value)}
                                className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors shadow-2xs cursor-pointer"
                            >
                                <option value="DE_THI">Đề thi / Kiểm tra</option>
                                <option value="BAI_GIANG">Slide / Bài giảng</option>
                                <option value="DO_AN">Đồ án / Source code</option>
                                <option value="BAI_TAP">Bài tập / Lời giải</option>
                                <option value="KHAC">Khác</option>
                            </select>
                            {errors.phan_loai && <p className="text-rose-500 text-xs mt-1">{errors.phan_loai}</p>}
                        </div>
                    </div>

                    {/* Mô tả chi tiết */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Mô tả cụ thể yêu cầu</label>
                        <textarea
                            rows={3}
                            placeholder="Mô tả kỹ đề bài, định dạng file cần tìm hoặc phạm vi kiến thức..."
                            value={data.mo_ta}
                            onChange={(e) => setData('mo_ta', e.target.value)}
                            className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 cursor-text caret-[#005baa] focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors shadow-2xs"
                        />
                    </div>

                    {/* Điểm treo thưởng Bounty */}
                    <div className="p-4 bg-amber-50/60 rounded-xl border border-amber-200/60">
                        <div className="flex items-center justify-between mb-2">
                            <label className="text-xs font-bold text-amber-900 uppercase flex items-center gap-1.5">
                                Điểm treo thưởng (Bounty Points) <span className="text-rose-500">*</span>
                            </label>
                            <span className="text-xs font-semibold text-slate-600">
                                Ví khả dụng: <strong className="text-amber-600">{availablePoints}</strong> điểm
                            </span>
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
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
                                className="w-32 text-base font-bold text-amber-700 rounded-lg border border-amber-300 bg-white px-3.5 py-2 cursor-text caret-amber-600 focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-2xs"
                            />

                            {/* Quick Presets */}
                            <div className="flex items-center gap-1.5 flex-wrap">
                                {[20, 30, 50, 100].map((preset) => (
                                    <button
                                        key={preset}
                                        type="button"
                                        onClick={() => setData('diem_thuong', preset)}
                                        className={`px-2.5 py-1 text-xs font-bold rounded-lg border transition-all ${
                                            data.diem_thuong === preset
                                                ? 'bg-amber-500 text-slate-950 border-amber-500 shadow-2xs'
                                                : 'bg-white text-amber-800 border-amber-300 hover:bg-amber-100/60'
                                        }`}
                                    >
                                        +{preset}
                                    </button>
                                ))}
                            </div>

                            <p className="text-xs text-slate-500 w-full sm:w-auto">
                                Điểm này sẽ bị <strong>tạm giữ</strong> ngay khi tạo bài và chỉ chuyển giao khi bạn bấm duyệt.
                            </p>
                        </div>

                        {data.diem_thuong !== '' && Number(data.diem_thuong) < 10 && (
                            <p className="text-rose-500 text-xs mt-2 flex items-center gap-1">
                                <AlertCircle className="w-4 h-4 shrink-0" />
                                Điểm treo thưởng tối thiểu là 10 điểm.
                            </p>
                        )}
                        {data.diem_thuong !== '' && Number(data.diem_thuong) > availablePoints && (
                            <p className="text-rose-500 text-xs mt-2 flex items-center gap-1">
                                <AlertCircle className="w-4 h-4 shrink-0" />
                                Số dư khả dụng không đủ ({availablePoints} điểm). Hãy tích lũy thêm điểm bằng cách đóng góp tài liệu.
                            </p>
                        )}
                        {errors.diem_thuong && <p className="text-rose-500 text-xs mt-1">{errors.diem_thuong}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
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
                                !data.ma_mon_hoc ||
                                !data.tieu_de?.trim()
                            }
                            className="px-5 py-2 text-sm font-bold text-white bg-[#005baa] hover:bg-blue-800 disabled:bg-slate-300 rounded-xl shadow-xs transition-colors flex items-center gap-2"
                        >
                            {processing ? 'Đang đóng băng điểm...' : 'Đăng Yêu Cầu'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}