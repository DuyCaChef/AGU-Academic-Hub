import React, { useState } from 'react';
import { Star } from 'lucide-react';

export default function RatingStarInput({
    label,
    description,
    value = 3,
    onChange,
    disabled = false,
    customLabels = null,
}) {
    const [hoverValue, setHoverValue] = useState(0);

    const defaultLabels = {
        1: 'Rất tệ',
        2: 'Chưa tốt',
        3: 'Bình thường',
        4: 'Tốt',
        5: 'Tuyệt vời',
    };

    const labels = customLabels || defaultLabels;
    const activeValue = hoverValue || value || 3;

    return (
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-3 sm:p-3.5 rounded-xl bg-white border border-slate-200/80 hover:border-slate-300 shadow-sm transition-all">
            {/* Tiêu đề & mô tả */}
            <div className="space-y-0.5 sm:max-w-[210px] flex-shrink-0">
                <div className="flex items-center gap-1.5">
                    <span className="text-sm font-semibold text-slate-800">{label}</span>
                    <span className="text-[#e63946] font-bold text-xs">*</span>
                </div>
                {description && (
                    <p className="text-xs text-slate-500 leading-tight">{description}</p>
                )}
            </div>

            {/* Dãy sao đánh giá & nhãn cảm xúc */}
            <div className="flex items-center justify-between sm:justify-end gap-3 flex-wrap sm:flex-nowrap">
                <div className="flex items-center gap-1">
                    {[1, 2, 3, 4, 5].map((star) => {
                        const isFilled = star <= activeValue;
                        return (
                            <button
                                key={star}
                                type="button"
                                disabled={disabled}
                                onClick={() => onChange(star)}
                                onMouseEnter={() => setHoverValue(star)}
                                onMouseLeave={() => setHoverValue(0)}
                                className="p-1 rounded-lg transition-transform hover:scale-110 active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#005baa]/20 disabled:cursor-not-allowed group"
                                title={`${star} sao`}
                            >
                                <Star
                                    className={`w-6 h-6 transition-colors ${
                                        isFilled
                                            ? 'fill-amber-400 text-amber-400 drop-shadow-sm'
                                            : 'text-slate-200 group-hover:text-amber-200'
                                    }`}
                                />
                            </button>
                        );
                    })}
                </div>

                {/* Badge điểm và text mô tả ngắn */}
                <div className="flex items-center gap-2 min-w-[130px] justify-end">
                    <span className="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 flex-shrink-0">
                        {activeValue}/5
                    </span>
                    <span className="text-xs font-medium text-slate-600 truncate max-w-[110px]" title={labels[activeValue]}>
                        {labels[activeValue] || `${activeValue} sao`}
                    </span>
                </div>
            </div>
        </div>
    );
}