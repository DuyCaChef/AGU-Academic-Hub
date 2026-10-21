import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, ExternalLink } from 'lucide-react';

/**
 * GuestLayout: Layout dành cho người dùng chưa đăng nhập (Khách vãng lai).
 * Áp dụng cho các màn hình: Đăng nhập, Đăng ký, Quên mật khẩu, Nộp KYC,...
 *
 * Tuân thủ DESIGN_GUIDELINES.md:
 * - Nền sáng bg-surface-page (#f8fafc), chữ text-text-primary (#0f172a).
 * - Thẻ Card ở giữa chuẩn bo góc rounded-xl (12px), nền bg-surface-default (#ffffff).
 * - Sử dụng màu nhận diện AGU: brand-primary (#005baa), brand-secondary (#00a896).
 * - Sử dụng Icon Lucide đồng bộ cho giao diện chuyên nghiệp.
 */
export default function GuestLayout({ children, title = 'Cổng thông tin' }) {
    // Lấy thông báo flash được Laravel gửi kèm trong phiên làm việc
    const { flash } = usePage().props;

    // Helper kiểm tra an toàn sự tồn tại của route Ziggy trước khi gọi
    const getHomeUrl = () => {
        try {
            if (typeof route === 'function') {
                if (route().has('home')) return route('home');
                if (route().has('welcome')) return route('welcome');
            }
        } catch (e) {
            // Fallback an toàn nếu Ziggy chưa tải
        }
        return '/';
    };

    return (
        <div className="min-h-screen flex flex-col justify-between bg-surface-page text-text-primary antialiased selection:bg-brand-primary selection:text-white">
            {/* Tự động cập nhật tiêu đề trình duyệt qua thẻ Head của Inertia */}
            <Head title={title} />

            {/* 1. Header tinh gọn */}
            <header className="w-full border-b border-border-default bg-surface-default/80 backdrop-blur-md sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    {/* Logo & Tên dự án */}
                    <Link href={getHomeUrl()} className="flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-primary to-brand-secondary flex items-center justify-center text-white font-black text-xl shadow-md shadow-brand-primary/10 group-hover:scale-105 transition-transform">
                            AGU
                        </div>
                        <div className="flex flex-col">
                            <span className="font-bold text-text-primary text-base leading-tight tracking-tight">
                                AGU Academic Hub
                            </span>
                            <span className="text-xs text-text-secondary font-medium">
                                Nền tảng Tri thức Sinh viên
                            </span>
                        </div>
                    </Link>

                    {/* Điều hướng Header */}
                    <div className="flex items-center gap-3 text-sm">
                        <Link
                            href={getHomeUrl()}
                            className="text-text-secondary hover:text-brand-primary font-medium transition-colors hidden sm:block"
                        >
                            Trang chủ
                        </Link>
                        <span className="text-border-default hidden sm:block">|</span>
                        <a
                            href="https://agu.edu.vn"
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1.5 text-xs text-text-secondary hover:text-text-primary bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-full transition-colors"
                        >
                            <span>Cổng AGU</span>
                            <ExternalLink className="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>
            </header>

            {/* 2. Vùng hiển thị thông báo Flash Toast */}
            <div className="w-full max-w-md mx-auto px-4 space-y-2 mt-4">
                {flash?.error && (
                    <div className="p-3.5 bg-semantic-danger/10 border border-semantic-danger/20 rounded-xl text-sm text-semantic-danger flex items-start gap-2.5 shadow-sm">
                        <AlertTriangle className="w-5 h-5 shrink-0 mt-0.5" />
                        <div className="flex-1 font-medium">{flash.error}</div>
                    </div>
                )}
                {flash?.success && (
                    <div className="p-3.5 bg-semantic-success/10 border border-semantic-success/20 rounded-xl text-sm text-semantic-success flex items-start gap-2.5 shadow-sm">
                        <CheckCircle2 className="w-5 h-5 shrink-0 mt-0.5" />
                        <div className="flex-1 font-medium">{flash.success}</div>
                    </div>
                )}
            </div>

            {/* 3. Vùng trung tâm chứa Form */}
            <main className="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 py-10">
                <div className="w-full max-w-md">
                    {/* Hộp Card bọc nội dung Form (chuẩn rounded-xl 12px theo Design Guidelines) */}
                    <div className="bg-surface-default px-6 py-8 sm:px-8 border border-border-default rounded-xl shadow-lg shadow-slate-200/50">
                        {children}
                    </div>
                </div>
            </main>

            {/* 4. Footer */}
            <footer className="py-6 border-t border-border-default text-center text-xs text-text-secondary">
                <p>© {new Date().getFullYear()} AGU Academic Hub - Đại học An Giang (ĐHQG-HCM)</p>
            </footer>
        </div>
    );
}