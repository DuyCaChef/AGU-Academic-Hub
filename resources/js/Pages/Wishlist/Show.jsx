import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import FulfillRequestModal from '@/Components/Wishlist/FulfillRequestModal';
import confetti from 'canvas-confetti';
import {
    Coins,
    CheckCircle,
    ArrowLeft,
    Clock,
    Trash2,
    Award,
    FileText,
    CheckCircle2,
    UserCheck,
    AlertTriangle,
    ExternalLink,
    Sparkles
} from 'lucide-react';

export default function WishlistShow({ item, is_creator = false }) {
    const { auth, flash } = usePage().props;
    const [isFulfillOpen, setIsFulfillOpen] = useState(false);
    const [processingFulfillmentId, setProcessingFulfillmentId] = useState(null);

    // Kích hoạt pháo hoa Confetti chúc mừng
    const triggerConfettiEffect = () => {
        const count = 200;
        const defaults = {
            origin: { y: 0.7 },
            zIndex: 9999,
        };

        const fire = (particleRatio, opts) => {
            confetti({
                ...defaults,
                ...opts,
                particleCount: Math.floor(count * particleRatio),
            });
        };

        fire(0.25, { spread: 26, startVelocity: 55 });
        fire(0.2, { spread: 60 });
        fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
        fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
        fire(0.1, { spread: 120, startVelocity: 45 });
    };

    // Xác nhận giải ngân điểm thưởng cho người đóng góp
    const handleConfirmFulfillment = (fulfillmentId) => {
        if (!confirm('Xác nhận tài liệu này chính xác và giải ngân toàn bộ điểm thưởng cho người đóng góp?')) {
            return;
        }

        setProcessingFulfillmentId(fulfillmentId);
        router.post(
            route('document-requests.confirm', item.ma_yeu_cau),
            { ma_dap_ung: fulfillmentId },
            {
                onSuccess: () => {
                    setProcessingFulfillmentId(null);
                    triggerConfettiEffect();
                },
                onError: () => setProcessingFulfillmentId(null),
            }
        );
    };

    // Hủy yêu cầu và hoàn lại điểm vào ví
    const handleCancelRequest = () => {
        if (confirm('Bạn có chắc muốn hủy yêu cầu này? Điểm tạm giữ sẽ được hoàn lại 100% vào ví của bạn.')) {
            router.delete(route('document-requests.destroy', item.ma_yeu_cau));
        }
    };

    const getCategoryBadge = (category) => {
        const map = {
            DE_THI: { label: 'Đề thi', bg: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
            BAI_GIANG: { label: 'Bài giảng', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
            DO_AN: { label: 'Đồ án', bg: 'bg-purple-50 text-purple-700 border-purple-200' },
            BAI_TAP: { label: 'Bài tập', bg: 'bg-amber-50 text-amber-700 border-amber-200' },
            GIAO_TRINH: { label: 'Giáo trình', bg: 'bg-blue-50 text-blue-700 border-blue-200' },
            KHAC: { label: 'Khác', bg: 'bg-slate-100 text-slate-700 border-slate-200' },
        };
        return map[category] || { label: 'Tài liệu', bg: 'bg-slate-100 text-slate-700 border-slate-200' };
    };

    const isOpen = item.trang_thai === 'MO';
    const isFulfilled = item.trang_thai === 'DA_HOAN_THANH';
    const categoryBadge = getCategoryBadge(item.phan_loai || item.loai_tai_lieu);
    const fulfillmentList = item.dap_ungs || item.danh_sach_phan_hoi || [];

    const getDocumentUrl = (docId) => {
        if (typeof route === 'function' && route().has('documents.show')) {
            return route('documents.show', docId);
        }
        return `/tai-lieu/${docId}`;
    };

    return (
        <StudentLayout>
            <Head title={`Yêu cầu: ${item.tieu_de}`} />

            <div className="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-sm font-semibold text-emerald-800 flex items-center gap-3 shadow-xs">
                        <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
                        <span>{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-sm font-semibold text-rose-800 flex items-center gap-3 shadow-xs">
                        <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* Nút quay lại */}
                <Link
                    href={route('document-requests.index')}
                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-[#005baa] transition-colors"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Quay lại danh sách săn thưởng
                </Link>

                {/* Banner khi yêu cầu đã hoàn thành */}
                {isFulfilled && (
                    <div className="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-5 text-white shadow-md flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-white/20 backdrop-blur-md rounded-xl">
                                <Sparkles className="w-6 h-6 text-amber-300" />
                            </div>
                            <div>
                                <h3 className="font-extrabold text-base">Yêu cầu đã được giải quyết thành công!</h3>
                                <p className="text-xs text-emerald-100">
                                    Điểm thưởng {item.diem_thuong} Bounty đã được giải ngân chuyển tới người đóng góp chính xác.
                                </p>
                            </div>
                        </div>

                        {item.ma_tai_lieu_dap_ung && (
                            <a
                                href={getDocumentUrl(item.ma_tai_lieu_dap_ung)}
                                target="_blank"
                                rel="noreferrer"
                                className="px-4 py-2 bg-white text-emerald-800 hover:bg-emerald-50 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 shadow-xs"
                            >
                                <ExternalLink className="w-3.5 h-3.5" />
                                Xem tài liệu duyệt
                            </a>
                        )}
                    </div>
                )}

                {/* Khối Thông Tin Chi Tiết Yêu Cầu */}
                <div className="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 flex-wrap">
                                <span className={`font-mono text-xs font-bold px-2.5 py-0.5 rounded-full border ${categoryBadge.bg}`}>
                                    {categoryBadge.label}
                                </span>
                                <span className="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-blue-50 text-[#005baa]">
                                    {item.mon_hoc?.ma_hoc_phan}
                                </span>
                                <span className="text-xs font-semibold text-slate-500">
                                    {item.mon_hoc?.ten_mon_hoc}
                                </span>
                                {isFulfilled ? (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <CheckCircle2 className="w-3.5 h-3.5" /> Đã hoàn thành
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                        <Clock className="w-3.5 h-3.5" /> Đang tìm kiếm
                                    </span>
                                )}
                            </div>
                            <h1 className="text-xl sm:text-2xl font-black text-slate-800 leading-tight">
                                {item.tieu_de}
                            </h1>
                        </div>

                        {/* Badge Điểm Treo Thưởng */}
                        <div className="px-5 py-3 bg-gradient-to-br from-amber-50 to-amber-100/60 rounded-2xl border border-amber-200 flex items-center gap-3 shrink-0">
                            <div className="p-2 bg-amber-500 text-white rounded-xl shadow-2xs">
                                <Coins className="w-6 h-6" />
                            </div>
                            <div>
                                <span className="block text-[10px] font-bold text-amber-800 uppercase tracking-wider">Phần Thưởng</span>
                                <span className="text-2xl font-black text-amber-600">+{item.diem_thuong}</span>
                                <span className="text-xs font-bold text-amber-700 ml-1">Điểm</span>
                            </div>
                        </div>
                    </div>

                    {/* Chi tiết thông tin */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl text-xs text-slate-600">
                        <div>
                            <span className="text-slate-400 font-semibold block">Giảng viên phụ trách:</span>
                            <span className="font-bold text-slate-700">{item.ten_giang_vien || 'Không đề cập'}</span>
                        </div>
                        <div>
                            <span className="text-slate-400 font-semibold block">Người tạo yêu cầu:</span>
                            <span className="font-bold text-slate-700">
                                {item.nguoi_tao?.ho_ten} ({item.nguoi_tao?.mssv}) - Uy tín: {item.nguoi_tao?.diem_uy_tin || 0} ⭐
                            </span>
                        </div>
                    </div>

                    {/* Nội dung mô tả */}
                    <div>
                        <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Mô tả yêu cầu</h4>
                        <p className="text-sm text-slate-600 leading-relaxed whitespace-pre-line bg-white p-4 rounded-xl border border-slate-100">
                            {item.mo_ta || 'Không có mô tả chi tiết thêm.'}
                        </p>
                    </div>

                    {/* Thanh hành động chính */}
                    <div className="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-100">
                        <div className="text-xs text-slate-500">
                            {isOpen ? 'Đang tiếp nhận các bản nộp phản hồi từ cộng đồng.' : 'Yêu cầu này đã khép lại do đã giải ngân phần thưởng.'}
                        </div>

                        <div className="flex items-center gap-2">
                            {/* Nút hủy yêu cầu (Chỉ người tạo hoặc Admin khi bài còn mở) */}
                            {is_creator && isOpen && (
                                <button
                                    onClick={handleCancelRequest}
                                    className="px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-xl transition-colors flex items-center gap-1.5"
                                >
                                    <Trash2 className="w-3.5 h-3.5" />
                                    Hủy & Hoàn Điểm
                                </button>
                            )}

                            {/* Nút nộp tài liệu (Dành cho người khác khi bài còn mở) */}
                            {!is_creator && isOpen && (
                                <button
                                    onClick={() => setIsFulfillOpen(true)}
                                    className="px-5 py-2.5 text-xs font-bold text-white bg-[#00a896] hover:bg-teal-700 rounded-xl shadow-xs transition-all flex items-center gap-2 active:scale-95"
                                >
                                    <Award className="w-4 h-4" />
                                    Nộp Tài Liệu Săn Thưởng
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Danh Sách Các Bài Nộp Giải Quyết */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="text-base font-bold text-slate-800 flex items-center gap-2">
                            <FileText className="w-5 h-5 text-[#005baa]" />
                            Danh Sách Phản Hồi ({fulfillmentList.length})
                        </h3>
                    </div>

                    {fulfillmentList.length > 0 ? (
                        <div className="space-y-3">
                            {fulfillmentList.map((fh) => {
                                const responseId = fh.ma_dap_ung || fh.ma_phan_hoi;
                                const isAccepted = fh.trang_thai === 'CHAP_NHAN';
                                const contributor = fh.nguoi_dong_gop || fh.nguoiDongGop;
                                const document = fh.tai_lieu || fh.taiLieu;
                                const note = fh.ghi_chu_dong_gop || fh.ghi_chu;

                                return (
                                    <div
                                        key={responseId}
                                        className={`p-5 rounded-2xl border transition-all ${
                                            isAccepted
                                                ? 'bg-emerald-50/50 border-emerald-300 ring-2 ring-emerald-400/20'
                                                : 'bg-white border-slate-200/80'
                                        }`}
                                    >
                                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            {/* Thông tin người nộp & tài liệu */}
                                            <div className="space-y-1">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className="text-xs font-bold text-slate-800">
                                                        {contributor?.ho_ten || 'Thành viên'}
                                                    </span>
                                                    <span className="text-[11px] text-slate-400">
                                                        ({contributor?.mssv || 'Sinh viên'})
                                                    </span>
                                                    {isAccepted && (
                                                        <span className="text-[10px] font-extrabold px-2 py-0.5 bg-emerald-600 text-white rounded-md flex items-center gap-1">
                                                            <CheckCircle className="w-3 h-3" /> ĐÃ NHẬN THƯỞNG
                                                        </span>
                                                    )}
                                                </div>

                                                <p className="text-xs text-slate-600 italic">
                                                    "{note || 'Không có ghi chú kèm theo.'}"
                                                </p>

                                                <div className="text-[11px] text-slate-500 flex items-center gap-3 pt-1 flex-wrap">
                                                    <span>
                                                        Tài liệu: <strong>{document?.tieu_de || `#${fh.ma_tai_lieu}`}</strong>
                                                    </span>
                                                    <span>
                                                        Lượt tải: <strong>{document?.luot_tai || 0}</strong>
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Hành động duyệt giải ngân */}
                                            <div className="flex items-center gap-2 shrink-0">
                                                {/* Nút xem tài liệu */}
                                                <a
                                                    href={getDocumentUrl(fh.ma_tai_lieu)}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-[#005baa] bg-slate-100 hover:bg-blue-50 rounded-lg transition-colors flex items-center gap-1"
                                                >
                                                    <ExternalLink className="w-3 h-3" />
                                                    Xem File
                                                </a>

                                                {/* Nút xác nhận giải ngân (Chỉ hiển thị cho người tạo bài khi bài còn mở) */}
                                                {is_creator && isOpen && (
                                                    <button
                                                        onClick={() => handleConfirmFulfillment(responseId)}
                                                        disabled={processingFulfillmentId === responseId}
                                                        className="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 rounded-lg shadow-xs flex items-center gap-1.5 transition-all active:scale-95"
                                                    >
                                                        <UserCheck className="w-3.5 h-3.5" />
                                                        {processingFulfillmentId === responseId ? 'Đang giải ngân...' : 'Xác Nhận Hợp Lệ'}
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="bg-white rounded-xl border border-dashed border-slate-300 p-8 text-center">
                            <Clock className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                            <p className="text-xs text-slate-500">Chưa có ai gửi tài liệu phản hồi cho yêu cầu này.</p>
                        </div>
                    )}
                </div>

                {/* Modal Nộp Tài Liệu */}
                <FulfillRequestModal
                    isOpen={isFulfillOpen}
                    onClose={() => setIsFulfillOpen(false)}
                    requestId={item.ma_yeu_cau}
                />
            </div>
        </StudentLayout>
    );
}