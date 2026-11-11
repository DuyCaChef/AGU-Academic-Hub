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
    Sparkles,
    XCircle,
    X,
    Send,
    Hourglass,
    RefreshCw,
    Upload,
    Check,
    MessageSquareQuote
} from 'lucide-react';

export default function WishlistShow({ item, is_creator = false }) {
    const { auth, flash } = usePage().props;
    const currentUserId = auth?.user?.ma_nguoi_dung;

    const [isFulfillOpen, setIsFulfillOpen] = useState(false);
    const [processingFulfillmentId, setProcessingFulfillmentId] = useState(null);

    // Modal từ chối đóng góp
    const [rejectSubmissionModal, setRejectSubmissionModal] = useState({
        isOpen: false,
        submissionId: null,
        reason: '',
        isSubmitting: false,
    });

    // Modal tạo yêu cầu chỉnh sửa sau hoàn thành
    const [correctionModal, setCorrectionModal] = useState({
        isOpen: false,
        reason: '',
        isSubmitting: false,
    });

    // Modal phản hồi từ chối chỉnh sửa (Contributor B)
    const [rejectCorrectionModal, setRejectCorrectionModal] = useState({
        isOpen: false,
        correctionId: null,
        reason: '',
        isSubmitting: false,
    });

    // Modal nộp tài liệu thay thế (Contributor B)
    const [replacementModal, setReplacementModal] = useState({
        isOpen: false,
        correctionId: null,
        file: null,
        title: '',
        note: '',
        category: 'DE_THI',
        isSubmitting: false,
    });

    // Kích hoạt pháo hoa Confetti chúc mừng
    const triggerConfettiEffect = () => {
        const count = 200;
        const defaults = { origin: { y: 0.7 }, zIndex: 9999 };
        const fire = (particleRatio, opts) => {
            confetti({ ...defaults, ...opts, particleCount: Math.floor(count * particleRatio) });
        };
        fire(0.25, { spread: 26, startVelocity: 55 });
        fire(0.2, { spread: 60 });
        fire(0.35, { spread: 100, decay: 0.91, scalar: 0.8 });
        fire(0.1, { spread: 120, startVelocity: 25, decay: 0.92, scalar: 1.2 });
        fire(0.1, { spread: 120, startVelocity: 45 });
    };

    // 1. Xác nhận bài đóng góp -> Chuyển sang chờ Ban kiểm duyệt kiểm tra tính hợp lệ
    const handleConfirmFulfillment = (fulfillmentId) => {
        if (!confirm('Xác nhận tài liệu này chính xác và chuyển yêu cầu tới Ban kiểm duyệt để kiểm tra? Khi tài liệu hợp lệ, điểm thưởng sẽ được cộng ngay cho người đóng góp.')) {
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

    // 2. Từ chối bài đóng góp kèm lý do
    const handleRejectFulfillmentSubmit = (e) => {
        e.preventDefault();
        if (!rejectSubmissionModal.reason.trim()) return;

        setRejectSubmissionModal((prev) => ({ ...prev, isSubmitting: true }));
        router.post(
            route('document-requests.reject-fulfillment', item.ma_yeu_cau),
            {
                ma_dap_ung: rejectSubmissionModal.submissionId,
                ly_do: rejectSubmissionModal.reason,
            },
            {
                onSuccess: () => {
                    setRejectSubmissionModal({ isOpen: false, submissionId: null, reason: '', isSubmitting: false });
                },
                onError: () => {
                    setRejectSubmissionModal((prev) => ({ ...prev, isSubmitting: false }));
                },
            }
        );
    };

    // 3. Hủy yêu cầu và hoàn lại điểm vào ví
    const handleCancelRequest = () => {
        if (confirm('Bạn có chắc muốn hủy yêu cầu này? Điểm tạm giữ sẽ được hoàn lại 100% vào ví của bạn.')) {
            router.delete(route('document-requests.destroy', item.ma_yeu_cau));
        }
    };

    // 4. Tạo yêu cầu chỉnh sửa sau khi hoàn thành (Phần 6)
    const handleCreateCorrectionSubmit = (e) => {
        e.preventDefault();
        if (!correctionModal.reason.trim()) return;

        setCorrectionModal((prev) => ({ ...prev, isSubmitting: true }));
        router.post(
            route('document-requests.corrections.store', item.ma_yeu_cau),
            { ly_do_chinh_sua: correctionModal.reason },
            {
                onSuccess: () => {
                    setCorrectionModal({ isOpen: false, reason: '', isSubmitting: false });
                },
                onError: () => {
                    setCorrectionModal((prev) => ({ ...prev, isSubmitting: false }));
                },
            }
        );
    };

    // 5. Contributor phản hồi Chấp nhận yêu cầu chỉnh sửa
    const handleAcceptCorrection = (correctionId) => {
        if (!confirm('Bạn đồng ý hỗ trợ chỉnh sửa và sẽ tải lên phiên bản thay thế cho tài liệu này?')) return;

        router.post(route('corrections.respond', correctionId), {
            hanh_dong: 'CHAP_NHAN',
        });
    };

    // 6. Contributor phản hồi Từ chối yêu cầu chỉnh sửa
    const handleRejectCorrectionSubmit = (e) => {
        e.preventDefault();
        if (!rejectCorrectionModal.reason.trim()) return;

        setRejectCorrectionModal((prev) => ({ ...prev, isSubmitting: true }));
        router.post(
            route('corrections.respond', rejectCorrectionModal.correctionId),
            {
                hanh_dong: 'TU_CHOI',
                ly_do_tu_choi: rejectCorrectionModal.reason,
            },
            {
                onSuccess: () => {
                    setRejectCorrectionModal({ isOpen: false, correctionId: null, reason: '', isSubmitting: false });
                },
                onError: () => {
                    setRejectCorrectionModal((prev) => ({ ...prev, isSubmitting: false }));
                },
            }
        );
    };

    // 7. Contributor nộp phiên bản tài liệu thay thế
    const handleReplacementSubmit = (e) => {
        e.preventDefault();
        if (!replacementModal.file && !replacementModal.title) return;

        const formData = new FormData();
        if (replacementModal.file) {
            formData.append('file', replacementModal.file);
        }
        formData.append('tieu_de', replacementModal.title || `Bản thay thế - ${item.tieu_de}`);
        formData.append('ghi_chu', replacementModal.note);
        formData.append('phan_loai', replacementModal.category);

        setReplacementModal((prev) => ({ ...prev, isSubmitting: true }));
        router.post(route('corrections.submit-replacement', replacementModal.correctionId), formData, {
            forceFormData: true,
            onSuccess: () => {
                setReplacementModal({
                    isOpen: false,
                    correctionId: null,
                    file: null,
                    title: '',
                    note: '',
                    category: 'DE_THI',
                    isSubmitting: false,
                });
            },
            onError: () => {
                setReplacementModal((prev) => ({ ...prev, isSubmitting: false }));
            },
        });
    };

    // 8. Creator nghiệm thu hoàn thành bản thay thế
    const handleConfirmCompletion = (correctionId, replacementId) => {
        if (!confirm('Xác nhận phiên bản tài liệu thay thế này đã đạt yêu cầu và hoàn tất quá trình chỉnh sửa?')) {
            return;
        }

        router.post(route('corrections.confirm-completion', correctionId), {
            ma_lan_nop_thay_the: replacementId,
        });
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
    const isPendingPayout = item.trang_thai === 'CHO_GIAI_NGAN';
    const isFulfilled = item.trang_thai === 'DA_HOAN_THANH';
    const isCancelled = item.trang_thai === 'DA_HUY';
    const categoryBadge = getCategoryBadge(item.phan_loai || item.loai_tai_lieu);
    const fulfillmentList = item.dap_ungs || item.danh_sach_phan_hoi || [];
    const correctionsList = item.yeu_cau_chinh_suas || [];
    const activeCorrection = correctionsList.find((c) => c.trang_thai === 'CHO_XU_LY' || c.trang_thai === 'DANG_XU_LY') || correctionsList[0];

    const isContributorOfAccepted = item.dap_ung_duoc_chon?.ma_nguoi_dong_gop === currentUserId
        || fulfillmentList.some((f) => f.trang_thai === 'CHAP_NHAN' && (f.ma_nguoi_dong_gop === currentUserId || f.nguoi_dong_gop?.ma_nguoi_dung === currentUserId));

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

                {/* Banner khi yêu cầu đang chờ kiểm duyệt tài liệu (DEC-04 & Section 4.4) */}
                {isPendingPayout && (
                    <div className="bg-gradient-to-r from-purple-700 to-indigo-700 rounded-2xl p-5 text-white shadow-md flex items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-white/20 backdrop-blur-md rounded-xl">
                                <Hourglass className="w-6 h-6 text-purple-200 animate-pulse" />
                            </div>
                            <div>
                                <h3 className="font-extrabold text-base">Đang chờ Ban kiểm duyệt kiểm tra tài liệu</h3>
                                <p className="text-xs text-purple-100">
                                    Người tạo đã xác nhận tài liệu phù hợp. Hệ thống đang chuyển yêu cầu đến Ban kiểm duyệt để kiểm tra tính hợp lệ của tài liệu. Khi được duyệt, điểm thưởng {item.diem_thuong} Bounty sẽ được tự động cộng ngay cho người đóng góp.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Banner khi yêu cầu đã hoàn thành */}
                {isFulfilled && (
                    <div className="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-5 text-white shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-white/20 backdrop-blur-md rounded-xl">
                                <Sparkles className="w-6 h-6 text-amber-300" />
                            </div>
                            <div>
                                <h3 className="font-extrabold text-base">Yêu cầu đã được giải quyết thành công!</h3>
                                <p className="text-xs text-emerald-100">
                                    Điểm thưởng {item.diem_thuong} Bounty đã được giải ngân chuyển tới ví người đóng góp chính xác.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            {item.ma_tai_lieu_dap_ung && (
                                <a
                                    href={getDocumentUrl(item.ma_tai_lieu_dap_ung)}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="px-4 py-2 bg-white text-emerald-800 hover:bg-emerald-50 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 shadow-xs cursor-pointer"
                                >
                                    <ExternalLink className="w-3.5 h-3.5" />
                                    Xem tài liệu duyệt
                                </a>
                            )}

                            {/* Nút yêu cầu chỉnh sửa cho Người tạo (Phần 6.2) */}
                            {is_creator && (!activeCorrection || activeCorrection.trang_thai === 'HOAN_THANH' || activeCorrection.trang_thai === 'TU_CHOI' || activeCorrection.trang_thai === 'DA_HUY') && (
                                <button
                                    onClick={() => setCorrectionModal({ isOpen: true, reason: '', isSubmitting: false })}
                                    className="px-4 py-2 bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold text-xs rounded-xl transition-all shadow-xs flex items-center gap-1.5 cursor-pointer"
                                >
                                    <RefreshCw className="w-3.5 h-3.5" />
                                    Yêu cầu chỉnh sửa / bổ sung
                                </button>
                            )}
                        </div>
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
                                {isFulfilled && (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <CheckCircle2 className="w-3.5 h-3.5" /> Đã hoàn thành
                                    </span>
                                )}
                                {isPendingPayout && (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                        <Hourglass className="w-3.5 h-3.5" /> Chờ duyệt tài liệu
                                    </span>
                                )}
                                {isOpen && (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                        <Clock className="w-3.5 h-3.5" /> Đang tìm kiếm
                                    </span>
                                )}
                                {isCancelled && (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                        Đã hủy
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
                            {isOpen && 'Đang tiếp nhận các bản nộp phản hồi từ cộng đồng.'}
                            {isPendingPayout && 'Đang chờ Ban kiểm duyệt thực hiện quyết định giải ngân cuối cùng.'}
                            {isFulfilled && 'Yêu cầu này đã khép lại và giải ngân điểm thưởng hoàn tất.'}
                            {isCancelled && 'Yêu cầu này đã bị hủy và điểm tạm giữ đã hoàn lại vào ví.'}
                        </div>

                        <div className="flex items-center gap-2">
                            {/* Nút hủy yêu cầu (Chỉ người tạo hoặc Admin khi bài còn mở) */}
                            {is_creator && isOpen && (
                                <button
                                    onClick={handleCancelRequest}
                                    className="px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <Trash2 className="w-3.5 h-3.5" />
                                    Hủy & Hoàn Điểm
                                </button>
                            )}

                            {/* Nút nộp tài liệu (Dành cho người khác khi bài còn mở) */}
                            {!is_creator && isOpen && (
                                <button
                                    onClick={() => setIsFulfillOpen(true)}
                                    className="px-5 py-2.5 text-xs font-bold text-white bg-[#00a896] hover:bg-teal-700 rounded-xl shadow-xs transition-all flex items-center gap-2 active:scale-95 cursor-pointer"
                                >
                                    <Award className="w-4 h-4" />
                                    Nộp Tài Liệu Săn Thưởng
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* KHỐI YÊU CẦU CHỈNH SỬA SAU HOÀN THÀNH (PHẦN 6) */}
                {isFulfilled && activeCorrection && (
                    <div className="bg-white rounded-2xl border border-indigo-200 p-6 shadow-xs space-y-4">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div className="flex items-center gap-2">
                                <div className="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                                    <RefreshCw className="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 className="text-base font-bold text-slate-800">Tiến Trình Chỉnh Sửa / Bổ Sung Tài Liệu</h3>
                                    <p className="text-xs text-slate-500">Trao đổi và cập nhật phiên bản tài liệu trực tiếp giữa 2 bên</p>
                                </div>
                            </div>

                            {/* Trạng thái yêu cầu chỉnh sửa */}
                            <div>
                                {activeCorrection.trang_thai === 'CHO_XU_LY' && (
                                    <span className="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold rounded-full flex items-center gap-1">
                                        <Clock className="w-3.5 h-3.5" /> Chờ người đóng góp phản hồi
                                    </span>
                                )}
                                {activeCorrection.trang_thai === 'DANG_XU_LY' && (
                                    <span className="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-bold rounded-full flex items-center gap-1">
                                        <RefreshCw className="w-3.5 h-3.5" /> Đang xử lý chỉnh sửa
                                    </span>
                                )}
                                {activeCorrection.trang_thai === 'HOAN_THANH' && (
                                    <span className="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-full flex items-center gap-1">
                                        <CheckCircle2 className="w-3.5 h-3.5" /> Đã hoàn tất chỉnh sửa
                                    </span>
                                )}
                                {activeCorrection.trang_thai === 'TU_CHOI' && (
                                    <span className="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold rounded-full flex items-center gap-1">
                                        <XCircle className="w-3.5 h-3.5" /> Người đóng góp từ chối
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Lý do chỉnh sửa */}
                        <div className="p-4 bg-slate-50 rounded-xl space-y-1.5 text-xs">
                            <span className="font-bold text-slate-700 block">Nội dung yêu cầu từ người tạo:</span>
                            <p className="text-slate-600 italic">"{activeCorrection.ly_do_chinh_sua}"</p>
                            {activeCorrection.ly_do_tu_choi && (
                                <div className="pt-2 border-t border-slate-200 text-rose-600">
                                    <span className="font-bold block">Lý do từ chối hỗ trợ:</span>
                                    <span>"{activeCorrection.ly_do_tu_choi}"</span>
                                </div>
                            )}
                        </div>

                        {/* Các hành động đối với người đóng góp (B) */}
                        {isContributorOfAccepted && activeCorrection.trang_thai === 'CHO_XU_LY' && (
                            <div className="flex items-center gap-3 pt-2">
                                <button
                                    onClick={() => handleAcceptCorrection(activeCorrection.ma_yeu_cau_chinh_sua)}
                                    className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <Check className="w-3.5 h-3.5" />
                                    Chấp Nhận Chỉnh Sửa
                                </button>
                                <button
                                    onClick={() => setRejectCorrectionModal({
                                        isOpen: true,
                                        correctionId: activeCorrection.ma_yeu_cau_chinh_sua,
                                        reason: '',
                                        isSubmitting: false,
                                    })}
                                    className="px-4 py-2 bg-white text-rose-600 hover:bg-rose-50 border border-rose-200 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <X className="w-3.5 h-3.5" />
                                    Từ Chối Yêu Cầu
                                </button>
                            </div>
                        )}

                        {/* Nút nộp bản thay thế đối với B khi đang xử lý */}
                        {isContributorOfAccepted && activeCorrection.trang_thai === 'DANG_XU_LY' && (
                            <div className="pt-2">
                                <button
                                    onClick={() => setReplacementModal({
                                        isOpen: true,
                                        correctionId: activeCorrection.ma_yeu_cau_chinh_sua,
                                        file: null,
                                        title: '',
                                        note: '',
                                        category: 'DE_THI',
                                        isSubmitting: false,
                                    })}
                                    className="px-4 py-2 bg-[#005baa] hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <Upload className="w-3.5 h-3.5" />
                                    Nộp Phiên Bản Tài Liệu Thay Thế Mới
                                </button>
                            </div>
                        )}

                        {/* Danh sách các phiên bản tài liệu thay thế đã nộp */}
                        {activeCorrection.lan_nop_thay_thes?.length > 0 && (
                            <div className="space-y-3 pt-3 border-t border-slate-100">
                                <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Lịch sử các phiên bản thay thế ({activeCorrection.lan_nop_thay_thes.length})
                                </h4>
                                <div className="space-y-2">
                                    {activeCorrection.lan_nop_thay_thes.map((sub, idx) => {
                                        const isAcceptedVersion = sub.la_phien_ban_duoc_chap_nhan;
                                        return (
                                            <div
                                                key={sub.ma_lan_nop_thay_the}
                                                className={`p-3.5 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition-all ${
                                                    isAcceptedVersion
                                                        ? 'bg-emerald-50/70 border-emerald-300'
                                                        : 'bg-slate-50/60 border-slate-200'
                                                }`}
                                            >
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-bold text-slate-800">
                                                            Phiên bản #{activeCorrection.lan_nop_thay_thes.length - idx}
                                                        </span>
                                                        {isAcceptedVersion && (
                                                            <span className="px-2 py-0.5 bg-emerald-600 text-white text-[10px] font-extrabold rounded-md flex items-center gap-1">
                                                                <CheckCircle className="w-3 h-3" /> BẢN HIỂN THỊ CHÍNH
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="text-slate-600 italic">"{sub.ghi_chu || 'Không có ghi chú'}"</p>
                                                    <span className="text-[11px] text-slate-400 block">
                                                        Tài liệu: <strong>{sub.tai_lieu?.tieu_de || `#${sub.ma_tai_lieu}`}</strong>
                                                    </span>
                                                </div>

                                                <div className="flex items-center gap-2 shrink-0">
                                                    <a
                                                        href={getDocumentUrl(sub.ma_tai_lieu)}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="px-3 py-1.5 bg-white text-slate-700 border border-slate-200 hover:bg-slate-100 rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors"
                                                    >
                                                        <ExternalLink className="w-3 h-3" /> Xem File
                                                    </a>

                                                    {/* Người tạo nghiệm thu bản này nếu đang xử lý */}
                                                    {is_creator && activeCorrection.trang_thai === 'DANG_XU_LY' && !isAcceptedVersion && (
                                                        <button
                                                            onClick={() => handleConfirmCompletion(activeCorrection.ma_yeu_cau_chinh_sua, sub.ma_lan_nop_thay_the)}
                                                            className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs flex items-center gap-1 transition-colors shadow-2xs cursor-pointer"
                                                        >
                                                            <Check className="w-3.5 h-3.5" /> Nghiệm thu bản này
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Danh Sách Các Bài Nộp Giải Quyết */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="text-base font-bold text-slate-800 flex items-center gap-2">
                            <FileText className="w-5 h-5 text-[#005baa]" />
                            Danh Sách Phản Hồi Đóng Góp ({fulfillmentList.length})
                        </h3>
                    </div>

                    {fulfillmentList.length > 0 ? (
                        <div className="space-y-3">
                            {fulfillmentList.map((fh) => {
                                const responseId = fh.ma_dap_ung || fh.ma_phan_hoi;
                                const isAccepted = fh.trang_thai === 'CHAP_NHAN';
                                const isPendingMod = fh.trang_thai === 'NGUOI_YEU_CAU_XAC_NHAN';
                                const isRejected = fh.trang_thai === 'TU_CHOI';
                                const isPending = fh.trang_thai === 'CHO_DUYET';
                                const contributor = fh.nguoi_dong_gop || fh.nguoiDongGop;
                                const document = fh.tai_lieu || fh.taiLieu;
                                const note = fh.ghi_chu_dong_gop || fh.ghi_chu;
                                const rejectionReason = fh.ly_do_nguoi_tao_tu_choi || fh.ly_do_tu_choi_giai_ngan;

                                return (
                                    <div
                                        key={responseId}
                                        className={`p-5 rounded-2xl border transition-all ${
                                            isAccepted
                                                ? 'bg-emerald-50/50 border-emerald-300 ring-2 ring-emerald-400/20'
                                                : isPendingMod
                                                ? 'bg-purple-50/40 border-purple-200'
                                                : isRejected
                                                ? 'bg-slate-50/70 border-slate-200 opacity-80'
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

                                                    {/* Status badges */}
                                                    {isAccepted && (
                                                        <span className="text-[10px] font-extrabold px-2 py-0.5 bg-emerald-600 text-white rounded-md flex items-center gap-1">
                                                            <CheckCircle className="w-3 h-3" /> ĐÃ NHẬN THƯỞNG
                                                        </span>
                                                    )}
                                                    {isPendingMod && (
                                                        <span className="text-[10px] font-extrabold px-2 py-0.5 bg-purple-600 text-white rounded-md flex items-center gap-1">
                                                            <Hourglass className="w-3 h-3" /> CHỜ DUYỆT TÀI LIỆU
                                                        </span>
                                                    )}
                                                    {isRejected && (
                                                        <span className="text-[10px] font-extrabold px-2 py-0.5 bg-rose-600 text-white rounded-md flex items-center gap-1">
                                                            <XCircle className="w-3 h-3" /> ĐÃ TỪ CHỐI
                                                        </span>
                                                    )}
                                                    {isPending && (
                                                        <span className="text-[10px] font-bold px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-md flex items-center gap-1">
                                                            <Clock className="w-3 h-3" /> Chờ thẩm định
                                                        </span>
                                                    )}
                                                </div>

                                                <p className="text-xs text-slate-600 italic">
                                                    "{note || 'Không có ghi chú kèm theo.'}"
                                                </p>

                                                {isRejected && rejectionReason && (
                                                    <p className="text-xs text-rose-600 font-semibold pt-1">
                                                        Lý do từ chối: "{rejectionReason}"
                                                    </p>
                                                )}

                                                <div className="text-[11px] text-slate-500 flex items-center gap-3 pt-1 flex-wrap">
                                                    <span>
                                                        Tài liệu: <strong>{document?.tieu_de || `#${fh.ma_tai_lieu}`}</strong>
                                                    </span>
                                                    <span>
                                                        Lượt tải: <strong>{document?.luot_tai || 0}</strong>
                                                    </span>
                                                </div>
                                            </div>

                                            {/* Hành động duyệt / từ chối */}
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

                                                {/* Hành động của người tạo khi Bounty còn mở và bài nộp đang chờ duyệt */}
                                                {is_creator && isOpen && isPending && (
                                                    <>
                                                        <button
                                                            onClick={() => handleConfirmFulfillment(responseId)}
                                                            disabled={processingFulfillmentId === responseId}
                                                            className="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 rounded-lg shadow-xs flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer"
                                                        >
                                                            <UserCheck className="w-3.5 h-3.5" />
                                                            {processingFulfillmentId === responseId ? 'Đang duyệt...' : 'Chấp Thuận'}
                                                        </button>

                                                        <button
                                                            onClick={() => setRejectSubmissionModal({
                                                                isOpen: true,
                                                                submissionId: responseId,
                                                                reason: '',
                                                                isSubmitting: false,
                                                            })}
                                                            className="px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                                                        >
                                                            <X className="w-3.5 h-3.5" />
                                                            Từ Chối
                                                        </button>
                                                    </>
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

                {/* MODAL 1: TỪ CHỐI BÀI ĐÓNG GÓP (CREATOR) */}
                {rejectSubmissionModal.isOpen && (
                    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                <h3 className="text-base font-bold text-slate-800">Từ Chối Bài Đóng Góp</h3>
                                <button
                                    onClick={() => setRejectSubmissionModal({ isOpen: false, submissionId: null, reason: '', isSubmitting: false })}
                                    className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>

                            <form onSubmit={handleRejectFulfillmentSubmit} className="mt-4 space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                        Lý do từ chối <span className="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        rows={3}
                                        placeholder="Nhập lý do cụ thể (tài liệu sai môn, mờ, thiếu nội dung...)"
                                        value={rejectSubmissionModal.reason}
                                        onChange={(e) => setRejectSubmissionModal({ ...rejectSubmissionModal, reason: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 p-3 focus:outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                        required
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setRejectSubmissionModal({ isOpen: false, submissionId: null, reason: '', isSubmitting: false })}
                                        className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                                    >
                                        Hủy
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={rejectSubmissionModal.isSubmitting || !rejectSubmissionModal.reason.trim()}
                                        className="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:bg-slate-300 rounded-xl transition-colors"
                                    >
                                        {rejectSubmissionModal.isSubmitting ? 'Đang gửi...' : 'Xác Nhận Từ Chối'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* MODAL 2: TẠO YÊU CẦU CHỈNH SỬA SAU HOÀN THÀNH (CREATOR) */}
                {correctionModal.isOpen && (
                    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div className="flex items-center gap-2">
                                    <div className="p-2 bg-amber-50 text-amber-600 rounded-xl">
                                        <RefreshCw className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3 className="text-base font-bold text-slate-800">Yêu Cầu Chỉnh Sửa / Bổ Sung Tài Liệu</h3>
                                        <p className="text-xs text-slate-500">Trao đổi trực tiếp với người đóng góp để hoàn thiện học liệu</p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setCorrectionModal({ isOpen: false, reason: '', isSubmitting: false })}
                                    className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>

                            <form onSubmit={handleCreateCorrectionSubmit} className="mt-4 space-y-4">
                                <div className="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-800 space-y-1">
                                    <p className="font-bold">Lưu ý nghiệp vụ:</p>
                                    <p>Yêu cầu chỉnh sửa không làm thay đổi điểm thưởng đã giải ngân. Người đóng góp sẽ nhận được thông báo để bổ sung phiên bản mới.</p>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                        Nội dung cần chỉnh sửa, bổ sung <span className="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        rows={4}
                                        placeholder="Mô tả chi tiết những phần còn thiếu, các lỗi trang, câu hỏi cần giải thích thêm..."
                                        value={correctionModal.reason}
                                        onChange={(e) => setCorrectionModal({ ...correctionModal, reason: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 p-3 focus:outline-none focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20"
                                        required
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setCorrectionModal({ isOpen: false, reason: '', isSubmitting: false })}
                                        className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                                    >
                                        Hủy
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={correctionModal.isSubmitting || !correctionModal.reason.trim()}
                                        className="px-5 py-2 text-xs font-bold text-white bg-[#005baa] hover:bg-blue-800 disabled:bg-slate-300 rounded-xl transition-colors"
                                    >
                                        {correctionModal.isSubmitting ? 'Đang gửi...' : 'Gửi Yêu Cầu Chỉnh Sửa'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* MODAL 3: TỪ CHỐI HỖ TRỢ CHỈNH SỬA (CONTRIBUTOR B) */}
                {rejectCorrectionModal.isOpen && (
                    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                <h3 className="text-base font-bold text-slate-800">Từ Chối Chỉnh Sửa</h3>
                                <button
                                    onClick={() => setRejectCorrectionModal({ isOpen: false, correctionId: null, reason: '', isSubmitting: false })}
                                    className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>

                            <form onSubmit={handleRejectCorrectionSubmit} className="mt-4 space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                        Lý do từ chối hỗ trợ <span className="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        rows={3}
                                        placeholder="Nhập lý do (ví dụ: đã xóa file gốc, không còn lưu trữ tài liệu...)"
                                        value={rejectCorrectionModal.reason}
                                        onChange={(e) => setRejectCorrectionModal({ ...rejectCorrectionModal, reason: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 p-3 focus:outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                        required
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setRejectCorrectionModal({ isOpen: false, correctionId: null, reason: '', isSubmitting: false })}
                                        className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                                    >
                                        Hủy
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={rejectCorrectionModal.isSubmitting || !rejectCorrectionModal.reason.trim()}
                                        className="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:bg-slate-300 rounded-xl transition-colors"
                                    >
                                        {rejectCorrectionModal.isSubmitting ? 'Đang gửi...' : 'Xác Nhận Từ Chối'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* MODAL 4: NỘP PHIÊN BẢN TÀI LIỆU THAY THẾ MỚI (CONTRIBUTOR B) */}
                {replacementModal.isOpen && (
                    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                        <div className="relative bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div className="flex items-center gap-2">
                                    <div className="p-2 bg-blue-50 text-[#005baa] rounded-xl">
                                        <Upload className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h3 className="text-base font-bold text-slate-800">Nộp Phiên Bản Thay Thế Mới</h3>
                                        <p className="text-xs text-slate-500">Tải lên tệp đã hoàn thiện theo yêu cầu</p>
                                    </div>
                                </div>
                                <button
                                    onClick={() => setReplacementModal({ ...replacementModal, isOpen: false })}
                                    className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>

                            <form onSubmit={handleReplacementSubmit} className="mt-4 space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                        Chọn tệp thay thế (PDF, DOCX, PPTX, ZIP - Max 50MB) <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        accept=".pdf,.docx,.pptx,.zip"
                                        onChange={(e) => setReplacementModal({ ...replacementModal, file: e.target.files[0] })}
                                        className="w-full text-xs file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-[#005baa] hover:file:bg-blue-100 border border-slate-200 rounded-xl p-2 cursor-pointer"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Tiêu đề tài liệu</label>
                                    <input
                                        type="text"
                                        placeholder={`Bản thay thế - ${item.tieu_de}`}
                                        value={replacementModal.title}
                                        onChange={(e) => setReplacementModal({ ...replacementModal, title: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:border-[#005baa]"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">Ghi chú cho phiên bản này</label>
                                    <textarea
                                        rows={2}
                                        placeholder="Mô tả tóm tắt những nội dung đã được cập nhật..."
                                        value={replacementModal.note}
                                        onChange={(e) => setReplacementModal({ ...replacementModal, note: e.target.value })}
                                        className="w-full text-xs rounded-xl border border-slate-300 p-2.5 focus:outline-none focus:border-[#005baa]"
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                    <button
                                        type="button"
                                        onClick={() => setReplacementModal({ ...replacementModal, isOpen: false })}
                                        className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl"
                                    >
                                        Hủy
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={replacementModal.isSubmitting || !replacementModal.file}
                                        className="px-5 py-2 text-xs font-bold text-white bg-[#005baa] hover:bg-blue-800 disabled:bg-slate-300 rounded-xl transition-colors"
                                    >
                                        {replacementModal.isSubmitting ? 'Đang tải lên...' : 'Nộp Phiên Bản Mới'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Modal Nộp Tài Liệu Săn Thưởng Ban Đầu */}
                <FulfillRequestModal
                    isOpen={isFulfillOpen}
                    onClose={() => setIsFulfillOpen(false)}
                    requestId={item.ma_yeu_cau}
                    course={item.mon_hoc}
                />
            </div>
        </StudentLayout>
    );
}
