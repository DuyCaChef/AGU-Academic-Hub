import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import CreateRequestModal from '@/Components/Wishlist/CreateRequestModal';
import Pagination from '@/Components/Common/Pagination';
import {
    Coins,
    Plus,
    Search,
    CheckCircle2,
    Clock,
    MessageSquare,
    AlertTriangle,
    RotateCcw,
    Bookmark,
    Sparkles,
    Send,
    Hourglass,
    ArrowRight,
    FileQuestion,
    ShieldCheck,
    Calendar,
    User,
} from 'lucide-react';

export default function WishlistIndex({ requests, filters = {}, courses = [] }) {
    const { auth, flash } = usePage().props;
    const [isCreateOpen, setIsCreateOpen] = useState(false);

    // State bộ lọc
    const [filterState, setFilterState] = useState({
        tab: filters.tab || 'all',
        q: filters.q || '',
        trang_thai: filters.trang_thai || (filters.tab && filters.tab !== 'all' ? 'TAT_CA' : 'MO'),
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

    const handleTabChange = (newTab) => {
        const nextFilters = {
            ...filterState,
            tab: newTab,
            trang_thai: newTab === 'all' ? 'MO' : 'TAT_CA',
        };
        setFilterState(nextFilters);
        router.get(route('document-requests.index'), nextFilters, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        router.get(route('document-requests.index'), filterState, {
            preserveState: true,
            replace: true,
        });
    };

    const handleResetFilters = () => {
        const defaultFilters = {
            tab: 'all',
            q: '',
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

    const getCategoryMeta = (category) => {
        const map = {
            DE_THI: { label: 'Đề thi', badge: 'bg-amber-50 text-amber-800 border-amber-200/80' },
            BAI_GIANG: { label: 'Slide bài giảng', badge: 'bg-blue-50 text-[#005BAA] border-blue-200/80' },
            DO_AN: { label: 'Đồ án & Mã nguồn', badge: 'bg-violet-50 text-violet-700 border-violet-200/80' },
            BAI_TAP: { label: 'Bài tập thực hành', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200/80' },
            GIAO_TRINH: { label: 'Giáo trình', badge: 'bg-cyan-50 text-cyan-700 border-cyan-200/80' },
            KHAC: { label: 'Tài liệu tham khảo', badge: 'bg-slate-100 text-slate-700 border-slate-200' },
        };
        return map[category] || { label: 'Tài liệu học tập', badge: 'bg-slate-100 text-slate-700 border-slate-200' };
    };

    const getAccentBar = (status) => {
        switch (status) {
            case 'DA_HOAN_THANH':
                return 'bg-gradient-to-r from-emerald-400 to-teal-500';
            case 'CHO_GIAI_NGAN':
                return 'bg-gradient-to-r from-[#005BAA] to-sky-500';
            case 'DA_HUY':
                return 'bg-slate-300';
            case 'MO':
            default:
                return 'bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500';
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'DA_HOAN_THANH':
                return (
                    <span className="text-[11px] font-medium px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/80 inline-flex items-center gap-1">
                        <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                        <span>Đã hoàn thành</span>
                    </span>
                );
            case 'CHO_GIAI_NGAN':
                return (
                    <span className="text-[11px] font-medium px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/80 inline-flex items-center gap-1">
                        <Hourglass className="w-3 h-3 text-blue-600" />
                        <span>Chờ giải ngân</span>
                    </span>
                );
            case 'DA_HUY':
                return (
                    <span className="text-[11px] font-medium px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 border border-slate-200 inline-flex items-center gap-1">
                        <span>Đã hủy</span>
                    </span>
                );
            case 'MO':
            default:
                return (
                    <span className="text-[11px] font-medium px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200/80 inline-flex items-center gap-1">
                        <Clock className="w-3 h-3 text-amber-600" />
                        <span>Đang tìm kiếm</span>
                    </span>
                );
        }
    };

    const paginationLinks = requests?.links || [];

    return (
        <StudentLayout title="Săn Thưởng Tài Liệu">
            <Head title="Săn Thưởng Tài Liệu - Bounty" />

            <div className="space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-medium text-emerald-800 flex items-center gap-3 shadow-xs">
                        <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs font-medium text-rose-800 flex items-center gap-3 shadow-xs">
                        <AlertTriangle className="w-4 h-4 text-rose-600 shrink-0" />
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* 1. Hero Banner: AGU Gradient with Academic Ledger Split */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#005BAA] via-[#004e95] to-blue-900 text-white p-6 sm:p-7 shadow-sm">
                    {/* Subtle dot pattern & glow */}
                    <div className="pointer-events-none absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]" />
                    <div className="pointer-events-none absolute -right-10 -bottom-10 h-48 w-48 rounded-full bg-[#FFC72C]/15 blur-2xl" />

                    <div className="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div className="space-y-2 max-w-xl">
                            <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-blue-100 backdrop-blur-xs border border-white/15">
                                <Coins className="w-3.5 h-3.5 text-[#FFC72C]" />
                                <span>Thị trường Yêu cầu & Săn thưởng AGU</span>
                            </div>
                            <h1 className="text-2xl font-extrabold tracking-tight sm:text-3xl text-white">
                                Săn Thưởng Tài Liệu Học Thuật
                            </h1>
                            <p className="text-xs sm:text-sm leading-relaxed text-blue-100/90">
                                Treo thưởng điểm ví để tìm tài liệu còn thiếu, hoặc đóng góp tài liệu để nhận Bounty và gia tăng điểm uy tín học thuật cùng cộng đồng AGU.
                            </p>
                        </div>

                        <div className="flex flex-col sm:flex-row sm:items-center gap-3.5 shrink-0">
                            {/* Sổ cái ví điểm (Academic Ledger Box) */}
                            <div className="rounded-xl border border-white/20 bg-white/10 p-3.5 backdrop-blur-xs flex items-center gap-4 text-white">
                                <div className="space-y-0.5">
                                    <span className="block text-[11px] font-medium text-blue-200">
                                        Khả dụng
                                    </span>
                                    <span className="font-mono text-xl font-bold text-[#FFC72C] tabular-nums">
                                        {auth?.user?.vi_diem?.diem_kha_dung || 0} pts
                                    </span>
                                </div>
                                <div className="h-8 w-px bg-white/20" />
                                <div className="space-y-0.5">
                                    <span className="block text-[11px] font-medium text-blue-200">
                                        Đang ký quỹ
                                    </span>
                                    <span className="font-mono text-xl font-bold text-white/95 tabular-nums">
                                        {auth?.user?.vi_diem?.diem_tam_giu || 0} pts
                                    </span>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => setIsCreateOpen(true)}
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#FFC72C] hover:bg-amber-300 text-slate-950 px-5 py-3.5 text-xs font-bold shadow-sm transition-all active:scale-[0.98] cursor-pointer"
                            >
                                <Plus size={16} />
                                <span>Tạo yêu cầu mới</span>
                            </button>
                        </div>
                    </div>
                </div>

                {/* 2. Tabs Phân nhóm (Segmented Navigation) */}
                <div className="inline-flex items-center gap-1 rounded-xl border border-slate-200/80 bg-white p-1.5 shadow-2xs overflow-x-auto max-w-full">
                    <button
                        type="button"
                        onClick={() => handleTabChange('all')}
                        className={`inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold transition-all cursor-pointer shrink-0 ${
                            filterState.tab === 'all'
                                ? 'bg-[#005BAA] text-white shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        }`}
                    >
                        <Sparkles size={14} />
                        <span>Khám phá tất cả</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => handleTabChange('my_requests')}
                        className={`inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold transition-all cursor-pointer shrink-0 ${
                            filterState.tab === 'my_requests'
                                ? 'bg-[#005BAA] text-white shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        }`}
                    >
                        <Bookmark size={14} />
                        <span>Yêu cầu tôi đã tạo</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => handleTabChange('my_contributions')}
                        className={`inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-semibold transition-all cursor-pointer shrink-0 ${
                            filterState.tab === 'my_contributions'
                                ? 'bg-[#005BAA] text-white shadow-xs'
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        }`}
                    >
                        <Send size={14} />
                        <span>Bài tôi đã đóng góp</span>
                    </button>
                </div>

                {/* 3. Thanh Tìm Kiếm & Bộ Lọc */}
                <div className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs space-y-3">
                    {/* Search bar */}
                    <form onSubmit={handleSearchSubmit} className="relative flex items-center">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3.5 pointer-events-none" />
                        <input
                            type="text"
                            placeholder="Tìm kiếm theo tiêu đề tài liệu, mã môn hoặc tên môn học..."
                            value={filterState.q}
                            onChange={(e) => setFilterState({ ...filterState, q: e.target.value })}
                            className="h-10 w-full text-xs font-medium rounded-xl border border-slate-200 bg-slate-50/60 focus:bg-white pl-10 pr-24 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-all"
                        />
                        <button
                            type="submit"
                            className="absolute right-1.5 px-3.5 py-1.5 bg-[#005BAA] hover:bg-blue-700 text-white font-semibold text-xs rounded-lg transition-colors cursor-pointer"
                        >
                            Tìm kiếm
                        </button>
                    </form>

                    {/* Bộ lọc Dropdowns */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                        <select
                            value={filterState.trang_thai}
                            onChange={(e) => handleFilterChange('trang_thai', e.target.value)}
                            className="h-9 w-full text-xs font-medium rounded-xl border border-slate-200 bg-white px-3 text-slate-700 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors cursor-pointer"
                        >
                            <option value="TAT_CA">Tất cả trạng thái</option>
                            <option value="MO">Đang mở tìm kiếm</option>
                            <option value="CHO_GIAI_NGAN">Chờ duyệt giải ngân</option>
                            <option value="DA_HOAN_THANH">Đã hoàn thành</option>
                            <option value="DA_HUY">Đã hủy</option>
                        </select>

                        <select
                            value={filterState.ma_mon_hoc}
                            onChange={(e) => handleFilterChange('ma_mon_hoc', e.target.value)}
                            className="h-9 w-full text-xs font-medium rounded-xl border border-slate-200 bg-white px-3 text-slate-700 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors cursor-pointer"
                        >
                            <option value="">Tất cả môn học</option>
                            {courses.map((c) => (
                                <option key={c.ma_mon_hoc} value={c.ma_mon_hoc}>
                                    [{c.ma_hoc_phan}] {c.ten_mon_hoc}
                                </option>
                            ))}
                        </select>

                        <select
                            value={filterState.phan_loai}
                            onChange={(e) => handleFilterChange('phan_loai', e.target.value)}
                            className="h-9 w-full text-xs font-medium rounded-xl border border-slate-200 bg-white px-3 text-slate-700 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors cursor-pointer"
                        >
                            <option value="">Tất cả phân loại</option>
                            <option value="DE_THI">Đề thi / Kiểm tra</option>
                            <option value="BAI_GIANG">Slide / Bài giảng</option>
                            <option value="DO_AN">Đồ án / Source code</option>
                            <option value="BAI_TAP">Bài tập / Lời giải</option>
                            <option value="KHAC">Tài liệu tham khảo</option>
                        </select>

                        <select
                            value={filterState.sap_xep}
                            onChange={(e) => handleFilterChange('sap_xep', e.target.value)}
                            className="h-9 w-full text-xs font-medium rounded-xl border border-slate-200 bg-white px-3 text-slate-700 focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors cursor-pointer"
                        >
                            <option value="moi_nhat">Mới nhất trước</option>
                            <option value="diem_cao">Điểm thưởng cao nhất</option>
                        </select>
                    </div>
                </div>

                {/* 4. Danh Sách Yêu Cầu (Bounty Cards Grid) */}
                {requests?.data?.length > 0 ? (
                    <div className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {requests.data.map((item) => {
                                const cat = getCategoryMeta(item.phan_loai || item.loai_tai_lieu);
                                const responsesCount = item.dap_ungs_count ?? item.danh_sach_phan_hoi_count ?? 0;
                                const createdDate = item.created_at
                                    ? new Date(item.created_at).toLocaleDateString('vi-VN')
                                    : 'Mới đăng';

                                return (
                                    <article
                                        key={item.ma_yeu_cau}
                                        className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-300/80 hover:shadow-md"
                                    >
                                        {/* Thanh nhấn màu theo trạng thái Bounty */}
                                        <div className={`h-1 w-full ${getAccentBar(item.trang_thai)}`} />

                                        <div className="flex-1 space-y-3 p-5">
                                            {/* Top Header Card */}
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <span className={`text-[11px] font-semibold px-2.5 py-0.5 rounded-full border ${cat.badge}`}>
                                                        {cat.label}
                                                    </span>
                                                    {getStatusBadge(item.trang_thai)}
                                                </div>

                                                {/* Bounty Points Badge */}
                                                <div className="flex items-center gap-1 px-2.5 py-1 bg-gradient-to-r from-amber-50 to-orange-50 text-amber-900 font-mono text-xs font-bold rounded-xl border border-amber-300/80 shadow-2xs shrink-0">
                                                    <Coins className="w-3.5 h-3.5 text-amber-600" />
                                                    <span>+{item.diem_thuong} pts</span>
                                                </div>
                                            </div>

                                            {/* Tên môn học */}
                                            <div className="text-xs font-medium text-[#005BAA] flex items-center gap-1.5">
                                                <span className="font-mono font-bold bg-blue-50 px-2 py-0.5 rounded-md text-[11px] border border-blue-100 shrink-0">
                                                    {item.mon_hoc?.ma_hoc_phan || 'AGU'}
                                                </span>
                                                <span className="truncate text-slate-700 font-semibold">
                                                    {item.mon_hoc?.ten_mon_hoc || 'Môn học chung'}
                                                </span>
                                            </div>

                                            {/* Tiêu đề yêu cầu */}
                                            <Link
                                                href={route('document-requests.show', item.ma_yeu_cau)}
                                                className="block"
                                            >
                                                <h3 className="text-[15px] font-bold text-slate-900 group-hover:text-[#005BAA] transition-colors line-clamp-2 min-h-[2.6rem] leading-snug">
                                                    {item.tieu_de}
                                                </h3>
                                            </Link>

                                            {/* Mô tả tóm tắt */}
                                            {item.mo_ta && (
                                                <p className="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                                    {item.mo_ta}
                                                </p>
                                            )}
                                        </div>

                                        {/* Footer Card */}
                                        <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/70 px-5 py-3 text-xs text-slate-500">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <div className="w-7 h-7 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center text-[11px] font-bold text-[#005BAA] shrink-0">
                                                    {item.nguoi_tao?.ho_ten?.charAt(0)?.toUpperCase() || 'U'}
                                                </div>
                                                <div className="truncate leading-tight">
                                                    <span className="font-semibold text-slate-700 block truncate max-w-[120px]">
                                                        {item.nguoi_tao?.ho_ten || 'Thành viên AGU'}
                                                    </span>
                                                    <span className="text-[10px] text-slate-400">
                                                        {createdDate}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2 shrink-0">
                                                <span className="inline-flex items-center gap-1 rounded-lg border border-slate-200/80 bg-white px-2 py-1 text-[11px] font-medium text-slate-600">
                                                    <MessageSquare size={12} className="text-slate-400" />
                                                    <span>{responsesCount} nộp</span>
                                                </span>

                                                <Link
                                                    href={route('document-requests.show', item.ma_yeu_cau)}
                                                    className="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white hover:border-[#005BAA] hover:bg-[#005BAA] hover:text-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition-all"
                                                    title="Xem chi tiết & nộp tài liệu"
                                                >
                                                    <span>Chi tiết</span>
                                                    <ArrowRight size={13} />
                                                </Link>
                                            </div>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>

                        {/* Phân Trang (Pagination) */}
                        {paginationLinks.length > 3 && (
                            <Pagination links={paginationLinks} className="border-t border-slate-100 pt-4" />
                        )}
                    </div>
                ) : (
                    <div className="rounded-xl border border-dashed border-slate-200 bg-white p-10 text-center space-y-3">
                        <FileQuestion className="w-10 h-10 text-slate-400 mx-auto" />
                        <div>
                            <h3 className="text-sm font-semibold text-slate-800">
                                Chưa có yêu cầu nào phù hợp
                            </h3>
                            <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                {filterState.tab === 'my_requests'
                                    ? 'Bạn chưa đăng yêu cầu tìm tài liệu nào.'
                                    : filterState.tab === 'my_contributions'
                                    ? 'Bạn chưa tham gia đóng góp tài liệu cho yêu cầu nào.'
                                    : 'Hãy thay đổi tiêu chí bộ lọc hoặc tạo yêu cầu mới để nhận hỗ trợ từ các bạn sinh viên AGU.'}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={handleResetFilters}
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium text-[#005BAA] bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors cursor-pointer"
                        >
                            <RotateCcw className="w-3.5 h-3.5" />
                            <span>Đặt lại bộ lọc</span>
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
