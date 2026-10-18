import React from 'react';
import { Link } from '@inertiajs/react';
import {
    FileText,
    Download,
    ThumbsUp,
    Shield,
    Eye,
    Sparkles
} from 'lucide-react';

export default function DocumentCard({ document }) {
    const isCommunityOwned = document.luot_tai >= 10 || document.luot_thich >= 10;

    // Badge styling theo loại tài liệu
    const getCategoryBadge = (category) => {
        switch (category) {
            case 'DE_THI':
                return 'bg-semantic-warning/10 text-semantic-warning border-semantic-warning/20';
            case 'BAI_GIANG':
                return 'bg-brand-secondary/10 text-brand-secondary border-brand-secondary/20';
            case 'DO_AN':
                return 'bg-semantic-info/10 text-semantic-info border-semantic-info/20';
            default:
                return 'bg-border-default/50 text-text-secondary border-border-default';
        }
    };

    return (
        <div className="bg-surface-default border border-border-default rounded-xl p-4 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between relative group">
            {/* Header Thẻ: Badge Phân loại & Icon Khiên Bảo Vệ */}
            <div>
                <div className="flex items-center justify-between gap-2 mb-2">
                    <div className="flex items-center gap-1.5">
                        <span className={`text-[11px] font-semibold px-2 py-0.5 rounded-full border ${getCategoryBadge(document.phan_loai)}`}>
                            {document.phan_loai_text || document.phan_loai}
                        </span>
                        <span className="font-mono text-[11px] font-medium bg-surface-page border border-border-default px-2 py-0.5 rounded text-text-secondary">
                            v{document.phien_ban || '1.0'}
                        </span>
                    </div>

                    {/* Community Ownership Lock Icon 🛡️ */}
                    {isCommunityOwned && (
                        <div
                            className="flex items-center gap-1 text-xs font-semibold text-brand-primary bg-brand-primary/10 px-2 py-0.5 rounded-full border border-brand-primary/20"
                            title="Tài liệu thuộc Sở hữu cộng đồng (Không thể xóa vĩnh viễn)"
                        >
                            <Shield size={13} className="fill-brand-primary/20" />
                            <span className="text-[10px] hidden sm:inline">Cộng đồng</span>
                        </div>
                    )}
                </div>

                {/* Tiêu đề tài liệu */}
                <Link
                    href={route('documents.show', document.ma_tai_lieu)}
                    className="font-semibold text-text-primary hover:text-brand-primary text-base line-clamp-2 leading-snug mb-2 group-hover:underline"
                >
                    {document.tieu_de}
                </Link>

                {/* Tên môn học & Mã học phần */}
                {document.mon_hoc && (
                    <p className="text-xs text-text-secondary flex items-center gap-1 mb-3">
                        <span className="font-mono bg-border-default/40 px-1.5 py-0.5 rounded text-[11px] font-bold text-text-primary">
                            {document.mon_hoc.ma_hoc_phan}
                        </span>
                        <span className="truncate">{document.mon_hoc.ten_mon_hoc}</span>
                    </p>
                )}
            </div>

            {/* Footer Thẻ: Lượt tải, Upvote & Nút Xem trước */}
            <div className="pt-3 border-t border-border-default/60 flex items-center justify-between text-xs text-text-secondary">
                <div className="flex items-center gap-3">
                    <span className="flex items-center gap-1 font-medium" title="Lượt tải">
                        <Download size={14} className="text-brand-secondary" />
                        {document.luot_tai || 0}
                    </span>
                    <span className="flex items-center gap-1 font-medium" title="Upvote">
                        <ThumbsUp size={14} className="text-brand-primary" />
                        {document.luot_thich || 0}
                    </span>
                    <span className="uppercase font-mono font-bold text-[10px] bg-surface-page border border-border-default px-1 rounded">
                        {document.dinh_dang_tep || 'PDF'}
                    </span>
                </div>

                <Link
                    href={route('documents.show', document.ma_tai_lieu)}
                    className="flex items-center gap-1 text-brand-primary font-semibold hover:bg-brand-primary/10 px-2.5 py-1 rounded-md transition-colors"
                >
                    <Eye size={14} />
                    <span>Xem</span>
                </Link>
            </div>
        </div>
    );
}