import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import {
    GraduationCap,
    BookOpen,
    Building2,
    Search,
    ChevronRight,
    Sparkles,
    Layers,
    UserCheck,
    ArrowRight
} from 'lucide-react';

export default function FacultiesIndex({ faculties = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [search, setSearch] = useState('');

    const filteredFaculties = faculties.filter((faculty) => {
        const query = search.toLowerCase();
        return (
            (faculty.ten_khoa || '').toLowerCase().includes(query) ||
            (faculty.ky_hieu_khoa || '').toLowerCase().includes(query)
        );
    });

    return (
        <StudentLayout title="Danh Sách Khoa & Viện Đào Tạo">
            <Head title="Danh Sách Khoa & Viện Đào Tạo - AGU Academic Hub" />

            <div className="space-y-6">
                {/* 1. Header Banner */}
                <div className="bg-gradient-to-r from-[#005baa] to-blue-800 text-white rounded-2xl p-6 sm:p-8 shadow-sm relative overflow-hidden">
                    <div className="pointer-events-none absolute -right-6 -bottom-6 w-56 h-56 bg-white/5 rounded-full blur-2xl" />

                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                        <div className="space-y-2">
                            <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-blue-100">
                                <GraduationCap size={14} className="text-amber-300" />
                                CƠ CẤU ĐÀO TẠO TRƯỜNG ĐẠI HỌC AN GIANG
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                Danh Sách Khoa & Viện Đào Tạo
                            </h1>
                            <p className="text-blue-100 text-sm max-w-xl leading-relaxed">
                                Khám phá các Khoa chuyên ngành, hệ thống học phần và tài liệu học tập chính thức tương ứng tại AGU.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3 shrink-0">
                            <Link
                                href={route('courses.index')}
                                className="inline-flex items-center gap-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold px-4 py-2.5 rounded-xl text-sm shadow-xs transition active:scale-95"
                            >
                                <BookOpen size={17} />
                                Kho môn học toàn trường
                            </Link>
                        </div>
                    </div>
                </div>

                {/* 2. Search Bar */}
                <div className="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
                    <div className="relative">
                        <Search
                            size={18}
                            className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Tìm kiếm theo tên Khoa, ký hiệu viết tắt (VD: CNTT, SP, KT)..."
                            className="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-all"
                        />
                    </div>
                </div>

                {/* 3. Faculties Grid */}
                {filteredFaculties.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        {filteredFaculties.map((faculty) => {
                            const isMyFaculty = user?.ma_khoa === faculty.ma_khoa;
                            const courseCount = faculty.mon_hocs_count ?? faculty.mon_hocs?.length ?? faculty.monHocs?.length ?? 0;
                            const majorCount = faculty.chuyen_nganhs_count ?? faculty.chuyen_nganhs?.length ?? faculty.chuyenNganhs?.length ?? 0;
                            const courses = faculty.mon_hocs || faculty.monHocs || [];

                            return (
                                <div
                                    key={faculty.ma_khoa}
                                    className={`bg-white border rounded-2xl p-5 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between group ${
                                        isMyFaculty
                                            ? 'border-[#005baa] ring-2 ring-[#005baa]/10'
                                            : 'border-slate-200/80 hover:border-[#005baa]/40'
                                    }`}
                                >
                                    <div>
                                        {/* Header Card */}
                                        <div className="flex items-start justify-between gap-2 mb-3">
                                            <span className="font-mono text-xs font-bold bg-[#005baa]/10 text-[#005baa] px-2.5 py-1 rounded-lg border border-[#005baa]/20">
                                                {faculty.ky_hieu_khoa}
                                            </span>

                                            {isMyFaculty && (
                                                <div
                                                    className="flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200"
                                                    title="Khoa bạn đang theo học"
                                                >
                                                    <UserCheck size={12} />
                                                    <span>Khoa của bạn</span>
                                                </div>
                                            )}
                                        </div>

                                        {/* Faculty Name */}
                                        <h3 className="font-bold text-base text-slate-900 group-hover:text-[#005baa] transition-colors leading-snug">
                                            {faculty.ten_khoa}
                                        </h3>

                                        {/* Stat Counters */}
                                        <div className="flex items-center gap-4 mt-3 text-xs text-slate-500">
                                            <span className="flex items-center gap-1.5">
                                                <BookOpen size={14} className="text-[#005baa]" />
                                                <strong className="text-slate-700">{courseCount}</strong> môn học
                                            </span>
                                            {majorCount > 0 && (
                                                <span className="flex items-center gap-1.5">
                                                    <Layers size={14} className="text-amber-600" />
                                                    <strong className="text-slate-700">{majorCount}</strong> chuyên ngành
                                                </span>
                                            )}
                                        </div>

                                        {/* Sample Courses List */}
                                        {courses.length > 0 && (
                                            <div className="mt-4 pt-3 border-t border-slate-100 space-y-1.5">
                                                <div className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                                    Học phần tiêu biểu:
                                                </div>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {courses.slice(0, 3).map((c) => (
                                                        <span
                                                            key={c.ma_mon_hoc}
                                                            className="text-[11px] bg-slate-50 border border-slate-200/80 px-2 py-0.5 rounded text-slate-700 font-medium truncate max-w-[180px]"
                                                            title={c.ten_mon_hoc}
                                                        >
                                                            {c.ma_hoc_phan} - {c.ten_mon_hoc}
                                                        </span>
                                                    ))}
                                                    {courses.length > 3 && (
                                                        <span className="text-[11px] text-slate-400 self-center">
                                                            +{courses.length - 3} môn khác
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Footer Action */}
                                    <div className="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                                        <span className="text-xs text-slate-400">
                                            Tài liệu & đề thi AGU
                                        </span>
                                        <Link
                                            href={route('courses.index')}
                                            className="inline-flex items-center gap-1 text-xs font-bold text-[#005baa] hover:underline group-hover:translate-x-0.5 transition-transform"
                                        >
                                            <span>Xem môn học</span>
                                            <ArrowRight size={13} />
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    /* Empty State */
                    <div className="bg-white border border-slate-200/80 rounded-2xl p-12 text-center space-y-3">
                        <div className="w-14 h-14 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto">
                            <Building2 size={28} />
                        </div>
                        <h3 className="font-bold text-base text-slate-800">
                            Không tìm thấy Khoa phù hợp
                        </h3>
                        <p className="text-xs text-slate-500 max-w-sm mx-auto">
                            Không có kết quả khớp với từ khóa tìm kiếm. Vui lòng kiểm tra lại ký hiệu hoặc tên Khoa.
                        </p>
                        <button
                            type="button"
                            onClick={() => setSearch('')}
                            className="text-xs font-semibold text-[#005baa] hover:underline"
                        >
                            Xóa bộ lọc tìm kiếm
                        </button>
                    </div>
                )}
            </div>
        </StudentLayout>
    );
}

