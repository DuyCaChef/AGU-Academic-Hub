import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import {
    GraduationCap,
    BookOpen,
    Building2,
    Search,
    Layers,
    UserCheck,
    ArrowRight,
    Clock,
    RotateCcw,
    Code2,
    TrendingUp,
    X,
    Sparkles,
} from 'lucide-react';

export default function FacultiesIndex({ faculties = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [search, setSearch] = useState('');

    const filteredFaculties = faculties.filter((faculty) => {
        const query = search.toLowerCase().trim();
        return (
            (faculty.ten_khoa || '').toLowerCase().includes(query) ||
            (faculty.ky_hieu_khoa || '').toLowerCase().includes(query)
        );
    });

    const totalCourses = faculties.reduce(
        (sum, f) => sum + (f.mon_hocs_count ?? f.mon_hocs?.length ?? f.monHocs?.length ?? 0),
        0
    );
    const totalMajors = faculties.reduce(
        (sum, f) => sum + (f.chuyen_nganhs_count ?? f.chuyen_nganhs?.length ?? f.chuyenNganhs?.length ?? 0),
        0
    );

    const getFacultyTheme = (kyHieu = '') => {
        const code = kyHieu.toUpperCase();
        if (code.includes('CNTT') || code.includes('IT')) {
            return {
                accentBar: 'bg-gradient-to-r from-[#005BAA] to-sky-500',
                iconBg: 'bg-blue-50 text-[#005BAA] border-blue-200/70',
                badge: 'bg-blue-50 text-[#005BAA] border-blue-200/80',
                Icon: Code2,
            };
        }
        if (code.includes('KT') || code.includes('QT')) {
            return {
                accentBar: 'bg-gradient-to-r from-amber-400 to-orange-500',
                iconBg: 'bg-amber-50 text-amber-600 border-amber-200/70',
                badge: 'bg-amber-50 text-amber-800 border-amber-200/80',
                Icon: TrendingUp,
            };
        }
        if (code.includes('SP')) {
            return {
                accentBar: 'bg-gradient-to-r from-emerald-500 to-teal-500',
                iconBg: 'bg-emerald-50 text-emerald-600 border-emerald-200/70',
                badge: 'bg-emerald-50 text-emerald-700 border-emerald-200/80',
                Icon: GraduationCap,
            };
        }
        return {
            accentBar: 'bg-gradient-to-r from-indigo-500 to-blue-600',
            iconBg: 'bg-indigo-50 text-indigo-600 border-indigo-200/70',
            badge: 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
            Icon: Building2,
        };
    };

    return (
        <StudentLayout title="Danh Mục Khoa Đào Tạo">
            <Head title="Danh Mục Khoa Đào Tạo - AGU Academic Hub" />

            <div className="space-y-6">
                {/* 1. Header Banner: AGU Gradient with Quick Stats */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#005BAA] via-[#004e95] to-blue-900 p-6 text-white shadow-sm sm:p-8">
                    <div className="pointer-events-none absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]" />
                    <div className="pointer-events-none absolute -right-10 -bottom-10 h-48 w-48 rounded-full bg-[#FFC72C]/15 blur-2xl" />

                    <div className="relative z-10 flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
                        <div className="max-w-xl space-y-2">
                            <div className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/15 px-3 py-1 text-xs font-semibold text-blue-100 backdrop-blur-xs">
                                <GraduationCap size={14} className="text-[#FFC72C]" />
                                <span>Cơ cấu tổ chức đào tạo • Trường Đại học An Giang</span>
                            </div>
                            <h1 className="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                                Danh Mục Khoa Đào Tạo
                            </h1>
                            <p className="text-xs leading-relaxed text-blue-100/90 sm:text-sm">
                                Khám phá các Khoa chuyên ngành, hệ thống học phần tín chỉ và tài liệu học tập chính thức tương ứng tại AGU.
                            </p>
                        </div>

                        <div className="flex flex-col gap-3.5 sm:flex-row sm:items-center shrink-0">
                            {/* Thống kê tổng quan toàn trường */}
                            <div className="flex items-center gap-4 rounded-xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur-xs">
                                <div className="text-center">
                                    <span className="block font-mono text-lg font-bold text-[#FFC72C] tabular-nums">
                                        {faculties.length}
                                    </span>
                                    <span className="text-[11px] font-medium text-blue-100">Khoa</span>
                                </div>
                                <div className="h-7 w-px bg-white/20" />
                                <div className="text-center">
                                    <span className="block font-mono text-lg font-bold text-white tabular-nums">
                                        {totalCourses}
                                    </span>
                                    <span className="text-[11px] font-medium text-blue-100">Môn học</span>
                                </div>
                                <div className="h-7 w-px bg-white/20" />
                                <div className="text-center">
                                    <span className="block font-mono text-lg font-bold text-white tabular-nums">
                                        {totalMajors}
                                    </span>
                                    <span className="text-[11px] font-medium text-blue-100">Chuyên ngành</span>
                                </div>
                            </div>

                            <Link
                                href={route('courses.index')}
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#FFC72C] px-4 py-3 text-xs font-bold text-slate-950 shadow-sm transition-all hover:bg-amber-300 active:scale-[0.98]"
                            >
                                <BookOpen size={16} />
                                <span>Kho môn học toàn trường</span>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* 2. Thanh Tìm Kiếm & Chỉ Số Kết Quả */}
                <div className="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:flex-row sm:items-center">
                    <div className="relative flex-1">
                        <Search
                            size={16}
                            className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Tìm kiếm theo tên Khoa, ký hiệu viết tắt (VD: CNTT, SP, KT)..."
                            className="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pl-10 pr-9 text-xs font-medium text-slate-800 placeholder:text-slate-400 transition-all focus:border-[#005BAA] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#005BAA]"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 cursor-pointer"
                                title="Xóa tìm kiếm"
                            >
                                <X size={14} />
                            </button>
                        )}
                    </div>

                    <div className="inline-flex shrink-0 items-center gap-1.5 self-end rounded-full border border-blue-100 bg-blue-50/70 px-3 py-1.5 text-xs font-medium text-slate-600 sm:self-center">
                        <Sparkles size={13} className="text-[#005BAA]" />
                        <span className="font-mono font-bold text-[#005BAA] tabular-nums">
                            {filteredFaculties.length}
                        </span>
                        <span>/</span>
                        <span className="font-mono tabular-nums">{faculties.length}</span>
                        <span>khoa đào tạo</span>
                    </div>
                </div>

                {/* 3. Lưới Các Thẻ Khoa */}
                {filteredFaculties.length > 0 ? (
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                        {filteredFaculties.map((faculty) => {
                            const isMyFaculty =
                                (user?.khoa?.ma_khoa && user.khoa.ma_khoa === faculty.ma_khoa) ||
                                (user?.ma_khoa && user.ma_khoa === faculty.ma_khoa);
                            const courseCount =
                                faculty.mon_hocs_count ??
                                faculty.mon_hocs?.length ??
                                faculty.monHocs?.length ??
                                0;
                            const majorCount =
                                faculty.chuyen_nganhs_count ??
                                faculty.chuyen_nganhs?.length ??
                                faculty.chuyenNganhs?.length ??
                                0;
                            const courses = faculty.mon_hocs || faculty.monHocs || [];
                            const theme = getFacultyTheme(faculty.ky_hieu_khoa);
                            const FacultyIcon = theme.Icon;

                            return (
                                <article
                                    key={faculty.ma_khoa}
                                    className={`group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md ${
                                        isMyFaculty
                                            ? 'border-[#005BAA] ring-2 ring-[#005BAA]/15'
                                            : 'border-slate-200/90 hover:border-blue-300/80'
                                    }`}
                                >
                                    {/* Thanh nhấn màu theo Khoa */}
                                    <div className={`h-1.5 w-full ${theme.accentBar}`} />

                                    <div className="flex-1 space-y-4 p-6">
                                        {/* Header Card: Icon + Mã khoa + Tên Khoa */}
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="flex items-start gap-3.5 min-w-0">
                                                <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border ${theme.iconBg}`}>
                                                    <FacultyIcon size={22} />
                                                </div>
                                                <div className="min-w-0">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className={`rounded-md border px-2 py-0.5 font-mono text-[11px] font-bold ${theme.badge}`}>
                                                            {faculty.ky_hieu_khoa}
                                                        </span>
                                                        {isMyFaculty && (
                                                            <span
                                                                className="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700"
                                                                title="Khoa bạn đang theo học"
                                                            >
                                                                <UserCheck size={12} />
                                                                <span>Khoa của bạn</span>
                                                            </span>
                                                        )}
                                                    </div>
                                                    <h3 className="mt-1.5 text-lg font-bold leading-snug text-slate-900 transition-colors group-hover:text-[#005BAA]">
                                                        {faculty.ten_khoa}
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Chỉ số: 2 thẻ mini */}
                                        <div className="grid grid-cols-2 gap-3">
                                            <div className="rounded-xl border border-blue-100/80 bg-blue-50/50 p-3">
                                                <div className="flex items-center gap-1.5 text-[11px] font-semibold text-[#005BAA]">
                                                    <BookOpen size={13} />
                                                    <span>Học phần</span>
                                                </div>
                                                <p className="mt-1 font-mono text-base font-extrabold text-slate-900 tabular-nums">
                                                    {courseCount}
                                                    <span className="ml-1 font-sans text-xs font-medium text-slate-500">môn học</span>
                                                </p>
                                            </div>

                                            <div className="rounded-xl border border-amber-100/80 bg-amber-50/50 p-3">
                                                <div className="flex items-center gap-1.5 text-[11px] font-semibold text-amber-700">
                                                    <Layers size={13} />
                                                    <span>Đào tạo</span>
                                                </div>
                                                <p className="mt-1 font-mono text-base font-extrabold text-slate-900 tabular-nums">
                                                    {majorCount}
                                                    <span className="ml-1 font-sans text-xs font-medium text-slate-500">chuyên ngành</span>
                                                </p>
                                            </div>
                                        </div>

                                        {/* Danh mục học phần tiêu biểu hoặc Trạng thái cập nhật */}
                                        <div className="space-y-2 border-t border-slate-100 pt-3.5">
                                            <span className="block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                                Học phần tiêu biểu
                                            </span>

                                            {courses.length > 0 ? (
                                                <div className="flex flex-wrap items-center gap-1.5 min-h-[2.25rem]">
                                                    {courses.slice(0, 3).map((c) => (
                                                        <Link
                                                            key={c.ma_mon_hoc}
                                                            href={route('courses.show', c.ma_hoc_phan)}
                                                            className="inline-flex max-w-[220px] items-center gap-1.5 truncate rounded-lg border border-slate-200/80 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-[#005BAA]"
                                                            title={c.ten_mon_hoc}
                                                        >
                                                            <span className="font-mono text-[10px] font-bold text-[#005BAA]">
                                                                {c.ma_hoc_phan}
                                                            </span>
                                                            <span className="truncate">{c.ten_mon_hoc}</span>
                                                        </Link>
                                                    ))}
                                                    {courses.length > 3 && (
                                                        <span className="pl-1 text-[11px] font-semibold text-slate-400">
                                                            +{courses.length - 3} môn khác
                                                        </span>
                                                    )}
                                                </div>
                                            ) : (
                                                <div className="flex min-h-[2.25rem] items-center gap-2 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-400">
                                                    <Clock size={13} className="shrink-0 text-slate-400" />
                                                    <span>Đang cập nhật danh mục học phần kỳ mới</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Chân Thẻ: Điều hướng chính xác theo ma_khoa */}
                                    <div className="flex items-center justify-between border-t border-slate-100 bg-slate-50/70 px-6 py-3.5 text-xs">
                                        <span className="font-medium text-slate-400">
                                            Hệ thống tín chỉ AGU
                                        </span>
                                        <Link
                                            href={route('courses.index', { ma_khoa: faculty.ma_khoa })}
                                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 font-semibold text-[#005BAA] shadow-2xs transition-all hover:border-[#005BAA] hover:bg-[#005BAA] hover:text-white"
                                        >
                                            <span>Xem {courseCount > 0 ? `${courseCount} môn học` : 'môn học'}</span>
                                            <ArrowRight size={13} />
                                        </Link>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                ) : (
                    /* Trạng thái không tìm thấy kết quả (Empty State) */
                    <div className="rounded-xl border border-dashed border-slate-200 bg-white p-10 text-center space-y-3">
                        <div className="w-12 h-12 bg-slate-100 text-slate-400 rounded-xl flex items-center justify-center mx-auto">
                            <Building2 size={24} />
                        </div>
                        <h3 className="text-sm font-semibold text-slate-800">
                            Không tìm thấy Khoa phù hợp
                        </h3>
                        <p className="text-xs text-slate-500 max-w-sm mx-auto">
                            Không có kết quả khớp với từ khóa tìm kiếm. Vui lòng kiểm tra lại ký hiệu hoặc tên Khoa đào tạo.
                        </p>
                        <button
                            type="button"
                            onClick={() => setSearch('')}
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium text-[#005BAA] bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors cursor-pointer"
                        >
                            <RotateCcw className="w-3.5 h-3.5" />
                            <span>Xóa bộ lọc tìm kiếm</span>
                        </button>
                    </div>
                )}
            </div>
        </StudentLayout>
    );
}
