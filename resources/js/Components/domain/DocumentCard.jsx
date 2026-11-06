import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import {
    FileText,
    Download,
    ThumbsUp,
    Shield,
    Eye,
    Calendar,
    User,
    ExternalLink
} from 'lucide-react';

export default function DocumentCard({ document, showCourseInfo = true }) {
    if (!document) return null;

    const [upvotes, setUpvotes] = useState(document.luot_thich || 0);
    const [hasUpvoted, setHasUpvoted] = useState(false);
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
    };

    // Badge styling theo loại tài liệu
    const getCategoryBadge = (category) => {
        switch (category) {
            case 'DE_THI':
                return 'bg-amber-500/10 text-amber-700 border-amber-500/20';
            case 'BAI_GIANG':
                return 'bg-blue-500/10 text-blue-700 border-blue-500/20';
            case 'DO_AN':
                return 'bg-purple-500/10 text-purple-700 border-purple-500/20';
            case 'BAI_TAP':
                return 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20';
            default:
                return 'bg-slate-100 text-slate-700 border-slate-200';
        }
    };

    // AJAX Upvote Handler
    const handleToggleUpvote = async (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (isVoting || !document.ma_tai_lieu) return;
        setIsVoting(true);

        try {
            const response = await axios.post(route('documents.upvote', document.ma_tai_lieu));
            if (response.data && response.data.success) {
                setUpvotes(response.data.upvotes);
                setHasUpvoted(true);
            }
        } catch (error) {
            console.error('Error upvoting document from card:', error);
        } finally {
            setTimeout(() => setIsVoting(false), 400);
        }
    };

    const hasId = Boolean(document.ma_tai_lieu);
    const detailUrl = hasId ? route('documents.show', document.ma_tai_lieu) : '#';
    const previewUrl = hasId ? route('documents.preview', document.ma_tai_lieu) : '#';
    const downloadUrl = hasId ? route('documents.download', document.ma_tai_lieu) : '#';

    return (
        <div className="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between relative group">
            {/* Header Thẻ: Badge Phân loại & Icon Khiên Bảo Vệ */}
            <div>
                <div className="flex items-center justify-between gap-2 mb-2.5">
                    <div className="flex items-center gap-1.5 flex-wrap">
                        <span className={`text-[11px] font-semibold px-2.5 py-0.5 rounded-full border ${getCategoryBadge(document.phan_loai)}`}>
                            {document.phan_loai_text || categoryLabels[document.phan_loai] || document.phan_loai || 'Tài Liệu'}
                        </span>
                        <span className="font-mono text-[11px] font-medium bg-slate-50 border border-slate-200 px-2 py-0.5 rounded text-slate-600">
                            v{document.phien_ban || '1.0'}
                        </span>
                        {document.dinh_dang_tep && (
                            <span className="uppercase font-mono font-bold text-[10px] bg-slate-100 text-slate-600 border border-slate-200 px-1.5 py-0.5 rounded">
                                {document.dinh_dang_tep}
                            </span>
                        )}
                        {formatFileSize(document.dung_luong_kb) && (
                            <span className="text-[11px] text-slate-400 font-mono">
                                {formatFileSize(document.dung_luong_kb)}
                            </span>
                        )}
                    </div>

                    {/* Community Ownership Lock Icon 🛡️ */}
                    {isCommunityOwned && (
                        <div
                            className="flex items-center gap-1 text-xs font-semibold text-[#005baa] bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200"
                            title="Tài liệu thuộc Sở hữu cộng đồng (Không thể xóa vĩnh viễn)"
                        >
                            <Shield size={12} className="fill-blue-500/20" />
                            <span className="text-[10px] hidden sm:inline">Cộng đồng</span>
                        </div>
                    )}
                </div>

                {/* Tiêu đề tài liệu */}
                {hasId ? (
                    <Link
                        href={detailUrl}
                        className="font-bold text-slate-900 hover:text-[#005baa] text-base line-clamp-2 leading-snug mb-2 group-hover:underline transition-colors block"
                    >
                        {document.tieu_de || 'Tài liệu không có tiêu đề'}
                    </Link>
                ) : (
                    <h4 className="font-bold text-slate-900 text-base line-clamp-2 leading-snug mb-2">
                        {document.tieu_de || 'Tài liệu không có tiêu đề'}
                    </h4>
                )}

                {/* Tên môn học & Mã học phần (nếu được bật) */}
                {showCourseInfo && document.mon_hoc && (
                    <p className="text-xs text-slate-500 flex items-center gap-1.5 mb-2.5">
                        <span className="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-[11px] font-bold text-slate-700">
                            {document.mon_hoc.ma_hoc_phan}
                        </span>
                        <span className="truncate">{document.mon_hoc.ten_mon_hoc}</span>
                    </p>
                )}

                {/* Metadata: Người đăng & Ngày đăng */}
                <div className="flex flex-wrap items-center gap-y-1 gap-x-3 text-xs text-slate-500 mb-3 pt-1">
                    {document.nguoi_dang?.ho_ten && (
                        <span className="flex items-center gap-1 truncate" title={`Người đăng: ${document.nguoi_dang.ho_ten}`}>
                            <User size={13} className="shrink-0 text-slate-400" />
                            <span className="truncate max-w-[130px] font-medium text-slate-700">
                                {document.nguoi_dang.ho_ten}
                            </span>
                        </span>
                    )}

                    {document.created_at && (
                        <span className="flex items-center gap-1 shrink-0 text-slate-400" title={`Ngày tải lên`}>
                            <Calendar size={13} className="shrink-0" />
                            <span>{formatDate(document.created_at)}</span>
                        </span>
                    )}
                </div>
            </div>

            {/* Footer Thẻ: Lượt tải, Upvote & Các nút Tương tác */}
            <div className="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 text-xs">
                {/* Số liệu thống kê */}
                <div className="flex items-center gap-2 sm:gap-3 text-slate-600">
                    <span className="flex items-center gap-1 font-medium" title="Lượt tải">
                        <Download size={14} className="text-slate-400" />
                        <span>{document.luot_tai || 0}</span>
                    </span>

                    {/* Interactive Upvote Button */}
                    <button
                        type="button"
                        onClick={handleToggleUpvote}
                        disabled={isVoting || !hasId}
                        className={`flex items-center gap-1 font-medium px-2 py-0.5 rounded transition-all ${
                            hasUpvoted
                                ? 'text-[#005baa] bg-blue-50 font-semibold'
                                : 'text-slate-600 hover:text-[#005baa] hover:bg-slate-50'
                        }`}
                        title="Bình chọn tài liệu hữu ích"
                    >
                        <ThumbsUp size={14} className={hasUpvoted ? 'fill-[#005baa] text-[#005baa]' : 'text-slate-400'} />
                        <span>{upvotes}</span>
                    </button>
                </div>

                {/* Các nút hành động: Xem trước / Tải về / Chi tiết */}
                <div className="flex items-center gap-1.5 shrink-0">
                    {/* Xem trước PDF trực tiếp */}
                    {isPdf && hasId && (
                        <a
                            href={previewUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 hover:text-[#005baa] bg-slate-50 hover:bg-blue-50 border border-slate-200 px-2 py-1 rounded-lg transition-colors"
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
                            className="inline-flex items-center gap-1 text-[11px] font-semibold text-white bg-[#005baa] hover:bg-blue-700 px-2.5 py-1 rounded-lg shadow-2xs transition-colors"
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
