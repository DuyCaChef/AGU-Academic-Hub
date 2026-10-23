import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import {
    User,
    Mail,
    Lock,
    Eye,
    EyeOff,
    UserPlus,
    Loader2,
    AlertCircle,
    BadgeCheck,
    BookOpen
} from 'lucide-react';

export default function Register() {
    // 1. Quản lý trạng thái form qua hook useForm của Inertia
    const { data, setData, post, processing, errors, reset } = useForm({
        ho_ten: '',
        email: '',
        mssv: '',
        mat_khau: '',
        mat_khau_confirmation: '',
        terms: false,
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

    // 2. Logic thông minh: Tự động trích xuất MSSV khi người dùng nhập email
    const handleEmailChange = (e) => {
        const inputEmail = e.target.value;
        setData((prev) => {
            const nextData = { ...prev, email: inputEmail };

            // Nếu người dùng nhập dạng "dth215001@student.agu.edu.vn" hoặc "dmk221054@..."
            if (inputEmail.includes('@')) {
                const prefix = inputEmail.split('@')[0].trim();
                // Nếu phần tiền tố có độ dài hợp lý của MSSV (6 - 12 ký tự)
                if (prefix.length >= 6 && prefix.length <= 12) {
                    nextData.student_code = prefix.toUpperCase();
                }
            }
            return nextData;
        });
    };

    // Kiểm tra định dạng đuôi email sinh viên AGU
    const isStudentEmail = (email) => {
        if (!email) return true;
        const val = email.trim().toLowerCase();
        return val.endsWith('@student.agu.edu.vn') || val.endsWith('@agu.edu.vn');
    };

    const hasInvalidDomain = data.email.length > 5 && data.email.includes('@') && !isStudentEmail(data.email);

    // Xử lý gửi Form đăng ký lên server
    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('mat_khau', 'mat_khau_confirmation'),
        });
    };

    return (
        <GuestLayout title="Đăng ký tài khoản">
            <Head title="Đăng ký tài khoản" />

            {/* Header Form */}
            <div className="text-center mb-6">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand-secondary/10 text-brand-secondary mb-3">
                    <UserPlus className="w-6 h-6 text-[#00a896]" />
                </div>
                <h1 className="text-2xl font-bold text-text-primary tracking-tight">
                    Tạo Tài Khoản Sinh Viên
                </h1>
                <p className="text-sm text-text-secondary mt-1">
                    Gia nhập cộng đồng chia sẻ tri thức AGU Academic Hub
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                {/* 1. Họ và tên sinh viên */}
                <div>
                    <label
                        htmlFor="full_name"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Họ và tên <span className="text-semantic-danger">*</span>
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <User className="w-4 h-4" />
                        </div>
                        <input
                            id="ho_ten"
                            type="text"
                            name="ho_ten"
                            value={data.ho_ten}
                            onChange={(e) => setData('ho_ten', e.target.value)}
                            placeholder="Ví dụ: Nguyễn Văn An"
                            autoComplete="name"
                            autoFocus
                            required
                            className={`w-full pl-10 pr-3.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${errors.full_name || errors.name
                                ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                }`}
                        />
                    </div>
                    {(errors.full_name || errors.name) && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.full_name || errors.name}
                        </p>
                    )}
                </div>

                {/* 2. Email AGU */}
                <div>
                    <label
                        htmlFor="email"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Email trường cấp (@student.agu.edu.vn) <span className="text-semantic-danger">*</span>
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <Mail className="w-4 h-4" />
                        </div>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            onChange={handleEmailChange}
                            placeholder="dth215001@student.agu.edu.vn"
                            autoComplete="username"
                            required
                            className={`w-full pl-10 pr-3.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${errors.email || hasInvalidDomain
                                ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                }`}
                        />
                    </div>

                    {hasInvalidDomain && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            Hệ thống chỉ chấp nhận email tên miền @student.agu.edu.vn hoặc @agu.edu.vn
                        </p>
                    )}

                    {errors.email && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.email}
                        </p>
                    )}
                </div>

                {/* 3. Mã số sinh viên (Tự động điền theo email) */}
                <div>
                    <label
                        htmlFor="mssv"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Mã số sinh viên (MSSV) <span className="text-semantic-danger">*</span>
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <BadgeCheck className="w-4 h-4" />
                        </div>
                        <input
                            id="mssv"
                            type="text"
                            name="mssv"
                            value={data.mssv}
                            onChange={(e) => setData('mssv', e.target.value.toUpperCase())}
                            placeholder="DTH215001"
                            required
                            className={`w-full pl-10 pr-3.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors uppercase font-mono ${errors.student_code || errors.mssv
                                ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                }`}
                        />
                    </div>
                    {(errors.student_code || errors.mssv) && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.student_code || errors.mssv}
                        </p>
                    )}
                </div>

                {/* 4. Mật khẩu */}
                <div>
                    <label
                        htmlFor="mat_khau"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Mật khẩu (Tối thiểu 8 ký tự) <span className="text-semantic-danger">*</span>
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <Lock className="w-4 h-4" />
                        </div>
                        <input
                            id="mat_khau"
                            type={showPassword ? 'text' : 'password'}
                            name="mat_khau"
                            value={data.mat_khau}
                            onChange={(e) => setData('mat_khau', e.target.value)}
                            placeholder="••••••••"
                            autoComplete="new-password"
                            required
                            className={`w-full pl-10 pr-10 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${errors.mat_khau
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
                    {errors.mat_khau && (
                        <p className="mt-1.5 text-xs text-semantic-danger flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {errors.mat_khau}
                        </p>
                    )}
                </div>

                {/* 5. Xác nhận lại Mật khẩu */}
                <div>
                    <label
                        htmlFor="mat_khau_xac_nhan"
                        className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1.5"
                    >
                        Xác nhận lại mật khẩu <span className="text-semantic-danger">*</span>
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <Lock className="w-4 h-4" />
                        </div>
                        <input
                            id="mat_khau_confirmation"
                            type={showConfirmPassword ? 'text' : 'password'}
                            name="mat_khau_confirmation"
                            value={data.mat_khau_confirmation}
                            onChange={(e) =>
                                setData('mat_khau_confirmation', e.target.value)
                            }
                            autoComplete="new-password"
                            required
                            className={`w-full pl-10 pr-10 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${errors.password_confirmation
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

                {/* 6. Checkbox Đồng ý Điều khoản & Tiêu chuẩn cộng đồng */}
                <div className="pt-1">
                    <label className="flex items-start gap-2.5 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            name="terms"
                            checked={data.terms}
                            onChange={(e) => setData('terms', e.target.checked)}
                            required
                            className="mt-0.5 w-4 h-4 rounded border-border-default text-brand-primary focus:ring-brand-primary cursor-pointer shrink-0"
                        />
                        <span className="text-xs text-text-secondary leading-relaxed">
                            Tôi cam kết là sinh viên của Trường Đại học An Giang và đồng ý tuân thủ{' '}
                            <span className="text-brand-primary font-medium hover:underline">
                                Quy chế chia sẻ tài nguyên học thuật
                            </span>.
                        </span>
                    </label>
                </div>

                {/* 7. Nút Tạo tài khoản (có Loading Spinner) */}
                <button
                    type="submit"
                    disabled={processing || hasInvalidDomain || !data.terms}
                    className="w-full mt-3 py-2.5 px-4 rounded-lg bg-brand-primary hover:bg-blue-800 text-white font-medium text-sm shadow-sm transition-all duration-150 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                >
                    {processing ? (
                        <>
                            <Loader2 className="w-4 h-4 animate-spin text-white" />
                            <span>Đang khởi tạo tài khoản...</span>
                        </>
                    ) : (
                        <>
                            <UserPlus className="w-4 h-4" />
                            <span>Đăng ký tài khoản</span>
                        </>
                    )}
                </button>
            </form>

            {/* Chuyển hướng sang Login */}
            <div className="mt-6 pt-5 border-t border-border-default text-center">
                <p className="text-xs text-text-secondary">
                    Đã có tài khoản AGU?{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-brand-primary hover:text-blue-800 transition-colors"
                    >
                        Đăng nhập ngay →
                    </Link>
                </p>
            </div>
        </GuestLayout>
    );
}