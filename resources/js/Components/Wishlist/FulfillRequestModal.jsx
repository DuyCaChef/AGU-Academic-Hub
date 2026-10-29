import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { X, UploadCloud, FileText, Send } from 'lucide-react';

export default function FulfillRequestModal({ isOpen, onClose, requestId }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        ma_tai_lieu: '',
        ghi_chu_dong_gop: '',
    });

    if (!isOpen) return null;

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
            <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 className="text-base font-bold text-slate-800 flex items-center gap-2">
                        <UploadCloud className="w-5 h-5 text-[#00a896]" />
                        Đóng Góp Tài Liệu Để Săn Thưởng
                    </h3>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="mt-4 space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                            Mã số tài liệu (Document ID) <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            placeholder="Nhập mã ID tài liệu của bạn trên hệ thống..."
                            value={data.ma_tai_lieu}
                            onChange={(e) => setData('ma_tai_lieu', e.target.value)}
                            className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 cursor-text caret-black focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20 transition-colors shadow-2xs"
                        />
                        <div className="flex items-center justify-between text-[11px] text-slate-500 mt-1">
                            <span>Lấy mã ID từ tài liệu bạn đã đăng tải.</span>
                            <a
                                href={typeof route === 'function' && route().has('documents.create') ? route('documents.create') : '/tai-lieu/dang-tai'}
                                target="_blank"
                                rel="noreferrer"
                                className="text-[#005baa] hover:underline font-semibold"
                            >
                                Đăng tài liệu mới &rarr;
                            </a>
                        </div>
                        {errors.ma_tai_lieu && <p className="text-rose-500 text-xs mt-1">{errors.ma_tai_lieu}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Ghi chú gửi người yêu cầu</label>
                        <textarea
                            rows={3}
                            placeholder="VD: Đây là tài liệu thầy A vừa cho thi năm ngoái kèm lời giải chi tiết..."
                            value={data.ghi_chu_dong_gop}
                            onChange={(e) => setData('ghi_chu_dong_gop', e.target.value)}
                            className="w-full text-sm rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 cursor-text caret-black focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20 transition-colors shadow-2xs"
                        />
                        {errors.ghi_chu_dong_gop && <p className="text-rose-500 text-xs mt-1">{errors.ghi_chu_dong_gop}</p>}
                    </div>

                    <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                        >
                            Đóng
                        </button>
                        <button
                            type="submit"
                            disabled={processing || !data.ma_tai_lieu}
                            className="px-4 py-2 text-xs font-bold text-white bg-[#00a896] hover:bg-teal-700 rounded-xl flex items-center gap-1.5 disabled:bg-slate-300"
                        >
                            <Send className="w-3.5 h-3.5" />
                            {processing ? 'Đang gửi...' : 'Nộp Tài Liệu'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}