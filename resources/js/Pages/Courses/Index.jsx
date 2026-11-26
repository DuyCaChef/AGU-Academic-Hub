import React, { useState, useEffect, useMemo, useRef } from "react";
import { Head, Link, router } from "@inertiajs/react";
import StudentLayout from "@/Layouts/StudentLayout";
import Pagination from "@/Components/Common/Pagination";
import {
    Search,
    BookOpen,
    GraduationCap,
    Building2,
    Layers,
    X,
    ArrowRight,
    SlidersHorizontal,
    BookX,
    RotateCcw,
    AlertCircle,
} from "lucide-react";

export default function CoursesIndex({
    courses = { data: [], links: [] },
    faculties = [],
    filters = {},
}) {
    // 1. Quản lý State cho Bộ lọc
    const [search, setSearch] = useState(filters.search || "");
    const [selectedFaculty, setSelectedFaculty] = useState(
        filters.ma_khoa ? String(filters.ma_khoa) : ""
    );
    const [selectedMajor, setSelectedMajor] = useState(
        filters.ma_chuyen_nganh ? String(filters.ma_chuyen_nganh) : ""
    );
    const [isLoading, setIsLoading] = useState(false);

    // Cờ kiểm soát lần render đầu tiên để tránh push query thừa
    const isFirstRender = useRef(true);

    // 2. Tính toán danh sách chuyên ngành khả dụng theo Khoa đã chọn
    const availableMajors = useMemo(() => {
        if (!selectedFaculty) {
            // Gom toàn bộ chuyên ngành từ các khoa nếu chưa chọn khoa
            return faculties.flatMap((f) => f.chuyen_nganhs || []);
        }
        const currentFaculty = faculties.find(
            (f) => String(f.ma_khoa) === String(selectedFaculty)
        );
        return currentFaculty ? currentFaculty.chuyen_nganhs || [] : [];
    }, [faculties, selectedFaculty]);

    // 3. Hàm gửi request lọc dữ liệu
    const applyFilters = (newFilters = {}) => {
        setIsLoading(true);
        const query = {
            search: newFilters.search !== undefined ? newFilters.search : search,
            ma_khoa:
                newFilters.ma_khoa !== undefined
                    ? newFilters.ma_khoa
                    : selectedFaculty,
            ma_chuyen_nganh:
                newFilters.ma_chuyen_nganh !== undefined
                    ? newFilters.ma_chuyen_nganh
                    : selectedMajor,
        };

        // Loại bỏ các trường rỗng
        Object.keys(query).forEach((key) => {
            if (!query[key]) {
                delete query[key];
            }
        });

        router.get(route("courses.index"), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    };

    // 4. Debounced Search (300ms) khi gõ từ khóa
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timer = setTimeout(() => {
            if (search !== (filters.search || "")) {
                applyFilters({ search });
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    // 5. Xử lý khi thay đổi Khoa
    const handleFacultyChange = (e) => {
        const newFaculty = e.target.value;
        setSelectedFaculty(newFaculty);

        // Kiểm tra xem chuyên ngành hiện tại có thuộc khoa mới hay không
        let newMajor = selectedMajor;
        if (newFaculty) {
            const facultyObj = faculties.find(
                (f) => String(f.ma_khoa) === String(newFaculty)
            );
            const majorBelongs = facultyObj?.chuyen_nganhs?.some(
                (m) => String(m.ma_chuyen_nganh) === String(selectedMajor)
            );
            if (!majorBelongs) {
                newMajor = "";
                setSelectedMajor("");
            }
        }

        applyFilters({ ma_khoa: newFaculty, ma_chuyen_nganh: newMajor });
    };

    // 6. Xử lý khi thay đổi Chuyên ngành
    const handleMajorChange = (e) => {
        const newMajor = e.target.value;
        setSelectedMajor(newMajor);
        applyFilters({ ma_chuyen_nganh: newMajor });
    };

    // 7. Xóa toàn bộ bộ lọc
    const handleResetFilters = () => {
        setSearch("");
        setSelectedFaculty("");
        setSelectedMajor("");
        setIsLoading(true);
        router.get(
            route("courses.index"),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsLoading(false),
            }
        );
    };

    const hasActiveFilters = Boolean(
        search || selectedFaculty || selectedMajor
    );

    return (
        <StudentLayout title="Kho Môn Học">
            <Head title="Kho Môn Học - AGU Academic Hub" />

            {/* Header & Breadcrumb */}
            <div className="space-y-4">
                <nav className="flex items-center gap-2 text-xs text-slate-500">
                    <Link
                        href={route("dashboard")}
                        className="hover:text-[#005baa] transition-colors"
                    >
                        Trang chủ
                    </Link>
                    <span>/</span>
                    <span className="font-semibold text-slate-800">
                        Kho Môn Học
                    </span>
                </nav>

                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl flex items-center gap-3">
                            <BookOpen className="text-[#005baa]" size={28} />
                            Kho Môn Học
                        </h1>
                        <p className="mt-1.5 text-sm text-slate-600 max-w-3xl">
                            Tra cứu danh mục môn học, mã học phần, số tín chỉ và chuyên ngành đào tạo chính thức tại Trường Đại học An Giang.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-[#005baa] border border-blue-200">
                            <GraduationCap size={15} />
                            Tổng số: {courses.total ?? 0} môn học
                        </span>
                    </div>
                </div>
            </div>

            {/* Search & Filter Bar */}
            <div className="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-xs space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
                    {/* Ô tìm kiếm theo Mã hoặc Tên môn */}
                    <div className="md:col-span-5 relative">
                        <Search
                            size={18}
                            className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                        />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Tìm kiếm theo mã học phần (COS101...) hoặc tên môn..."
                            className="w-full h-10 pl-10 pr-9 rounded-xl border border-slate-300 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa] transition-colors"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch("")}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                title="Xóa từ khóa"
                            >
                                <X size={16} />
                            </button>
                        )}
                    </div>

                    {/* Bộ lọc Khoa */}
                    <div className="md:col-span-4 relative">
                        <Building2
                            size={16}
                            className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                        />
                        <select
                            value={selectedFaculty}
                            onChange={handleFacultyChange}
                            className="w-full h-10 pl-9 pr-8 rounded-xl border border-slate-300 text-sm text-slate-900 focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa] transition-colors bg-white truncate"
                        >
                            <option value="">Tất cả các khoa</option>
                            {faculties.map((f) => (
                                <option key={f.ma_khoa} value={f.ma_khoa}>
                                    {f.ten_khoa}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Bộ lọc Chuyên ngành */}
                    <div className="md:col-span-3 relative">
                        <Layers
                            size={16}
                            className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                        />
                        <select
                            value={selectedMajor}
                            onChange={handleMajorChange}
                            className="w-full h-10 pl-9 pr-8 rounded-xl border border-slate-300 text-sm text-slate-900 focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa] transition-colors bg-white truncate"
                        >
                            <option value="">Tất cả chuyên ngành</option>
                            {availableMajors.map((m) => (
                                <option
                                    key={m.ma_chuyen_nganh}
                                    value={m.ma_chuyen_nganh}
                                >
                                    {m.ky_hieu_nganh
                                        ? `${m.ky_hieu_nganh} - ${m.ten_chuyen_nganh}`
                                        : m.ten_chuyen_nganh}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Dải trạng thái & nút Reset */}
                <div className="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 text-xs">
                    <div className="flex items-center gap-2 text-slate-600">
                        <SlidersHorizontal size={14} className="text-[#005baa]" />
                        <span>
                            Đang hiển thị <strong>{courses.data?.length ?? 0}</strong> trên tổng số <strong>{courses.total ?? 0}</strong> môn học
                        </span>
                        {isLoading && (
                            <span className="inline-flex items-center gap-1 text-[#005baa] font-medium animate-pulse ml-2">
                                Đang tải dữ liệu...
                            </span>
                        )}
                    </div>

                    {hasActiveFilters && (
                        <button
                            type="button"
                            onClick={handleResetFilters}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition-colors font-medium text-xs"
                        >
                            <RotateCcw size={13} />
                            <span>Xóa bộ lọc</span>
                        </button>
                    )}
                </div>
            </div>

            {/* Course Cards Grid */}
            {courses.data && courses.data.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
                    {courses.data.map((course) => {
                        const majors = course.chuyen_nganhs || [];

                        return (
                            <article
                                key={course.ma_mon_hoc}
                                className="group flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md hover:border-blue-300 transition-all duration-200"
                            >
                                <div className="space-y-3.5">
                                    {/* Header thẻ: Mã học phần & Tín chỉ */}
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-blue-50 text-[#005baa] border border-blue-200/80 uppercase tracking-wide">
                                            {course.ma_hoc_phan}
                                        </span>

                                        <span className="inline-flex items-center gap-1 text-xs font-medium text-slate-600 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-100">
                                            <BookOpen size={13} className="text-slate-400" />
                                            {course.so_tin_chi} tín chỉ
                                        </span>
                                    </div>

                                    {/* Tên môn học */}
                                    <h3
                                        className="font-bold text-base text-slate-900 group-hover:text-[#005baa] transition-colors leading-snug line-clamp-2"
                                        title={course.ten_mon_hoc}
                                    >
                                        <Link
                                            href={route("courses.show", course.ma_hoc_phan)}
                                            className="hover:underline focus:outline-none"
                                        >
                                            {course.ten_mon_hoc}
                                        </Link>
                                    </h3>

                                    {/* Khoa chủ quản */}
                                    <div className="flex items-center gap-1.5 text-xs text-slate-600">
                                        <Building2
                                            size={14}
                                            className="text-slate-400 shrink-0"
                                        />
                                        <span
                                            className="truncate"
                                            title={course.khoa?.ten_khoa || "Chưa phân khoa"}
                                        >
                                            {course.khoa?.ten_khoa || "Khoa trực thuộc"}
                                        </span>
                                    </div>

                                    {/* Danh sách Chuyên ngành */}
                                    <div className="pt-2 border-t border-slate-100 space-y-1.5">
                                        <div className="text-[11px] font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                                            <Layers size={11} />
                                            Chuyên ngành áp dụng:
                                        </div>

                                        {majors.length > 0 ? (
                                            <div className="flex flex-wrap gap-1.5">
                                                {majors.slice(0, 3).map((m) => (
                                                    <span
                                                        key={m.ma_chuyen_nganh}
                                                        className="text-[11px] bg-slate-50 border border-slate-200/80 px-2 py-0.5 rounded-md text-slate-700 font-medium truncate max-w-[170px]"
                                                        title={m.ten_chuyen_nganh}
                                                    >
                                                        {m.ky_hieu_nganh ? `${m.ky_hieu_nganh} - ` : ""}
                                                        {m.ten_chuyen_nganh}
                                                    </span>
                                                ))}
                                                {majors.length > 3 && (
                                                    <span className="text-[11px] text-slate-400 self-center font-medium">
                                                        +{majors.length - 3} ngành khác
                                                    </span>
                                                )}
                                            </div>
                                        ) : (
                                            <p className="text-xs text-slate-400 italic">
                                                Môn học đại cương / Không thuộc chuyên ngành
                                            </p>
                                        )}
                                    </div>

                                    {/* Mô tả ngắn nếu có */}
                                    {course.mo_ta && (
                                        <p className="text-xs text-slate-500 line-clamp-2 leading-relaxed pt-1">
                                            {course.mo_ta}
                                        </p>
                                    )}
                                </div>

                                {/* Footer Action */}
                                <div className="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between">
                                    <span className="text-xs text-slate-400">
                                        Đại học An Giang
                                    </span>

                                    <Link
                                        href={route("courses.show", course.ma_hoc_phan)}
                                        className="inline-flex items-center gap-1 text-xs font-bold text-[#005baa] hover:underline group-hover:translate-x-0.5 transition-transform"
                                    >
                                        <span>Xem môn học</span>
                                        <ArrowRight size={13} />
                                    </Link>
                                </div>
                            </article>
                        );
                    })}
                </div>
            ) : (
                /* Empty State */
                <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center">
                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 mb-4">
                        <BookX size={32} />
                    </div>
                    <h3 className="text-base font-bold text-slate-800">
                        Không tìm thấy môn học nào
                    </h3>
                    <p className="mt-1.5 text-sm text-slate-500 max-w-md mx-auto">
                        Không có môn học nào khớp với từ khóa tìm kiếm hoặc bộ lọc bạn đã chọn. Vui lòng thử lại với từ khóa khác hoặc xóa bộ lọc.
                    </p>
                    {hasActiveFilters && (
                        <button
                            type="button"
                            onClick={handleResetFilters}
                            className="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs"
                        >
                            <RotateCcw size={14} />
                            <span>Xóa toàn bộ bộ lọc</span>
                        </button>
                    )}
                </div>
            )}

            {/* Phân trang */}
            {courses.links && (
                <div className="pt-2">
                    <Pagination links={courses.links} />
                </div>
            )}
        </StudentLayout>
    );
}

