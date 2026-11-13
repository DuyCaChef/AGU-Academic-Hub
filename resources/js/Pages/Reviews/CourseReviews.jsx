import React, { useState } from 'react';
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
} from 'lucide-react';

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
    const paginationLinks = reviews?.links || [];

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

    return (
        <StudentLayout title={`Đánh giá môn học: ${courseCode} - ${courseName}`}>
            <Head title={`Đánh giá môn học: ${courseCode} - ${courseName} - AGU Academic Hub`} />

            <div className="space-y-6">
                {/* Breadcrumbs Navigation */}
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

                {/* 1. Header Môn học & Khối Thống kê 3 tiêu chí */}
                <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-6">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                        <div className="space-y-2 max-w-2xl">
                            <div className="flex items-center gap-2.5">
                                <span className="px-2.5 py-1 text-xs font-mono font-bold bg-[#005baa]/10 text-[#005baa] rounded-lg border border-[#005baa]/20">
                                    {courseCode}
                                </span>
                                <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                                    {credits} Tín chỉ
                                </span>
                            </div>
                            <h1 className="text-2xl font-bold text-slate-900">{courseName}</h1>
                            {description && (
                                <p className="text-xs sm:text-sm text-slate-600 leading-relaxed pt-1">
                                    {description}
                                </p>
                            )}
                        </div>

                        {/* Điểm tổng quan & Nút viết đánh giá */}
                        <div className="flex flex-col sm:flex-row md:flex-col items-start sm:items-center md:items-end gap-3 shrink-0">
                            <div className="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80 w-full sm:w-auto">
                                <div className="text-center">
                                    <span className="text-3xl font-black text-[#005baa]">
                                        {currentStats.overall_rating || currentStats.diem_tong_quan || '0.0'}
                                    </span>
                                    <span className="text-xs text-slate-500 block font-medium">/ 5.0</span>
                                </div>
                                <div className="border-l border-slate-200 pl-4 space-y-0.5">
                                    <div className="flex items-center gap-1 text-amber-500">
                                        {[1, 2, 3, 4, 5].map((star) => (
                                            <Star
                                                key={star}
                                                size={15}
                                                className={`fill-amber-400 ${
                                                    star <= Math.round(Number(currentStats.overall_rating || currentStats.diem_tong_quan || 0))
                                                        ? 'text-amber-400'
                                                        : 'text-slate-200 fill-slate-200'
                                                }`}
                                            />
                                        ))}
                                    </div>
                                    <span className="text-xs text-slate-500 block font-medium">
                                        {currentStats.total_reviews || currentStats.tong_so_danh_gia || 0} bài đánh giá
                                    </span>
                                </div>
                            </div>

                            <div className="flex items-center gap-2 w-full sm:w-auto">
                                <Link
                                    href={route('courses.show', courseCode)}
                                    className="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                                >
                                    <FileText size={14} />
                                    <span>Tài liệu môn</span>
                                </Link>

                                {auth?.user ? (
                                    <button
                                        type="button"
                                        onClick={() => setIsModalOpen(true)}
                                        className="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#005baa] hover:bg-blue-800 rounded-xl shadow-xs transition active:scale-95"
                                    >
                                        <MessageSquarePlus size={15} />
                                        <span>Viết đánh giá</span>
                                    </button>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="text-xs font-semibold text-[#005baa] hover:underline"
                                    >
                                        Đăng nhập để đánh giá
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Khối 3 tiêu chí cốt lõi */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div className="bg-amber-50/50 border border-amber-100 p-4 rounded-xl">
                            <span className="text-xs font-semibold text-amber-800 uppercase tracking-wider block mb-1">
                                Độ khó môn học
                            </span>
                            <div className="flex items-baseline gap-2">
                                <span className="text-2xl font-bold text-amber-900">
                                    {currentStats.avg_difficulty || currentStats.do_kho_trung_binh || '0.0'}
                                </span>
                                <span className="text-xs text-amber-700">/ 5.0</span>
                            </div>
                            <p className="text-[11px] text-amber-700/80 mt-1">
                                1 = Rất dễ, 5 = Cực kỳ thử thách
                            </p>
                        </div>

                        <div className="bg-teal-50/50 border border-teal-100 p-4 rounded-xl">
                            <span className="text-xs font-semibold text-teal-800 uppercase tracking-wider block mb-1">
                                Tính thực tiễn
                            </span>
                            <div className="flex items-baseline gap-2">
                                <span className="text-2xl font-bold text-teal-900">
                                    {currentStats.avg_practicality || currentStats.tinh_thuc_tien_trung_binh || '0.0'}
                                </span>
                                <span className="text-xs text-teal-700">/ 5.0</span>
                            </div>
                            <p className="text-[11px] text-teal-700/80 mt-1">
                                Áp dụng tốt vào thực tế & đồ án
                            </p>
                        </div>

                        <div className="bg-blue-50/50 border border-blue-100 p-4 rounded-xl">
                            <span className="text-xs font-semibold text-blue-800 uppercase tracking-wider block mb-1">
                                Khối lượng bài tập
                            </span>
                            <div className="flex items-baseline gap-2">
                                <span className="text-2xl font-bold text-blue-900">
                                    {currentStats.avg_workload || currentStats.ap_luc_trung_binh || '0.0'}
                                </span>
                                <span className="text-xs text-blue-700">/ 5.0</span>
                            </div>
                            <p className="text-[11px] text-blue-700/80 mt-1">
                                Tần suất deadline & bài kiểm tra
                            </p>
                        </div>
                    </div>
                </div>

                {/* 2. Danh Sách Bài Đánh Giá */}
                <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-5">
                    <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div className="flex items-center gap-2">
                            <BarChart3 size={18} className="text-[#005baa]" />
                            <h2 className="text-base font-bold text-slate-900">
                                Nhận xét từ sinh viên ({reviewItems.length})
                            </h2>
                        </div>
                    </div>

                    {reviewItems.length === 0 ? (
                        <div className="py-12 text-center space-y-3">
                            <div className="w-12 h-12 bg-blue-50 text-[#005baa] rounded-full flex items-center justify-center mx-auto">
                                <MessageSquarePlus size={24} />
                            </div>
                            <h3 className="font-bold text-base text-slate-800">Chưa có bài đánh giá nào</h3>
                            <p className="text-xs text-slate-500 max-w-sm mx-auto">
                                Bạn đã học môn học này? Hãy là người đầu tiên chia sẻ kinh nghiệm cho các khóa sinh viên sau!
                            </p>
                            {auth?.user && (
                                <button
                                    type="button"
                                    onClick={() => setIsModalOpen(true)}
                                    className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#005baa] hover:bg-blue-800 rounded-xl shadow-xs transition"
                                >
                                    <MessageSquarePlus size={14} />
                                    <span>Viết đánh giá đầu tiên</span>
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {reviewItems.map((review) => {
                                const revId = review.id || review.ma_danh_gia;
                                const author = review.user || review.nguoi_dung || {};
                                const authorName = author.name || author.ho_ten || 'Sinh viên AGU';
                                const authorMssv = author.mssv ? `MSSV: ${author.mssv}` : 'Thành viên ẩn danh';
                                const content = review.comment || review.nhan_xet_chi_tiet || '';
                                const isAnonymous = review.is_anonymous || review.an_danh;
                                const isVerified = review.is_verified_student || review.sinh_vien_da_xac_thuc;
                                const isVoted = review.is_helpful_voted || review.da_binh_chon_huu_ich;
                                const isOwnReview = Boolean(review.can_modify || review.co_quyen_chinh_sua || review.is_my_review);
                                const helpfulCount = review.helpful_votes_count || review.so_luot_huu_ich || 0;
                                const dateFormatted = review.created_at
                                    ? new Date(review.created_at).toLocaleDateString('vi-VN', {
                                          year: 'numeric',
                                          month: 'long',
                                          day: 'numeric',
                                      })
                                    : 'Gần đây';

                                return (
                                    <div
                                        key={revId}
                                        className="p-5 rounded-xl border border-slate-200/80 hover:border-slate-300 transition-colors bg-white space-y-3"
                                    >
                                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                                            <div className="flex items-center gap-3">
                                                <div className="w-9 h-9 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                    {isAnonymous ? <User size={16} /> : authorName.charAt(0).toUpperCase()}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-bold text-sm text-slate-900">
                                                            {isAnonymous ? 'Sinh viên ẩn danh' : authorName}
                                                        </span>
                                                        {isVerified && (
                                                            <span
                                                                className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200"
                                                                title="Đã xác thực từng học môn này tại AGU"
                                                            >
                                                                <ShieldCheck size={12} />
                                                                <span>Đã học môn này</span>
                                                            </span>
                                                        )}
                                                    </div>
                                                    <span className="text-[11px] text-slate-400">
                                                        {dateFormatted}
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Score Badges */}
                                            <div className="flex flex-wrap items-center gap-1.5">
                                                <span className="text-xs bg-amber-50 text-amber-800 px-2 py-1 rounded font-medium border border-amber-100">
                                                    Độ khó: <strong>{review.difficulty_score || review.diem_do_kho}★</strong>
                                                </span>
                                                <span className="text-xs bg-teal-50 text-teal-800 px-2 py-1 rounded font-medium border border-teal-100">
                                                    Thực tiễn: <strong>{review.practicality_score || review.diem_thuc_tien}★</strong>
                                                </span>
                                                <span className="text-xs bg-blue-50 text-blue-800 px-2 py-1 rounded font-medium border border-blue-100">
                                                    Khối lượng: <strong>{review.workload_score || review.diem_ap_luc_bai_tap}★</strong>
                                                </span>
                                            </div>
                                        </div>

                                        {/* Content */}
                                        <p className="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                                            {content}
                                        </p>

                                        {/* Interaction footer */}
                                        <div className="pt-2 flex items-center justify-between border-t border-slate-100 text-xs">
                                            <button
                                                type="button"
                                                onClick={() => handleToggleHelpful(revId, isOwnReview)}
                                                disabled={isOwnReview}
                                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border font-medium transition-colors ${
                                                    isOwnReview
                                                        ? 'bg-slate-50 border-slate-200 text-slate-400 cursor-not-allowed opacity-60'
                                                        : isVoted
                                                        ? 'bg-blue-50 border-blue-200 text-[#005baa]'
                                                        : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                                                }`}
                                                title={isOwnReview ? 'Bạn không thể tự bình chọn đánh giá của chính mình' : (isVoted ? 'Bỏ bình chọn hữu ích' : 'Bình chọn hữu ích')}
                                            >
                                                <ThumbsUp size={13} className={isVoted ? 'fill-[#005baa]' : ''} />
                                                <span>Hữu ích ({helpfulCount})</span>
                                            </button>

                                            {(review.can_modify || review.co_quyen_chinh_sua) ? (
                                                <span className="text-xs text-slate-400 italic">
                                                    Bài viết của bạn
                                                </span>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => handleOpenReportModal(review)}
                                                    className="inline-flex items-center gap-1 text-slate-400 hover:text-rose-600 transition-colors ml-auto py-1 px-2 rounded hover:bg-rose-50"
                                                    title="Báo cáo vi phạm tiêu chuẩn cộng đồng"
                                                >
                                                    <Flag size={12} />
                                                    <span>Báo cáo</span>
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}

                            {/* Pagination */}
                            {paginationLinks.length > 3 && (
                                <Pagination links={paginationLinks} className="border-t border-slate-100 mt-4" />
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Viết đánh giá */}
            <CreateReviewModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                course={currentCourse}
            />

            {/* Modal Báo cáo vi phạm bài đánh giá */}
            {reportModalReview && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div className="flex items-center gap-2 text-rose-600 font-bold text-base">
                                <Flag size={18} />
                                <h3>Báo cáo vi phạm đánh giá</h3>
                            </div>
                            <button
                                type="button"
                                onClick={() => setReportModalReview(null)}
                                className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
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
                                        className={`flex items-center justify-center gap-1.5 rounded-xl border p-2 text-xs font-semibold transition-all ${
                                            reportTargetType === 'MON_HOC'
                                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700 shadow-xs'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                                        }`}
                                    >
                                        <BookOpen size={14} />
                                        <span>Môn học / Học phần</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setReportTargetType('GIANG_VIEN')}
                                        className={`flex items-center justify-center gap-1.5 rounded-xl border p-2 text-xs font-semibold transition-all ${
                                            reportTargetType === 'GIANG_VIEN'
                                                ? 'border-amber-600 bg-amber-50 text-amber-800 shadow-xs'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50'
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
                                        className="mt-1 w-full rounded-xl border border-slate-200 p-2 text-xs focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                                    />
                                </div>
                            )}

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Lý do báo cáo vi phạm <span className="text-rose-600">*</span>
                                </label>
                                <div className="mt-2 space-y-2">
                                    {[
                                        { value: 'NGON_TU_KICH_DONG', label: 'Ngôn từ kích động / xúc phạm / thô tục' },
                                        { value: 'BOI_NHO', label: 'Bôi nhọ / Vu khống giảng viên hoặc nhà trường' },
                                        { value: 'QUAY_ROI', label: 'Quấy rối / Công kích sinh viên khác' },
                                        { value: 'SPAM', label: 'Spam / Rao vặt / Quảng cáo' },
                                        { value: 'KHAC', label: 'Lý do khác' },
                                    ].map((opt) => (
                                        <label
                                            key={opt.value}
                                            className={`flex items-center gap-3 rounded-xl border p-2.5 text-xs cursor-pointer transition-colors ${
                                                reportReason === opt.value
                                                    ? 'border-brand-primary bg-brand-primary/5 text-brand-primary font-semibold'
                                                    : 'border-slate-200 hover:bg-slate-50 text-slate-700'
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
                                    className="mt-1.5 w-full rounded-xl border border-slate-200 p-2.5 text-xs focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                                />
                            </div>

                            {reportError && (
                                <p className="text-xs text-rose-600">{reportError}</p>
                            )}

                            <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setReportModalReview(null)}
                                    className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                >
                                    Hủy bỏ
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmittingReport}
                                    className="rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-700 disabled:opacity-50"
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
