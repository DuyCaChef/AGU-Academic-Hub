import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import CreateRequestModal from '@/Components/Wishlist/CreateRequestModal';
import {
    Coins,
    Plus,
    Search,
    Filter,
    CheckCircle2,
    Clock,
    MessageSquare,
    ChevronRight,
    AlertCircle,
    AlertTriangle,
    Check,
    RotateCcw
} from 'lucide-react';

export default function WishlistIndex({ requests, filters = {}, courses = [] }) {
    const { auth, flash } = usePage().props;
    const [isCreateOpen, setIsCreateOpen] = useState(false);

    // State bộ lọc
    const [filterState, setFilterState] = useState({
        trang_thai: filters.trang_thai || 'MO',
        ma_mon_hoc: filters.ma_mon_hoc || '',
        phan_loai: filters.phan_loai || filters.loai_tai_lieu || '',
        sap_xep: filters.sap_xep || 'moi_nhat',
    });

    const handleFilterChange = (key, value) => {
        const nextFilters = { ...filterState, [key]: value };
        setFilterState(nextFilters);
        router.get(route('document-requests.index'), nextFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const handleResetFilters = () => {
        const defaultFilters = {
            trang_thai: 'MO',
            ma_mon_hoc: '',
            phan_loai: '',
            sap_xep: 'moi_nhat',
        };
        setFilterState(defaultFilters);
        router.get(route('document-requests.index'), defaultFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const getCategoryBadge = (category) => {
        const map = {
            DE_THI: { label: 'Đề thi', bg: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
            BAI_GIANG: { label: 'Bài giảng', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
            DO_AN: { label: 'Đồ án', bg: 'bg-purple-50 text-purple-700 border-purple-200' },
            BAI_TAP: { label: 'Bài tập', bg: 'bg-amber-50 text-amber-700 border-amber-200' },
            GIAO_TRINH: { label: 'Giáo trình', bg: 'bg-blue-50 text-blue-700 border-blue-200' },
            KHAC: { label: 'Khác', bg: 'bg-slate-100 text-slate-700 border-slate-200' },
        };
        return map[category] || { label: 'Tài liệu', bg: 'bg-slate-100 text-slate-700 border-slate-200' };
    };

    const paginationLinks = requests?.links || [];

    return (
        <StudentLayout>
            <Head title="Săn Thưởng Tài Liệu - Wishlist" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-sm font-semibold text-emerald-800 flex items-center gap-3 shadow-xs">
                        <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-sm font-semibold text-rose-800 flex items-center gap-3 shadow-xs">
                        <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* Banner & Ví Điểm */}
                <div className="bg-gradient-to-r from-[#005baa] to-[#00a896] rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div className="inline-flex items-center gap-2 px-3 py-1 bg-white/15 backdrop-blur-md rounded-full text-xs font-semibold tracking-wide mb-3">
                            <Coins className="w-3.5 h-3.5 text-amber-300" />
                            Thị trường Yêu cầu & Treo thưởng AGU
                        </div>
                        <h1 className="text-2xl font-extrabold">Cộng Đồng Săn Thưởng Tài Liệu</h1>
                        <p className="text-sm text-blue-100 mt-1 max-w-xl">
                            Tìm kiếm tài liệu học tập còn thiếu hoặc hỗ trợ tài liệu cho bạn bè để nhận điểm thưởng Bounty và gia tăng điểm uy tín học thuật.
                        </p>
                    </div>

                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full md:w-auto">
                        {/* Thẻ hiển thị ví điểm */}
                        <div className="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/20 flex items-center gap-4">
                            <div>
                                <span className="block text-[11px] text-blue-100 uppercase tracking-wider font-semibold">Khả dụng</span>
                                <span className="text-xl font-black text-amber-300">
                                    {auth?.user?.vi_diem?.diem_kha_dung || 0}
                                </span>
                            </div>
                            <div className="h-8 w-px bg-white/20" />
                            <div>
                                <span className="block text-[11px] text-blue-100 uppercase tracking-wider font-semibold">Tạm giữ</span>
                                <span className="text-xl font-bold text-white/80">
                                    {auth?.user?.vi_diem?.diem_tam_giu || 0}
                                </span>
                            </div>
                        </div>

                        <button
                            onClick={() => setIsCreateOpen(true)}
                            className="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-sm rounded-xl shadow-md transition-all flex items-center justify-center gap-2 shrink-0 active:scale-95"
                        >
                            <Plus className="w-4 h-4 stroke-[3]" />
                            Tạo Yêu Cầu Mới
                        </button>
                    </div>
                </div>

                {/* Thanh Lọc & Tìm Kiếm */}
                <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-2xs grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    {/* Trạng thái */}
                    <div>
                        <select
                            value={filterState.trang_thai}
                            onChange={(e) => handleFilterChange('trang_thai', e.target.value)}
                            className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-slate-700 focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors cursor-pointer shadow-2xs"
                        >
                            <option value="MO">🟢 Đang mở tìm kiếm</option>
                            <option value="DA_HOAN_THANH">✅ Đã giải quyết</option>
                            <option value="TAT_CA">Tất cả trạng thái</option>
                        </select>
                    </div>

                    {/* Môn học */}
                    <div>
                        <select
                            value={filterState.ma_mon_hoc}
                            onChange={(e) => handleFilterChange('ma_mon_hoc', e.target.value)}
                            className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-slate-700 focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors cursor-pointer shadow-2xs"
                        >
                            <option value="">Tất cả môn học</option>
                            {courses.map((c) => (
                                <option key={c.ma_mon_hoc} value={c.ma_mon_hoc}>
                                    [{c.ma_hoc_phan}] {c.ten_mon_hoc}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Phân loại tài liệu */}
                    <div>
                        <select
                            value={filterState.phan_loai}
                            onChange={(e) => handleFilterChange('phan_loai', e.target.value)}
                            className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-slate-700 focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors cursor-pointer shadow-2xs"
                        >
                            <option value="">Tất cả phân loại</option>
                            <option value="DE_THI">Đề thi / Kiểm tra</option>
                            <option value="BAI_GIANG">Slide / Bài giảng</option>
                            <option value="DO_AN">Đồ án / Source code</option>
                            <option value="BAI_TAP">Bài tập / Lời giải</option>
                            <option value="KHAC">Khác</option>
                        </select>
                    </div>

                    {/* Sắp xếp */}
                    <div>
                        <select
                            value={filterState.sap_xep}
                            onChange={(e) => handleFilterChange('sap_xep', e.target.value)}
                            className="w-full text-xs font-medium rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-slate-700 focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-colors cursor-pointer shadow-2xs"
                        >
                            <option value="moi_nhat">Mới nhất trước</option>
                            <option value="diem_cao">Điểm thưởng cao nhất</option>
                        </select>
                    </div>
                </div>

                {/* Danh Sách Yêu Cầu (Grid) */}
                {requests?.data?.length > 0 ? (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            {requests.data.map((item) => {
                                const cat = getCategoryBadge(item.phan_loai || item.loai_tai_lieu);
                                const isClosed = item.trang_thai === 'DA_HOAN_THANH';
                                const responsesCount = item.dap_ungs_count ?? item.danh_sach_phan_hoi_count ?? 0;

                                return (
                                    <div
                                        key={item.ma_yeu_cau}
                                        className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs hover:shadow-md transition-all flex flex-col justify-between group"
                                    >
                                        <div>
                                            {/* Top Header Card */}
                                            <div className="flex items-center justify-between gap-2 mb-3">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <span className={`text-[11px] font-bold px-2.5 py-0.5 rounded-full border ${cat.bg}`}>
                                                        {cat.label}
                                                    </span>
                                                    {isClosed ? (
                                                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                                            <CheckCircle2 className="w-3 h-3" /> Đã chốt
                                                        </span>
                                                    ) : (
                                                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1">
                                                            <Clock className="w-3 h-3" /> Đang mở
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Bounty Points Badge */}
                                                <div className="flex items-center gap-1 px-2.5 py-0.5 bg-amber-50 text-amber-700 font-black text-xs sm:text-sm rounded-full border border-amber-200 shadow-2xs shrink-0">
                                                    <Coins className="w-3.5 h-3.5 text-amber-500 fill-amber-500" />
                                                    +{item.diem_thuong} Điểm
                                                </div>
                                            </div>

                                            {/* Tên môn học */}
                                            <div className="text-xs font-semibold text-[#005baa] flex items-center gap-1.5">
                                                <span className="font-mono bg-blue-50 px-1.5 py-0.5 rounded text-[11px]">
                                                    {item.mon_hoc?.ma_hoc_phan}
                                                </span>
                                                <span className="truncate">{item.mon_hoc?.ten_mon_hoc}</span>
                                            </div>

                                            {/* Tiêu đề */}
                                            <Link
                                                href={route('document-requests.show', item.ma_yeu_cau)}
                                                className="block mt-2"
                                            >
                                                <h3 className="text-base font-bold text-slate-800 line-clamp-2 group-hover:text-[#005baa] transition-colors">
                                                    {item.tieu_de}
                                                </h3>
                                            </Link>

                                            {/* Mô tả tóm tắt */}
                                            {item.mo_ta && (
                                                <p className="text-xs text-slate-500 mt-2 line-clamp-2">
                                                    {item.mo_ta}
                                                </p>
                                            )}
                                        </div>

                                        {/* Footer Card */}
                                        <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <div className="w-7 h-7 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600 overflow-hidden">
                                                    {item.nguoi_tao?.anh_dai_dien ? (
                                                        <img src={item.nguoi_tao.anh_dai_dien} alt="avatar" className="w-full h-full object-cover" />
                                                    ) : (
                                                        item.nguoi_tao?.ho_ten?.charAt(0) || 'U'
                                                    )}
                                                </div>
                                                <div className="text-[11px] text-slate-500 leading-tight">
                                                    <span className="font-semibold text-slate-700 block truncate max-w-[100px]">
                                                        {item.nguoi_tao?.ho_ten}
                                                    </span>
                                                    <span className="text-[10px]">{item.nguoi_tao?.mssv}</span>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-3">
                                                <div className="flex items-center gap-1 text-xs text-slate-500 font-medium">
                                                    <MessageSquare className="w-3.5 h-3.5" />
                                                    {responsesCount} nộp
                                                </div>

                                                <Link
                                                    href={route('document-requests.show', item.ma_yeu_cau)}
                                                    className="p-1.5 text-slate-400 hover:text-[#005baa] hover:bg-blue-50 rounded-lg transition-colors"
                                                >
                                                    <ChevronRight className="w-5 h-5" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        {/* Phân Trang (Pagination) */}
                        {paginationLinks.length > 3 && (
                            <div className="flex items-center justify-center gap-1 pt-4">
                                {paginationLinks.map((link, idx) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={idx}
                                                className="px-3 py-1.5 text-xs text-slate-400 rounded-lg border border-slate-200 bg-slate-50"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    }
                                    return (
                                        <Link
                                            key={idx}
                                            href={link.url}
                                            preserveScroll
                                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors ${
                                                link.active
                                                    ? 'bg-[#005baa] text-white border-[#005baa]'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center space-y-4">
                        <AlertCircle className="w-12 h-12 text-slate-300 mx-auto" />
                        <div>
                            <h3 className="text-base font-bold text-slate-700">Chưa có yêu cầu nào phù hợp</h3>
                            <p className="text-xs text-slate-500 mt-1">
                                Hãy thay đổi bộ lọc hoặc là người đầu tiên tạo yêu cầu tìm tài liệu!
                            </p>
                        </div>
                        <button
                            onClick={handleResetFilters}
                            className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-[#005baa] bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors"
                        >
                            <RotateCcw className="w-3.5 h-3.5" />
                            Đặt lại bộ lọc
                        </button>
                    </div>
                )}

                {/* Modal Tạo Yêu Cầu */}
                <CreateRequestModal
                    isOpen={isCreateOpen}
                    onClose={() => setIsCreateOpen(false)}
                    courses={courses}
                />
            </div>
        </StudentLayout>
    );
}