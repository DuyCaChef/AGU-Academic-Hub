import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import {
    Mail,
    Lock,
    Eye,
    EyeOff,
    LogIn,
    Loader2,
    AlertCircle,
    CheckCircle2,
    ShieldCheck
} from 'lucide-react';

export default function Login({ status, canResetPassword }) {
    // 1. Quản lý trạng thái form qua hook useForm của Inertia
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    // Trạng thái hiển thị mật khẩu dạng text hay password
    const [showPassword, setShowPassword] = useState(false);

    // Xử lý gửi Form lên Laravel Backend
    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'), // Tự động xóa ô mật khẩu nếu đăng nhập không thành công
        });
    };

    return (
        <GuestLayout title="Đăng nhập tài khoản">
            <Head title="Đăng nhập" />

            {/* Tiêu đề & Giới thiệu khối Form */}
            <div className="text-center mb-6">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand-primary/10 text-brand-primary mb-3">
                    <ShieldCheck className="w-6 h-6 text-[#005baa]" />
                </div>
                <h1 className="text-2xl font-bold text-text-primary tracking-tight">
                    Cổng Đăng Nhập
                </h1>
                <p className="text-sm text-text-secondary mt-1">
                    Cổng truy cập dành cho Sinh viên & Cựu sinh viên AGU
                </p>
            </div>

            {/* Thông báo trạng thái nếu có (ví dụ: vừa đổi mật khẩu thành công) */}
            {status && (
                <div className="mb-5 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800 flex items-center gap-2">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{status}</span>
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                {/* 1. Ô nhập Email hoặc MSSV */}
                <div>
                    <label
                        htmlFor="email"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Email định danh AGU hoặc MSSV <span className="text-semantic-danger">*</span>
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <Mail className="w-4 h-4" />
                        </div>
                        <input
                            id="email"
                            type="text"
                            name="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="mssv@student.agu.edu.vn hoặc MSSV (VD: DTH215001)"
                            autoComplete="username"
                            autoFocus
                            required
                            className={`w-full pl-10 pr-3.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${errors.email
                                    ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger focus:border-semantic-danger'
                                    : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                }`}
                        />
                    </div>

                    {/* Lỗi trả về từ Backend Laravel */}
                    {errors.email && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.email}
                        </p>
                    )}
                </div>

                {/* 2. Ô nhập Mật khẩu */}
                <div>
                    <div className="flex items-center justify-between mb-1.5">
                        <label
                            htmlFor="password"
                            className="block text-xs font-semibold uppercase tracking-wider text-text-secondary"
                        >
                            Mật khẩu <span className="text-semantic-danger">*</span>
                        </label>
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="text-xs font-medium text-brand-primary hover:text-blue-800 transition-colors"
                            >
                                Quên mật khẩu?
                            </Link>
                        )}
                    </div>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <Lock className="w-4 h-4" />
                        </div>
                        <input
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Nhập mật khẩu của bạn"
                            autoComplete="current-password"
                            required
                            className={`w-full pl-10 pr-10 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${errors.password
                                    ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                    : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                }`}
                        />
                        {/* Nút bấm ẩn/hiện mật khẩu */}
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                            tabIndex={-1}
                        >
                            {showPassword ? (
                                <EyeOff className="w-4 h-4" />
                            ) : (
                                <Eye className="w-4 h-4" />
                            )}
                        </button>
                    </div>

                    {errors.password && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.password}
                        </p>
                    )}
                </div>

                {/* 3. Checkbox Ghi nhớ đăng nhập */}
                <div className="flex items-center pt-1">
                    <label className="flex items-center gap-2 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="w-4 h-4 rounded border-border-default text-brand-primary focus:ring-brand-primary cursor-pointer"
                        />
                        <span className="text-xs text-text-secondary">
                            Duy trì phiên đăng nhập trên thiết bị này
                        </span>
                    </label>
                </div>

                {/* 4. Nút bấm Đăng nhập (có Loading Spinner chống double-click) */}
                <button
                    type="submit"
                    disabled={processing || !data.email || !data.password}
                    className="w-full mt-2 py-2.5 px-4 rounded-lg bg-brand-primary hover:bg-blue-800 text-white font-medium text-sm shadow-sm transition-all duration-150 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                >
                    {processing ? (
                        <>
                            <Loader2 className="w-4 h-4 animate-spin text-white" />
                            <span>Đang xác thực tài khoản...</span>
                        </>
                    ) : (
                        <>
                            <LogIn className="w-4 h-4" />
                            <span>Đăng nhập hệ thống</span>
                        </>
                    )}
                </button>
            </form>

            {/* Hướng dẫn đăng nhập cho Sinh viên & Liên kết nộp KYC Cựu sinh viên */}
            <div className="mt-6 pt-5 border-t border-border-default space-y-3">
                <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-text-secondary leading-relaxed">
                    💡 <strong className="text-text-primary">Dành cho Sinh viên:</strong> Đăng nhập lần đầu bằng Email định danh (<code className="font-mono text-brand-primary">@student.agu.edu.vn</code>) hoặc MSSV và <strong>mật khẩu mặc định</strong> được nhà trường cấp. Hệ thống sẽ yêu cầu bạn đổi mật khẩu mới để kích hoạt tài khoản.
                </div>

                <div className="text-center pt-1">
                    <p className="text-xs text-text-secondary">
                        Bạn là cựu sinh viên AGU?{' '}
                        <Link
                            href={route('alumni.register')}
                            className="font-semibold text-brand-secondary hover:underline transition-colors"
                        >
                            Nộp hồ sơ đăng ký Cựu sinh viên (KYC) →
                        </Link>
                    </p>
                </div>
            </div>
        </GuestLayout>
    );
}