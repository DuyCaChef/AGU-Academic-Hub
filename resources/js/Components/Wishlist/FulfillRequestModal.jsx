import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import {
    X,
    UploadCloud,
    FileText,
    Send,
    Search,
    CheckCircle2,
    AlertCircle,
    Loader2,
    PlusCircle,
    ExternalLink
} from 'lucide-react';

export default function FulfillRequestModal({ isOpen, onClose, requestId }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        ma_tai_lieu: '',
        ghi_chu_dong_gop: '',
    });

    const [myDocuments, setMyDocuments] = useState([]);
    const [isLoadingDocs, setIsLoadingDocs] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [isManualMode, setIsManualMode] = useState(false);

    // Fetch user's approved documents when modal opens
    useEffect(() => {
        if (isOpen) {
            fetchMyDocuments();
        } else {
            reset();
            clearErrors();
            setSearchQuery('');
            setIsManualMode(false);
        }
    }, [isOpen]);

    const fetchMyDocuments = async () => {
        setIsLoadingDocs(true);
        try {
            const response = await axios.get(route('documents.my-documents'));
            setMyDocuments(response.data || []);
            // If user has no documents, default to manual mode
            if (!response.data || response.data.length === 0) {
                setIsManualMode(true);
            }
        } catch (error) {
            console.error('Lỗi khi tải danh sách tài liệu cá nhân:', error);
            setIsManualMode(true);
        } finally {
            setIsLoadingDocs(false);
        }
    };

    if (!isOpen) return null;

    const filteredDocs = myDocuments.filter((doc) => {
        const query = searchQuery.toLowerCase();
        return (
            (doc.tieu_de || '').toLowerCase().includes(query) ||
            (doc.mon_hoc?.ten_mon_hoc || '').toLowerCase().includes(query) ||
            (doc.mon_hoc?.ma_hoc_phan || '').toLowerCase().includes(query)
        );
    });

    const selectedDoc = myDocuments.find((d) => String(d.ma_tai_lieu) === String(data.ma_tai_lieu));

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('document-requests.fulfill', requestId), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div className="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
                {/* Header */}
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 className="text-base font-bold text-slate-800 flex items-center gap-2">
                        <UploadCloud className="w-5 h-5 text-[#00a896]" />
                        Đóng Góp Tài Liệu Để Săn Thưởng
                    </h3>
                    <button
                        onClick={onClose}
                        className="text-slate-400 hover:text-slate-600 p-1 rounded-lg transition-colors"
                        aria-label="Đóng modal"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="mt-4 space-y-4">
                    {/* Document Selection Section */}
                    <div>
                        <div className="flex items-center justify-between mb-1.5">
                            <label className="block text-xs font-semibold text-slate-700 uppercase">
                                Tài liệu nộp <span className="text-rose-500">*</span>
                            </label>

                            {myDocuments.length > 0 && (
                                <button
                                    type="button"
                                    onClick={() => setIsManualMode(!isManualMode)}
                                    className="text-[11px] text-[#005baa] hover:underline font-medium"
                                >
                                    {isManualMode ? 'Chọn từ kho của tôi' : 'Nhập mã ID thủ công'}
                                </button>
                            )}
                        </div>

                        {isManualMode ? (
                            /* Manual ID Input */
                            <div className="space-y-1">
                                <input
                                    type="number"
                                    placeholder="Nhập mã ID tài liệu của bạn trên hệ thống..."
                                    value={data.ma_tai_lieu}
                                    onChange={(e) => setData('ma_tai_lieu', e.target.value)}
                                    className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20 transition-colors shadow-2xs"
                                />
                                <div className="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                                    <span>Nhập số ID tài liệu bạn đã đăng tải.</span>
                                    <a
                                        href={route('documents.create')}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-[#005baa] hover:underline font-semibold flex items-center gap-0.5"
                                    >
                                        <span>Đăng tài liệu mới</span>
                                        <ExternalLink size={11} />
                                    </a>
                                </div>
                            </div>
                        ) : (
                            /* Interactive Document Picker */
                            <div className="space-y-2">
                                {isLoadingDocs ? (
                                    <div className="p-4 border border-slate-200 rounded-xl bg-slate-50 flex items-center justify-center gap-2 text-xs text-slate-500">
                                        <Loader2 size={16} className="animate-spin text-[#00a896]" />
                                        <span>Đang tải danh sách tài liệu cá nhân...</span>
                                    </div>
                                ) : myDocuments.length === 0 ? (
                                    <div className="p-4 border border-amber-200 bg-amber-50/70 rounded-xl space-y-2 text-xs text-slate-700">
                                        <div className="flex items-center gap-2 text-amber-700 font-semibold">
                                            <AlertCircle size={16} />
                                            <span>Bạn chưa có tài liệu nào trong kho lưu trữ</span>
                                        </div>
                                        <p className="text-slate-600">
                                            Bạn cần tải lên tài liệu học tập trước khi có thể dùng tài liệu đó để đóng góp nhận thưởng Bounty.
                                        </p>
                                        <a
                                            href={route('documents.create')}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-1.5 font-bold text-[#005baa] hover:underline pt-1"
                                        >
                                            <PlusCircle size={14} />
                                            <span>Tải lên tài liệu ngay (Mở tab mới)</span>
                                        </a>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {/* Search Filter for Picker */}
                                        <div className="relative">
                                            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                            <input
                                                type="text"
                                                value={searchQuery}
                                                onChange={(e) => setSearchQuery(e.target.value)}
                                                placeholder="Tìm kiếm tài liệu cá nhân theo tên hoặc môn..."
                                                className="w-full text-xs pl-8 pr-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00a896]"
                                            />
                                        </div>

                                        {/* Selectable Document List */}
                                        <div className="max-h-48 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 bg-white">
                                            {filteredDocs.length > 0 ? (
                                                filteredDocs.map((doc) => {
                                                    const isSelected = String(data.ma_tai_lieu) === String(doc.ma_tai_lieu);
                                                    return (
                                                        <div
                                                            key={doc.ma_tai_lieu}
                                                            onClick={() => setData('ma_tai_lieu', doc.ma_tai_lieu)}
                                                            className={`p-3 cursor-pointer transition-colors flex items-start justify-between gap-3 ${
                                                                isSelected
                                                                    ? 'bg-teal-50/80 border-l-4 border-l-[#00a896]'
                                                                    : 'hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <div className="min-w-0 flex-1">
                                                                <div className="flex items-center gap-1.5 mb-0.5">
                                                                    <span className="font-mono text-[10px] font-bold bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">
                                                                        ID: {doc.ma_tai_lieu}
                                                                    </span>
                                                                    {doc.mon_hoc && (
                                                                        <span className="font-mono text-[10px] font-semibold text-[#005baa] bg-blue-50 px-1.5 py-0.5 rounded">
                                                                            {doc.mon_hoc.ma_hoc_phan}
                                                                        </span>
                                                                    )}
                                                                    <span className="uppercase text-[10px] text-slate-400 font-mono">
                                                                        {doc.dinh_dang_tep || 'PDF'}
                                                                    </span>
                                                                </div>
                                                                <div className="text-xs font-semibold text-slate-800 line-clamp-1">
                                                                    {doc.tieu_de}
                                                                </div>
                                                            </div>

                                                            {isSelected && (
                                                                <CheckCircle2 size={18} className="text-[#00a896] shrink-0 mt-1" />
                                                            )}
                                                        </div>
                                                    );
                                                })
                                            ) : (
                                                <div className="p-4 text-center text-xs text-slate-400">
                                                    Không tìm thấy tài liệu phù hợp từ khóa
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {errors.ma_tai_lieu && (
                            <p className="text-rose-500 text-xs mt-1">{errors.ma_tai_lieu}</p>
                        )}
                    </div>

                    {/* Note to Requester */}
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Ghi chú gửi người yêu cầu <span className="text-slate-400 font-normal lowercase">(tùy chọn)</span>
                        </label>
                        <textarea
                            rows={3}
                            placeholder="VD: Đây là tài liệu thầy A vừa cho thi năm ngoái kèm lời giải chi tiết..."
                            value={data.ghi_chu_dong_gop}
                            onChange={(e) => setData('ghi_chu_dong_gop', e.target.value)}
                            className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20 transition-colors shadow-2xs"
                        />
                        {errors.ghi_chu_dong_gop && (
                            <p className="text-rose-500 text-xs mt-1">{errors.ghi_chu_dong_gop}</p>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                        >
                            Đóng
                        </button>
                        <button
                            type="submit"
                            disabled={processing || !data.ma_tai_lieu}
                            className="px-4 py-2 text-xs font-bold text-white bg-[#00a896] hover:bg-teal-700 rounded-xl flex items-center gap-1.5 disabled:bg-slate-300 transition-colors shadow-xs"
                        >
                            <Send className="w-3.5 h-3.5" />
                            <span>{processing ? 'Đang gửi...' : 'Nộp Tài Liệu'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
