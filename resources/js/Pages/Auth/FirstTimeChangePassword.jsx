import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import {
    KeyRound,
    Lock,
    Eye,
    EyeOff,
    ShieldAlert,
    CheckCircle2,
    Loader2,
    AlertCircle,
    UserCheck,
    LogOut
} from 'lucide-react';

export default function FirstTimeChangePassword({ user }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    // Tính toán độ mạnh mật khẩu cơ bản (Client-side)
    const getPasswordStrength = (pwd) => {
        if (!pwd) return { score: 0, text: 'Chưa nhập', color: 'bg-slate-200' };
        let score = 0;
        if (pwd.length >= 8) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^A-Za-z0-9]/.test(pwd)) score++;

        if (score <= 1) return { score, text: 'Yếu', color: 'bg-semantic-danger' };
        if (score === 2 || score === 3) return { score, text: 'Trung bình', color: 'bg-semantic-warning' };
        return { score, text: 'Mạnh', color: 'bg-semantic-success' };
    };

    const strength = getPasswordStrength(data.password);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('password.change.update'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout title="Kích hoạt tài khoản - Đổi mật khẩu lần đầu" maxWidth="max-w-lg">
            <Head title="Đổi mật khẩu lần đầu" />

            {/* Header */}
            <div className="text-center mb-6">
                <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200 mb-3 shadow-inner">
                    <KeyRound className="w-7 h-7" />
                </div>
                <h1 className="text-2xl font-bold text-text-primary tracking-tight">
                    Đổi Mật Khẩu Lần Đầu
                </h1>
                <p className="text-xs text-text-secondary mt-1">
                    Thiết lập mật khẩu cá nhân để kích hoạt tài khoản sinh viên
                </p>
            </div>

            {/* Thẻ định danh sinh viên */}
            {user && (
                <div className="mb-5 p-3.5 rounded-xl bg-slate-50 border border-border-default flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2.5">
                        <div className="w-8 h-8 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center font-bold">
                            <UserCheck className="w-4 h-4" />
                        </div>
                        <div>
                            <div className="font-semibold text-text-primary">{user.ho_ten}</div>
                            <div className="text-text-secondary text-[11px] font-mono">
                                MSSV: <strong className="text-brand-primary">{user.mssv}</strong> • {user.email}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Cảnh báo an toàn */}
            <div className="mb-5 p-3.5 rounded-xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-900 flex items-start gap-2.5">
                <ShieldAlert className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                <p className="leading-relaxed">
                    Bạn vừa đăng nhập bằng <strong>mật khẩu mặc định</strong> được nhà trường cấp. Để bảo vệ thông tin học tập và tài nguyên cá nhân, bạn bắt buộc phải tạo mật khẩu mới trước khi tiếp tục.
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                {/* 1. Mật khẩu mới */}
                <div>
                    <label
                        htmlFor="password"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Mật khẩu mới <span className="text-semantic-danger">*</span>
                    </label>
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
                            placeholder="Tối thiểu 8 ký tự"
                            autoComplete="new-password"
                            autoFocus
                            required
                            className={`w-full pl-10 pr-10 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${
                                errors.password
                                    ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                    : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                            }`}
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                            tabIndex={-1}
                        >
                            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                    </div>

                    {/* Thanh đo độ mạnh mật khẩu */}
                    {data.password && (
                        <div className="mt-2">
                            <div className="flex justify-between items-center text-[11px] mb-1">
                                <span className="text-text-secondary">Độ an toàn:</span>
                                <span className="font-semibold text-text-primary">{strength.text}</span>
                            </div>
                            <div className="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden flex gap-1">
                                {[1, 2, 3, 4].map((step) => (
                                    <div
                                        key={step}
                                        className={`flex-1 h-full rounded-full transition-all duration-300 ${
                                            strength.score >= step ? strength.color : 'bg-slate-200'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {errors.password && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.password}
                        </p>
                    )}
                </div>

                {/* 2. Xác nhận mật khẩu mới */}
                <div>
                    <label
                        htmlFor="password_confirmation"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Xác nhận lại mật khẩu mới <span className="text-semantic-danger">*</span>
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <Lock className="w-4 h-4" />
                        </div>
                        <input
                            id="password_confirmation"
                            type={showConfirmPassword ? 'text' : 'password'}
                            name="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            placeholder="Nhập lại chính xác mật khẩu trên"
                            autoComplete="new-password"
                            required
                            className={`w-full pl-10 pr-10 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${
                                errors.password_confirmation
                                    ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                    : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                            }`}
                        />
                        <button
                            type="button"
                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                            className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                            tabIndex={-1}
                        >
                            {showConfirmPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                        </button>
                    </div>

                    {errors.password_confirmation && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.password_confirmation}
                        </p>
                    )}
                </div>

                {/* Nút Đổi mật khẩu */}
                <button
                    type="submit"
                    disabled={processing || !data.password || !data.password_confirmation}
                    className="w-full mt-2 py-2.5 px-4 rounded-lg bg-brand-primary hover:bg-blue-800 text-white font-medium text-sm shadow-sm transition-all duration-150 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                >
                    {processing ? (
                        <>
                            <Loader2 className="w-4 h-4 animate-spin text-white" />
                            <span>Đang kích hoạt tài khoản...</span>
                        </>
                    ) : (
                        <>
                            <CheckCircle2 className="w-4 h-4" />
                            <span>Lưu mật khẩu & Kích hoạt tài khoản</span>
                        </>
                    )}
                </button>
            </form>

            {/* Thoát phiên đăng nhập nếu không phải là sinh viên này */}
            <div className="mt-6 pt-4 border-t border-border-default text-center">
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-semantic-danger transition-colors"
                >
                    <LogOut className="w-3.5 h-3.5" />
                    <span>Không phải tài khoản của bạn? Đăng xuất</span>
                </Link>
            </div>
        </GuestLayout>
    );
}
