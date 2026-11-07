import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    CheckCircle2,
    Eye,
    MessageSquareWarning,
    Search,
    Trash2,
    ExternalLink,
    RotateCcw,
    BookOpen,
    GraduationCap,
    FileText,
    Clock,
    Layers,
    User,
    AlertCircle,
} from 'lucide-react';

const reasonLabels = {
    NGON_TU_KICH_DONG: 'Ngôn từ kích động / xúc phạm',
    BOI_NHO: 'Bôi nhọ / Vu khống',
    QUAY_ROI: 'Quấy rối sinh viên khác',
    SPAM: 'Spam / Rao vặt',
    BAN_QUYEN: 'Vi phạm bản quyền',
    SAI_NOI_DUNG: 'Sai lệch nội dung',
    MA_DOC: 'Chứa mã độc / Virus',
    TRUNG_LAP: 'Tài liệu trùng lặp',
    KHAC: 'Lý do khác',
};

const reasonStyles = {
    NGON_TU_KICH_DONG: 'border-rose-200 bg-rose-50 text-rose-700',
    BOI_NHO: 'border-purple-200 bg-purple-50 text-purple-700',
    QUAY_ROI: 'border-amber-200 bg-amber-50 text-amber-700',
    SPAM: 'border-sky-200 bg-sky-50 text-sky-700',
    BAN_QUYEN: 'border-red-200 bg-red-50 text-red-700',
    SAI_NOI_DUNG: 'border-orange-200 bg-orange-50 text-orange-700',
    MA_DOC: 'border-rose-300 bg-rose-100 text-rose-800 font-bold',
    TRUNG_LAP: 'border-slate-200 bg-slate-100 text-slate-700',
    KHAC: 'border-slate-200 bg-slate-100 text-slate-700',
};

const statusLabels = {
    CHO_XU_LY: 'Chờ xử lý',
    DA_XU_LY: 'Đã xử lý',
    BAC_BO: 'Bác bỏ',
};

const statusStyles = {
    CHO_XU_LY: 'border-amber-200 bg-amber-50 text-amber-700',
    DA_XU_LY: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    BAC_BO: 'border-slate-200 bg-slate-100 text-slate-600',
};

export default function ReviewReportsIndex({ reports, filters = {}, reasons = [], stats = {} }) {
    const items = reports?.data || [];
    const [query, setQuery] = useState({
        type: filters.type || 'ALL',
        status: filters.status || '',
        reason: filters.reason || '',
        from: filters.from || '',
        to: filters.to || '',
        search: filters.search || '',
    });

    const [expandedReviews, setExpandedReviews] = useState({});

    const toggleExpand = (id) => {
        setExpandedReviews((prev) => ({
            ...prev,
            [id]: !prev[id],
        }));
    };

    const handleTabChange = (typeVal) => {
        const nextQuery = { ...query, type: typeVal };
        setQuery(nextQuery);
        router.get(route('admin.review-reports.index'), nextQuery, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const submitFilters = (event) => {
        event.preventDefault();
        router.get(route('admin.review-reports.index'), query, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        const cleared = { type: 'ALL', status: '', reason: '', from: '', to: '', search: '' };
        setQuery(cleared);
        router.get(route('admin.review-reports.index'), cleared, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const archiveReport = (report) => {
        if (!window.confirm(`Bạn có chắc muốn lưu trữ báo cáo #${report.ma_bao_cao} khỏi hàng đợi?`)) {
            return;
        }

        const archiveUrl = report.url_luu_tru || route('admin.review-reports.archive', report.ma_bao_cao);
        router.delete(archiveUrl, {
            preserveScroll: true,
        });
    };

    const formatDate = (dateString) => {
        if (!dateString) return '—';
        return new Date(dateString).toLocaleDateString('vi-VN', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AdminLayout title="Hàng đợi Báo cáo Vi phạm">
            <Head title="Hàng đợi Báo cáo Vi phạm - AGU Hub Admin" />

            <div className="space-y-6">
                {/* 1. Header & Thống kê nhanh toàn diện */}
                <div className="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="rounded-xl border border-rose-100 bg-rose-50 p-2 text-rose-600">
                                <MessageSquareWarning className="h-5 w-5" />
                            </span>
                            <div>
                                <h1 className="text-xl font-bold text-slate-900">
                                    Hàng đợi Tiếp nhận & Xử lý Báo cáo Vi phạm
                                </h1>
                                <p className="mt-1 text-xs text-slate-500">
                                    Trung tâm kiểm duyệt các phản ánh vi phạm tiêu chuẩn cộng đồng về Môn học, Giảng viên và Tài liệu.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Quick Stats Badges */}
                    <div className="flex flex-wrap items-center gap-2">
                        <div className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">
                            Chờ xử lý: <strong className="text-amber-900">{stats?.pending ?? 0}</strong>
                        </div>
                        <div className="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">
                            Môn học: <strong className="text-indigo-900">{stats?.course_count ?? 0}</strong>
                        </div>
                        <div className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">
                            Giảng viên: <strong className="text-amber-900">{stats?.lecturer_count ?? 0}</strong>
                        </div>
                        <div className="rounded-xl border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                            Tài liệu: <strong className="text-blue-900">{stats?.document_count ?? 0}</strong>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700">
                            Tổng cộng: <strong className="text-slate-900">{stats?.total ?? 0}</strong>
                        </div>
                    </div>
                </div>

                {/* 2. Hàng Tab lọc nhanh theo Loại vi phạm (Crystal-Clear Category Navigation) */}
                <div className="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-2">
                    {[
                        {
                            id: 'ALL',
                            label: 'Tất cả báo cáo',
                            icon: Layers,
                            count: stats?.total ?? 0,
                            activeColor: 'bg-slate-900 text-white',
                            inactiveColor: 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200',
                        },
                        {
                            id: 'MON_HOC',
                            label: 'Đánh giá Môn học',
                            icon: BookOpen,
                            count: stats?.course_count ?? 0,
                            pending: stats?.course_pending ?? 0,
                            activeColor: 'bg-indigo-600 text-white',
                            inactiveColor: 'bg-white text-indigo-700 hover:bg-indigo-50/50 border border-indigo-200',
                        },
                        {
                            id: 'GIANG_VIEN',
                            label: 'Đánh giá Giảng viên',
                            icon: GraduationCap,
                            count: stats?.lecturer_count ?? 0,
                            pending: stats?.lecturer_pending ?? 0,
                            activeColor: 'bg-amber-600 text-white',
                            inactiveColor: 'bg-white text-amber-800 hover:bg-amber-50/50 border border-amber-200',
                        },
                        {
                            id: 'TAI_LIEU',
                            label: 'Tài liệu học thuật',
                            icon: FileText,
                            count: stats?.document_count ?? 0,
                            pending: stats?.document_pending ?? 0,
                            activeColor: 'bg-[#005baa] text-white',
                            inactiveColor: 'bg-white text-blue-700 hover:bg-blue-50/50 border border-blue-200',
                        },
                    ].map((tab) => {
                        const Icon = tab.icon;
                        const isActive = (query.type || 'ALL') === tab.id;
                        return (
                            <button
                                key={tab.id}
                                type="button"
                                onClick={() => handleTabChange(tab.id)}
                                className={`inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all shadow-2xs ${isActive ? tab.activeColor : tab.inactiveColor
                                    }`}
                            >
                                <Icon className="h-4 w-4" />
                                <span>{tab.label}</span>
                                <span
                                    className={`rounded-full px-2 py-0.5 text-[10px] font-extrabold ${isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'
                                        }`}
                                >
                                    {tab.count}
                                </span>
                                {tab.pending > 0 && !isActive && (
                                    <span className="flex h-2 w-2 rounded-full bg-amber-500 animate-pulse" title={`${tab.pending} chờ xử lý`} />
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* 3. Bộ lọc tìm kiếm */}
                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs sm:grid-cols-2 lg:grid-cols-6"
                >
                    {/* Search box */}
                    <div className="lg:col-span-2">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                            <input
                                type="text"
                                value={query.search}
                                onChange={(e) => setQuery({ ...query, search: e.target.value })}
                                placeholder="Tìm mã môn, tên môn, giảng viên, tài liệu..."
                                className="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa]"
                            />
                        </div>
                    </div>

                    {/* Filter Status */}
                    <select
                        value={query.status}
                        onChange={(e) => setQuery({ ...query, status: e.target.value })}
                        className="rounded-lg border border-slate-200 text-sm focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa]"
                    >
                        <option value="">Mọi trạng thái</option>
                        {Object.entries(statusLabels).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </select>

                    {/* Filter Reason */}
                    <select
                        value={query.reason}
                        onChange={(e) => setQuery({ ...query, reason: e.target.value })}
                        className="rounded-lg border border-slate-200 text-sm focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa]"
                    >
                        <option value="">Mọi lý do</option>
                        {reasons.map((reason) => (
                            <option key={reason} value={reason}>
                                {reasonLabels[reason] || reason}
                            </option>
                        ))}
                    </select>

                    {/* Filter From Date */}
                    <input
                        type="date"
                        value={query.from}
                        onChange={(e) => setQuery({ ...query, from: e.target.value })}
                        className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa]"
                        aria-label="Từ ngày"
                    />

                    {/* Action buttons */}
                    <div className="flex items-center gap-2">
                        <button
                            type="submit"
                            className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#005baa] px-3 py-2 text-sm font-semibold text-white shadow-xs transition hover:bg-[#004b8d]"
                        >
                            <Search className="h-4 w-4" />
                            <span>Lọc</span>
                        </button>
                        <button
                            type="button"
                            onClick={resetFilters}
                            title="Xóa bộ lọc"
                            className="rounded-lg border border-slate-200 p-2 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
                        >
                            <RotateCcw className="h-4 w-4" />
                        </button>
                    </div>
                </form>

                {/* 4. Bảng danh sách báo cáo với nhận diện loại vi phạm trực quan */}
                <div className="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    {items.length === 0 ? (
                        <div className="p-12 text-center">
                            <CheckCircle2 className="mx-auto mb-4 h-10 w-10 text-emerald-600" />
                            <h3 className="text-base font-bold text-slate-900">
                                Không có báo cáo nào phù hợp
                            </h3>
                            <p className="mt-1 text-xs text-slate-500">
                                Các bài đánh giá và tài liệu chia sẻ hiện đang tuân thủ tốt tiêu chuẩn cộng đồng AGU Academic Hub.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="border-b border-slate-200/80 bg-slate-50/75 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th className="px-6 py-4">Phân loại & Mục tiêu</th>
                                        <th className="px-6 py-4">Nội dung bị phản ánh</th>
                                        <th className="px-6 py-4">Lý do vi phạm</th>
                                        <th className="px-6 py-4">Người báo cáo</th>
                                        <th className="px-6 py-4">Trạng thái</th>
                                        <th className="px-6 py-4 text-right">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((report) => {
                                        const isCourse = report.loai_doi_tuong === 'MON_HOC';
                                        const isLecturer = report.loai_doi_tuong === 'GIANG_VIEN';
                                        const isDocument = report.loai_doi_tuong === 'TAI_LIEU';

                                        return (
                                            <tr key={report.unique_id || report.ma_bao_cao} className="hover:bg-slate-50/50 transition-colors">
                                                {/* Cột 1: Phân loại & Mục tiêu phản ánh */}
                                                <td className="px-6 py-4 align-top">
                                                    <div className="space-y-1.5">
                                                        {/* Distinct Type Badge */}
                                                        {isCourse && (
                                                            <span className="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">
                                                                <BookOpen className="h-3.5 w-3.5" />
                                                                <span>Đánh giá Môn học</span>
                                                            </span>
                                                        )}
                                                        {isLecturer && (
                                                            <span className="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">
                                                                <GraduationCap className="h-3.5 w-3.5" />
                                                                <span>Đánh giá Giảng viên</span>
                                                            </span>
                                                        )}
                                                        {isDocument && (
                                                            <span className="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">
                                                                <FileText className="h-3.5 w-3.5" />
                                                                <span>Tài liệu học thuật</span>
                                                            </span>
                                                        )}

                                                        {/* Target Info */}
                                                        {isLecturer ? (
                                                            <div>
                                                                <div className="font-bold text-slate-900 text-sm flex items-center gap-1">
                                                                    <User className="h-3.5 w-3.5 text-amber-700 shrink-0" />
                                                                    <span>{report.ten_giang_vien || 'Giảng viên phụ trách'}</span>
                                                                </div>
                                                                <div className="text-xs text-slate-500 mt-0.5">
                                                                    Môn: <span className="font-semibold">{report.ma_mon_hoc}</span> - {report.ten_mon_hoc}
                                                                </div>
                                                            </div>
                                                        ) : isDocument ? (
                                                            <div>
                                                                <div className="font-semibold text-slate-900 text-sm line-clamp-1" title={report.ten_mon_hoc}>
                                                                    {report.ten_mon_hoc}
                                                                </div>
                                                                <div className="text-xs text-slate-500 mt-0.5">
                                                                    Mã: <span className="font-mono font-medium">{report.ma_mon_hoc}</span>
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <div>
                                                                <div className="font-bold text-slate-900 text-sm">
                                                                    {report.ma_mon_hoc}
                                                                </div>
                                                                <div className="text-xs text-slate-500 line-clamp-1">
                                                                    {report.ten_mon_hoc}
                                                                </div>
                                                            </div>
                                                        )}

                                                        {/* External View Link */}
                                                        {report.url_xem_ngoai && (
                                                            <a
                                                                href={report.url_xem_ngoai}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex items-center gap-1 text-[11px] font-medium text-brand-primary hover:underline pt-0.5"
                                                            >
                                                                <span>Xem trực tiếp</span>
                                                                <ExternalLink size={10} />
                                                            </a>
                                                        )}
                                                    </div>
                                                </td>

                                                {/* Cột 2: Nội dung bị phản ánh (Kèm xem thêm/thu gọn) */}
                                                <td className="px-6 py-4 max-w-sm align-top">
                                                    {(() => {
                                                        const fullContent = report.noi_dung_chinh || '';
                                                        const isLong = fullContent.length > 80;
                                                        const isExpanded = !!expandedReviews[report.unique_id || report.ma_bao_cao];

                                                        return (
                                                            <>
                                                                <div className="text-xs italic text-slate-800 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80 leading-relaxed break-words">
                                                                    "{fullContent ? (
                                                                        isLong && !isExpanded ? (
                                                                            <>
                                                                                <span>{fullContent.slice(0, 80).trim()}...</span>{' '}
                                                                                <button
                                                                                    type="button"
                                                                                    onClick={() => toggleExpand(report.unique_id || report.ma_bao_cao)}
                                                                                    className="not-italic inline-flex items-center text-[#005baa] hover:text-[#00437d] font-semibold hover:underline cursor-pointer ml-1"
                                                                                >
                                                                                    (xem thêm)
                                                                                </button>
                                                                            </>
                                                                        ) : (
                                                                            <>
                                                                                <span>{fullContent}</span>
                                                                                {isLong && (
                                                                                    <button
                                                                                        type="button"
                                                                                        onClick={() => toggleExpand(report.unique_id || report.ma_bao_cao)}
                                                                                        className="not-italic inline-flex items-center text-slate-500 hover:text-slate-700 font-semibold hover:underline cursor-pointer ml-1.5"
                                                                                    >
                                                                                        (thu gọn)
                                                                                    </button>
                                                                                )}
                                                                            </>
                                                                        )
                                                                    ) : (
                                                                        <span className="text-slate-400 not-italic">Không có nội dung mô tả</span>
                                                                    )}"
                                                                </div>

                                                                {/* Contextual metadata */}
                                                                {isDocument ? (
                                                                    <div className="mt-1.5 flex items-center gap-2 text-[11px] text-slate-400">
                                                                        <span>Loại: <strong className="text-slate-600 font-mono uppercase">{report.loai_tep || 'TÀI LIỆU'}</strong></span>
                                                                        {report.dung_luong && (
                                                                            <>
                                                                                <span>•</span>
                                                                                <span>Dung lượng: <strong className="text-slate-600">{(report.dung_luong / 1024).toFixed(1)} KB</strong></span>
                                                                            </>
                                                                        )}
                                                                    </div>
                                                                ) : (
                                                                    <div className="mt-1.5 flex items-center gap-2 text-[11px] text-slate-400">
                                                                        <span>Học kỳ: <strong className="text-slate-600">{report.hoc_ky || '—'}</strong></span>
                                                                        <span>•</span>
                                                                        <span>Độ khó: <strong className="text-slate-600">{report.diem_do_kho || 0}/5</strong></span>
                                                                        {report.an_danh && (
                                                                            <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-600 font-medium">Ẩn danh</span>
                                                                        )}
                                                                    </div>
                                                                )}
                                                            </>
                                                        );
                                                    })()}
                                                </td>

                                                {/* Cột 3: Lý do vi phạm & Phản ánh */}
                                                <td className="px-6 py-4 align-top">
                                                    <span className={`inline-block rounded-full border px-2.5 py-0.5 text-xs font-semibold ${reasonStyles[report.ly_do] || 'border-slate-200 bg-slate-50 text-slate-700'}`}>
                                                        {reasonLabels[report.ly_do] || report.ly_do}
                                                    </span>
                                                    {report.chi_tiet && (
                                                        <p className="mt-1.5 text-xs text-slate-500 italic break-words max-w-xs" title={report.chi_tiet}>
                                                            {report.chi_tiet.length > 70 ? `${report.chi_tiet.slice(0, 70)}...` : report.chi_tiet}
                                                        </p>
                                                    )}
                                                </td>

                                                {/* Cột 4: Người báo cáo */}
                                                <td className="px-6 py-4 text-xs align-top">
                                                    <div className="font-medium text-slate-800">
                                                        {report.nguoi_bao_cao?.ho_ten || 'Thành viên Hub'}
                                                    </div>
                                                    {report.nguoi_bao_cao?.mssv && (
                                                        <div className="text-[11px] font-mono text-slate-500">
                                                            {report.nguoi_bao_cao.mssv}
                                                        </div>
                                                    )}
                                                    <div className="mt-1 text-[11px] text-slate-400 flex items-center gap-1">
                                                        <Clock size={11} />
                                                        <span>{formatDate(report.created_at)}</span>
                                                    </div>
                                                </td>

                                                {/* Cột 5: Trạng thái & Ghi chú xử lý */}
                                                <td className="px-6 py-4 text-xs align-top">
                                                    <span className={`inline-block rounded-full border px-2.5 py-0.5 font-medium ${statusStyles[report.trang_thai] || 'border-slate-200 bg-slate-50 text-slate-600'}`}>
                                                        {statusLabels[report.trang_thai] || report.trang_thai}
                                                    </span>
                                                    {report.nguoi_xu_ly && (
                                                        <div className="mt-1 text-[11px] text-slate-400">
                                                            bởi {report.nguoi_xu_ly?.ho_ten}
                                                        </div>
                                                    )}
                                                    {report.ghi_chu_xu_ly && (
                                                        <div
                                                            className="mt-1.5 max-w-[220px] rounded-lg border border-slate-200 bg-slate-50 p-2 text-[11px] text-slate-700 break-words"
                                                            title={report.ghi_chu_xu_ly}
                                                        >
                                                            <div className="font-bold text-[10px] uppercase text-slate-500">Ghi chú xử lý:</div>
                                                            <div className="italic mt-0.5 line-clamp-2">
                                                                "{report.ghi_chu_xu_ly}"
                                                            </div>
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Cột 6: Thao tác */}
                                                <td className="px-6 py-4 text-right align-top">
                                                    <div className="inline-flex items-center gap-2">
                                                        <Link
                                                            href={report.url_chi_tiet}
                                                            className="inline-flex items-center gap-1 rounded-lg bg-brand-primary/10 px-2.5 py-1.5 text-xs font-semibold text-brand-primary hover:bg-brand-primary hover:text-white transition-colors"
                                                        >
                                                            <Eye className="h-3.5 w-3.5" />
                                                            <span>Xem & Xử lý</span>
                                                        </Link>

                                                        {['DA_XU_LY', 'BAC_BO'].includes(report.trang_thai) && (
                                                            <button
                                                                type="button"
                                                                onClick={() => archiveReport(report)}
                                                                title="Lưu trữ báo cáo khỏi hàng đợi"
                                                                aria-label={`Lưu trữ báo cáo #${report.ma_bao_cao}`}
                                                                className="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-200"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={reports?.links} />
                </div>
            </div>
        </AdminLayout>
    );
}
