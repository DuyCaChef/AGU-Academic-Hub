import React, { useState, useEffect, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import DocumentCard from '@/Components/domain/DocumentCard';
import {
    Search,
    Filter,
    Upload,
    BookOpen,
    Sparkles,
    FileSearch,
    X
} from 'lucide-react';

export default function Index({ documents = { data: [] }, filters = {}, course = null }) {
    const [search, setSearch] = useState(filters.search || '');
    const [category, setCategory] = useState(filters.category || 'ALL');
    const [sort, setSort] = useState(filters.sort || 'latest');
    const [isLoading, setIsLoading] = useState(false);

    // Debounced Search Handler (300ms)
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters.search || '')) {
                fetchFilteredData();
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    const fetchFilteredData = useCallback(() => {
        setIsLoading(true);
        router.get(
            route('courses.index'),
            { search, category, sort },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsLoading(false)
            }
        );
    }, [search, category, sort]);

    const handleCategoryChange = (newCat) => {
        setCategory(newCat);
        setIsLoading(true);
        router.get(
            route('courses.index'),
            { search, category: newCat, sort },
            { preserveState: true, preserveScroll: true, onFinish: () => setIsLoading(false) }
        );
    };

    return (
        <StudentLayout title={course ? `Tài liệu - ${course.ten_mon_hoc}` : 'Kho Tài Liệu Học Tập'}>
            <Head title={course ? course.ten_mon_hoc : 'Kho Tài Liệu Học Thuật AGU'} />

            {/* Header Section */}
            <div className="bg-surface-default border border-border-default rounded-xl p-6 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <span className="p-2 bg-brand-primary/10 text-brand-primary rounded-lg">
                            <BookOpen size={20} />
                        </span>
                        <h1 className="text-2xl font-bold text-text-primary">
                            {course ? course.ten_mon_hoc : 'Kho Tài Liệu Học Thuật'}
                        </h1>
                        {course && (
                            <span className="font-mono text-xs font-bold bg-brand-primary text-white px-2.5 py-1 rounded-full">
                                {course.ma_hoc_phan}
                            </span>
                        )}
                    </div>
                    <p className="text-sm text-text-secondary">
                        {course
                            ? `Tổng hợp slide, đề thi và bài giảng học phần ${course.ten_mon_hoc}.`
                            : 'Tra cứu & chia sẻ tài liệu học tập chính thức từ sinh viên Đại học An Giang.'}
                    </p>
                </div>

                <Link
                    href={route('documents.create')}
                    className="inline-flex items-center gap-2 bg-brand-primary hover:bg-blue-800 text-white font-semibold text-sm px-4 py-2.5 rounded-md shadow-xs transition-all shrink-0 justify-center"
                >
                    <Upload size={18} />
                    <span>Đóng góp tài liệu</span>
                </Link>
            </div>

            {/* Filter & Search Bar */}
            <div className="bg-surface-default border border-border-default rounded-xl p-4 shadow-2xs space-y-4">
                <div className="flex flex-col md:flex-row items-center gap-3">
                    {/* Search Input */}
                    <div className="relative flex-1 w-full">
                        <Search size={18} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-text-secondary" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Tìm kiếm theo tiêu đề tài liệu, tên môn, mã học phần..."
                            className="w-full pl-10 pr-9 py-2 text-sm bg-surface-page border border-border-default rounded-md focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hover:text-text-primary"
                            >
                                <X size={16} />
                            </button>
                        )}
                    </div>

                    {/* Sắp xếp */}
                    <select
                        value={sort}
                        onChange={(e) => {
                            setSort(e.target.value);
                            fetchFilteredData();
                        }}
                        className="w-full md:w-48 py-2 px-3 text-sm bg-surface-page border border-border-default rounded-md focus:outline-none focus:border-brand-primary"
                    >
                        <option value="latest">Mới nhất</option>
                        <option value="downloads">Lượt tải nhiều nhất</option>
                        <option value="upvotes">Đánh giá cao nhất</option>
                    </select>
                </div>

                {/* Filter Tabs Phân Loại */}
                <div className="flex items-center gap-2 overflow-x-auto pt-1 pb-1">
                    <span className="text-xs font-semibold text-text-secondary shrink-0 flex items-center gap-1">
                        <Filter size={14} /> Phân loại:
                    </span>
                    {[
                        { id: 'ALL', label: 'Tất cả' },
                        { id: 'DE_THI', label: 'Đề Thi' },
                        { id: 'BAI_GIANG', label: 'Bài Giảng' },
                        { id: 'DO_AN', label: 'Đồ Án' },
                        { id: 'BAI_TAP', label: 'Bài Tập' }
                    ].map((tab) => (
                        <button
                            key={tab.id}
                            type="button"
                            onClick={() => handleCategoryChange(tab.id)}
                            className={`px-3 py-1 rounded-full text-xs font-semibold shrink-0 transition-colors ${
                                category === tab.id
                                    ? 'bg-brand-primary text-white shadow-2xs'
                                    : 'bg-surface-page text-text-secondary hover:bg-border-default/60'
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>
            </div>

            {/* Document Grid / Skeleton UI */}
            {isLoading ? (
                /* Skeleton Loading State */
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {[1, 2, 3, 4, 5, 6].map((n) => (
                        <div key={n} className="bg-surface-default border border-border-default rounded-xl p-4 animate-pulse space-y-3">
                            <div className="h-4 bg-border-default/60 rounded w-1/3"></div>
                            <div className="h-5 bg-border-default/60 rounded w-3/4"></div>
                            <div className="h-3 bg-border-default/60 rounded w-1/2"></div>
                            <div className="pt-3 border-t border-border-default/40 flex justify-between">
                                <div className="h-4 bg-border-default/60 rounded w-1/4"></div>
                                <div className="h-4 bg-border-default/60 rounded w-1/6"></div>
                            </div>
                        </div>
                    ))}
                </div>
            ) : documents.data && documents.data.length > 0 ? (
                /* Document Grid */
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {documents.data.map((doc) => (
                        <DocumentCard key={doc.ma_tai_lieu} document={doc} />
                    ))}
                </div>
            ) : (
                /* Empty State */
                <div className="bg-surface-default border border-border-default rounded-xl p-12 text-center space-y-4">
                    <div className="w-16 h-16 bg-brand-primary/10 text-brand-primary rounded-full flex items-center justify-center mx-auto">
                        <FileSearch size={32} />
                    </div>
                    <div>
                        <h3 className="font-bold text-lg text-text-primary">Không tìm thấy tài liệu phù hợp</h3>
                        <p className="text-sm text-text-secondary max-w-md mx-auto mt-1">
                            Thử điều chỉnh từ khóa tìm kiếm, thay đổi bộ lọc phân loại hoặc là người đầu tiên tải lên tài liệu cho môn học này!
                        </p>
                    </div>
                    <Link
                        href={route('documents.create')}
                        className="inline-flex items-center gap-2 bg-brand-primary hover:bg-blue-800 text-white font-medium text-sm px-4 py-2 rounded-md shadow-xs transition-all"
                    >
                        <Upload size={16} />
                        <span>Tải lên tài liệu ngay</span>
                    </Link>
                </div>
            )}
        </StudentLayout>
    );
}