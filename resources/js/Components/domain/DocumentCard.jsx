import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import axios from 'axios';
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
    Eye,
    Calendar,
} from 'lucide-react';

export default function DocumentCard({ document, showCourseInfo = true }) {
    if (!document) return null;

    const pageProps = usePage?.()?.props || {};
    const currentUser = pageProps.auth?.user;
    const isAuthor = Boolean(
        currentUser && (
            (document.ma_nguoi_dang && (currentUser.ma_nguoi_dung === document.ma_nguoi_dang || currentUser.id === document.ma_nguoi_dang)) ||
            (document.nguoi_dang?.ma_nguoi_dung && currentUser.ma_nguoi_dung === document.nguoi_dang.ma_nguoi_dung)
        )
    );

    const [upvotes, setUpvotes] = useState(document.luot_thich || 0);
    const [hasUpvoted, setHasUpvoted] = useState(Boolean(document.da_thich || document.has_upvoted));
    const [isVoting, setIsVoting] = useState(false);

    const isCommunityOwned = (document.luot_tai || 0) >= 10 || upvotes >= 10;
    const isPdf = (document.dinh_dang_tep || '').toLowerCase() === 'pdf';

    // Format Ngày đăng an toàn
    const formatDate = (dateString) => {
        if (!dateString) return null;
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return null;
            return date.toLocaleDateString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
        } catch {
            return null;
        }
    };

    // Format Dung lượng file an toàn
    const formatFileSize = (kb) => {
        if (!kb || typeof kb !== 'number') return null;
        if (kb >= 1024) return `${(kb / 1024).toFixed(1)} MB`;
        return `${kb} KB`;
    };

    // Mapping nhãn phân loại
    const categoryLabels = {
        DE_THI: 'Đề Thi',
        BAI_GIANG: 'Bài Giảng',
        DO_AN: 'Đồ Án',
        BAI_TAP: 'Bài Tập',
        GIAO_TRINH: 'Giáo Trình',
        TAI_LIEU: 'Tài Liệu',
        KHAC: 'Tài Liệu Khác',
    };

    // Theme màu sắc & icon theo loại tài liệu
    const getCategoryTheme = (category) => {
        switch (category) {
            case 'DE_THI':
                return {
                    accentBar: 'bg-gradient-to-r from-amber-400 to-orange-500',
                    iconBg: 'bg-amber-50 text-amber-600 border-amber-200/70',
                    badge: 'bg-amber-50 text-amber-700 border-amber-200',
                    Icon: FileCheck2,
                };
            case 'BAI_GIANG':
                return {
                    accentBar: 'bg-gradient-to-r from-[#005baa] to-sky-500',
                    iconBg: 'bg-blue-50 text-[#005baa] border-blue-200/70',
                    badge: 'bg-blue-50 text-[#005baa] border-blue-200',
                    Icon: BookOpen,
                };
            case 'DO_AN':
                return {
                    accentBar: 'bg-gradient-to-r from-violet-500 to-purple-600',
                    iconBg: 'bg-violet-50 text-violet-600 border-violet-200/70',
                    badge: 'bg-violet-50 text-violet-700 border-violet-200',
                    Icon: Layers,
                };
            case 'BAI_TAP':
                return {
                    accentBar: 'bg-gradient-to-r from-emerald-500 to-teal-500',
                    iconBg: 'bg-emerald-50 text-emerald-600 border-emerald-200/70',
                    badge: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    Icon: PenTool,
                };
            case 'GIAO_TRINH':
                return {
                    accentBar: 'bg-gradient-to-r from-cyan-500 to-blue-500',
                    iconBg: 'bg-cyan-50 text-cyan-700 border-cyan-200/70',
                    badge: 'bg-cyan-50 text-cyan-700 border-cyan-200',
                    Icon: BookMarked,
                };
            default:
                return {
                    accentBar: 'bg-gradient-to-r from-indigo-400 to-slate-500',
                    iconBg: 'bg-slate-100 text-slate-600 border-slate-200/80',
                    badge: 'bg-slate-100 text-slate-700 border-slate-200',
                    Icon: FileText,
                };
        }
    };

    const categoryTheme = getCategoryTheme(document.phan_loai);
    const CategoryIcon = categoryTheme.Icon;
    const rawFormat = (document.dinh_dang_tep || '').trim();
    const hasValidFormat = Boolean(rawFormat && rawFormat.toUpperCase() !== 'N/A');

    // AJAX Upvote Handler
    const handleToggleUpvote = async (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (isVoting || !document.ma_tai_lieu) return;
        if (isAuthor) {
            alert('Bạn không thể tự thích tài liệu của chính mình.');
            return;
        }
        setIsVoting(true);

        try {
            const response = await axios.post(route('documents.upvote', document.ma_tai_lieu));
            if (response.data && response.data.success) {
                setUpvotes(response.data.upvotes);
                setHasUpvoted(Boolean(response.data.has_upvoted));
            }
        } catch (error) {
            console.error('Error upvoting document from card:', error);
            const errMsg = error.response?.data?.message || 'Có lỗi xảy ra.';
            alert(errMsg);
        } finally {
            setTimeout(() => setIsVoting(false), 400);
        }
    };

    const hasId = Boolean(document.ma_tai_lieu);
    const detailUrl = hasId ? route('documents.show', document.ma_tai_lieu) : '#';
    const previewUrl = hasId ? route('documents.preview', document.ma_tai_lieu) : '#';
    const downloadUrl = hasId ? route('documents.download', document.ma_tai_lieu) : '#';

    return (
        <div className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-300/80 hover:shadow-md">
            {/* Thanh nhấn màu theo loại tài liệu */}
            <div className={`h-1 w-full ${categoryTheme.accentBar}`} />

            {/* Nội dung chính của thẻ */}
            <div className="flex flex-1 flex-col p-4 sm:p-5">
                <div className="mb-3 flex items-start justify-between gap-2.5">
                    <div className="flex items-center gap-2.5 min-w-0">
                        <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border ${categoryTheme.iconBg}`}>
                            <CategoryIcon size={17} />
                        </div>
                        <div className="flex flex-wrap items-center gap-1.5">
                            <span className={`rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ${categoryTheme.badge}`}>
                                {document.phan_loai_text || categoryLabels[document.phan_loai] || document.phan_loai || 'Tài Liệu'}
                            </span>
                            <span className="rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[11px] font-medium text-slate-600">
                                v{document.phien_ban || '1.0'}
                            </span>
                            {hasValidFormat && (
                                <span className="rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold uppercase text-slate-600">
                                    {rawFormat}
                                </span>
                            )}
                            {formatFileSize(document.dung_luong_kb) && (
                                <span className="font-mono text-[11px] text-slate-400">
                                    {formatFileSize(document.dung_luong_kb)}
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Community Ownership Lock Icon */}
                    {isCommunityOwned && (
                        <div
                            className="flex shrink-0 items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-semibold text-[#005baa]"
                            title="Tài liệu thuộc Sở hữu cộng đồng (Không thể xóa vĩnh viễn)"
                        >
                            <Shield size={12} className="fill-blue-500/20" />
                            <span className="hidden text-[10px] sm:inline">Cộng đồng</span>
                        </div>
                    )}
                </div>

                {/* Tiêu đề tài liệu */}
                {hasId ? (
                    <Link
                        href={detailUrl}
                        className="mb-2.5 block line-clamp-2 text-[15px] font-bold leading-snug text-slate-900 transition-colors hover:text-[#005baa] group-hover:text-[#005baa]"
                    >
                        {document.tieu_de || 'Tài liệu không có tiêu đề'}
                    </Link>
                ) : (
                    <h4 className="mb-2.5 line-clamp-2 text-[15px] font-bold leading-snug text-slate-900">
                        {document.tieu_de || 'Tài liệu không có tiêu đề'}
                    </h4>
                )}

                {/* Tên môn học & Mã học phần (nếu được bật) */}
                {showCourseInfo && document.mon_hoc && (
                    <p className="mb-2.5 flex items-center gap-1.5 text-xs text-slate-500">
                        <span className="rounded bg-blue-50 px-1.5 py-0.5 font-mono text-[11px] font-bold text-[#005baa] border border-blue-100">
                            {document.mon_hoc.ma_hoc_phan}
                        </span>
                        <span className="truncate">{document.mon_hoc.ten_mon_hoc}</span>
                    </p>
                )}

                {/* Metadata: Người đăng & Ngày đăng */}
                <div className="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1.5 pt-2 text-xs text-slate-500">
                    {document.nguoi_dang?.ho_ten && (
                        <span className="flex items-center gap-1.5 truncate" title={`Người đăng: ${document.nguoi_dang.ho_ten}`}>
                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[10px] font-bold text-[#005baa] border border-blue-100">
                                {document.nguoi_dang.ho_ten.charAt(0).toUpperCase()}
                            </span>
                            <span className="max-w-[140px] truncate font-medium text-slate-700">
                                {document.nguoi_dang.ho_ten}
                            </span>
                        </span>
                    )}

                    {document.created_at && (
                        <span className="flex shrink-0 items-center gap-1 text-slate-400" title="Ngày tải lên">
                            <Calendar size={13} className="shrink-0" />
                            <span>{formatDate(document.created_at)}</span>
                        </span>
                    )}
                </div>
            </div>

            {/* Footer Thẻ: Lượt tải, Upvote & Các nút Tương tác */}
            <div className="flex items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-3 text-xs sm:px-5">
                {/* Số liệu thống kê */}
                <div className="flex items-center gap-2 text-slate-600">
                    <span
                        className="inline-flex items-center gap-1.5 rounded-lg bg-white px-2 py-1 font-medium text-slate-600 border border-slate-200/80"
                        title="Lượt tải"
                    >
                        <Download size={13} className="text-slate-400" />
                        <span>{document.luot_tai || 0}</span>
                    </span>

                    {/* Interactive Upvote Button */}
                    <button
                        type="button"
                        onClick={handleToggleUpvote}
                        disabled={isVoting || !hasId || isAuthor}
                        className={`inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 font-semibold transition-all ${
                            isAuthor
                                ? 'cursor-not-allowed border-slate-200/70 bg-white/60 text-slate-400 opacity-70'
                                : hasUpvoted
                                ? 'border-blue-200 bg-blue-50 text-[#005baa]'
                                : 'border-slate-200/80 bg-white text-slate-600 hover:border-blue-200 hover:bg-blue-50/50 hover:text-[#005baa]'
                        }`}
                        title={
                            isAuthor
                                ? 'Bạn không thể tự thích tài liệu của chính mình'
                                : (hasUpvoted ? 'Bỏ thích tài liệu' : 'Bình chọn tài liệu hữu ích')
                        }
                    >
                        <ThumbsUp size={13} className={hasUpvoted ? 'fill-[#005baa] text-[#005baa]' : 'text-slate-400'} />
                        <span>{upvotes}</span>
                    </button>
                </div>

                {/* Các nút hành động: Xem trước / Tải về */}
                <div className="flex shrink-0 items-center gap-1.5">
                    {/* Xem trước PDF trực tiếp */}
                    {isPdf && hasId && (
                        <a
                            href={previewUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-[#005baa]"
                            title="Xem trước PDF trong tab mới"
                        >
                            <Eye size={13} />
                            <span className="hidden sm:inline">Xem trước</span>
                        </a>
                    )}

                    {/* Tải về máy */}
                    {hasId && (
                        <a
                            href={downloadUrl}
                            className="inline-flex items-center gap-1.5 rounded-xl bg-[#005baa] px-3 py-1.5 text-[11px] font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98]"
                            title="Tải tài liệu về máy"
                        >
                            <Download size={13} />
                            <span>Tải về</span>
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}
