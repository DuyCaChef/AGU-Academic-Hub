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

    return (
        <StudentLayout title="Danh Mục Khoa Đào Tạo">
            <Head title="Danh Mục Khoa Đào Tạo - AGU Academic Hub" />

            <div className="space-y-6">
                {/* 1. Header Banner: AGU Navy Solid with Subtle Grid */}
                <div className="relative overflow-hidden rounded-2xl bg-[#005BAA] text-white p-6 sm:p-8 shadow-xs">
                    {/* Subtle dot pattern */}
                    <div className="pointer-events-none absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]" />

                    <div className="relative flex flex-col md:flex-row md:items-center justify-between gap-6 z-10">
                        <div className="space-y-2 max-w-xl">
                            <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-blue-100 backdrop-blur-xs border border-white/10">
                                <GraduationCap size={14} className="text-amber-300" />
                                <span>Cơ cấu tổ chức đào tạo • Trường Đại học An Giang</span>
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-semibold tracking-tight text-white">
                                Danh Mục Khoa Đào Tạo
                            </h1>
                            <p className="text-xs sm:text-sm text-blue-100 leading-relaxed">
                                Khám phá các Khoa chuyên ngành, hệ thống học phần tín chỉ và tài liệu học tập chính thức tương ứng tại AGU.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3 shrink-0">
                            <Link
                                href={route('courses.index')}
                                className="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-xs font-semibold text-[#005BAA] shadow-xs transition-colors hover:bg-blue-50"
                            >
                                <BookOpen size={16} />
                                <span>Kho môn học toàn trường</span>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* 2. Thanh Tìm Kiếm & Chỉ Số Kết Quả */}
                <div className="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="relative flex-1">
                        <Search
                            size={16}
                            className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                        />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Tìm kiếm theo tên Khoa, ký hiệu viết tắt (VD: CNTT, SP, KT)..."
                            className="w-full pl-9 pr-4 py-2 text-xs font-medium rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:border-[#005BAA] focus:ring-1 focus:ring-[#005BAA] transition-colors"
                        />
                    </div>

                    <div className="text-xs text-slate-500 font-medium shrink-0 flex items-center gap-1.5 self-end sm:self-center">
                        <span className="font-mono font-semibold text-slate-700 tabular-nums">
                            {filteredFaculties.length}
                        </span>
                        <span>/</span>
                        <span className="font-mono text-slate-500 tabular-nums">
                            {faculties.length}
                        </span>
                        <span>khoa đào tạo</span>
                    </div>
                </div>

                {/* 3. Lưới Các Thẻ Khoa (Ma trận 2x2 Cân Xứng) */}
                {filteredFaculties.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {filteredFaculties.map((faculty) => {
                            const isMyFaculty = user?.ma_khoa === faculty.ma_khoa;
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

                            return (
                                <article
                                    key={faculty.ma_khoa}
                                    className={`group rounded-xl border bg-white p-6 shadow-xs transition-colors flex flex-col justify-between ${
                                        isMyFaculty
                                            ? 'border-[#005BAA] ring-1 ring-[#005BAA]/20'
                                            : 'border-slate-200/80 hover:border-slate-300'
                                    }`}
                                >
                                    <div className="space-y-4">
                                        {/* Header Card: Mã khoa & Huy hiệu */}
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="font-mono text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-[#005BAA] border border-blue-100">
                                                {faculty.ky_hieu_khoa}
                                            </span>

                                            {isMyFaculty && (
                                                <span
                                                    className="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200/80"
                                                    title="Khoa bạn đang theo học"
                                                >
                                                    <UserCheck size={12} />
                                                    <span>Khoa của bạn</span>
                                                </span>
                                            )}
                                        </div>

                                        {/* Tên Khoa */}
                                        <div>
                                            <h3 className="text-base font-semibold text-slate-900 group-hover:text-[#005BAA] transition-colors leading-snug">
                                                {faculty.ten_khoa}
                                            </h3>

                                            {/* Chỉ số: Số môn học & Chuyên ngành */}
                                            <div className="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                                <span className="flex items-center gap-1.5">
                                                    <BookOpen size={14} className="text-[#005BAA]" />
                                                    <span>
                                                        <strong className="font-mono font-semibold text-slate-800 tabular-nums">
                                                            {courseCount}
                                                        </strong>{' '}
                                                        môn học
                                                    </span>
                                                </span>
                                                <span className="flex items-center gap-1.5">
                                                    <Layers size={14} className="text-amber-600" />
                                                    <span>
                                                        <strong className="font-mono font-semibold text-slate-800 tabular-nums">
                                                            {majorCount}
                                                        </strong>{' '}
                                                        chuyên ngành
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        {/* Danh mục học phần tiêu biểu hoặc Trạng thái cập nhật */}
                                        <div className="pt-3 border-t border-slate-100 space-y-2">
                                            <span className="text-[11px] font-medium text-slate-500 block">
                                                Học phần tiêu biểu:
                                            </span>

                                            {courses.length > 0 ? (
                                                <div className="flex flex-wrap gap-1.5 min-h-[2rem] items-center">
                                                    {courses.slice(0, 3).map((c) => (
                                                        <span
                                                            key={c.ma_mon_hoc}
                                                            className="text-[11px] bg-slate-50 border border-slate-200/80 px-2 py-0.5 rounded text-slate-700 font-medium inline-flex items-center gap-1 truncate max-w-[200px]"
                                                            title={c.ten_mon_hoc}
                                                        >
                                                            <span className="font-mono text-[#005BAA] text-[10px]">
                                                                {c.ma_hoc_phan}
                                                            </span>
                                                            <span className="truncate">{c.ten_mon_hoc}</span>
                                                        </span>
                                                    ))}
                                                    {courses.length > 3 && (
                                                        <span className="text-[11px] text-slate-400 font-medium pl-1">
                                                            +{courses.length - 3} môn khác
                                                        </span>
                                                    )}
                                                </div>
                                            ) : (
                                                <div className="rounded-lg border border-dashed border-slate-200 bg-slate-50/50 px-3 py-1.5 text-xs text-slate-400 flex items-center gap-2 min-h-[2rem]">
                                                    <Clock size={13} className="text-slate-400 shrink-0" />
                                                    <span>Đang cập nhật danh mục học phần kỳ mới</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Chân Thẻ: Điều hướng chính xác theo ma_khoa */}
                                    <div className="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                        <span className="text-slate-400">
                                            Học phần tín chỉ AGU
                                        </span>
                                        <Link
                                            href={route('courses.index', { ma_khoa: faculty.ma_khoa })}
                                            className="inline-flex items-center gap-1.5 font-semibold text-[#005BAA] hover:underline transition-colors"
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
