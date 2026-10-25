import React, { useEffect, useRef, useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
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
} from 'lucide-react';


export default function StudentHeader({
}) {


    // ham xử lý hiển thị role
    const roleLabels = {
        SINH_VIEN: "Sinh viên",
        GIANG_VIEN: "Giảng viên",
        QUAN_TRI: "Quản trị viên",
    };

    const getRoleLabel = (role) => {
        return roleLabels[role] ?? role;
    };

    const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
    const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);
    const { auth } = usePage().props;
    const user = auth?.user;
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
            fixed top-0 left-0 right-0 z-99999 h-16 bg-[#005BAA] 
            shadow-sm px-4 lg:px-6 flex items-center justify-between gap-4
            transition-transform duration-300 ease-in-out
            ${isHeaderVisible ? 'translate-y-0' : '-translate-y-full'}
            `}>
                {/* Brand Logo & Mobile Menu Button */}
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => setIsMobileSidebarOpen(!isMobileSidebarOpen)}
                        className="lg:hidden p-2 text-text-secondary hover:text-brand-primary rounded-md focus:outline-none focus:ring-2 focus:ring-brand-primary"
                        aria-label="Toggle Navigation Drawer"
                    >
                        {isMobileSidebarOpen ? <X size={22} /> : <Menu size={22} />}
                    </button>

                    <Link href={route('courses.index')} className="flex items-center gap-2.5">
                        <div className="w-10 h-10 flex items-center justify-center shrink-0">
                            <img
                                src="/storage/imgs/logo-agu.webp"
                                alt="Logo Trường Đại học An Giang"
                                className="w-full h-full object-contain"
                            />
                        </div>
                        <div className="hidden sm:block">
                            <h1 className="font-bold text-[#FFC72C] leading-tight text-base">Academic Hub</h1>
                            <p className="font-bold text-[11px] text-white">ĐHQG-HCM - Trường Đại học An Giang</p>
                        </div>
                    </Link>
                </div>

                {/* Global Search Bar (Mã học phần / Tên môn học) */}
                <form onSubmit={handleGlobalSearch} className=" flex-1 text-gray-800 max-w-3xl hidden md:block">
                    <div className="relative">
                        <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Tìm theo mã HP (COS101) hoặc tên môn học..."
                            className="w-full pl-9 pr-4 py-2 text-sm bg-surface-page border border-gray-300 rounded-full focus:outline-none focus:border-[#00ff00] focus:ring-1 focus:ring-[#00ff00] transition-all placeholder:text-gray-500"
                        />
                    </div>
                </form>

                {/* thông tin bên phải header */}
                <div className="flex items-center gap-3">
                    {/* nút đăng tải tài liệu */}
                    <Link
                        href={route('documents.create')}
                        className="flex items-center gap-2 bg-blue-800 hover:bg-blue-900 text-white font-medium text-sm px-3.5 py-2 rounded-full shadow-xs active:scale-95 transition-all"
                    >
                        <Upload size={16} />
                        <span className="hidden sm:inline">Đăng tài liệu</span>
                    </Link>

                    {/* nút Săn thưởng tài liệu */}
                    <Link
                        href={typeof route === 'function' && route().has('document-requests.index') ? route('document-requests.index') : '/yeu-cau-tai-lieu'}
                        className="flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs sm:text-sm px-3.5 py-2 rounded-full shadow-xs active:scale-95 transition-all"
                        title="Thị trường yêu cầu & săn thưởng Bounty"
                    >
                        <Coins size={15} />
                        <span className="hidden sm:inline">Săn thưởng</span>
                    </Link>

                    {/* Điểm Uy Tín (Reputation Badge) */}
                    {user && (
                        <div
                            className="hidden lg:flex items-center gap-1.5 bg-green-700 text-white px-2.5 py-1 rounded-full text-xs font-semibold"
                            title="Điểm uy tín tích lũy từ đóng góp học thuật"
                        >
                            <Shield size={14} />
                            <span>{user.diem_uy_tin || 100} pts</span>
                        </div>
                    )}

                    {/* Notifications */}
                    <button
                        type="button"
                        className="p-2 text-white hover:text-brand-primary hover:bg-surface-page rounded-full relative transition-colors"
                        aria-label="Thông báo"
                    >
                        <Bell size={20} />
                        <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-green-700 rounded-full ring-2 ring-white"></span>
                    </button>

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
                                className="flex items-center gap-2 rounded-full bg-blue-800 p-1 hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-white/50">
                                <div className="flex w-8 h-8 items-center justify-center rounded-full border border-brand-primary/20 bg-blue-600 text-sm font-bold text-white">
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
                                            <span className="inline-block mt-1 text-[10px] font-mono px-2 py-0.5 bg-brand-primary/10 text-brand-primary rounded-md uppercase font-semibold">
                                                {getRoleLabel(user.vai_tro)}
                                            </span>
                                        )}
                                    </div>

                                    {/* Ho so ca nhan */}
                                    <button
                                        type="button"
                                        role='menuitem'
                                        onClick={() => {
                                            setIsUserMenuOpen(false);
                                            setIsProfileModalOpen(true);
                                        }}
                                        className='flex w-full items-center gap-3 px-4 py-3
                                        text-left text-sm text-text-secondary 
                                        transition-colors hover:bg-surface-page 
                                        hover:text-brand-primary
                                        '
                                    >
                                        <User size={16} /> Hồ sơ cá nhân
                                    </button>

                                    {/* Săn thưởng tài liệu */}
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
                            href={route('auth.login')}
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
                    className='fixed inset-0 z-[100] flex items-center justify-center
                            overflow-y-auto bg-black/50 p-4 backdrop-blur-sm
                            '
                    onMouseDown={(Event) => {
                        if (Event.target === Event.currentTarget) {
                            setIsProfileModalOpen(false)
                        }
                    }}
                >
                    <section
                        role='dialog'
                        aria-modal="true"
                        aria-labelledby='profile-title'
                        className='my-auto max-w-lg rounded-2xl border border-border-default bg-surface-default shadow-2xl'
                    >
                        {/* header modal */}
                        <div className='flex items-center justify-between border-b border-border-default px-6 py-4'>
                            <div>
                                <h2 id='profile-title' className='text-lg font-bold'>Hồ sơ cá nhân</h2>
                                <p className='mt-1 text-sm text-text-secondary'> Thông tin tài khoản AGU Hub</p>
                            </div>
                            <button
                                type='button'
                                onClick={() => setIsProfileModalOpen(false)}
                                aria-label='Đóng modal'
                                className='rounded-lg p-2 text-text-secondary transition-colors
                                    hover:bg-surface-page hover:text-text-primary'
                            >
                                <X size={20} />
                            </button>
                        </div>
                        {/* noi dung ho so */}
                        <div className='space-y-5 p-6'>
                            <div className='flex items-center gap-4'>
                                <div className='flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-800 text-xl font-bold text-white'>
                                    {user.ho_ten?.charAt(0)?.toUpperCase() || 'U'}
                                </div>
                                <div className='min-w-0'>
                                    <p className='truncate text-base font-semibold text-text-primary'>{user.ho_ten || 'Chưa cập nhật'}</p>
                                    <p className='break-all text-sm text-text-secondary'> {user.email || 'Chưa cập nhật'}</p>
                                </div>
                            </div>
                            <div className='space-y-3'>
                                <div className='rounded-lg bg-surface-page p-3'>
                                    <p className='text-xs text-text-secondary'>
                                        Họ và Tên
                                    </p>
                                    <p className='mt-1 text-sm font-medium'>
                                        {user.ho_ten || 'Chưa cập nhật'}
                                    </p>
                                </div>
                                <div className='rounded-lg bg-surface-page p-3'>
                                    <p className='text-xs text-text-secondary'>
                                        Mã số sinh viên
                                    </p>
                                    <p className='mt-1 text-sm font-medium'>
                                        {user.mssv || 'Chưa cập nhật'}
                                    </p>
                                </div>
                                <div className="rounded-lg bg-surface-page p-3">
                                    <p className="text-xs text-text-secondary">
                                        Vai trò
                                    </p>
                                    <p className="mt-1 text-sm font-medium">
                                        {getRoleLabel(user.vai_tro)}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div className="flex justify-end border-t border-border-default
                px-6 py-4">
                            <button
                                type="button"
                                onClick={() => setIsProfileModalOpen(false)}
                                className="rounded-lg bg-brand-primary px-4 py-2
                        text-sm font-medium text-white hover:opacity-90"
                            >
                                Đóng
                            </button>
                        </div>
                    </section>
                </div>
            )}
        </div>
    );
}