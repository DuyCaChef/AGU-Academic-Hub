import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ShieldCheck,
    Users,
    Flag,
    Trash2,
    GraduationCap,
    LogOut,
    Menu,
    X,
    Coins,
    CheckCircle2,
    AlertTriangle,
    BookOpen,
} from 'lucide-react';

/**
 * AdminLayout: Khung giao diện Bảng điều khiển Quản trị (Admin Panel / Moderation Hub).
 *
 * Yêu cầu đặc tả:
 * - Sidebar cố định 280px tông màu tối: w-[280px] bg-surface-admin (#0f172a).
 * - Sử dụng tokens AGU từ resources/css/app.css (@theme).
 * - Tích hợp phân quyền Role-Based UI theo auth.user (Admin / Moderator).
 * - Responsive: Sidebar chuyển thành Drawer vuốt mở linh hoạt trên Mobile (<1024px).
 * - Đồng bộ Icon Lucide React & route Ziggy an toàn.
 */
export default function AdminLayout({ children, title = 'Quản trị hệ thống' }) {
    const { auth, flash } = usePage().props;
    const user = auth?.user;
    const [mobileOpen, setMobileOpen] = useState(false);

    // Helper sinh URL Ziggy an toàn (có fallback phòng trường hợp BE chưa nạp đủ route)
    const getSafeRoute = (routeName, fallbackUrl) => {
        try {
            if (typeof route === 'function' && route().has(routeName)) {
                return route(routeName);
            }
        } catch (e) {
            // bỏ qua lỗi nếu Ziggy gặp trục trặc
        }
        return fallbackUrl;
    };

    // Kiểm tra menu item hiện tại có đang Active hay không
    const isItemActive = (routeName, fallbackUrl) => {
        try {
            if (typeof route === 'function' && route().has(routeName)) {
                return route().current(routeName);
            }
        } catch (e) {}
        if (typeof window !== 'undefined') {
            return window.location.pathname.startsWith(fallbackUrl);
        }
        return false;
    };

    // Danh sách các mục điều hướng trong Sidebar Quản trị
    const navItems = [
        {
            label: 'Hàng đợi duyệt KYC',
            routeName: 'admin.kyc.index',
            fallbackUrl: '/quan-tri/duyet-kyc',
            icon: ShieldCheck,
            desc: 'Xác thực thẻ SV / Cựu SV',
            adminOnly: false, // Cả Mod và Admin đều duyệt được
        },
        {
            label: 'Quản lý Người dùng',
            routeName: 'admin.users.index',
            fallbackUrl: '/quan-tri/nguoi-dung',
            icon: Users,
            desc: 'Danh sách và khóa tài khoản',
            adminOnly: true, // Dành riêng cho Quản trị viên
        },
        {
            label: 'Hàng đợi Báo cáo',
            routeName: 'admin.reports.index',
            fallbackUrl: '/quan-tri/bao-cao',
            icon: Flag,
            desc: 'Xử lý báo cáo vi phạm',
            adminOnly: false,
        },
        {
            label: 'Kiểm tra tài liệu',
            routeName: 'admin.documents.review-queue',
            fallbackUrl: '/quan-tri/tai-lieu/cho-kiem-tra',
            icon: AlertTriangle,
            desc: 'Tài liệu có nhiều báo cáo',
            adminOnly: false,
        },
        {
            label: 'Thùng rác Tài liệu',
            routeName: 'admin.documents.deleted',
            fallbackUrl: '/quan-tri/tai-lieu/da-xoa',
            icon: Trash2,
            desc: 'Khôi phục file đã xóa',
            adminOnly: true,
        },
        {
            label: 'Danh mục học thuật',
            routeName: 'admin.catalog.index',
            fallbackUrl: '/quan-tri/danh-muc',
            icon: BookOpen,
            desc: 'Khoa, chuyên ngành, môn học',
            adminOnly: true,
        },
    ];

    return (
        <div className="min-h-screen flex bg-surface-page font-sans text-text-primary antialiased selection:bg-brand-secondary selection:text-white">
            {/* Tự động cập nhật thẻ title trình duyệt */}
            <Head title={title} />

            {/* Lớp phủ màn hình mờ khi mở menu Drawer trên Mobile (<1024px) */}
            {mobileOpen && (
                <div
                    onClick={() => setMobileOpen(false)}
                    aria-label="Đóng bảng điều khiển"
                    className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden transition-opacity"
                />
            )}

            {/* ========================================================= */}
            {/* 1. SIDEBAR CỐ ĐỊNH 280PX TÔNG MÀU TỐI bg-surface-admin    */}
            {/* ========================================================= */}
            <aside
                className={`fixed top-0 bottom-0 left-0 w-[280px] bg-surface-admin text-slate-200 z-50 flex flex-col justify-between border-r border-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0 ${
                    mobileOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div>
                    {/* Phần đầu Sidebar: Logo và nhận diện quản trị */}
                    <div className="h-16 flex items-center justify-between px-6 border-b border-slate-800/80 bg-slate-950/60">
                        <Link href={getSafeRoute('dashboard', '/')} className="flex items-center gap-3 group">
                            <div className="w-9 h-9 rounded-lg bg-brand-primary flex items-center justify-center text-white font-extrabold text-sm shadow group-hover:scale-105 transition-transform">
                                AGU
                            </div>
                            <div className="flex flex-col">
                                <span className="font-bold text-white text-sm tracking-tight">
                                    Admin Console
                                </span>
                                <span className="text-[10px] text-brand-secondary font-medium tracking-wide">
                                    AGU Academic Hub
                                </span>
                            </div>
                        </Link>

                        {/* Nút đóng drawer trên Mobile */}
                        <button
                            type="button"
                            onClick={() => setMobileOpen(false)}
                            aria-label="Đóng menu"
                            className="lg:hidden text-slate-400 hover:text-white p-1 rounded-md transition-colors"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>

                    {/* Thẻ thông tin vắn tắt của người dùng đăng nhập */}
                    <div className="p-4 mx-3 my-4 rounded-xl bg-slate-800/50 border border-slate-700/50 flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-brand-primary to-brand-secondary flex items-center justify-center font-bold text-white text-sm shrink-0 shadow-sm">
                            {user?.ho_ten ? user.ho_ten.charAt(0).toUpperCase() : 'A'}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs font-semibold text-white truncate">
                                {user?.ho_ten || 'Quản trị viên'}
                            </p>
                            <span className="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded-full bg-brand-secondary/20 text-brand-secondary border border-brand-secondary/30">
                                {user?.ten_vai_tro || 'Quản trị viên'}
                            </span>
                        </div>
                    </div>

                    {/* Danh mục menu điều hướng */}
                    <nav className="px-3 space-y-1">
                        <div className="px-3 py-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                            Nghiệp vụ Quản trị
                        </div>

                        {navItems.map((item, index) => {
                            // Phân quyền: Ẩn các mục dành riêng cho Admin nếu user chỉ là Moderator
                            if (item.adminOnly && !user?.la_quan_tri) return null;

                            const active = isItemActive(item.routeName, item.fallbackUrl);
                            const IconComponent = item.icon;

                            return (
                                <Link
                                    key={index}
                                    href={getSafeRoute(item.routeName, item.fallbackUrl)}
                                    className={`flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all group ${
                                        active
                                            ? 'bg-brand-primary text-white shadow-md shadow-brand-primary/20'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/80'
                                    }`}
                                >
                                    <IconComponent
                                        className={`w-5 h-5 shrink-0 transition-transform group-hover:scale-110 ${
                                            active ? 'text-white' : 'text-slate-400 group-hover:text-white'
                                        }`}
                                    />
                                    <div className="flex flex-col min-w-0">
                                        <span className="truncate">{item.label}</span>
                                        <span
                                            className={`text-[11px] font-normal truncate ${
                                                active
                                                    ? 'text-blue-100'
                                                    : 'text-slate-400 group-hover:text-slate-300'
                                            }`}
                                        >
                                            {item.desc}
                                        </span>
                                    </div>
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* Phần đáy Sidebar: Về Cổng Sinh Viên & Đăng xuất */}
                <div className="p-4 border-t border-slate-800/80 bg-slate-950/60 space-y-2">
                    <Link
                        href={getSafeRoute('dashboard', '/')}
                        className="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-md text-xs font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors"
                    >
                        <GraduationCap className="w-4 h-4" />
                        <span>Về Cổng Sinh Viên</span>
                    </Link>

                    <Link
                        href={getSafeRoute('logout', '/logout')}
                        method="post"
                        as="button"
                        className="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-md text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 border border-rose-900/30 transition-colors"
                    >
                        <LogOut className="w-4 h-4" />
                        <span>Đăng xuất</span>
                    </Link>
                </div>
            </aside>

            {/* ========================================================= */}
            {/* 2. MAIN CONTENT (Lùi sang phải 280px nhường chỗ cho Sidebar) */}
            {/* ========================================================= */}
            <div className="flex-1 flex flex-col min-w-0 lg:pl-[280px]">
                {/* Topbar điều hướng trên cùng */}
                <header className="h-16 bg-surface-default border-b border-border-default px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shadow-xs">
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setMobileOpen(true)}
                            aria-label="Mở bảng điều hướng quản trị"
                            className="lg:hidden p-2 rounded-md text-text-secondary hover:bg-slate-100 transition-colors"
                        >
                            <Menu className="w-5 h-5" />
                        </button>
                        <h1 className="text-base sm:text-lg font-bold text-text-primary truncate">
                            {title}
                        </h1>
                    </div>

                    {/* Huy hiệu hiển thị ví cá nhân (nếu có) */}
                    <div className="flex items-center gap-3">
                        <div className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 border border-amber-200 rounded-full text-xs text-amber-800 font-medium">
                            <Coins className="w-3.5 h-3.5 text-amber-600" />
                            <span>
                                Ví cá nhân: <strong>{user?.vi_diem?.diem_kha_dung ?? 0}</strong> điểm
                            </span>
                        </div>
                    </div>
                </header>

                {/* Vùng hiển thị Toast Flash Session */}
                <div className="px-4 sm:px-6 pt-4 space-y-2">
                    {flash?.success && (
                        <div className="p-3.5 bg-semantic-success/10 border border-semantic-success/20 rounded-xl text-sm text-semantic-success flex items-center gap-2.5 shadow-sm">
                            <CheckCircle2 className="w-5 h-5 shrink-0" />
                            <span className="font-medium">{flash.success}</span>
                        </div>
                    )}
                    {flash?.error && (
                        <div className="p-3.5 bg-semantic-danger/10 border border-semantic-danger/20 rounded-xl text-sm text-semantic-danger flex items-center gap-2.5 shadow-sm">
                            <AlertTriangle className="w-5 h-5 shrink-0" />
                            <span className="font-medium">{flash.error}</span>
                        </div>
                    )}
                </div>

                {/* Nội dung trang con do component truyền vào */}
                <main className="flex-1 p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}