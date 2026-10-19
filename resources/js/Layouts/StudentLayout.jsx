import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    BookOpen,
    FolderKanban,
    Upload,
    Search,
    Menu,
    X,
    Bell,
    Shield,
    ChevronDown,
    GraduationCap,
    HeartHandshake,
    FileText,
    LogOut,
    User
} from 'lucide-react';

export default function StudentLayout({ children, title = 'Cổng Học Tập Sinh Viên' }) {
    const { auth, faculties = [], flash = {} } = usePage().props;
    const user = auth?.user;

    const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false);
    const [expandedFaculty, setExpandedFaculty] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);

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
        <div className="min-h-screen bg-surface-page text-text-primary font-sans flex flex-col">
            {/* ========================================== */}
            {/* 1. TOPBAR / HEADER CỐ ĐỊNH                   */}
            {/* ========================================== */}
            <header className="sticky top-0 z-30 h-16 bg-surface-default border-b border-border-default shadow-xs px-4 lg:px-6 flex items-center justify-between gap-4">
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
                        <div className="w-10 h-10 rounded-xl bg-brand-primary text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            AGU
                        </div>
                        <div className="hidden sm:block">
                            <h1 className="font-bold text-brand-primary leading-tight text-base">Academic Hub</h1>
                            <p className="text-[11px] text-text-secondary">Đại học An Giang</p>
                        </div>
                    </Link>
                </div>

                {/* Global Search Bar (Mã học phần / Tên môn học) */}
                <form onSubmit={handleGlobalSearch} className="flex-1 max-w-md hidden md:block">
                    <div className="relative">
                        <Search size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Tìm theo mã HP (COS101) hoặc tên môn học..."
                            className="w-full pl-9 pr-4 py-2 text-sm bg-surface-page border border-border-default rounded-md focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all placeholder:text-text-secondary"
                        />
                    </div>
                </form>

                {/* Right Actions & User Stats */}
                <div className="flex items-center gap-3">
                    {/* CTA Upload Button */}
                    <Link
                        href={route('documents.create')}
                        className="flex items-center gap-2 bg-brand-primary hover:bg-blue-800 text-white font-medium text-sm px-3.5 py-2 rounded-md shadow-xs active:scale-95 transition-all"
                    >
                        <Upload size={16} />
                        <span className="hidden sm:inline">Đăng tài liệu</span>
                    </Link>

                    {/* Điểm Uy Tín (Reputation Badge) */}
                    {user && (
                        <div
                            className="hidden lg:flex items-center gap-1.5 bg-brand-secondary/10 border border-brand-secondary/20 text-brand-secondary px-2.5 py-1 rounded-full text-xs font-semibold"
                            title="Điểm uy tín tích lũy từ đóng góp học thuật"
                        >
                            <Shield size={14} />
                            <span>{user.diem_uy_tin || 100} pts</span>
                        </div>
                    )}

                    {/* Notifications */}
                    <button
                        type="button"
                        className="p-2 text-text-secondary hover:text-brand-primary hover:bg-surface-page rounded-full relative transition-colors"
                        aria-label="Thông báo"
                    >
                        <Bell size={20} />
                        <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-semantic-danger rounded-full ring-2 ring-white"></span>
                    </button>

                    {/* User Profile Menu */}
                    {user ? (
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setIsUserMenuOpen(!isUserMenuOpen)}
                                className="flex items-center gap-2 p-1 rounded-md hover:bg-surface-page focus:outline-none"
                            >
                                <div className="w-8 h-8 rounded-full bg-brand-primary/10 text-brand-primary font-bold flex items-center justify-center text-sm border border-brand-primary/20">
                                    {user.ho_ten ? user.ho_ten.charAt(0).toUpperCase() : 'U'}
                                </div>
                                <ChevronDown size={14} className="text-text-secondary hidden sm:block" />
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

            {/* ========================================== */}
            {/* 2. BODY CONTAINER (SIDEBAR + MAIN CONTENT)  */}
            {/* ========================================== */}
            <div className="flex-1 flex overflow-hidden">
                {/* Backdrop Overlay cho Mobile Drawer */}
                {isMobileSidebarOpen && (
                    <div
                        className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-30 lg:hidden"
                        onClick={() => setIsMobileSidebarOpen(false)}
                    />
                )}

                {/* 🔹 SIDEBAR TRÁI (260px cố định trên Desktop) */}
                <aside
                    className={`fixed lg:static top-16 left-0 z-40 h-[calc(100vh-64px)] w-[260px] bg-surface-default border-r border-border-default flex flex-col transition-transform duration-200 ease-in-out ${isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                        }`}
                >
                    {/* Navigation Menu Chính */}
                    <div className="p-4 border-b border-border-default">
                        <p className="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-2">
                            Menu Điều Hướng
                        </p>
                        <nav className="space-y-1">
                            <Link
                                href={route('courses.index')}
                                className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-text-primary hover:bg-brand-primary/5 hover:text-brand-primary rounded-md transition-colors"
                            >
                                <BookOpen size={18} className="text-brand-primary" />
                                <span>Kho Tài Liệu / Môn Học</span>
                            </Link>
                            <Link
                                //  href={route('document-requests.index')}
                                className="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-text-primary hover:bg-brand-secondary/10 hover:text-brand-secondary rounded-md transition-colors"
                            >
                                <HeartHandshake size={18} className="text-brand-secondary" />
                                <span>Wishlist & Săn Thưởng</span>
                            </Link>
                        </nav>
                    </div>

                    {/* Danh mục Khoa & Môn Học (Cuộn dọc) */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-3">
                        <div className="flex items-center justify-between mb-1">
                            <p className="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                                Khoa / Bộ Môn
                            </p>
                            <span className="text-[10px] bg-surface-page border border-border-default px-1.5 py-0.5 rounded text-text-secondary">
                                {faculties.length} Khoa
                            </span>
                        </div>

                        {faculties.length === 0 ? (
                            <div className="text-xs text-text-secondary py-3 text-center italic">
                                Đang tải danh mục Khoa...
                            </div>
                        ) : (
                            faculties.map((khoa) => (
                                <div key={khoa.ma_khoa} className="rounded-md border border-border-default/60 overflow-hidden">
                                    <button
                                        type="button"
                                        onClick={() => toggleFaculty(khoa.ma_khoa)}
                                        className="w-full flex items-center justify-between p-2.5 text-left text-xs font-semibold text-text-primary bg-surface-page hover:bg-border-default/40 transition-colors"
                                    >
                                        <div className="flex items-center gap-2 truncate">
                                            <FolderKanban size={15} className="text-brand-primary shrink-0" />
                                            <span className="truncate">{khoa.ten_khoa}</span>
                                        </div>
                                        <ChevronDown
                                            size={14}
                                            className={`text-text-secondary transition-transform duration-150 ${expandedFaculty === khoa.ma_khoa ? 'rotate-180' : ''
                                                }`}
                                        />
                                    </button>

                                    {/* Môn học thuộc Khoa */}
                                    {expandedFaculty === khoa.ma_khoa && (
                                        <div className="bg-surface-default p-2 space-y-1 border-t border-border-default/40">
                                            {khoa.mon_hocs && khoa.mon_hocs.length > 0 ? (
                                                khoa.mon_hocs.map((mon) => (
                                                    <Link
                                                        key={mon.ma_mon_hoc}
                                                        href={route('courses.show', mon.ma_hoc_phan)}
                                                        className="flex items-center justify-between p-1.5 rounded text-xs text-text-secondary hover:text-brand-primary hover:bg-brand-primary/5 transition-colors"
                                                    >
                                                        <span className="truncate max-w-[140px]">{mon.ten_mon_hoc}</span>
                                                        <span className="font-mono text-[10px] bg-surface-page border border-border-default px-1 rounded text-text-secondary">
                                                            {mon.ma_hoc_phan}
                                                        </span>
                                                    </Link>
                                                ))
                                            ) : (
                                                <p className="text-[11px] text-text-secondary italic px-1 py-1">
                                                    Chưa có môn học.
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))
                        )}
                    </div>

                    {/* Footer Sidebar (Thông tin hỗ trợ) */}
                    <div className="p-3 border-t border-border-default bg-surface-page text-[11px] text-text-secondary flex items-center justify-between">
                        <div className="flex items-center gap-1.5">
                            <GraduationCap size={16} className="text-brand-primary" />
                            <span>AGU Hub v1.0</span>
                        </div>
                        <span className="text-semantic-success font-semibold">● Online</span>
                    </div>
                </aside>

                {/* 🔹 MAIN CONTENT AREA (Responsive) */}
                <main className="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 bg-surface-page">
                    <div className="max-w-6xl mx-auto space-y-6">
                        {/* Global Flash Messages */}
                        {flash.success && (
                            <div className="p-4 bg-semantic-success/10 border border-semantic-success/30 text-semantic-success rounded-xl text-sm font-medium flex items-center justify-between">
                                <span>{flash.success}</span>
                            </div>
                        )}
                        {flash.error && (
                            <div className="p-4 bg-semantic-danger/10 border border-semantic-danger/30 text-semantic-danger rounded-xl text-sm font-medium flex items-center justify-between">
                                <span>{flash.error}</span>
                            </div>
                        )}

                        {/* Page Content Render */}
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}