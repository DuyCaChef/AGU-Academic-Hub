import React, { useState, useMemo } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import CreateReviewModal from '@/Components/Reviews/CreateReviewModal';
import Pagination from '@/Components/Common/Pagination';
import {
    Star,
    MessageSquarePlus,
    ThumbsUp,
    ShieldCheck,
    User,
    BookOpen,
    CheckCircle2,
    AlertTriangle,
    BarChart3,
    ChevronRight,
    FileText,
    ArrowLeft,
    Flag,
    X,
    SlidersHorizontal,
    Sparkles,
    Check,
} from 'lucide-react';

export default function CourseReviews({ course, mon_hoc, stats, thong_ke, reviews }) {
    const { auth, flash } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [activeFilter, setActiveFilter] = useState('ALL'); // 'ALL' | 'VERIFIED' | 'HELPFUL'

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

    const overallRating = parseFloat(currentStats.overall_rating || currentStats.diem_tong_quan || 0).toFixed(1);
    const totalReviewsCount = currentStats.total_reviews || currentStats.tong_so_danh_gia || 0;
    const avgDifficulty = parseFloat(currentStats.avg_difficulty || currentStats.do_kho_trung_binh || 0);
    const avgPracticality = parseFloat(currentStats.avg_practicality || currentStats.tinh_thuc_tien_trung_binh || 0);
    const avgWorkload = parseFloat(currentStats.avg_workload || currentStats.ap_luc_trung_binh || 0);

    const reviewItems = reviews?.data || reviews || [];
    const paginationLinks = reviews?.links || [];

    // Client-side quick filter / sort for smoother scanning
    const displayedReviews = useMemo(() => {
        if (!Array.isArray(reviewItems)) return [];
        let list = [...reviewItems];
        if (activeFilter === 'VERIFIED') {
            list = list.filter((r) => r.is_verified_student || r.sinh_vien_da_xac_thuc);
        } else if (activeFilter === 'HELPFUL') {
            list.sort((a, b) => {
                const countA = a.helpful_votes_count || a.so_luot_huu_ich || 0;
                const countB = b.helpful_votes_count || b.so_luot_huu_ich || 0;
                return countB - countA;
            });
        }
        return list;
    }, [reviewItems, activeFilter]);

    // State Báo cáo vi phạm đánh giá
    const [reportModalReview, setReportModalReview] = useState(null);
    const [reportTargetType, setReportTargetType] = useState('MON_HOC');
    const [reportLecturerName, setReportLecturerName] = useState('');
    const [reportReason, setReportReason] = useState('NGON_TU_KICH_DONG');
    const [reportDetail, setReportDetail] = useState('');
    const [isSubmittingReport, setIsSubmittingReport] = useState(false);
    const [reportError, setReportError] = useState('');

    const handleOpenReportModal = (review) => {
        if (!auth?.user) {
            router.visit(route('login'));
            return;
        }
        setReportModalReview(review);
        setReportTargetType('MON_HOC');
        setReportLecturerName('');
        setReportReason('NGON_TU_KICH_DONG');
        setReportDetail('');
        setReportError('');
    };

    const handleSubmitReport = (e) => {
        e.preventDefault();
        if (!reportModalReview) return;

        setIsSubmittingReport(true);
        setReportError('');

        const revId = reportModalReview.id || reportModalReview.ma_danh_gia;
        const reportUrl = typeof route === 'function' && route().has('reviews.report')
            ? route('reviews.report', revId)
            : `/danh-gia/${revId}/bao-cao`;

        router.post(
            reportUrl,
            {
                ly_do: reportReason,
                loai_doi_tuong: reportTargetType,
                ten_giang_vien: reportLecturerName,
                chi_tiet: reportDetail,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReportModalReview(null);
                    setReportDetail('');
                    setReportLecturerName('');
                },
                onError: (errs) => {
                    setReportError(errs.report || errs.ly_do || 'Có lỗi xảy ra khi gửi báo cáo.');
                },
                onFinish: () => setIsSubmittingReport(false),
            }
        );
    };

    // Tương tác nút Bình chọn Hữu ích
    const handleToggleHelpful = (reviewId, isOwn = false) => {
        if (!auth?.user) {
            router.visit(route('login'));
            return;
        }

        if (isOwn) {
            alert('Bạn không thể tự bình chọn đánh giá của chính mình.');
            return;
        }

        const helpfulUrl = typeof route === 'function' && route().has('reviews.helpful')
            ? route('reviews.helpful', reviewId)
            : `/danh-gia/${reviewId}/huu-ich`;

        router.post(helpfulUrl, {}, {
            preserveScroll: true,
        });
    };

    // Descriptor ngữ nghĩa cho thước đo học thuật
    const getMetricDescriptor = (type, val) => {
        const num = parseFloat(val) || 0;
        if (num === 0) return 'Chưa đủ dữ liệu đánh giá';
        if (type === 'difficulty') {
            if (num <= 2.2) return 'Dễ thở • Tiếp thu nhanh';
            if (num <= 3.6) return 'Cân bằng • Thử thách vừa phải';
            return 'Khó • Cần đầu tư nhiều thời gian';
        }
        if (type === 'practicality') {
            if (num <= 2.5) return 'Nặng lý thuyết nền tảng';
            if (num <= 3.8) return 'Cân bằng giữa lý thuyết & bài tập';
            return 'Thực tiễn cao • Ứng dụng sát thực tế';
        }
        if (type === 'workload') {
            if (num <= 2.5) return 'Thong thả • Ít áp lực deadline';
            if (num <= 3.8) return 'Vừa sức • Deadline định kỳ';
            return 'Khối lượng lớn • Áp lực dồn dập';
        }
        return '';
    };

    return (
        <StudentLayout title={`Đánh giá môn học: ${courseCode} - ${courseName}`}>
            <Head title={`Đánh giá môn học: ${courseCode} - ${courseName} - AGU Academic Hub`} />

            <div className="space-y-6 max-w-7xl mx-auto">
                {/* 1. Breadcrumbs Navigation */}
                <nav className="flex items-center gap-1.5 text-xs text-text-secondary overflow-x-auto py-1">
                    <Link
                        href={route('courses.index')}
                        className="hover:text-brand-primary flex items-center gap-1 shrink-0 transition-colors"
                    >
                        <BookOpen size={14} />
                        <span>Kho môn học</span>
                    </Link>
                    <ChevronRight size={13} className="shrink-0 text-text-muted" />

                    <Link
                        href={route('courses.show', courseCode)}
                        className="hover:text-brand-primary shrink-0 transition-colors"
                    >
                        <span className="font-mono font-semibold">{courseCode}</span>
                        <span className="ml-1 hidden sm:inline">- {courseName}</span>
                    </Link>
                    <ChevronRight size={13} className="shrink-0 text-text-muted" />

                    <span className="text-text-primary font-medium">
                        Đánh giá & Nhận xét
                    </span>
                </nav>

                {/* 2. DIRECTION A: SPLIT-SCREEN ACADEMIC DASHBOARD */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                    {/* CỘT TRÁI (Sticky Sidebar - 4/12 cols): Tóm tắt môn & Thước đo học thuật */}
                    <aside className="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-20 space-y-5">
                        {/* Course Overview Card */}
                        <div className="bg-surface-default border border-border-default rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
                            {/* Mã môn & Số tín chỉ */}
                            <div className="space-y-3">
                                <div className="flex items-center justify-between gap-2">
                                    <span className="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-brand-primary/10 text-brand-primary border border-brand-primary/20 tracking-wider">
                                        {courseCode}
                                    </span>
                                    <span className="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-700">
                                        {credits} Tín chỉ
                                    </span>
                                </div>
                                <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight leading-snug">
                                    {courseName}
                                </h1>
                                {description && (
                                    <p className="text-xs text-slate-600 leading-relaxed line-clamp-3">
                                        {description}
                                    </p>
                                )}
                            </div>

                            {/* Khối Điểm tổng quan */}
                            <div className="pt-4 border-t border-border-default/80">
                                <div className="flex items-end justify-between gap-4">
                                    <div>
                                        <span className="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-1">
                                            Điểm đánh giá chung
                                        </span>
                                        <div className="flex items-baseline gap-2">
                                            <span className="text-4xl font-extrabold text-slate-900 font-mono tabular-nums tracking-tight">
                                                {overallRating}
                                            </span>
                                            <span className="text-sm font-semibold text-slate-400">/ 5.0</span>
                                        </div>
                                    </div>
                                    <div className="text-right space-y-1">
                                        <div className="flex items-center gap-1 justify-end text-amber-500">
                                            {[1, 2, 3, 4, 5].map((star) => (
                                                <Star
                                                    key={star}
                                                    size={16}
                                                    className={`fill-amber-400 ${star <= Math.round(Number(overallRating))
                                                            ? 'text-amber-400'
                                                            : 'text-slate-200 fill-slate-200'
                                                        }`}
                                                />
                                            ))}
                                        </div>
                                        <span className="text-xs text-slate-500 font-medium block">
                                            {totalReviewsCount} sinh viên đánh giá
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* UNSLOP CORE: THƯỚC ĐO 3 TIÊU CHÍ (Academic Metric Meters) */}
                            <div className="pt-4 border-t border-border-default/80 space-y-4">
                                <span className="text-xs font-bold uppercase tracking-wider text-slate-700 block">
                                    Chỉ số học thuật cốt lõi
                                </span>

                                {/* 1. Độ khó môn học */}
                                <div className="space-y-1.5">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-semibold text-slate-800">Độ khó học phần</span>
                                        <span className="font-mono font-bold text-slate-900 tabular-nums">
                                            {avgDifficulty > 0 ? `${avgDifficulty.toFixed(1)} / 5.0` : '--'}
                                        </span>
                                    </div>
                                    <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-amber-400 to-amber-500 rounded-full transition-all duration-500"
                                            style={{ width: `${Math.min(100, (avgDifficulty / 5) * 100)}%` }}
                                        />
                                    </div>
                                    <p className="text-[11px] text-slate-500 font-medium">
                                        {getMetricDescriptor('difficulty', avgDifficulty)}
                                    </p>
                                </div>

                                {/* 2. Tính thực tiễn */}
                                <div className="space-y-1.5">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-semibold text-slate-800">Tính thực tiễn & Đồ án</span>
                                        <span className="font-mono font-bold text-slate-900 tabular-nums">
                                            {avgPracticality > 0 ? `${avgPracticality.toFixed(1)} / 5.0` : '--'}
                                        </span>
                                    </div>
                                    <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500"
                                            style={{ width: `${Math.min(100, (avgPracticality / 5) * 100)}%` }}
                                        />
                                    </div>
                                    <p className="text-[11px] text-slate-500 font-medium">
                                        {getMetricDescriptor('practicality', avgPracticality)}
                                    </p>
                                </div>

                                {/* 3. Khối lượng bài tập */}
                                <div className="space-y-1.5">
                                    <div className="flex items-center justify-between text-xs">
                                        <span className="font-semibold text-slate-800">Khối lượng bài tập & Deadline</span>
                                        <span className="font-mono font-bold text-slate-900 tabular-nums">
                                            {avgWorkload > 0 ? `${avgWorkload.toFixed(1)} / 5.0` : '--'}
                                        </span>
                                    </div>
                                    <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-gradient-to-r from-blue-400 to-brand-primary rounded-full transition-all duration-500"
                                            style={{ width: `${Math.min(100, (avgWorkload / 5) * 100)}%` }}
                                        />
                                    </div>
                                    <p className="text-[11px] text-slate-500 font-medium">
                                        {getMetricDescriptor('workload', avgWorkload)}
                                    </p>
                                </div>
                            </div>

                            {/* Cụm Action Buttons */}
                            <div className="pt-4 border-t border-border-default/80 space-y-2.5">
                                {auth?.user ? (
                                    <button
                                        type="button"
                                        onClick={() => setIsModalOpen(true)}
                                        className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-white bg-brand-primary hover:bg-blue-800 rounded-xl shadow-xs transition active:scale-[0.98]"
                                    >
                                        <MessageSquarePlus size={16} />
                                        <span>Viết đánh giá của bạn</span>
                                    </button>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-brand-primary bg-brand-primary/10 hover:bg-brand-primary/15 border border-brand-primary/20 rounded-xl transition"
                                    >
                                        <User size={15} />
                                        <span>Đăng nhập để đánh giá môn</span>
                                    </Link>
                                )}

                                <Link
                                    href={route('courses.show', courseCode)}
                                    className="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-border-default rounded-xl transition"
                                >
                                    <FileText size={15} className="text-slate-500" />
                                    <span>Xem kho tài liệu môn học</span>
                                </Link>
                            </div>
                        </div>
                    </aside>

                    {/* CỘT PHẢI (Review Feed - 7/12 cols): Danh sách đánh giá & Bộ lọc */}
                    <main className="lg:col-span-7 xl:col-span-8 space-y-4">
                        {/* Feed Header & Filters */}
                        <div className="bg-surface-default border border-border-default rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div className="flex items-center gap-2.5">
                                <div className="w-8 h-8 rounded-lg bg-brand-primary/10 text-brand-primary flex items-center justify-center shrink-0">
                                    <BarChart3 size={18} />
                                </div>
                                <div>
                                    <h2 className="text-sm sm:text-base font-bold text-slate-900 leading-tight">
                                        Nhận xét từ sinh viên
                                    </h2>
                                    <p className="text-xs text-slate-500 font-medium">
                                        Hiển thị {displayedReviews.length} / {totalReviewsCount} đóng góp
                                    </p>
                                </div>
                            </div>

                            {/* Quick Filter Tabs */}
                            {reviewItems.length > 0 && (
                                <div className="inline-flex items-center bg-slate-100 p-1 rounded-xl self-start sm:self-auto">
                                    <button
                                        type="button"
                                        onClick={() => setActiveFilter('ALL')}
                                        className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-all ${activeFilter === 'ALL'
                                                ? 'bg-white text-slate-900 shadow-2xs'
                                                : 'text-slate-600 hover:text-slate-900'
                                            }`}
                                    >
                                        Tất cả
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setActiveFilter('VERIFIED')}
                                        className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1 ${activeFilter === 'VERIFIED'
                                                ? 'bg-white text-emerald-800 shadow-2xs'
                                                : 'text-slate-600 hover:text-slate-900'
                                            }`}
                                    >
                                        <ShieldCheck size={13} className="text-emerald-600" />
                                        <span>Đã học môn</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setActiveFilter('HELPFUL')}
                                        className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-all flex items-center gap-1 ${activeFilter === 'HELPFUL'
                                                ? 'bg-white text-brand-primary shadow-2xs'
                                                : 'text-slate-600 hover:text-slate-900'
                                            }`}
                                    >
                                        <ThumbsUp size={12} className="text-brand-primary" />
                                        <span>Hữu ích nhất</span>
                                    </button>
                                </div>
                            )}
                        </div>

                        {/* Reviews List */}
                        {displayedReviews.length === 0 ? (
                            <div className="bg-surface-default border border-border-default rounded-2xl p-10 text-center space-y-4 shadow-xs">
                                <div className="w-14 h-14 bg-brand-primary/10 text-brand-primary rounded-2xl flex items-center justify-center mx-auto">
                                    <MessageSquarePlus size={26} />
                                </div>
                                <div className="space-y-1.5 max-w-sm mx-auto">
                                    <h3 className="font-bold text-base text-slate-900">
                                        {activeFilter === 'ALL' ? 'Chưa có bài đánh giá nào' : 'Không có bài đánh giá phù hợp bộ lọc'}
                                    </h3>
                                    <p className="text-xs text-slate-500 leading-relaxed">
                                        {activeFilter === 'ALL'
                                            ? 'Bạn đã từng học môn này? Hãy để lại những lưu ý thực tế để giúp các bạn sinh viên khóa sau chuẩn bị tốt hơn!'
                                            : 'Hãy thử chuyển sang tab "Tất cả" để xem toàn bộ nhận xét của các bạn sinh viên khác.'}
                                    </p>
                                </div>
                                {auth?.user && activeFilter === 'ALL' && (
                                    <button
                                        type="button"
                                        onClick={() => setIsModalOpen(true)}
                                        className="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-white bg-brand-primary hover:bg-blue-800 rounded-xl shadow-xs transition"
                                    >
                                        <MessageSquarePlus size={15} />
                                        <span>Viết đánh giá đầu tiên</span>
                                    </button>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-3.5">
                                {displayedReviews.map((review) => {
                                    const revId = review.id || review.ma_danh_gia;
                                    const author = review.user || review.nguoi_dung || {};
                                    const authorName = author.name || author.ho_ten || 'Sinh viên AGU';
                                    const content = review.comment || review.nhan_xet_chi_tiet || '';
                                    const isAnonymous = review.is_anonymous || review.an_danh;
                                    const isVerified = review.is_verified_student || review.sinh_vien_da_xac_thuc;
                                    const isVoted = review.is_helpful_voted || review.da_binh_chon_huu_ich;
                                    const isOwnReview = Boolean(review.can_modify || review.co_quyen_chinh_sua || review.is_my_review);
                                    const helpfulCount = review.helpful_votes_count || review.so_luot_huu_ich || 0;
                                    const diffScore = review.difficulty_score || review.diem_do_kho || 0;
                                    const pracScore = review.practicality_score || review.diem_thuc_tien || 0;
                                    const workScore = review.workload_score || review.diem_ap_luc_bai_tap || 0;

                                    const dateFormatted = review.created_at
                                        ? new Date(review.created_at).toLocaleDateString('vi-VN', {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                        })
                                        : 'Gần đây';

                                    return (
                                        <article
                                            key={revId}
                                            className="bg-surface-default border border-border-default rounded-2xl p-5 shadow-xs hover:border-slate-300 transition-colors space-y-3.5"
                                        >
                                            {/* Card Top: Author & Micro Metrics */}
                                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border-default/70">
                                                {/* Author Profile */}
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-xl bg-slate-100 border border-border-default flex items-center justify-center font-bold text-xs text-slate-700 shrink-0 select-none">
                                                        {isAnonymous ? (
                                                            <User size={18} className="text-slate-400" />
                                                        ) : (
                                                            <span className="text-brand-primary font-bold">
                                                                {authorName.charAt(0).toUpperCase()}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2 flex-wrap">
                                                            <span className="font-bold text-sm text-slate-900">
                                                                {isAnonymous ? 'Sinh viên ẩn danh' : authorName}
                                                            </span>
                                                            {isVerified && (
                                                                <span
                                                                    className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/80"
                                                                    title="Đã xác thực từng học và có điểm môn học này tại AGU"
                                                                >
                                                                    <ShieldCheck size={12} className="text-emerald-600" />
                                                                    <span>Đã học môn</span>
                                                                </span>
                                                            )}
                                                        </div>
                                                        <span className="text-[11px] text-slate-400 font-medium">
                                                            {dateFormatted}
                                                        </span>
                                                    </div>
                                                </div>

                                                {/* UNSLOP: Micro Metrics Strip (Gộp 3 chỉ số thanh lịch, không phân mảnh) */}
                                                <div className="flex items-center gap-2 text-xs font-mono bg-slate-50/80 px-2.5 py-1.5 rounded-lg border border-border-default shrink-0 self-start sm:self-auto">
                                                    <div className="flex items-center gap-1">
                                                        <span className="text-slate-500 font-sans text-[11px]">Độ khó:</span>
                                                        <span className="font-bold text-amber-700">{diffScore}★</span>
                                                    </div>
                                                    <span className="text-slate-300">/</span>
                                                    <div className="flex items-center gap-1">
                                                        <span className="text-slate-500 font-sans text-[11px]">Thực tế:</span>
                                                        <span className="font-bold text-teal-700">{pracScore}★</span>
                                                    </div>
                                                    <span className="text-slate-300">/</span>
                                                    <div className="flex items-center gap-1">
                                                        <span className="text-slate-500 font-sans text-[11px]">Deadline:</span>
                                                        <span className="font-bold text-blue-700">{workScore}★</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Review Comment Content */}
                                            <p className="text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line tracking-normal">
                                                {content}
                                            </p>

                                            {/* Card Footer: Helpful button & Flag */}
                                            <div className="pt-2 flex items-center justify-between text-xs">
                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleHelpful(revId, isOwnReview)}
                                                    disabled={isOwnReview}
                                                    className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border font-semibold transition-all ${isOwnReview
                                                            ? 'bg-slate-50 border-border-default text-slate-400 cursor-not-allowed opacity-60'
                                                            : isVoted
                                                                ? 'bg-brand-primary/10 border-brand-primary/30 text-brand-primary shadow-2xs'
                                                                : 'bg-white border-border-default text-slate-600 hover:bg-slate-50 hover:border-slate-300'
                                                        }`}
                                                    title={
                                                        isOwnReview
                                                            ? 'Bạn không thể tự bình chọn đánh giá của chính mình'
                                                            : isVoted
                                                                ? 'Bỏ bình chọn hữu ích'
                                                                : 'Đánh giá này hữu ích với bạn'
                                                    }
                                                >
                                                    <ThumbsUp size={13} className={isVoted ? 'fill-brand-primary' : ''} />
                                                    <span>Hữu ích ({helpfulCount})</span>
                                                </button>

                                                {isOwnReview ? (
                                                    <span className="text-xs font-medium text-slate-400 italic">
                                                        Bài đánh giá của bạn
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleOpenReportModal(review)}
                                                        className="inline-flex items-center gap-1 text-slate-400 hover:text-rose-600 transition-colors ml-auto py-1 px-2 rounded-lg hover:bg-rose-50 font-medium"
                                                        title="Báo cáo vi phạm tiêu chuẩn cộng đồng"
                                                    >
                                                        <Flag size={12} />
                                                        <span>Báo cáo</span>
                                                    </button>
                                                )}
                                            </div>
                                        </article>
                                    );
                                })}

                                {/* Pagination */}
                                {paginationLinks.length > 3 && (
                                    <div className="pt-3">
                                        <Pagination links={paginationLinks} />
                                    </div>
                                )}
                            </div>
                        )}
                    </main>
                </div>
            </div>

            {/* Modal Viết đánh giá (giữ nguyên props & logic hiện tại) */}
            <CreateReviewModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                course={currentCourse}
            />

            {/* Modal Báo cáo vi phạm bài đánh giá (Unslop Redesign) */}
            {reportModalReview && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200">
                    <div className="w-full max-w-md rounded-2xl bg-surface-default p-6 shadow-2xl border border-border-default animate-in zoom-in-95 duration-200">
                        <div className="flex items-center justify-between pb-3 border-b border-border-default">
                            <div className="flex items-center gap-2 text-rose-600 font-bold text-base">
                                <Flag size={18} />
                                <h3>Báo cáo vi phạm đánh giá</h3>
                            </div>
                            <button
                                type="button"
                                onClick={() => setReportModalReview(null)}
                                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
                            >
                                <X size={18} />
                            </button>
                        </div>

                        <form onSubmit={handleSubmitReport} className="mt-4 space-y-4">
                            {/* Phân loại đối tượng phản ánh */}
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Đối tượng vi phạm <span className="text-rose-600">*</span>
                                </label>
                                <div className="mt-1.5 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setReportTargetType('MON_HOC')}
                                        className={`flex items-center justify-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold transition-all ${reportTargetType === 'MON_HOC'
                                                ? 'border-brand-primary bg-brand-primary/10 text-brand-primary shadow-2xs'
                                                : 'border-border-default text-slate-600 hover:bg-slate-50'
                                            }`}
                                    >
                                        <BookOpen size={14} />
                                        <span>Môn học / Học phần</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setReportTargetType('GIANG_VIEN')}
                                        className={`flex items-center justify-center gap-1.5 rounded-xl border p-2.5 text-xs font-semibold transition-all ${reportTargetType === 'GIANG_VIEN'
                                                ? 'border-amber-600 bg-amber-50 text-amber-800 shadow-2xs'
                                                : 'border-border-default text-slate-600 hover:bg-slate-50'
                                            }`}
                                    >
                                        <User size={14} />
                                        <span>Giảng viên giảng dạy</span>
                                    </button>
                                </div>
                            </div>

                            {/* Tên giảng viên nếu chọn đối tượng Giảng viên */}
                            {reportTargetType === 'GIANG_VIEN' && (
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                        Tên giảng viên bị ảnh hưởng (tùy chọn)
                                    </label>
                                    <input
                                        type="text"
                                        value={reportLecturerName}
                                        onChange={(e) => setReportLecturerName(e.target.value)}
                                        placeholder="VD: TS. Nguyễn Văn A..."
                                        className="mt-1 w-full rounded-xl border border-border-default p-2.5 text-xs focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                                    />
                                </div>
                            )}

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Lý do báo cáo vi phạm <span className="text-rose-600">*</span>
                                </label>
                                <div className="mt-2 space-y-1.5">
                                    {[
                                        { value: 'NGON_TU_KICH_DONG', label: 'Ngôn từ kích động / xúc phạm / thô tục' },
                                        { value: 'BOI_NHO', label: 'Bôi nhọ / Vu khống giảng viên hoặc nhà trường' },
                                        { value: 'QUAY_ROI', label: 'Quấy rối / Công kích sinh viên khác' },
                                        { value: 'SPAM', label: 'Spam / Rao vặt / Quảng cáo' },
                                        { value: 'KHAC', label: 'Lý do khác' },
                                    ].map((opt) => (
                                        <label
                                            key={opt.value}
                                            className={`flex items-center gap-3 rounded-xl border p-2.5 text-xs cursor-pointer transition-colors ${reportReason === opt.value
                                                    ? 'border-brand-primary bg-brand-primary/5 text-brand-primary font-semibold'
                                                    : 'border-border-default hover:bg-slate-50 text-slate-700'
                                                }`}
                                        >
                                            <input
                                                type="radio"
                                                name="report_reason"
                                                value={opt.value}
                                                checked={reportReason === opt.value}
                                                onChange={(e) => {
                                                    const val = e.target.value;
                                                    setReportReason(val);
                                                    if (val === 'BOI_NHO') {
                                                        setReportTargetType('GIANG_VIEN');
                                                    }
                                                }}
                                                className="text-brand-primary focus:ring-brand-primary h-4 w-4"
                                            />
                                            <span>{opt.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Chi tiết phản ánh (tùy chọn)
                                </label>
                                <textarea
                                    rows={3}
                                    value={reportDetail}
                                    onChange={(e) => setReportDetail(e.target.value)}
                                    placeholder="Cung cấp thêm ngữ cảnh vi phạm để Ban kiểm duyệt thẩm định..."
                                    className="mt-1.5 w-full rounded-xl border border-border-default p-2.5 text-xs focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                                />
                            </div>

                            {reportError && (
                                <p className="text-xs text-rose-600 font-medium">{reportError}</p>
                            )}

                            <div className="flex items-center justify-end gap-2 pt-3 border-t border-border-default">
                                <button
                                    type="button"
                                    onClick={() => setReportModalReview(null)}
                                    className="rounded-xl border border-border-default px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors"
                                >
                                    Hủy bỏ
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmittingReport}
                                    className="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-700 disabled:opacity-50 transition-colors"
                                >
                                    {isSubmittingReport ? 'Đang gửi...' : 'Gửi báo cáo vi phạm'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </StudentLayout>
    );
}
