import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    Star,
    MessageSquarePlus,
    ThumbsUp,
    ShieldCheck,
    User,
    BookOpen,
    CheckCircle2,
    AlertTriangle,
    BarChart3
} from 'lucide-react';
import CreateReviewModal from '@/Components/Reviews/CreateReviewModal';

export default function CourseReviews({ course, mon_hoc, stats, thong_ke, reviews }) {
    const { auth, flash } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);

    // Chuẩn hóa dữ liệu tương thích cả 2 chuẩn biến (Tiếng Anh & Tiếng Việt)
    const currentCourse = course || mon_hoc || {};
    const courseCode = currentCourse.course_code || currentCourse.ma_hoc_phan || '';
    const courseName = currentCourse.name || currentCourse.ten_mon_hoc || 'Học phần';
    const credits = currentCourse.credits || currentCourse.so_tin_chi || 3;
    const description = currentCourse.description || currentCourse.mo_ta || '';

    const currentStats = stats || thong_ke || {
        total_reviews: 0,
        avg_difficulty: 0,
        avg_practicality: 0,
        avg_workload: 0,
        overall_rating: 0,
    };

    const reviewItems = reviews?.data || reviews || [];

    // Tương tác nút Bình chọn Hữu ích
    const handleToggleHelpful = (reviewId) => {
        if (!auth?.user) {
            router.visit('/login');
            return;
        }

        const helpfulUrl = typeof route === 'function' && route().has('reviews.helpful')
            ? route('reviews.helpful', reviewId)
            : `/danh-gia/${reviewId}/huu-ich`;

        router.post(helpfulUrl, {}, {
            preserveScroll: true,
        });
    };

    return (
        <div className="min-h-screen bg-[#f8fafc] text-[#0f172a] antialiased">
            <Head title={`Đánh giá môn học: ${courseCode} - ${courseName}`} />

            {/* Topbar / Navigation */}
            <header className="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/"
                            className="font-black text-lg text-[#005baa] hover:opacity-80 transition-opacity"
                        >
                            AGU Academic Hub
                        </Link>
                        <span className="text-slate-300">/</span>
                        <span className="text-xs sm:text-sm font-semibold text-slate-600 truncate max-w-xs sm:max-w-md">
                            {courseCode} - {courseName}
                        </span>
                    </div>

                    <div className="flex items-center gap-3">
                        {auth?.user ? (
                            <button
                                type="button"
                                onClick={() => setIsModalOpen(true)}
                                className="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-[#005baa] hover:bg-blue-800 rounded-lg shadow-sm transition-all active:scale-95"
                            >
                                <MessageSquarePlus className="w-4 h-4" />
                                <span>Viết đánh giá</span>
                            </button>
                        ) : (
                            <Link
                                href="/login"
                                className="text-xs sm:text-sm font-semibold text-[#005baa] hover:underline"
                            >
                                Đăng nhập để đánh giá
                            </Link>
                        )}
                    </div>
                </div>
            </header>

            {/* Flash Messages */}
            <div className="max-w-6xl mx-auto px-4 sm:px-6 mt-4">
                {flash?.success && (
                    <div className="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800 flex items-center gap-2.5 shadow-sm">
                        <CheckCircle2 className="w-5 h-5 text-[#10b981] flex-shrink-0" />
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-800 flex items-center gap-2.5 shadow-sm">
                        <AlertTriangle className="w-5 h-5 text-[#e63946] flex-shrink-0" />
                        <span>{flash.error}</span>
                    </div>
                )}
            </div>

            {/* Main Content */}
            <main className="max-w-6xl mx-auto px-4 sm:px-6 py-6 space-y-6">

                {/* 1. Header Môn học & Khối Thống kê 3 tiêu chí */}
                <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                        <div className="space-y-1.5 max-w-2xl">
                            <div className="flex items-center gap-2.5">
                                <kbd className="px-2.5 py-1 text-sm font-mono font-bold bg-[#005baa]/10 text-[#005baa] rounded-md border border-[#005baa]/20">
                                    {courseCode}
                                </kbd>
                                <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                    {credits} Tín chỉ
                                </span>
                            </div>
                            <h1 className="text-2xl font-bold text-[#0f172a]">{courseName}</h1>
                            {description && (
                                <p className="text-sm text-slate-600 leading-relaxed pt-1">
                                    {description}
                                </p>
                            )}
                        </div>

                        {/* Điểm tổng quan */}
                        <div className="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80 flex-shrink-0">
                            <div className="text-center">
                                <span className="text-3xl font-black text-[#005baa]">
                                    {currentStats.overall_rating || currentStats.diem_tong_quan || '0.0'}
                                </span>
                                <span className="text-xs text-slate-500 block font-medium">/ 5.0</span>
                            </div>
                            <div className="h-10 w-px bg-slate-200" />
                            <div>
                                <div className="flex items-center gap-1 text-amber-400">
                                    {[1, 2, 3, 4, 5].map((s) => (
                                        <Star
                                            key={s}
                                            className={`w-4 h-4 ${s <= Math.round(currentStats.overall_rating || currentStats.diem_tong_quan || 0)
                                                    ? 'fill-amber-400'
                                                    : 'text-slate-200'
                                                }`}
                                        />
                                    ))}
                                </div>
                                <span className="text-xs text-slate-500 font-medium block mt-1">
                                    Dựa trên {currentStats.total_reviews || currentStats.tong_so_danh_gia || 0} bài đánh giá
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Chi tiết 3 tiêu chí trực quan */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-6">
                        <div className="p-4 rounded-lg bg-slate-50/80 border border-slate-200/60">
                            <div className="flex justify-between items-center mb-1.5">
                                <span className="text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Độ khó môn học
                                </span>
                                <span className="text-sm font-mono font-bold text-slate-800">
                                    {currentStats.avg_difficulty || currentStats.avg_do_kho || 0} / 5
                                </span>
                            </div>
                            <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                <div
                                    className="bg-amber-500 h-full rounded-full transition-all"
                                    style={{
                                        width: `${((currentStats.avg_difficulty || currentStats.avg_do_kho || 0) / 5) * 100}%`,
                                    }}
                                />
                            </div>
                        </div>

                        <div className="p-4 rounded-lg bg-slate-50/80 border border-slate-200/60">
                            <div className="flex justify-between items-center mb-1.5">
                                <span className="text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Tính thực tiễn
                                </span>
                                <span className="text-sm font-mono font-bold text-[#00a896]">
                                    {currentStats.avg_practicality || currentStats.avg_thuc_tien || 0} / 5
                                </span>
                            </div>
                            <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                <div
                                    className="bg-[#00a896] h-full rounded-full transition-all"
                                    style={{
                                        width: `${((currentStats.avg_practicality || currentStats.avg_thuc_tien || 0) / 5) * 100}%`,
                                    }}
                                />
                            </div>
                        </div>

                        <div className="p-4 rounded-lg bg-slate-50/80 border border-slate-200/60">
                            <div className="flex justify-between items-center mb-1.5">
                                <span className="text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Khối lượng bài tập
                                </span>
                                <span className="text-sm font-mono font-bold text-blue-600">
                                    {currentStats.avg_workload || currentStats.avg_ap_luc || 0} / 5
                                </span>
                            </div>
                            <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                <div
                                    className="bg-blue-600 h-full rounded-full transition-all"
                                    style={{
                                        width: `${((currentStats.avg_workload || currentStats.avg_ap_luc || 0) / 5) * 100}%`,
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* 2. Danh sách Bài đánh giá */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-bold text-[#0f172a] flex items-center gap-2">
                            <span>Bài đánh giá từ sinh viên</span>
                            <span className="text-xs bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full font-mono font-bold">
                                {reviewItems.length}
                            </span>
                        </h2>
                    </div>

                    {reviewItems.length === 0 ? (
                        <div className="p-10 text-center bg-white rounded-xl border border-slate-200">
                            <BookOpen className="w-10 h-10 text-slate-300 mx-auto mb-3" />
                            <h3 className="text-base font-semibold text-slate-700">Chưa có bài đánh giá nào</h3>
                            <p className="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">
                                Hãy là người đầu tiên chia sẻ kinh nghiệm học môn này cho các bạn sinh viên khóa sau!
                            </p>
                            {auth?.user && (
                                <button
                                    onClick={() => setIsModalOpen(true)}
                                    className="px-4 py-2 text-xs font-semibold text-white bg-[#005baa] hover:bg-blue-800 rounded-lg shadow-sm"
                                >
                                    Viết đánh giá đầu tiên
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {reviewItems.map((review) => {
                                const revId = review.id || review.ma_danh_gia;
                                const author = review.author || review.tac_gia || {};
                                const isAnon = author.is_anonymous || author.la_an_danh;
                                const term = review.academic_term || review.hoc_ky;
                                const content = review.content || review.nhan_xet;
                                const createdAt = review.created_at || review.ngay_tao;
                                const helpfulCount = review.helpful_votes_count || 0;
                                const isVoted = review.is_voted_by_me || false;

                                return (
                                    <div
                                        key={revId}
                                        className="p-5 bg-white rounded-xl border border-slate-200/90 shadow-sm space-y-3.5 hover:shadow-md transition-shadow"
                                    >
                                        {/* Tác giả & Header bài review */}
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 font-bold">
                                                    {isAnon ? (
                                                        <ShieldCheck className="w-5 h-5 text-[#00a896]" />
                                                    ) : (
                                                        <User className="w-5 h-5 text-[#005baa]" />
                                                    )}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-semibold text-sm text-[#0f172a]">
                                                            {author.display_name || author.ten_hien_thi}
                                                        </span>
                                                        {author.note || author.ghi_chu ? (
                                                            <span className="text-[11px] text-[#005baa] bg-blue-50 px-2 py-0.5 rounded font-medium">
                                                                {author.note || author.ghi_chu}
                                                            </span>
                                                        ) : null}
                                                    </div>
                                                    <div className="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                                        <span>Học kỳ: {term}</span>
                                                        <span>•</span>
                                                        <span>{createdAt}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Điểm 3 tiêu chí dạng badge nhỏ */}
                                            <div className="flex items-center gap-2">
                                                <span className="text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded font-medium">
                                                    Độ khó: <strong>{review.difficulty_score || review.diem_do_kho}★</strong>
                                                </span>
                                                <span className="text-xs bg-teal-50 text-[#00a896] px-2 py-1 rounded font-medium">
                                                    Thực tiễn: <strong>{review.practicality_score || review.diem_thuc_tien}★</strong>
                                                </span>
                                                <span className="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded font-medium">
                                                    Khối lượng: <strong>{review.workload_score || review.diem_ap_luc_bai_tap}★</strong>
                                                </span>
                                            </div>
                                        </div>

                                        {/* Nội dung nhận xét */}
                                        <p className="text-sm text-slate-700 leading-relaxed whitespace-pre-line pl-1">
                                            {content}
                                        </p>

                                        {/* Footer tương tác: Nút Hữu ích */}
                                        <div className="pt-2 flex items-center justify-between border-t border-slate-100 text-xs">
                                            <button
                                                type="button"
                                                onClick={() => handleToggleHelpful(revId)}
                                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border font-medium transition-colors ${isVoted
                                                        ? 'bg-blue-50 border-blue-200 text-[#005baa]'
                                                        : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                                                    }`}
                                            >
                                                <ThumbsUp className={`w-3.5 h-3.5 ${isVoted ? 'fill-[#005baa]' : ''}`} />
                                                <span>Hữu ích ({helpfulCount})</span>
                                            </button>

                                            {(review.can_modify || review.co_quyen_chinh_sua) && (
                                                <span className="text-xs text-slate-400 italic">
                                                    Bài viết của bạn
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </main>

            {/* Modal Viết đánh giá */}
            <CreateReviewModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                course={currentCourse}
            />
        </div>
    );
}