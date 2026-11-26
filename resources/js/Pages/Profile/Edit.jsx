import React from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import {
    User,
    Shield,
    Lock,
    KeyRound,
    AlertTriangle,
    Coins,
    GraduationCap,
    School,
    CheckCircle2
} from 'lucide-react';

export default function Edit({ mustVerifyEmail, status }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const roleLabels = {
        SINH_VIEN: 'Sinh viên chính quy',
        CUU_SINH_VIEN: 'Cựu sinh viên AGU',
        GIANG_VIEN: 'Giảng viên',
        QUAN_TRI_VIEN: 'Quản trị viên',
        KIEM_DUYET_VIEN: 'Kiểm duyệt viên',
    };

    return (
        <StudentLayout title="Hồ Sơ Cá Nhân & Thiết Lập">
            <Head title="Hồ Sơ Cá Nhân & Thiết Lập Tài Khoản - AGU Academic Hub" />

            <div className="space-y-6">
                {/* 1. Header Banner */}
                <div className="bg-gradient-to-r from-[#005baa] to-blue-800 text-white rounded-2xl p-6 sm:p-8 shadow-sm relative overflow-hidden">
                    <div className="pointer-events-none absolute -right-6 -bottom-6 w-56 h-56 bg-white/5 rounded-full blur-2xl" />

                    <div className="flex flex-col sm:flex-row sm:items-center gap-5 relative z-10">
                        <div className="w-16 h-16 rounded-2xl bg-white/15 border border-white/20 text-white flex items-center justify-center font-bold text-2xl shrink-0 shadow-inner">
                            {user?.ho_ten?.charAt(0)?.toUpperCase() || user?.name?.charAt(0)?.toUpperCase() || 'U'}
                        </div>
                        <div className="space-y-1">
                            <div className="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-0.5 text-xs font-semibold text-blue-100">
                                <Shield size={12} className="text-amber-300" />
                                {roleLabels[user?.vai_tro] || user?.vai_tro || 'Tài khoản AGU'}
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                {user?.ho_ten || user?.name || 'Hồ Sơ Cá Nhân'}
                            </h1>
                            <p className="text-blue-100 text-xs sm:text-sm">
                                Quản lý thông tin định danh học thuật, cập nhật mật khẩu và bảo mật tài khoản.
                            </p>
                        </div>
                    </div>
                </div>

                {/* 2. Readonly Academic Information Card */}
                <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-4">
                    <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div className="flex items-center gap-2">
                            <GraduationCap size={18} className="text-[#005baa]" />
                            <h2 className="text-base font-bold text-slate-900">
                                Thông Tin Học Thuật Định Danh (Chỉ Đọc)
                            </h2>
                        </div>
                        <span className="text-[11px] font-semibold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full flex items-center gap-1">
                            <Lock size={11} /> Đã khóa bảo vệ
                        </span>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        {/* MSSV */}
                        <div className="p-3.5 bg-slate-50/70 border border-slate-200/60 rounded-xl space-y-1">
                            <span className="text-slate-400 uppercase font-bold text-[10px] tracking-wider block">
                                Mã số sinh viên
                            </span>
                            <span className="font-mono font-bold text-sm text-slate-800 block">
                                {user?.mssv || 'Không áp dụng'}
                            </span>
                        </div>

                        {/* Khoa */}
                        <div className="p-3.5 bg-slate-50/70 border border-slate-200/60 rounded-xl space-y-1">
                            <span className="text-slate-400 uppercase font-bold text-[10px] tracking-wider block">
                                Khoa / Viện đào tạo
                            </span>
                            <span className="font-semibold text-sm text-slate-800 block truncate" title={user?.khoa?.ten_khoa}>
                                {user?.khoa?.ten_khoa || 'Chưa cập nhật khoa'}
                            </span>
                        </div>

                        {/* Điểm uy tín */}
                        <div className="p-3.5 bg-emerald-50/50 border border-emerald-100 rounded-xl space-y-1">
                            <span className="text-emerald-700 uppercase font-bold text-[10px] tracking-wider block">
                                Điểm uy tín học thuật
                            </span>
                            <div className="flex items-baseline gap-1 text-emerald-800">
                                <span className="font-extrabold text-base">{user?.diem_uy_tin ?? 100}</span>
                                <span className="text-[11px]">điểm</span>
                            </div>
                        </div>

                        {/* Ví điểm */}
                        <div className="p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl space-y-1">
                            <span className="text-[#005baa] uppercase font-bold text-[10px] tracking-wider block">
                                Số dư điểm ví
                            </span>
                            <div className="flex items-center justify-between">
                                <span className="font-extrabold text-base text-[#005baa]">
                                    {user?.vi_diem?.diem_kha_dung ?? 0} pts
                                </span>
                                <Link
                                    href={route('wallet.index')}
                                    className="text-[11px] font-bold text-[#005baa] hover:underline"
                                >
                                    Xem ví &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3. Editable Forms */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Cập nhật thông tin cơ bản */}
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                        />
                    </div>

                    {/* Cập nhật mật khẩu */}
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs">
                        <UpdatePasswordForm />
                    </div>
                </div>

                {/* 4. Delete Account (Danger Zone) */}
                <div className="bg-white rounded-2xl border border-rose-200/70 p-6 shadow-2xs">
                    <DeleteUserForm />
                </div>
            </div>
        </StudentLayout>
    );
}
