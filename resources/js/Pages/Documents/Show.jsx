import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import DocumentReportModal from '@/Components/Documents/DocumentReportModal';
import {
    FileText,
    Download,
    ThumbsUp,
    Shield,
    Calendar,
    User,
    BookOpen,
    ExternalLink,
    AlertTriangle,
    Maximize2,
    Minimize2,
    Share2,
    Check,
    Copy,
    ChevronRight,
    ArrowLeft,
    Clock,
    FileCheck,
    Layers,
    Trash2,
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

    // Category badge style
    const getCategoryBadge = (category) => {
        switch (category) {
            case 'DE_THI':
                return {
                    label: 'Đề Thi',
                    style: 'bg-semantic-warning/10 text-semantic-warning border-semantic-warning/20',
                };
            case 'BAI_GIANG':
                return {
                    label: 'Bài Giảng',
                    style: 'bg-brand-secondary/10 text-brand-secondary border-brand-secondary/20',
                };
            case 'DO_AN':
                return {
                    label: 'Đồ Án',
                    style: 'bg-semantic-info/10 text-semantic-info border-semantic-info/20',
                };
            case 'BAI_TAP':
                return {
                    label: 'Bài Tập',
                    style: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                };
            default:
                return {
                    label: 'Tài Liệu Chung',
                    style: 'bg-border-default/50 text-text-secondary border-border-default',
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

    const catBadge = getCategoryBadge(document.phan_loai);

    return (
        <StudentLayout title={`Tài liệu: ${document.tieu_de}`}>
            <Head title={`${document.tieu_de} - AGU Academic Hub`} />

            {/* Breadcrumb Navigation */}
            <nav className="flex items-center gap-1.5 text-xs text-text-secondary overflow-x-auto py-1">
                <Link
                    href={route('courses.index')}
                    className="hover:text-brand-primary flex items-center gap-1 shrink-0 transition-colors"
                >
                    <BookOpen size={14} />
                    <span>Môn học</span>
                </Link>
                <ChevronRight size={13} className="shrink-0 text-text-muted" />

                {document.mon_hoc && (
                    <>
                        <Link
                            href={route('courses.show', document.mon_hoc.ma_hoc_phan)}
                            className="hover:text-brand-primary shrink-0 transition-colors"
                        >
                            <span className="font-mono font-semibold">{document.mon_hoc.ma_hoc_phan}</span>
                            <span className="ml-1 hidden sm:inline">- {document.mon_hoc.ten_mon_hoc}</span>
                        </Link>
                        <ChevronRight size={13} className="shrink-0 text-text-muted" />
                    </>
                )}

                <span className="text-text-primary font-medium truncate max-w-xs sm:max-w-md">
                    {document.tieu_de}
                </span>
            </nav>

            {/* Main Layout Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                {/* LEFT: Main Content & Preview (2 Columns on Large Screens) */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Header Card */}
                    <div className="bg-surface-default border border-border-default rounded-xl p-6 shadow-2xs space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`text-xs font-semibold px-2.5 py-1 rounded-full border ${catBadge.style}`}>
                                {catBadge.label}
                            </span>
                            <span className="font-mono text-xs font-semibold bg-surface-page border border-border-default px-2 py-0.5 rounded text-text-secondary">
                                Phiên bản v{document.phien_ban || '1.0'}
                            </span>
                            <span className="font-mono text-xs font-bold uppercase bg-border-default/40 text-text-secondary px-2 py-0.5 rounded">
                                {document.dinh_dang_tep || 'PDF'}
                            </span>

                            {isCommunityOwned && (
                                <div
                                    className="flex items-center gap-1 text-xs font-semibold text-brand-primary bg-brand-primary/10 px-2.5 py-0.5 rounded-full border border-brand-primary/20"
                                    title="Tài liệu thuộc Sở hữu cộng đồng AGU (Đạt ngưỡng bảo tồn học thuật)"
                                >
                                    <Shield size={13} className="fill-brand-primary/20" />
                                    <span>Sở hữu cộng đồng</span>
                                </div>
                            )}
                        </div>

                        <h1 className="text-xl sm:text-2xl font-bold text-text-primary leading-snug">
                            {document.tieu_de}
                        </h1>

                        {/* Subject summary bar */}
                        {document.mon_hoc && (
                            <div className="p-3 bg-surface-page border border-border-default rounded-lg flex items-center justify-between text-xs">
                                <div className="flex items-center gap-2 truncate">
                                    <span className="font-mono font-bold bg-brand-primary/10 text-brand-primary px-2 py-0.5 rounded">
                                        {document.mon_hoc.ma_hoc_phan}
                                    </span>
                                    <span className="font-medium text-text-primary truncate">
                                        {document.mon_hoc.ten_mon_hoc}
                                    </span>
                                </div>
                                <Link
                                    href={route('courses.show', document.mon_hoc.ma_hoc_phan)}
                                    className="text-brand-primary hover:underline font-semibold shrink-0 ml-2"
                                >
                                    Xem kho môn &rarr;
                                </Link>
                            </div>
                        )}

                        {/* Description */}
                        <div>
                            <h2 className="text-xs font-bold uppercase tracking-wider text-text-secondary mb-1.5">
                                Mô tả tài liệu
                            </h2>
                            <p className="text-sm text-text-secondary whitespace-pre-line leading-relaxed">
                                {document.mo_ta || 'Tác giả chưa cập nhật mô tả chi tiết cho tài liệu này. Vui lòng xem trước trực tuyến hoặc tải về máy để kiểm tra nội dung.'}
                            </p>
                        </div>
                    </div>

                    {/* PDF Viewer / Document Preview Card (TSK-103) */}
                    <div className="bg-surface-default border border-border-default rounded-xl overflow-hidden shadow-2xs">
                        <div className="p-4 bg-surface-page/80 border-b border-border-default flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <FileText size={18} className="text-brand-primary" />
                                <span className="font-bold text-sm text-text-primary">
                                    Xem trước tài liệu trực tuyến
                                </span>
                            </div>

                            <div className="flex items-center gap-2">
                                {isPdf && (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => setIsPdfExpanded(!isPdfExpanded)}
                                            className="hidden sm:inline-flex items-center gap-1 text-xs font-medium text-text-secondary hover:text-text-primary bg-surface-default border border-border-default px-2.5 py-1 rounded transition-colors"
                                            title={isPdfExpanded ? 'Thu nhỏ khung xem' : 'Mở rộng khung xem'}
                                        >
                                            {isPdfExpanded ? <Minimize2 size={13} /> : <Maximize2 size={13} />}
                                            <span>{isPdfExpanded ? 'Thu gọn' : 'Mở rộng'}</span>
                                        </button>
                                        <a
                                            href={route('documents.preview', document.ma_tai_lieu)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center gap-1 text-xs font-medium text-brand-primary hover:text-blue-800 bg-brand-primary/10 border border-brand-primary/20 px-2.5 py-1 rounded transition-colors"
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
                            <div className="p-10 text-center space-y-4 bg-surface-page/30">
                                <div className="w-14 h-14 bg-brand-primary/10 text-brand-primary rounded-full flex items-center justify-center mx-auto">
                                    <FileText size={28} />
                                </div>
                                <div className="max-w-md mx-auto">
                                    <h3 className="font-semibold text-text-primary text-base">
                                        Định dạng tệp {document.dinh_dang_tep?.toUpperCase()}
                                    </h3>
                                    <p className="text-xs text-text-secondary mt-1 leading-relaxed">
                                        Trình duyệt không hỗ trợ hiển thị trực tuyến trực tiếp định dạng tệp này.
                                        Vui lòng nhấn nút <span className="font-semibold text-text-primary">"Tải tài liệu về máy"</span> để xem nội dung bằng ứng dụng chuyên dụng.
                                    </p>
                                </div>
                                <a
                                    href={route('documents.download', document.ma_tai_lieu)}
                                    onClick={handleDownloadClick}
                                    className="inline-flex items-center gap-2 bg-brand-primary hover:bg-blue-800 text-white text-xs font-semibold px-4 py-2 rounded-md shadow-xs transition-all"
                                >
                                    <Download size={14} />
                                    <span>Tải tệp về máy ngay</span>
                                </a>
                            </div>
                        )}
                    </div>

                    {/* Version History (If multi-version exists) */}
                    {document.phien_bans && document.phien_bans.length > 0 && (
                        <div className="bg-surface-default border border-border-default rounded-xl p-5 shadow-2xs space-y-3">
                            <div className="flex items-center gap-2 text-sm font-bold text-text-primary">
                                <Layers size={16} className="text-brand-primary" />
                                <span>Lịch sử các phiên bản</span>
                            </div>
                            <div className="divide-y divide-border-default/60">
                                {document.phien_bans.map((ver) => (
                                    <div key={ver.ma_tai_lieu} className="py-2.5 flex items-center justify-between text-xs">
                                        <div>
                                            <span className="font-mono font-semibold text-text-primary mr-2">
                                                v{ver.phien_ban}
                                            </span>
                                            <span className="text-text-secondary">{ver.tieu_de}</span>
                                        </div>
                                        <Link
                                            href={route('documents.show', ver.ma_tai_lieu)}
                                            className="text-brand-primary hover:underline font-semibold"
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
                <div className="space-y-6">
                    {/* Primary Actions Card */}
                    <div className="bg-surface-default border border-border-default rounded-xl p-5 shadow-2xs space-y-4">
                        {/* Download CTA Button */}
                        <a
                            href={route('documents.download', document.ma_tai_lieu)}
                            onClick={handleDownloadClick}
                            className="w-full flex items-center justify-center gap-2.5 bg-brand-primary hover:bg-blue-800 text-white font-bold text-base py-3 px-4 rounded-xl shadow-xs transition-all transform active:scale-[0.99]"
                        >
                            <Download size={20} />
                            <span>Tải tài liệu về máy</span>
                        </a>

                        {/* Interactive Action Row: Upvote & Share */}
                        <div className="grid grid-cols-2 gap-2 pt-1">
                            <button
                                type="button"
                                onClick={handleUpvote}
                                disabled={isVoting || isAuthor}
                                className={`flex items-center justify-center gap-2 py-2 px-3 rounded-lg border text-xs font-semibold transition-all ${
                                    isAuthor
                                        ? 'bg-surface-page/50 border-border-default/60 text-text-secondary/50 cursor-not-allowed opacity-75'
                                        : hasUpvoted
                                        ? 'bg-brand-primary/10 border-brand-primary text-brand-primary'
                                        : 'bg-surface-page border-border-default text-text-primary hover:border-brand-primary/50'
                                }`}
                                title={
                                    isAuthor
                                        ? 'Bạn không thể tự thích tài liệu của chính mình'
                                        : (hasUpvoted ? 'Bỏ thích tài liệu' : 'Bình chọn tài liệu hữu ích')
                                }
                            >
                                <ThumbsUp size={15} className={hasUpvoted ? 'fill-brand-primary text-brand-primary' : ''} />
                                <span>{upvotesCount} Thích</span>
                            </button>

                            <button
                                type="button"
                                onClick={handleCopyLink}
                                className="flex items-center justify-center gap-2 py-2 px-3 rounded-lg border border-border-default bg-surface-page text-text-primary hover:bg-border-default/40 text-xs font-semibold transition-all"
                                title="Chia sẻ liên kết tài liệu"
                            >
                                {isCopiedLink ? (
                                    <>
                                        <Check size={14} className="text-semantic-success" />
                                        <span className="text-semantic-success">Đã sao chép!</span>
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
                                className="w-full flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg border border-transparent text-semantic-danger/80 hover:text-semantic-danger hover:bg-semantic-danger/5 text-xs font-medium transition-colors"
                            >
                                <AlertTriangle size={14} />
                                <span>Báo cáo tài liệu vi phạm</span>
                            </button>
                        )}
                    </div>

                    {/* Metadata Details Card */}
                    <div className="bg-surface-default border border-border-default rounded-xl p-5 shadow-2xs space-y-4">
                        <h3 className="text-xs font-bold uppercase tracking-wider text-text-secondary pb-2 border-b border-border-default">
                            Thông tin chi tiết
                        </h3>

                        <div className="space-y-3 text-xs">
                            <div className="flex items-center justify-between">
                                <span className="text-text-secondary flex items-center gap-1.5">
                                    <Download size={14} /> Lượt tải về:
                                </span>
                                <span className="font-semibold text-text-primary">{downloadsCount}</span>
                            </div>

                            <div className="flex items-center justify-between">
                                <span className="text-text-secondary flex items-center gap-1.5">
                                    <ThumbsUp size={14} /> Lượt đánh giá:
                                </span>
                                <span className="font-semibold text-text-primary">{upvotesCount}</span>
                            </div>

                            <div className="flex items-center justify-between">
                                <span className="text-text-secondary flex items-center gap-1.5">
                                    <FileCheck size={14} /> Dung lượng tệp:
                                </span>
                                <span className="font-semibold text-text-primary">
                                    {formatFileSize(document.dung_luong_kb)}
                                </span>
                            </div>

                            <div className="flex items-center justify-between">
                                <span className="text-text-secondary flex items-center gap-1.5">
                                    <Calendar size={14} /> Ngày tải lên:
                                </span>
                                <span className="font-semibold text-text-primary">
                                    {formatDate(document.created_at)}
                                </span>
                            </div>

                            {/* SHA-256 Hash */}
                            {document.ma_bam_file && (
                                <div className="pt-2 border-t border-border-default/60">
                                    <div className="flex items-center justify-between text-text-secondary mb-1">
                                        <span className="font-medium">Mã băm SHA-256:</span>
                                        <button
                                            type="button"
                                            onClick={handleCopyHash}
                                            className="text-brand-primary hover:underline flex items-center gap-1 text-[11px]"
                                            title="Sao chép toàn bộ mã SHA-256"
                                        >
                                            {isCopiedHash ? (
                                                <Check size={12} className="text-semantic-success" />
                                            ) : (
                                                <Copy size={12} />
                                            )}
                                            <span>{isCopiedHash ? 'Đã chép' : 'Sao chép'}</span>
                                        </button>
                                    </div>
                                    <div className="p-1.5 bg-surface-page border border-border-default rounded font-mono text-[10px] text-text-muted break-all select-all">
                                        {document.ma_bam_file}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Uploader Card */}
                    <div className="bg-surface-default border border-border-default rounded-xl p-5 shadow-2xs space-y-3">
                        <h3 className="text-xs font-bold uppercase tracking-wider text-text-secondary pb-2 border-b border-border-default">
                            Người đăng tải
                        </h3>

                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center font-bold text-sm shrink-0">
                                {document.nguoi_dang?.ho_ten?.charAt(0)?.toUpperCase() || <User size={18} />}
                            </div>
                            <div className="min-w-0 flex-1">
                                <h4 className="font-bold text-sm text-text-primary truncate">
                                    {document.nguoi_dang?.ho_ten || 'Thành viên AGU'}
                                </h4>
                                <p className="text-xs text-text-secondary">
                                    {document.nguoi_dang?.mssv ? `MSSV: ${document.nguoi_dang.mssv}` : 'Thành viên đóng góp'}
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

