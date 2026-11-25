import React, { useEffect, useRef, useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import NotificationDropdown from '@/Components/NotificationDropdown';
import {
    Upload,
    Search,
    Menu,
    X,
    Mail,
    Bell,
    ShieldCheck,
    Shield,
    ChevronDown,
    LogOut,
    User,
    Coins,
    GraduationCap,
    Hash,
    ArrowRight,
    Award,
} from 'lucide-react';


export default function StudentHeader({
    onMenuClick,
    isMobileSidebarOpen: externalIsMobileSidebarOpen,
}) {


    // ham xử lý hiển thị role
    const roleLabels = {
        SINH_VIEN: "Sinh viên",
        CUU_SINH_VIEN: "Cựu sinh viên",
        GIANG_VIEN: "Giảng viên",
        KIEM_DUYET_VIEN: "Kiểm duyệt viên",
        QUAN_TRI: "Quản trị viên",
        QUAN_TRI_VIEN: "Quản trị viên",
    };

    const getRoleLabel = (role) => {
        return roleLabels[role] ?? role;
    };

    const [localIsMobileSidebarOpen, setLocalIsMobileSidebarOpen] = useState(false);
    const isSidebarOpen = externalIsMobileSidebarOpen !== undefined ? externalIsMobileSidebarOpen : localIsMobileSidebarOpen;

    const handleToggleMobileSidebar = () => {
        if (onMenuClick) {
            onMenuClick();
        } else {
            setLocalIsMobileSidebarOpen((prev) => !prev);
        }
    };
    const [searchQuery, setSearchQuery] = useState('');
    const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
    const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);
    const { auth } = usePage().props;
    const user = auth?.user;
    const isStaff = Boolean(
        user?.la_quan_tri ||
        user?.la_kiem_duyet ||
        user?.vai_tro === "QUAN_TRI_VIEN" ||
        user?.vai_tro === "KIEM_DUYET_VIEN"
    );
    // Xử lý tìm kiếm toàn cục (Global Search)
    const handleGlobalSearch = (e) => {
        e.preventDefault();
        if (!searchQuery.trim()) return;
        router.get(route('courses.index'), { search: searchQuery }, { preserveState: true });
    };
    const [isHeaderVisible, setIsHeaderVisible] = useState(true);
    const lastScrollY = useRef(0);

    useEffect(() => {
        const handleScroll = () => {
            const currentScrollY = window.scrollY;
            const previousScrollY = lastScrollY.current;
            const scrollDifference = currentScrollY - previousScrollY;

            // Luôn hiện header khi ở gần đầu trang
            if (currentScrollY < 80) {
                setIsHeaderVisible(true);
            } else if (scrollDifference > 5) {
                // Cuộn xuống: ẩn header
                setIsHeaderVisible(false);
            } else if (scrollDifference < -5) {
                // Cuộn lên: hiện header
                setIsHeaderVisible(true);
            }

            lastScrollY.current = currentScrollY;
        };

        window.addEventListener("scroll", handleScroll, { passive: true });

        return () => {
            window.removeEventListener("scroll", handleScroll);
        };
    }, []);
    useEffect(() => {
        if (!isUserMenuOpen) return;

        const handlePointerDown = (event) => {
            const menu = document.getElementById("user-profile-menu");

            if (menu && !menu.contains(event.target)) {
                setIsUserMenuOpen(false);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === "Escape") {
                setIsUserMenuOpen(false);
            }
        };

        document.addEventListener("pointerdown", handlePointerDown);
        document.addEventListener("keydown", handleKeyDown);

        return () => {
            document.removeEventListener("pointerdown", handlePointerDown);
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [isUserMenuOpen]);
    useEffect(() => {
        if (!isProfileModalOpen) return;

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";

        const handleKeyDown = (event) => {
            if (event.key === "Escape") {
                setIsProfileModalOpen(false);
            }
        };

        document.addEventListener("keydown", handleKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [isProfileModalOpen]);

    // xu ly logout
    const handleLogout = () => {
        setIsUserMenuOpen(false);

        router.post(route('logout'), {}, {
            onStart: () => {
                console.log('Đang đăng xuất...');
            },
            onError: (errors) => {
                console.error('Đăng xuất thất bại:', errors);
            },
        });
    };



    return (
        <div className="bg-surface-page text-text-primary font-sans flex flex-col">
            {/* hearder cố định */}
            <header className={`
            fixed top-0 left-0 right-0 z-100 h-16 bg-[#005BAA] 
            shadow-sm px-2 sm:px-4 lg:px-6 flex items-center justify-between gap-1.5 sm:gap-3 lg:gap-4
            transition-transform duration-300 ease-in-out
            ${isHeaderVisible ? 'translate-y-0' : '-translate-y-full'}
            `}>
                {/* Brand Logo & Mobile Menu Button */}
                <div className="flex items-center gap-1.5 sm:gap-3 shrink-0">
                    <button
                        type="button"
                        onClick={handleToggleMobileSidebar}
                        className="lg:hidden p-1.5 sm:p-2 text-white hover:text-[#FFC72C] hover:bg-white/10 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-white/50"
                        aria-label="Toggle Navigation Drawer"
                    >
                        {isSidebarOpen ? <X size={20} className="sm:w-[22px] sm:h-[22px]" /> : <Menu size={20} className="sm:w-[22px] sm:h-[22px]" />}
                    </button>

                    <Link
                        href={user ? route('dashboard') : route('home')}
                        className="flex items-center gap-2 sm:gap-2.5 shrink-0 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/50 rounded-lg p-0.5"
                    >
                        <div className="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center shrink-0">
                            <ApplicationLogo className="w-full h-full group-hover:scale-105 transition-transform" />
                        </div>
                        <div className="hidden sm:block">
                            <h1 className="font-bold text-[#FFC72C] leading-tight text-base group-hover:text-amber-200 transition-colors">Academic Hub</h1>
                            <p className="font-bold text-[11px] text-white">ĐHQG-HCM - Trường Đại học An Giang</p>
                        </div>
                    </Link>
                </div>

                {/* Global Search Bar (Mã học phần / Tên môn học) - Giữ nguyên trên mobile và thu nhỏ gọn gàng */}
                <form onSubmit={handleGlobalSearch} className="flex-1 min-w-0 max-w-3xl mx-1 sm:mx-2 text-gray-800">
                    <div className="relative flex items-center">
                        <Search className="absolute left-2.5 sm:left-3 top-1/2 -translate-y-1/2 text-gray-400 w-3.5 h-3.5 sm:w-4 sm:h-4 pointer-events-none shrink-0" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Tìm môn học, mã HP..."
                            className="w-full pl-7 sm:pl-9 pr-2 sm:pr-4 py-1 sm:py-2 text-xs sm:text-sm bg-surface-page border border-gray-300 rounded-full focus:outline-none focus:border-[#FFC72C] focus:ring-1 focus:ring-[#FFC72C] transition-all placeholder:text-gray-400"
                        />
                    </div>
                </form>

                {/* thông tin bên phải header */}
                <div className="flex items-center gap-1.5 sm:gap-2.5 lg:gap-3 shrink-0">
                    {/* Nút hành động nhanh: Quản trị (nếu là Admin/Mod) hoặc Săn thưởng (nếu là Sinh viên) */}
                    {isStaff ? (
                        <Link
                            href={route('admin.dashboard')}
                            className="flex items-center gap-1 sm:gap-1.5 bg-[#FFC72C] hover:bg-amber-300 text-slate-950 font-bold text-xs sm:text-sm px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-full shadow-xs active:scale-95 transition-all shrink-0"
                            title="Vào Bảng điều khiển Quản trị hệ thống"
                        >
                            <ShieldCheck size={15} className="text-slate-950" />
                            <span className="hidden sm:inline">Trang Quản trị</span>
                        </Link>
                    ) : (
                        <Link
                            href={typeof route === 'function' && route().has('document-requests.index') ? route('document-requests.index') : '/yeu-cau-tai-lieu'}
                            className="flex items-center gap-1 sm:gap-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs sm:text-sm px-2 py-1.5 sm:px-3.5 sm:py-2 rounded-full shadow-xs active:scale-95 transition-all shrink-0"
                            title="Thị trường yêu cầu & săn thưởng Bounty"
                        >
                            <Coins size={14} className="sm:w-[15px] sm:h-[15px]" />
                            <span className="hidden sm:inline">Săn thưởng</span>
                        </Link>
                    )}

                    {/* Điểm Uy Tín hoặc Huy hiệu Quản trị */}
                    {user && (
                        <div
                            className={`hidden lg:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 border ${
                                isStaff
                                    ? 'bg-blue-900/80 text-white border-blue-600'
                                    : 'bg-green-700 text-white border-green-600'
                            }`}
                            title={isStaff ? 'Tài khoản Quản trị hệ thống' : 'Điểm uy tín tích lũy từ đóng góp học thuật'}
                        >
                            <Shield size={14} className={isStaff ? 'text-amber-300' : 'text-emerald-300'} />
                            <span>{isStaff ? (user.cap_quan_tri === 'TOI_CAO' ? 'Super Admin' : 'Quản trị viên') : `${user.diem_uy_tin || 100} pts`}</span>
                        </div>
                    )}

                    {/* Notifications */}
                    <NotificationDropdown variant="student" />

                    {/* User Profile Menu */}
                    {user ? (
                        <div className="relative" id="user-profile-menu">
                            {/* Avt va nut mo menu */}
                            <button
                                type="button"
                                onClick={() => setIsUserMenuOpen((prev) => !prev)}
                                aria-expanded={isUserMenuOpen}
                                aria-haspopup="menu"
                                aria-label="Mở profile"
                                className="flex items-center gap-1 sm:gap-2 rounded-full bg-blue-800 p-0.5 sm:p-1 hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-white/50">
                                <div className="flex w-7 h-7 sm:w-8 sm:h-8 items-center justify-center rounded-full border border-brand-primary/20 bg-blue-600 text-xs sm:text-sm font-bold text-white">
                                    {user.ho_ten ? user.ho_ten.charAt(0).toUpperCase() : 'U'}
                                </div>
                                <ChevronDown size={14} className={`mr-1 hidden text-white transition-transform duration-200 sm:block ${isUserMenuOpen ? "rotate-180" : ""}`} />
                            </button>

                            {/* Dropdown Profile */}
                            {isUserMenuOpen && (
                                <div
                                    role="menu"
                                    className='absolute right-0 top-full z-50 mt-2 w-64 overflow-hidden rounded-xl border 
                                    border-border-default bg-surface-default py-2 shadow-xl
                                    '
                                >
                                    {/* thong tin tai khoan */}
                                    <div className="px-4 py-3 border-b border-border-default">
                                        <p className="font-semibold text-sm truncate">{user.ho_ten || "Người dùng"} </p>
                                        <p className="text-xs text-text-secondary truncate">{user.email || "Chưa có email"}</p>
                                        {user.vai_tro && (
                                            <span className="inline-block mt-1 text-[11px] px-2 py-0.5 bg-blue-50 text-[#005BAA] rounded-md font-medium border border-blue-100">
                                                {getRoleLabel(user.vai_tro)}
                                            </span>
                                        )}
                                    </div>

                                    {/* Bảng điều khiển Quản trị (nếu là Admin/Moderator) */}
                                    {isStaff && (
                                        <Link
                                            href={route('admin.dashboard')}
                                            role="menuitem"
                                            onClick={() => setIsUserMenuOpen(false)}
                                            className="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-[#005baa] bg-blue-50/70 hover:bg-blue-100/70 transition-colors border-b border-border-default/60"
                                        >
                                            <ShieldCheck size={16} className="text-[#005baa]" /> Bảng điều khiển Quản trị
                                        </Link>
                                    )}

                                    {/* Ho so ca nhan */}
                                    <button
                                        type="button"
                                        role='menuitem'
                                        onClick={() => {
                                            setIsUserMenuOpen(false);
                                            setIsProfileModalOpen(true);
                                        }}
                                        className='flex w-full items-center gap-3 px-4 py-2.5
                                        text-left text-sm text-text-secondary 
                                        transition-colors hover:bg-surface-page 
                                        hover:text-brand-primary
                                        '
                                    >
                                        <User size={16} /> Xem hồ sơ nhanh
                                    </button>

                                    {/* Chỉnh sửa hồ sơ & Bảo mật */}
                                    <Link
                                        href={route('profile.edit')}
                                        role='menuitem'
                                        onClick={() => setIsUserMenuOpen(false)}
                                        className='flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-text-secondary transition-colors hover:bg-surface-page hover:text-brand-primary'
                                    >
                                        <ShieldCheck size={16} className="text-emerald-600" /> Thiết lập tài khoản
                                    </Link>

                                    {/* Săn thưởng tài liệu hoặc Duyệt Bounty */}
                                    {isStaff ? (
                                        <Link
                                            href={route('admin.bounties.payout-queue')}
                                            role='menuitem'
                                            onClick={() => setIsUserMenuOpen(false)}
                                            className='flex w-full items-center justify-between px-4 py-2.5 text-left text-sm text-text-secondary transition-colors hover:bg-surface-page hover:text-brand-primary'
                                        >
                                            <span className="flex items-center gap-3">
                                                <Coins size={16} className="text-amber-500" /> Duyệt giải ngân Bounty
                                            </span>
                                            <span className="text-[10px] font-bold text-[#005baa] bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">
                                                Admin
                                            </span>
                                        </Link>
                                    ) : (
                                        <Link
                                            href={typeof route === 'function' && route().has('document-requests.index') ? route('document-requests.index') : '/yeu-cau-tai-lieu'}
                                            role='menuitem'
                                            onClick={() => setIsUserMenuOpen(false)}
                                            className='flex w-full items-center justify-between px-4 py-2.5 text-left text-sm text-text-secondary transition-colors hover:bg-surface-page hover:text-brand-primary'
                                        >
                                            <span className="flex items-center gap-3">
                                                <Coins size={16} className="text-amber-500" /> Săn thưởng Bounty
                                            </span>
                                            {user.vi_diem && (
                                                <span className="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                                                    {user.vi_diem.diem_kha_dung || 0} pts
                                                </span>
                                            )}
                                        </Link>
                                    )}

                                    {/* Đăng xuất */}
                                    <div className='border-t border-border-default pt-2'>
                                        <button
                                            type="button"
                                            role='menuitem'
                                            onClick={handleLogout}
                                            className="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-semantic-danger hover:bg-semantic-danger/5"
                                        >
                                            <LogOut size={16} /> Đăng xuất
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Link
                            href={route('login')}
                            className="text-sm font-medium text-white rounded-lg px-3 py-2 hover:bg-white/10 transition:colors"
                        >
                            Đăng nhập
                        </Link>
                    )}
                </div>
            </header>
            {/* profile modal */}
            {isProfileModalOpen && user && (
                <div
                    className="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-slate-900/60 p-4 backdrop-blur-xs"
                    onMouseDown={(event) => {
                        if (event.target === event.currentTarget) {
                            setIsProfileModalOpen(false);
                        }
                    }}
                >
                    <section
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="profile-title"
                        className="my-auto w-full max-w-md overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xl"
                    >
                        {/* Header Banner đồng bộ tông AGU */}
                        <div className="relative overflow-hidden bg-gradient-to-br from-[#005baa] via-[#004f96] to-blue-900 px-6 pt-5 pb-6 text-white">
                            <div className="pointer-events-none absolute -right-8 -bottom-10 h-36 w-36 rounded-full bg-white/10 blur-2xl" />
                            <div className="pointer-events-none absolute -left-10 -top-10 h-28 w-28 rounded-full bg-[#FFC72C]/15 blur-2xl" />

                            <div className="relative z-10 flex items-center justify-between">
                                <span className="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-blue-100/90">
                                    <User size={13} className="text-[#FFC72C]" />
                                    Hồ sơ cá nhân · AGU Hub
                                </span>
                                <button
                                    type="button"
                                    onClick={() => setIsProfileModalOpen(false)}
                                    aria-label="Đóng modal"
                                    className="rounded-xl p-1.5 text-white/80 transition-colors hover:bg-white/15 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/40"
                                >
                                    <X size={18} />
                                </button>
                            </div>

                            <div className="relative z-10 mt-4 flex items-center gap-4">
                                <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/25 bg-white/15 text-xl font-bold text-white shadow-inner">
                                    {user.ho_ten?.charAt(0)?.toUpperCase() || 'U'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <h2
                                        id="profile-title"
                                        className="truncate text-lg font-bold leading-snug text-white"
                                    >
                                        {user.ho_ten || 'Chưa cập nhật'}
                                    </h2>
                                    <p className="mt-0.5 flex items-center gap-1.5 truncate text-xs text-blue-100/90">
                                        <Mail size={12} className="shrink-0 opacity-80" />
                                        <span className="truncate">{user.email || 'Chưa cập nhật'}</span>
                                    </p>
                                    {user.vai_tro && (
                                        <span className="mt-2 inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/15 px-2.5 py-0.5 text-[11px] font-semibold text-white">
                                            <Shield size={11} className="text-[#FFC72C]" />
                                            {getRoleLabel(user.vai_tro)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Nội dung hồ sơ */}
                        <div className="space-y-4 p-6">
                            {/* Chỉ số nhanh */}
                            {!isStaff ? (
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="rounded-xl border border-emerald-200/70 bg-emerald-50/70 p-3.5">
                                        <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-emerald-700">
                                            <Award size={13} />
                                            <span>Điểm uy tín</span>
                                        </div>
                                        <p className="mt-1 text-lg font-extrabold text-emerald-900">
                                            {user.diem_uy_tin ?? 100}
                                            <span className="ml-1 text-xs font-semibold text-emerald-700">điểm</span>
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-amber-200/70 bg-amber-50/70 p-3.5">
                                        <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-amber-700">
                                            <Coins size={13} />
                                            <span>Số dư ví điểm</span>
                                        </div>
                                        <p className="mt-1 text-lg font-extrabold text-amber-900">
                                            {user.vi_diem?.diem_kha_dung ?? 0}
                                            <span className="ml-1 text-xs font-semibold text-amber-700">pts</span>
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-xl border border-blue-200/70 bg-blue-50/60 p-3.5">
                                    <div className="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-[#005baa]">
                                        <ShieldCheck size={13} />
                                        <span>Cấp quyền quản trị</span>
                                    </div>
                                    <p className="mt-1 text-sm font-bold text-slate-900">
                                        {user.ten_cap_quan_tri || user.ten_vai_tro || 'Quản trị viên Hệ thống'}
                                    </p>
                                </div>
                            )}

                            {/* Chi tiết định danh học thuật */}
                            <div className="divide-y divide-slate-200/70 rounded-xl border border-slate-200/80 bg-slate-50/70">
                                {!isStaff && (
                                    <div className="flex items-center justify-between gap-4 px-4 py-3">
                                        <span className="flex items-center gap-2 text-xs font-medium text-slate-500">
                                            <Hash size={14} className="text-slate-400" />
                                            Mã số sinh viên
                                        </span>
                                        <span className="font-mono text-sm font-bold text-[#005baa]">
                                            {user.mssv || 'Chưa cập nhật'}
                                        </span>
                                    </div>
                                )}

                                <div className="flex items-center justify-between gap-4 px-4 py-3">
                                    <span className="flex shrink-0 items-center gap-2 text-xs font-medium text-slate-500">
                                        <GraduationCap size={14} className="text-slate-400" />
                                        Khoa / Đơn vị
                                    </span>
                                    <span
                                        className="truncate text-right text-sm font-semibold text-slate-800"
                                        title={user.khoa?.ten_khoa}
                                    >
                                        {user.khoa?.ten_khoa || 'Chưa cập nhật khoa'}
                                    </span>
                                </div>

                                {user.vai_tro === 'CUU_SINH_VIEN' && user.nam_tot_nghiep && (
                                    <div className="flex items-center justify-between gap-4 px-4 py-3">
                                        <span className="text-xs font-medium text-slate-500">
                                            Năm tốt nghiệp
                                        </span>
                                        <span className="text-sm font-semibold text-slate-800">
                                            {user.nam_tot_nghiep}
                                        </span>
                                    </div>
                                )}

                                <div className="flex items-center justify-between gap-4 px-4 py-3">
                                    <span className="text-xs font-medium text-slate-500">
                                        Trạng thái tài khoản
                                    </span>
                                    <span
                                        className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                            user.dang_hoat_dong === false
                                                ? 'bg-amber-100/80 text-amber-800'
                                                : 'bg-emerald-100/80 text-emerald-700'
                                        }`}
                                    >
                                        <span
                                            className={`h-1.5 w-1.5 rounded-full ${
                                                user.dang_hoat_dong === false ? 'bg-amber-500' : 'bg-emerald-500'
                                            }`}
                                        />
                                        {user.ten_trang_thai || 'Đang hoạt động'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="flex flex-col-reverse gap-2.5 border-t border-slate-200/80 bg-slate-50/70 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <button
                                type="button"
                                onClick={() => setIsProfileModalOpen(false)}
                                className="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 sm:w-auto"
                            >
                                Đóng
                            </button>
                            <Link
                                href={route('profile.edit')}
                                onClick={() => setIsProfileModalOpen(false)}
                                className="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-[#005baa] px-4 py-2 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98] sm:w-auto"
                            >
                                <span>Chỉnh sửa hồ sơ & Bảo mật</span>
                                <ArrowRight size={15} />
                            </Link>
                        </div>
                    </section>
                </div>
            )}
        </div>
    );
}