import React, { useState, useRef } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import {
    GraduationCap,
    User,
    Mail,
    Lock,
    Eye,
    EyeOff,
    UploadCloud,
    FileCheck,
    X,
    AlertCircle,
    Info,
    Loader2,
    Calendar,
    School,
    CheckCircle2,
    IdCard,
    FileText
} from 'lucide-react';

export default function AlumniRegister({ danh_sach_khoa = [] }) {
    const currentYear = new Date().getFullYear();
    const fileInputRef = useRef(null);

    // 1. Quản lý trạng thái form qua useForm của Inertia
    const { data, setData, post, processing, errors, reset, progress } = useForm({
        ho_ten: '',
        email: '',
        mssv: '',
        nam_tot_nghiep: currentYear - 1,
        ma_khoa: '',
        mat_khau: '',
        mat_khau_confirmation: '',
        loai_minh_chung: 'BANG_TOT_NGHIEP',
        anh_minh_chung: null,
        terms: false,
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [imagePreview, setImagePreview] = useState(null);
    const [clientFileError, setClientFileError] = useState('');
    const [isDragging, setIsDragging] = useState(false);

    // 2. Xử lý khi chọn file ảnh minh chứng
    const handleFileSelect = (file) => {
        setClientFileError('');

        if (!file) {
            setData('anh_minh_chung', null);
            setImagePreview(null);
            return;
        }

        // Kiểm tra loại file ảnh
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            setClientFileError('Chỉ chấp nhận tệp hình ảnh định dạng JPEG, PNG, JPG hoặc WEBP.');
            return;
        }

        // Kiểm tra kích thước <= 5MB
        const maxSizeBytes = 5 * 1024 * 1024;
        if (file.size > maxSizeBytes) {
            setClientFileError('Dung lượng ảnh vượt quá giới hạn 5MB.');
            return;
        }

        setData('anh_minh_chung', file);

        // Tạo preview ảnh
        const reader = new FileReader();
        reader.onloadend = () => {
            setImagePreview(reader.result);
        };
        reader.readAsDataURL(file);
    };

    const handleFileInputChange = (e) => {
        const file = e.target.files?.[0];
        if (file) handleFileSelect(file);
    };

    const handleRemoveFile = () => {
        setData('anh_minh_chung', null);
        setImagePreview(null);
        setClientFileError('');
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    // Kéo thả file
    const handleDragOver = (e) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        const file = e.dataTransfer.files?.[0];
        if (file) handleFileSelect(file);
    };

    // 3. Xử lý gửi Form nộp hồ sơ KYC
    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('alumni.register.store'), {
            forceFormData: true,
            onFinish: () => reset('mat_khau', 'mat_khau_confirmation'),
        });
    };

    return (
        <GuestLayout title="Đăng ký tài khoản Cựu sinh viên" maxWidth="max-w-3xl">
            <Head title="Đăng ký Cựu sinh viên - AGU Academic Hub" />

            {/* Header Form */}
            <div className="text-center mb-6">
                <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-brand-primary/10 to-brand-secondary/15 text-brand-primary mb-3 shadow-inner">
                    <GraduationCap className="w-7 h-7 text-brand-primary" />
                </div>
                <h1 className="text-2xl font-bold text-text-primary tracking-tight">
                    Đăng Ký Tài Khoản Cựu Sinh Viên
                </h1>
                <p className="text-sm text-text-secondary mt-1 max-w-lg mx-auto">
                    Nộp hồ sơ định danh (KYC) để xác thực danh tính cựu sinh viên và tham gia chia sẻ học liệu cùng cộng đồng AGU.
                </p>
            </div>

            {/* Hộp giải thích quy trình xét duyệt KYC */}
            <div className="mb-6 p-4 rounded-xl bg-blue-50/70 border border-blue-200/80 text-xs text-slate-700 space-y-1.5">
                <div className="flex items-center gap-2 font-semibold text-brand-primary text-sm">
                    <Info className="w-4 h-4 shrink-0" />
                    <span>Quy trình xác thực hồ sơ cựu sinh viên</span>
                </div>
                <p className="leading-relaxed">
                    Sau khi hoàn tất đăng ký, hồ sơ minh chứng sẽ được Quản trị viên đối soát với danh sách tốt nghiệp của Trường ĐH An Giang trong <strong>24 – 48 giờ làm việc</strong>. Khi được phê duyệt, bạn có thể đăng nhập ngay bằng email và mật khẩu đã tạo.
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
                {/* Khung chia 2 cột */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {/* ──── CỘT 1: THÔNG TIN CÁ NHÂN ──── */}
                    <div className="space-y-4">
                        <h2 className="text-xs font-bold uppercase tracking-wider text-brand-primary flex items-center gap-1.5 pb-1 border-b border-border-default">
                            <User className="w-3.5 h-3.5" />
                            Thông tin cá nhân & Khóa học
                        </h2>

                        {/* 1. Họ và tên */}
                        <div>
                            <label htmlFor="ho_ten" className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
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
                                    required
                                    className={`w-full pl-10 pr-3.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${
                                        errors.ho_ten
                                            ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                            : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                    }`}
                                />
                            </div>
                            {errors.ho_ten && (
                                <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {errors.ho_ten}
                                </p>
                            )}
                        </div>

                        {/* 2. Email liên hệ (cá nhân) */}
                        <div>
                            <label htmlFor="email" className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
                                Email liên hệ nhận thông báo <span className="text-semantic-danger">*</span>
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
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="email_ca_nhan@gmail.com"
                                    autoComplete="email"
                                    required
                                    className={`w-full pl-10 pr-3.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${
                                        errors.email
                                            ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                            : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                    }`}
                                />
                            </div>
                            <span className="text-[11px] text-text-secondary mt-0.5 block">
                                Cựu sinh viên có thể sử dụng Gmail, Outlook hoặc email cá nhân hiện tại.
                            </span>
                            {errors.email && (
                                <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        {/* 3. MSSV khóa cũ & Năm tốt nghiệp */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label htmlFor="mssv" className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
                                    MSSV khóa cũ <span className="text-semantic-danger">*</span>
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <IdCard className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="mssv"
                                        type="text"
                                        name="mssv"
                                        value={data.mssv}
                                        onChange={(e) => setData('mssv', e.target.value.toUpperCase())}
                                        placeholder="DTH180001"
                                        required
                                        className={`w-full pl-9 pr-2.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary uppercase font-mono placeholder:text-slate-400 focus:outline-none transition-colors ${
                                            errors.mssv
                                                ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                                : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                        }`}
                                    />
                                </div>
                                {errors.mssv && (
                                    <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.mssv}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="nam_tot_nghiep" className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
                                    Năm tốt nghiệp <span className="text-semantic-danger">*</span>
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <Calendar className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="nam_tot_nghiep"
                                        type="number"
                                        min="1999"
                                        max={currentYear}
                                        name="nam_tot_nghiep"
                                        value={data.nam_tot_nghiep}
                                        onChange={(e) => setData('nam_tot_nghiep', e.target.value)}
                                        placeholder="2023"
                                        required
                                        className={`w-full pl-9 pr-2.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${
                                            errors.nam_tot_nghiep
                                                ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                                : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                        }`}
                                    />
                                </div>
                                {errors.nam_tot_nghiep && (
                                    <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.nam_tot_nghiep}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* 4. Khoa từng theo học */}
                        <div>
                            <label htmlFor="ma_khoa" className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
                                Khoa đã theo học
                            </label>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <School className="w-4 h-4" />
                                </div>
                                <select
                                    id="ma_khoa"
                                    name="ma_khoa"
                                    value={data.ma_khoa}
                                    onChange={(e) => setData('ma_khoa', e.target.value)}
                                    className={`w-full pl-10 pr-3.5 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary focus:outline-none transition-colors ${
                                        errors.ma_khoa
                                            ? 'border-semantic-danger focus:ring-1 focus:ring-semantic-danger'
                                            : 'border-border-default focus:border-brand-primary focus:ring-1 focus:ring-brand-primary'
                                    }`}
                                >
                                    <option value="">-- Chọn Khoa (không bắt buộc) --</option>
                                    {danh_sach_khoa.map((k) => (
                                        <option key={k.ma_khoa} value={k.ma_khoa}>
                                            {k.ten_khoa} ({k.ky_hieu_khoa})
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {errors.ma_khoa && (
                                <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {errors.ma_khoa}
                                </p>
                            )}
                        </div>

                        {/* 5. Mật khẩu & Nhập lại */}
                        <div className="space-y-3 pt-1">
                            <div>
                                <label htmlFor="mat_khau" className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
                                    Mật khẩu đăng nhập <span className="text-semantic-danger">*</span>
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
                                        placeholder="Tối thiểu 8 ký tự"
                                        autoComplete="new-password"
                                        required
                                        className={`w-full pl-10 pr-10 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${
                                            errors.mat_khau
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
                                    <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.mat_khau}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="mat_khau_confirmation" className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
                                    Xác nhận mật khẩu <span className="text-semantic-danger">*</span>
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
                                        onChange={(e) => setData('mat_khau_confirmation', e.target.value)}
                                        placeholder="Nhập lại mật khẩu"
                                        autoComplete="new-password"
                                        required
                                        className={`w-full pl-10 pr-10 py-2.5 bg-surface-default border rounded-lg text-sm text-text-primary placeholder:text-slate-400 focus:outline-none transition-colors ${
                                            errors.mat_khau_confirmation
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
                                {errors.mat_khau_confirmation && (
                                    <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.mat_khau_confirmation}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* ──── CỘT 2: HỒ SƠ MINH CHỨNG (KYC) ──── */}
                    <div className="space-y-4">
                        <h2 className="text-xs font-bold uppercase tracking-wider text-brand-secondary flex items-center gap-1.5 pb-1 border-b border-border-default">
                            <FileCheck className="w-3.5 h-3.5 text-brand-secondary" />
                            Minh chứng xác thực danh tính (KYC)
                        </h2>

                        {/* Loại minh chứng */}
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-2">
                                Loại tài liệu minh chứng <span className="text-semantic-danger">*</span>
                            </label>
                            <div className="grid grid-cols-1 gap-2">
                                {[
                                    {
                                        value: 'BANG_TOT_NGHIEP',
                                        label: 'Bằng tốt nghiệp ĐH / CĐ',
                                        desc: 'Bản gốc hoặc bản công chứng bằng tốt nghiệp AGU',
                                    },
                                    {
                                        value: 'GIAY_CHUNG_NHAN',
                                        label: 'Giấy chứng nhận tốt nghiệp',
                                        desc: 'Giấy chứng nhận tốt nghiệp tạm thời do trường cấp',
                                    },
                                    {
                                        value: 'THE_SINH_VIEN',
                                        label: 'Thẻ sinh viên AGU khóa cũ',
                                        desc: 'Thẻ sinh viên thể hiện rõ họ tên, MSSV và ảnh thẻ',
                                    },
                                ].map((item) => (
                                    <label
                                        key={item.value}
                                        className={`flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all ${
                                            data.loai_minh_chung === item.value
                                                ? 'border-brand-primary bg-brand-primary/5 ring-1 ring-brand-primary/30'
                                                : 'border-border-default hover:border-slate-300 bg-surface-default'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="loai_minh_chung"
                                            value={item.value}
                                            checked={data.loai_minh_chung === item.value}
                                            onChange={(e) => setData('loai_minh_chung', e.target.value)}
                                            className="mt-0.5 text-brand-primary focus:ring-brand-primary"
                                        />
                                        <div className="flex-1">
                                            <div className="text-xs font-semibold text-text-primary">
                                                {item.label}
                                            </div>
                                            <div className="text-[11px] text-text-secondary">
                                                {item.desc}
                                            </div>
                                        </div>
                                    </label>
                                ))}
                            </div>
                            {errors.loai_minh_chung && (
                                <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {errors.loai_minh_chung}
                                </p>
                            )}
                        </div>

                        {/* Upload ảnh minh chứng */}
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-text-secondary mb-1">
                                Ảnh chụp minh chứng <span className="text-semantic-danger">*</span>
                            </label>

                            {/* Khu vực Drag & Drop */}
                            {!imagePreview ? (
                                <div
                                    onDragOver={handleDragOver}
                                    onDragLeave={handleDragLeave}
                                    onDrop={handleDrop}
                                    onClick={() => fileInputRef.current?.click()}
                                    className={`relative flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-xl cursor-pointer transition-all ${
                                        isDragging
                                            ? 'border-brand-primary bg-brand-primary/10'
                                            : 'border-border-default hover:border-brand-primary/60 hover:bg-slate-50/70 bg-surface-page'
                                    }`}
                                >
                                    <div className="w-12 h-12 rounded-full bg-brand-primary/10 flex items-center justify-center text-brand-primary mb-2">
                                        <UploadCloud className="w-6 h-6" />
                                    </div>
                                    <p className="text-xs font-semibold text-text-primary text-center">
                                        Kéo thả ảnh hoặc <span className="text-brand-primary underline">nhấp để chọn</span>
                                    </p>
                                    <p className="text-[11px] text-text-secondary mt-1 text-center">
                                        Chấp nhận JPEG, PNG, JPG, WEBP (Tối đa 5MB)
                                    </p>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg,image/webp"
                                        className="hidden"
                                        onChange={handleFileInputChange}
                                    />
                                </div>
                            ) : (
                                /* Khung xem trước ảnh */
                                <div className="relative border border-border-default rounded-xl overflow-hidden bg-slate-900/5 p-3">
                                    <div className="flex items-center gap-3">
                                        <img
                                            src={imagePreview}
                                            alt="Minh chứng KYC"
                                            className="w-20 h-20 object-cover rounded-lg border border-border-default shadow-sm shrink-0"
                                        />
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-1.5 text-xs font-semibold text-text-primary truncate">
                                                <CheckCircle2 className="w-4 h-4 text-semantic-success shrink-0" />
                                                <span className="truncate">{data.anh_minh_chung?.name}</span>
                                            </div>
                                            <p className="text-[11px] text-text-secondary mt-0.5">
                                                {(data.anh_minh_chung.size / (1024 * 1024)).toFixed(2)} MB • Sẵn sàng tải lên
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() => fileInputRef.current?.click()}
                                                className="text-xs font-medium text-brand-primary hover:underline mt-1.5 inline-block"
                                            >
                                                Chọn ảnh khác
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={handleRemoveFile}
                                            className="p-1.5 rounded-full hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors"
                                            title="Xóa ảnh này"
                                        >
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/jpg,image/webp"
                                        className="hidden"
                                        onChange={handleFileInputChange}
                                    />
                                </div>
                            )}

                            {/* Lỗi kích thước/loại file client */}
                            {clientFileError && (
                                <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {clientFileError}
                                </p>
                            )}

                            {/* Lỗi server trả về */}
                            {errors.anh_minh_chung && (
                                <p className="mt-1 text-xs text-semantic-danger flex items-center gap-1">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    {errors.anh_minh_chung}
                                </p>
                            )}

                            {/* Thanh tiến trình upload nếu đang tải lên */}
                            {progress && (
                                <div className="mt-2">
                                    <div className="flex justify-between text-[11px] text-text-secondary mb-1">
                                        <span>Đang tải tệp lên server...</span>
                                        <span>{progress.percentage}%</span>
                                    </div>
                                    <div className="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div
                                            className="bg-brand-primary h-1.5 transition-all duration-300"
                                            style={{ width: `${progress.percentage}%` }}
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* ──── ĐIỀU KHOẢN & NÚT SUBMIT ──── */}
                <div className="pt-2 border-t border-border-default space-y-4">
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
                            Tôi cam kết thông tin cá nhân và tài liệu minh chứng cung cấp là hoàn toàn chính xác, trung thực. Tôi đồng ý chịu trách nhiệm trước quy chế của Trường Đại học An Giang và Điều khoản sử dụng AGU Academic Hub.
                        </span>
                    </label>

                    {/* Nút gửi hồ sơ */}
                    <button
                        type="submit"
                        disabled={processing || !data.terms || !data.anh_minh_chung || !!clientFileError}
                        className="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-brand-primary to-blue-700 hover:from-blue-800 hover:to-blue-900 text-white font-semibold text-sm shadow-md shadow-brand-primary/20 transition-all duration-150 flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                    >
                        {processing ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin text-white" />
                                <span>Đang nộp hồ sơ xét duyệt...</span>
                            </>
                        ) : (
                            <>
                                <FileCheck className="w-4 h-4" />
                                <span>Gửi hồ sơ đăng ký cựu sinh viên</span>
                            </>
                        )}
                    </button>
                </div>
            </form>

            {/* Điều hướng chân trang */}
            <div className="mt-6 pt-5 border-t border-border-default flex flex-col sm:flex-row items-center justify-between text-xs text-text-secondary gap-3">
                <div>
                    Đã có tài khoản được duyệt?{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-brand-primary hover:underline transition-colors"
                    >
                        Đăng nhập ngay
                    </Link>
                </div>
                <div>
                    Bạn là sinh viên đang học?{' '}
                    <Link
                        href={route('login')}
                        className="font-semibold text-brand-secondary hover:underline transition-colors"
                    >
                        Đăng nhập bằng tài khoản trường cấp →
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}
