import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import DocumentReportModal from '@/Components/Documents/DocumentReportModal';
import {
    FileText,
    FileCheck2,
    BookOpen,
    Layers,
    PenTool,
    BookMarked,
    Download,
    ThumbsUp,
    Shield,
    ShieldCheck,
    Calendar,
    User,
    ExternalLink,
    AlertTriangle,
    Maximize2,
    Minimize2,
    Share2,
    Check,
    Copy,
    ChevronRight,
    FileCheck,
    Info,
    ArrowRight,
} from 'lucide-react';

export default function Show({ document }) {
    const { auth } = usePage().props;
    const currentUser = auth?.user;

    const [isReportModalOpen, setIsReportModalOpen] = useState(false);
    const [upvotesCount, setUpvotesCount] = useState(document.luot_thich || 0);
    const [downloadsCount, setDownloadsCount] = useState(document.luot_tai || 0);
    const [hasUpvoted, setHasUpvoted] = useState(Boolean(document.has_upvoted || document.da_thich));
    const [isVoting, setIsVoting] = useState(false);
    const [isPdfExpanded, setIsPdfExpanded] = useState(false);
    const [isCopiedHash, setIsCopiedHash] = useState(false);
    const [isCopiedLink, setIsCopiedLink] = useState(false);

    const isAuthor = currentUser && (currentUser.ma_nguoi_dung === document.ma_nguoi_dang || currentUser.id === document.ma_nguoi_dang);
    const isCommunityOwned = downloadsCount >= 10 || upvotesCount >= 10;
    const isPdf = (document.dinh_dang_tep || '').toLowerCase() === 'pdf';

    // Format file size
    const formatFileSize = (kb) => {
        if (!kb) return '0 KB';
        if (kb >= 1024) {
            return `${(kb / 1024).toFixed(1)} MB`;
        }
        return `${kb} KB`;
    };

    // Format created date
    const formatDate = (dateString) => {
        if (!dateString) return 'Chưa xác định';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    // Category badge & theme style
    const getCategoryTheme = (category) => {
        switch (category) {
            case 'DE_THI':
                return {
                    label: 'Đề Thi',
                    badgeStyle: 'bg-amber-400/20 text-amber-100 border-amber-300/30',
                    Icon: FileCheck2,
                };
            case 'BAI_GIANG':
                return {
                    label: 'Bài Giảng',
                    badgeStyle: 'bg-sky-400/20 text-sky-100 border-sky-300/30',
                    Icon: BookOpen,
                };
            case 'DO_AN':
                return {
                    label: 'Đồ Án',
                    badgeStyle: 'bg-violet-400/20 text-violet-100 border-violet-300/30',
                    Icon: Layers,
                };
            case 'BAI_TAP':
                return {
                    label: 'Bài Tập',
                    badgeStyle: 'bg-emerald-400/20 text-emerald-100 border-emerald-300/30',
                    Icon: PenTool,
                };
            case 'GIAO_TRINH':
                return {
                    label: 'Giáo Trình',
                    badgeStyle: 'bg-cyan-400/20 text-cyan-100 border-cyan-300/30',
                    Icon: BookMarked,
                };
            default:
                return {
                    label: 'Tài Liệu Khác',
                    badgeStyle: 'bg-white/15 text-blue-100 border-white/25',
                    Icon: FileText,
                };
        }
    };

    // Handle AJAX Upvote
    const handleUpvote = async () => {
        if (isVoting) return;
        if (isAuthor) {
            alert('Bạn không thể tự thích tài liệu của chính mình.');
            return;
        }
        setIsVoting(true);

        try {
            const response = await window.axios.post(route('documents.upvote', document.ma_tai_lieu));
            if (response.data && response.data.success) {
                setUpvotesCount(response.data.upvotes);
                setHasUpvoted(Boolean(response.data.has_upvoted));
            }
        } catch (error) {
            console.error('Error upvoting document:', error);
            const errMsg = error.response?.data?.message || 'Có lỗi xảy ra khi thực hiện thao tác.';
            alert(errMsg);
        } finally {
            setTimeout(() => setIsVoting(false), 400);
        }
    };

    // Handle Download click (Optimistic UI count increment)
    const handleDownloadClick = () => {
        setDownloadsCount((prev) => prev + 1);
    };

    // Copy SHA-256 hash
    const handleCopyHash = () => {
        if (!document.ma_bam_file) return;
        navigator.clipboard.writeText(document.ma_bam_file);
        setIsCopiedHash(true);
        setTimeout(() => setIsCopiedHash(false), 2000);
    };

    // Copy share link
    const handleCopyLink = () => {
        navigator.clipboard.writeText(window.location.href);
        setIsCopiedLink(true);
        setTimeout(() => setIsCopiedLink(false), 2000);
    };

    const catTheme = getCategoryTheme(document.phan_loai);
    const CategoryIcon = catTheme.Icon;
    const rawFormat = (document.dinh_dang_tep || '').trim();
    const displayFormat = rawFormat && rawFormat.toUpperCase() !== 'N/A' ? rawFormat.toUpperCase() : 'TỆP';

    return (
        <StudentLayout title={`Tài liệu: ${document.tieu_de}`}>
            <Head title={`${document.tieu_de} - AGU Academic Hub`} />

            {/* Breadcrumb Navigation */}
            <nav className="flex items-center gap-1.5 overflow-x-auto py-1 text-xs text-slate-500">
                <Link
                    href={route('courses.index')}
                    className="flex shrink-0 items-center gap-1.5 rounded-lg px-2 py-1 font-medium transition-colors hover:bg-white hover:text-[#005baa]"
                >
                    <BookOpen size={14} />
                    <span>Môn học</span>
                </Link>
                <ChevronRight size={13} className="shrink-0 text-slate-400" />

                {document.mon_hoc && (
                    <>
                        <Link
                            href={route('courses.show', document.mon_hoc.ma_hoc_phan)}
                            className="shrink-0 rounded-lg px-2 py-1 transition-colors hover:bg-white hover:text-[#005baa]"
                        >
                            <span className="font-mono font-bold text-[#005baa]">{document.mon_hoc.ma_hoc_phan}</span>
                            <span className="ml-1 hidden sm:inline font-medium">- {document.mon_hoc.ten_mon_hoc}</span>
                        </Link>
                        <ChevronRight size={13} className="shrink-0 text-slate-400" />
                    </>
                )}

                <span className="max-w-xs truncate px-1 font-semibold text-slate-800 sm:max-w-md">
                    {document.tieu_de}
                </span>
            </nav>

            {/* Main Layout Grid */}
            <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
                {/* LEFT: Main Content & Preview (2 Columns on Large Screens) */}
                <div className="space-y-6 lg:col-span-2">
                    {/* Hero Header + Description Card */}
                    <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                        {/* AGU Blue Gradient Hero Banner */}
                        <div className="relative overflow-hidden bg-gradient-to-br from-[#005baa] via-[#004e95] to-blue-900 p-6 text-white sm:p-7">
                            <div className="pointer-events-none absolute -right-10 -bottom-10 h-48 w-48 rounded-full bg-white/10 blur-2xl" />
                            <div className="pointer-events-none absolute -left-8 -top-10 h-32 w-32 rounded-full bg-[#FFC72C]/15 blur-2xl" />

                            <div className="relative z-10 space-y-4">
                                {/* Badges Row */}
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold ${catTheme.badgeStyle}`}>
                                        <CategoryIcon size={13} />
                                        {catTheme.label}
                                    </span>
                                    <span className="rounded-full border border-white/20 bg-white/10 px-2.5 py-1 font-mono text-xs font-semibold text-blue-50">
                                        Phiên bản v{document.phien_ban || '1.0'}
                                    </span>
                                    {displayFormat !== 'TỆP' && (
                                        <span className="rounded-full border border-white/20 bg-white/15 px-2.5 py-1 font-mono text-xs font-bold uppercase text-white">
                                            {displayFormat}
                                        </span>
                                    )}
                                    {isCommunityOwned && (
                                        <div
                                            className="inline-flex items-center gap-1.5 rounded-full border border-[#FFC72C]/40 bg-[#FFC72C]/20 px-2.5 py-1 text-xs font-semibold text-[#FFC72C]"
                                            title="Tài liệu thuộc Sở hữu cộng đồng AGU (Đạt ngưỡng bảo tồn học thuật)"
                                        >
                                            <Shield size={13} className="fill-[#FFC72C]/20" />
                                            <span>Sở hữu cộng đồng</span>
                                        </div>
                                    )}
                                </div>

                                {/* Title + Icon */}
                                <div className="flex items-start gap-4">
                                    <div className="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/25 bg-white/15 text-white shadow-inner sm:flex">
                                        <CategoryIcon size={24} />
                                    </div>
                                    <h1 className="text-xl font-extrabold leading-snug tracking-tight text-white sm:text-2xl">
                                        {document.tieu_de}
                                    </h1>
                                </div>

                                {/* Subject Summary Bar */}
                                {document.mon_hoc && (
                                    <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-white/15 bg-white/10 px-3.5 py-2.5 text-xs backdrop-blur-xs">
                                        <div className="flex min-w-0 items-center gap-2">
                                            <span className="shrink-0 rounded-md bg-[#FFC72C] px-2 py-0.5 font-mono text-[11px] font-bold text-slate-950">
                                                {document.mon_hoc.ma_hoc_phan}
                                            </span>
                                            <span className="truncate font-semibold text-white">
                                                {document.mon_hoc.ten_mon_hoc}
                                            </span>
                                        </div>
                                        <Link
                                            href={route('courses.show', document.mon_hoc.ma_hoc_phan)}
                                            className="inline-flex shrink-0 items-center gap-1 font-semibold text-[#FFC72C] transition-colors hover:text-amber-200"
                                        >
                                            <span>Xem kho môn</span>
                                            <ArrowRight size={13} />
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Description Section */}
                        <div className="p-6">
                            <div className="mb-2.5 flex items-center gap-2">
                                <Info size={15} className="text-[#005baa]" />
                                <h2 className="text-xs font-bold uppercase tracking-wider text-slate-600">
                                    Mô tả tài liệu
                                </h2>
                            </div>
                            {document.mo_ta ? (
                                <p className="whitespace-pre-line text-sm leading-relaxed text-slate-700">
                                    {document.mo_ta}
                                </p>
                            ) : (
                                <p className="rounded-xl border border-slate-200/70 bg-slate-50/80 p-3.5 text-sm italic leading-relaxed text-slate-500">
                                    Tác giả chưa cập nhật mô tả chi tiết cho tài liệu này. Vui lòng xem trước trực tuyến hoặc tải về máy để kiểm tra nội dung.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* PDF Viewer / Document Preview Card (TSK-103) */}
                    <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                        <div className="flex items-center justify-between border-b border-slate-200/70 bg-gradient-to-r from-slate-50 via-blue-50/30 to-white px-5 py-3.5">
                            <div className="flex items-center gap-2.5">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-[#005baa] border border-blue-100">
                                    <FileText size={16} />
                                </div>
                                <span className="text-sm font-bold text-slate-900">
                                    Xem trước tài liệu trực tuyến
                                </span>
                            </div>

                            <div className="flex items-center gap-2">
                                {isPdf && (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => setIsPdfExpanded(!isPdfExpanded)}
                                            className="hidden items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 sm:inline-flex"
                                            title={isPdfExpanded ? 'Thu nhỏ khung xem' : 'Mở rộng khung xem'}
                                        >
                                            {isPdfExpanded ? <Minimize2 size={13} /> : <Maximize2 size={13} />}
                                            <span>{isPdfExpanded ? 'Thu gọn' : 'Mở rộng'}</span>
                                        </button>
                                        <a
                                            href={route('documents.preview', document.ma_tai_lieu)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-[#005baa] transition-colors hover:bg-blue-100"
                                        >
                                            <ExternalLink size={13} />
                                            <span>Mở tab mới</span>
                                        </a>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Preview Display Body */}
                        {isPdf ? (
                            <div className="relative w-full bg-slate-900 transition-all duration-300">
                                <iframe
                                    src={`${route('documents.preview', document.ma_tai_lieu)}#toolbar=1&navpanes=0`}
                                    title={`Xem trước: ${document.tieu_de}`}
                                    className={`w-full border-0 ${isPdfExpanded ? 'h-[850px]' : 'h-[560px]'}`}
                                />
                            </div>
                        ) : (
                            <div className="bg-gradient-to-b from-slate-50/70 to-blue-50/20 px-6 py-12 text-center">
                                <div className="relative mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-100 bg-white text-[#005baa] shadow-sm">
                                    <CategoryIcon size={30} />
                                    <span className="absolute -bottom-2 rounded-md border border-blue-200 bg-[#005baa] px-1.5 py-0.5 font-mono text-[10px] font-bold uppercase text-white shadow-2xs">
                                        {displayFormat}
                                    </span>
                                </div>
                                <div className="mx-auto max-w-md space-y-2 pt-1">
                                    <h3 className="text-base font-bold text-slate-900">
                                        Định dạng tệp {displayFormat}
                                    </h3>
                                    <p className="text-xs leading-relaxed text-slate-500">
                                        Trình duyệt không hỗ trợ hiển thị trực tuyến trực tiếp định dạng tệp này.
                                        Vui lòng nhấn nút <span className="font-semibold text-slate-800">"Tải tài liệu về máy"</span> để xem nội dung bằng ứng dụng chuyên dụng.
                                    </p>
                                </div>
                                <div className="mt-5 flex flex-wrap items-center justify-center gap-3">
                                    <a
                                        href={route('documents.download', document.ma_tai_lieu)}
                                        onClick={handleDownloadClick}
                                        className="inline-flex items-center gap-2 rounded-xl bg-[#005baa] px-5 py-2.5 text-xs font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98]"
                                    >
                                        <Download size={14} />
                                        <span>Tải tệp về máy ngay ({formatFileSize(document.dung_luong_kb)})</span>
                                    </a>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Version History (If multi-version exists) */}
                    {document.phien_bans && document.phien_bans.length > 0 && (
                        <div className="space-y-3 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                            <div className="flex items-center gap-2 text-sm font-bold text-slate-900">
                                <Layers size={16} className="text-[#005baa]" />
                                <span>Lịch sử các phiên bản</span>
                            </div>
                            <div className="divide-y divide-slate-100">
                                {document.phien_bans.map((ver) => (
                                    <div key={ver.ma_tai_lieu} className="flex items-center justify-between py-2.5 text-xs">
                                        <div>
                                            <span className="mr-2 rounded bg-slate-100 px-1.5 py-0.5 font-mono font-semibold text-slate-700">
                                                v{ver.phien_ban}
                                            </span>
                                            <span className="text-slate-600">{ver.tieu_de}</span>
                                        </div>
                                        <Link
                                            href={route('documents.show', ver.ma_tai_lieu)}
                                            className="font-semibold text-[#005baa] hover:underline"
                                        >
                                            Xem phiên bản này
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* RIGHT: Sidebar Actions, Metadata & Creator Info (1 Column) */}
                <div className="space-y-5 lg:sticky lg:top-20">
                    {/* Primary Actions Card */}
                    <div className="space-y-3.5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                        {/* Download CTA Button */}
                        <a
                            href={route('documents.download', document.ma_tai_lieu)}
                            onClick={handleDownloadClick}
                            className="flex w-full items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#005baa] to-blue-700 px-4 py-3.5 text-sm font-bold text-white shadow-sm transition-all hover:from-blue-700 hover:to-blue-800 active:scale-[0.99]"
                        >
                            <Download size={18} />
                            <span>Tải tài liệu về máy</span>
                        </a>

                        {/* Interactive Action Row: Upvote & Share */}
                        <div className="grid grid-cols-2 gap-2.5">
                            <button
                                type="button"
                                onClick={handleUpvote}
                                disabled={isVoting || isAuthor}
                                className={`flex items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition-all ${
                                    isAuthor
                                        ? 'cursor-not-allowed border-slate-200/70 bg-slate-50 text-slate-400 opacity-75'
                                        : hasUpvoted
                                        ? 'border-[#005baa] bg-blue-50 text-[#005baa]'
                                        : 'border-slate-200 bg-slate-50/70 text-slate-700 hover:border-blue-200 hover:bg-blue-50/50 hover:text-[#005baa]'
                                }`}
                                title={
                                    isAuthor
                                        ? 'Bạn không thể tự thích tài liệu của chính mình'
                                        : (hasUpvoted ? 'Bỏ thích tài liệu' : 'Bình chọn tài liệu hữu ích')
                                }
                            >
                                <ThumbsUp size={15} className={hasUpvoted ? 'fill-[#005baa] text-[#005baa]' : ''} />
                                <span>{upvotesCount} Thích</span>
                            </button>

                            <button
                                type="button"
                                onClick={handleCopyLink}
                                className="flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50/70 px-3 py-2.5 text-xs font-semibold text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-100"
                                title="Chia sẻ liên kết tài liệu"
                            >
                                {isCopiedLink ? (
                                    <>
                                        <Check size={14} className="text-emerald-600" />
                                        <span className="text-emerald-600">Đã sao chép!</span>
                                    </>
                                ) : (
                                    <>
                                        <Share2 size={14} />
                                        <span>Chia sẻ</span>
                                    </>
                                )}
                            </button>
                        </div>

                        {/* Report Violation Button (TSK-105: hidden if current user is author) */}
                        {!isAuthor && (
                            <button
                                type="button"
                                onClick={() => setIsReportModalOpen(true)}
                                className="flex w-full items-center justify-center gap-1.5 rounded-xl border border-transparent px-3 py-2 text-xs font-medium text-rose-600/80 transition-colors hover:bg-rose-50 hover:text-rose-700"
                            >
                                <AlertTriangle size={14} />
                                <span>Báo cáo tài liệu vi phạm</span>
                            </button>
                        )}
                    </div>

                    {/* Metadata Details Card */}
                    <div className="space-y-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                        <h3 className="border-b border-slate-100 pb-2.5 text-xs font-bold uppercase tracking-wider text-slate-600">
                            Thông tin chi tiết
                        </h3>

                        {/* 2-Column Stat Highlight */}
                        <div className="grid grid-cols-2 gap-2.5">
                            <div className="rounded-xl border border-blue-100 bg-blue-50/60 p-3">
                                <div className="flex items-center gap-1.5 text-[11px] font-semibold text-[#005baa]">
                                    <Download size={13} />
                                    <span>Lượt tải về</span>
                                </div>
                                <p className="mt-1 text-lg font-extrabold text-slate-900">{downloadsCount}</p>
                            </div>
                            <div className="rounded-xl border border-amber-100 bg-amber-50/60 p-3">
                                <div className="flex items-center gap-1.5 text-[11px] font-semibold text-amber-700">
                                    <ThumbsUp size={13} />
                                    <span>Lượt đánh giá</span>
                                </div>
                                <p className="mt-1 text-lg font-extrabold text-slate-900">{upvotesCount}</p>
                            </div>
                        </div>

                        <div className="divide-y divide-slate-100 text-xs">
                            <div className="flex items-center justify-between py-2.5">
                                <span className="flex items-center gap-1.5 text-slate-500">
                                    <FileCheck size={14} className="text-slate-400" /> Dung lượng tệp
                                </span>
                                <span className="font-mono font-semibold text-slate-800">
                                    {formatFileSize(document.dung_luong_kb)}
                                </span>
                            </div>

                            <div className="flex items-center justify-between py-2.5">
                                <span className="flex items-center gap-1.5 text-slate-500">
                                    <Calendar size={14} className="text-slate-400" /> Ngày tải lên
                                </span>
                                <span className="font-semibold text-slate-800">
                                    {formatDate(document.created_at)}
                                </span>
                            </div>
                        </div>

                        {/* SHA-256 Hash */}
                        {document.ma_bam_file && (
                            <div className="border-t border-slate-100 pt-3">
                                <div className="mb-1.5 flex items-center justify-between text-xs text-slate-500">
                                    <span className="flex items-center gap-1.5 font-semibold text-slate-600">
                                        <ShieldCheck size={13} className="text-emerald-600" />
                                        Mã băm SHA-256
                                    </span>
                                    <button
                                        type="button"
                                        onClick={handleCopyHash}
                                        className="flex items-center gap-1 text-[11px] font-semibold text-[#005baa] hover:underline"
                                        title="Sao chép toàn bộ mã SHA-256"
                                    >
                                        {isCopiedHash ? (
                                            <Check size={12} className="text-emerald-600" />
                                        ) : (
                                            <Copy size={12} />
                                        )}
                                        <span>{isCopiedHash ? 'Đã chép' : 'Sao chép'}</span>
                                    </button>
                                </div>
                                <div className="select-all break-all rounded-xl border border-slate-200/80 bg-slate-50 p-2.5 font-mono text-[10px] leading-relaxed text-slate-500">
                                    {document.ma_bam_file}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Uploader Card */}
                    <div className="space-y-3 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                        <h3 className="border-b border-slate-100 pb-2.5 text-xs font-bold uppercase tracking-wider text-slate-600">
                            Người đăng tải
                        </h3>

                        <div className="flex items-center gap-3.5">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#005baa] to-blue-700 text-sm font-bold text-white shadow-xs">
                                {document.nguoi_dang?.ho_ten?.charAt(0)?.toUpperCase() || <User size={18} />}
                            </div>
                            <div className="min-w-0 flex-1">
                                <h4 className="truncate text-sm font-bold text-slate-900">
                                    {document.nguoi_dang?.ho_ten || 'Thành viên AGU'}
                                </h4>
                                <p className="mt-0.5 text-xs text-slate-500">
                                    {document.nguoi_dang?.mssv ? (
                                        <span className="inline-block rounded bg-blue-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-[#005baa] border border-blue-100">
                                            MSSV: {document.nguoi_dang.mssv}
                                        </span>
                                    ) : (
                                        'Thành viên đóng góp'
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Document Violation Report Modal (TSK-105) */}
            <DocumentReportModal
                isOpen={isReportModalOpen}
                onClose={() => setIsReportModalOpen(false)}
                document={document}
            />
        </StudentLayout>
    );
}

