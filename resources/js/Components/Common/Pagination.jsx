import React from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination({ links, className = '' }) {
    if (!links || links.length <= 3) return null;

    return (
        <nav className={`flex items-center justify-between border-t border-slate-200 px-4 py-3 sm:px-6 ${className}`}>
            <div className="hidden sm:block">
                <p className="text-xs text-slate-500">
                    Hiển thị danh sách kết quả phân trang
                </p>
            </div>
            <div className="flex flex-1 justify-between sm:justify-end gap-1">
                {links.map((link, index) => {
                    // Xử lý icon cho nút Trước / Sau
                    let labelContent = link.label;
                    if (link.label.includes('Previous') || link.label.includes('&laquo;')) {
                        labelContent = <ChevronLeft className="w-4 h-4" />;
                    } else if (link.label.includes('Next') || link.label.includes('&raquo;')) {
                        labelContent = <ChevronRight className="w-4 h-4" />;
                    }

                    if (!link.url) {
                        return (
                            <span
                                key={index}
                                className="inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-xs font-medium text-slate-400 bg-slate-50 border border-slate-200 rounded-lg cursor-not-allowed select-none"
                            >
                                {labelContent}
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            preserveState
                            className={`inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-xs font-semibold rounded-lg transition-colors border ${link.active
                                    ? 'bg-[#005baa] border-[#005baa] text-white shadow-xs'
                                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300'
                                }`}
                        >
                            {labelContent}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}