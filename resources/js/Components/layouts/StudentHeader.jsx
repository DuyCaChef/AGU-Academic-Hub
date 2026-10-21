import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    Upload,
    Search,
    Menu,
    X,
    Bell,
    Shield,
    ChevronDown,
    LogOut,
    User
} from 'lucide-react';

export default function StudentHeader({
}) {
    const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false);
    const [expandedFaculty, setExpandedFaculty] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
    const { auth } = usePage().props;
    const user = auth?.user;
    // Xử lý tìm kiếm toàn cục (Global Search)
    const handleGlobalSearch = (e) => {
        e.preventDefault();
        if (!searchQuery.trim()) return;
        router.get(route('courses.index'), { search: searchQuery }, { preserveState: true });
    };

    const toggleFaculty = (maKhoa) => {
        setExpandedFaculty(expandedFaculty === maKhoa ? null : maKhoa);
    };

    return (
        <div className="bg-surface-page text-text-primary font-sans flex flex-col">
            {/* hearder cố định */}
            <header className="sticky top-0 z-30 h-16 bg-[#005BAA] border-b border-border-default shadow-sm px-4 lg:px-6 flex items-center justify-between gap-4">
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
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setIsUserMenuOpen(!isUserMenuOpen)}
                                className="flex bg-blue-800 items-center gap-2 p-1 rounded-full hover:bg-blue-900 focus:outline-none"
                            >
                                <div className="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm border border-brand-primary/20">
                                    {user.ho_ten ? user.ho_ten.charAt(0).toUpperCase() : 'U'}
                                </div>
                                <ChevronDown size={14} className="text-white hidden sm:block" />
                            </button>

                            {/* Dropdown Profile */}
                            {isUserMenuOpen && (
                                <div className="absolute right-0 mt-2 w-56 bg-surface-default rounded-xl shadow-lg border border-border-default py-2 z-50">
                                    <div className="px-4 py-2 border-b border-border-default">
                                        <p className="font-semibold text-sm truncate">{user.ho_ten}</p>
                                        <p className="text-xs text-text-secondary truncate">{user.email}</p>
                                        <span className="inline-block mt-1 text-[10px] font-mono px-2 py-0.5 bg-brand-primary/10 text-brand-primary rounded-md uppercase font-semibold">
                                            {user.vai_tro}
                                        </span>
                                    </div>
                                    <Link
                                        href={route('auth.me')}
                                        className="flex items-center gap-2 px-4 py-2 text-sm text-text-secondary hover:bg-surface-page hover:text-brand-primary"
                                    >
                                        <User size={16} /> Hồ sơ cá nhân
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => router.post(route('auth.logout'))}
                                        className="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-semantic-danger hover:bg-semantic-danger/5"
                                    >
                                        <LogOut size={16} /> Đăng xuất
                                    </button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <Link
                            href={route('auth.login')}
                            className="text-sm font-medium text-brand-primary hover:underline"
                        >
                            Đăng nhập
                        </Link>
                    )}
                </div>
            </header>
        </div>
    );
}