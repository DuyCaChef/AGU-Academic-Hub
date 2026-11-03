import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    ClipboardList,
    Search,
    RotateCcw,
    Calendar,
    Filter,
    Shield,
    User,
    Clock,
    FileText,
    ArrowRight,
    Eye,
    X,
    CheckCircle2,
    AlertCircle,
    Info,
    Laptop,
    Globe,
    Layers,
    History
} from 'lucide-react';

export default function AuditLogsIndex({
    logs,
    filters = {},
    stats = {},
    available_actions = [],
    available_target_types = [],
}) {
    const items = logs?.data || [];

    // State phục vụ bộ lọc
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedAction, setSelectedAction] = useState(filters.hanh_dong || '');
    const [selectedTargetType, setSelectedTargetType] = useState(filters.loai_doi_tuong || '');
    const [fromDate, setFromDate] = useState(filters.tu_ngay || '');
    const [toDate, setToDate] = useState(filters.den_ngay || '');

    // State Modal Xem chi tiết Diff
    const [activeLog, setActiveLog] = useState(null);

    // Gửi request lọc
    const applyFilters = (newParams = {}) => {
        const queryParams = {
            search: searchTerm,
            hanh_dong: selectedAction,
            loai_doi_tuong: selectedTargetType,
            tu_ngay: fromDate,
            den_ngay: toDate,
            ...newParams,
        };

        Object.keys(queryParams).forEach((key) => {
            if (!queryParams[key]) delete queryParams[key];
        });

        router.get(route('admin.audit-logs.index'), queryParams, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Debounce tìm kiếm
    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchTerm !== (filters.search || '')) {
                applyFilters({ search: searchTerm });
            }
        }, 400);

        return () => clearTimeout(timer);
    }, [searchTerm]);

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedAction('');
        setSelectedTargetType('');
        setFromDate('');
        setToDate('');

        router.get(route('admin.audit-logs.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Badge màu cho Action
    const getBadgeStyle = (color) => {
        switch (color) {
            case 'emerald':
                return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
            case 'rose':
                return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
            case 'amber':
                return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
            case 'indigo':
                return 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800';
            case 'purple':
                return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800';
            case 'teal':
                return 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800';
            case 'blue':
                return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
            default:
                return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
        }
    };

    return (
        <AdminLayout title="Nhật ký kiểm toán nghiệp vụ Admin">
            <div className="space-y-6">
                {/* Header & Chỉ số tổng quan */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-blue-600/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <ClipboardList className="w-6 h-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                    Nhật ký kiểm toán nghiệp vụ
                                </h1>
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    Hệ thống lưu vết bất biến (Append-Only) bảo đảm tính toàn vẹn và truy cứu trách nhiệm
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Thống kê 4 thẻ tóm tắt */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                        <div className="flex items-center justify-between text-slate-500 text-xs font-medium">
                            <span>TỔNG SỐ NHẬT KÝ</span>
                            <History className="w-4 h-4 text-blue-500" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                            {Number(stats.tong_so_nhat_ky || 0).toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-slate-400">Bản ghi được bảo vệ toàn vẹn</p>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                        <div className="flex items-center justify-between text-slate-500 text-xs font-medium">
                            <span>HÔM NAY</span>
                            <Clock className="w-4 h-4 text-emerald-500" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                            {Number(stats.so_nhat_ky_hom_nay || 0).toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-slate-400">Hành động trong ngày</p>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                        <div className="flex items-center justify-between text-slate-500 text-xs font-medium">
                            <span>NGƯỜI THỰC HIỆN</span>
                            <User className="w-4 h-4 text-purple-500" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                            {Number(stats.so_nguoi_thuc_hien || 0).toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-slate-400">Admin & Mod ghi nhận</p>
                    </div>

                    <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                        <div className="flex items-center justify-between text-slate-500 text-xs font-medium">
                            <span>NGHIỆP VỤ GIÁM SÁT</span>
                            <Layers className="w-4 h-4 text-amber-500" />
                        </div>
                        <div className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                            {Number(stats.so_hanh_dong_khac_nhau || 0).toLocaleString()}
                        </div>
                        <p className="mt-1 text-xs text-slate-400">Các loại sự kiện nhạy cảm</p>
                    </div>
                </div>

                {/* Thanh công cụ & Bộ lọc */}
                <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-3">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-3">
                        {/* Tìm kiếm từ khóa */}
                        <div className="md:col-span-4 relative">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Tìm theo nội dung, mã đối tượng, IP, tên Admin..."
                                className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        {/* Lọc theo Hành động */}
                        <div className="md:col-span-3">
                            <select
                                value={selectedAction}
                                onChange={(e) => {
                                    setSelectedAction(e.target.value);
                                    applyFilters({ hanh_dong: e.target.value });
                                }}
                                className="w-full py-2 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Tất cả hành động</option>
                                {available_actions.map((act) => (
                                    <option key={act.value} value={act.value}>
                                        {act.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Lọc theo Loại đối tượng */}
                        <div className="md:col-span-2">
                            <select
                                value={selectedTargetType}
                                onChange={(e) => {
                                    setSelectedTargetType(e.target.value);
                                    applyFilters({ loai_doi_tuong: e.target.value });
                                }}
                                className="w-full py-2 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Tất cả đối tượng</option>
                                {available_target_types.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Từ ngày */}
                        <div className="md:col-span-3 flex items-center gap-2">
                            <input
                                type="date"
                                value={fromDate}
                                onChange={(e) => {
                                    setFromDate(e.target.value);
                                    applyFilters({ tu_ngay: e.target.value });
                                }}
                                className="w-full py-2 px-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <span className="text-slate-400 text-xs">→</span>
                            <input
                                type="date"
                                value={toDate}
                                onChange={(e) => {
                                    setToDate(e.target.value);
                                    applyFilters({ den_ngay: e.target.value });
                                }}
                                className="w-full py-2 px-2 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            {(searchTerm || selectedAction || selectedTargetType || fromDate || toDate) && (
                                <button
                                    onClick={handleResetFilters}
                                    title="Đặt lại bộ lọc"
                                    className="p-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                >
                                    <RotateCcw className="w-4 h-4" />
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Bảng dữ liệu Nhật ký kiểm toán */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th className="py-3.5 px-4 font-semibold">Thời điểm</th>
                                    <th className="py-3.5 px-4 font-semibold">Người thực hiện</th>
                                    <th className="py-3.5 px-4 font-semibold">Hành động</th>
                                    <th className="py-3.5 px-4 font-semibold">Đối tượng</th>
                                    <th className="py-3.5 px-4 font-semibold">Mô tả chi tiết</th>
                                    <th className="py-3.5 px-4 font-semibold">Địa chỉ IP</th>
                                    <th className="py-3.5 px-4 font-semibold text-right">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {items.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="py-12 text-center text-slate-400">
                                            <div className="flex flex-col items-center justify-center">
                                                <ClipboardList className="w-12 h-12 text-slate-300 dark:text-slate-600 mb-2" />
                                                <p className="text-base font-medium">Không tìm thấy bản ghi kiểm toán phù hợp</p>
                                                <p className="text-xs text-slate-400 mt-1">
                                                    Hãy thử điều chỉnh lại bộ lọc hoặc từ khóa tìm kiếm
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((log) => (
                                        <tr
                                            key={log.id}
                                            className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
                                        >
                                            {/* Thời điểm */}
                                            <td className="py-3 px-4 whitespace-nowrap">
                                                <div className="font-medium text-slate-900 dark:text-white">
                                                    {log.thoi_gian}
                                                </div>
                                                <div className="text-xs text-slate-400">
                                                    {log.thoi_gian_truoc}
                                                </div>
                                            </td>

                                            {/* Người thực hiện */}
                                            <td className="py-3 px-4">
                                                {log.nguoi_thuc_hien ? (
                                                    <div>
                                                        <div className="font-medium text-slate-900 dark:text-white">
                                                            {log.nguoi_thuc_hien.ho_ten}
                                                        </div>
                                                        <div className="text-xs text-slate-400">
                                                            {log.nguoi_thuc_hien.email}
                                                        </div>
                                                        <span className="inline-block mt-0.5 px-1.5 py-0.5 text-[10px] rounded font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                            {log.nguoi_thuc_hien.cap_quan_tri === 'TOI_CAO' ? 'Super Admin' : (log.nguoi_thuc_hien.vai_tro === 'QUAN_TRI_VIEN' ? 'Quản trị viên' : 'Kiểm duyệt viên')}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-slate-400 italic text-xs">Hệ thống</span>
                                                )}
                                            </td>

                                            {/* Hành động */}
                                            <td className="py-3 px-4 whitespace-nowrap">
                                                <span
                                                    className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border ${getBadgeStyle(
                                                        log.hanh_dong?.color
                                                    )}`}
                                                >
                                                    <span className="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                    {log.hanh_dong?.label}
                                                </span>
                                            </td>

                                            {/* Đối tượng tác động */}
                                            <td className="py-3 px-4 whitespace-nowrap">
                                                <div className="font-medium text-slate-800 dark:text-slate-200">
                                                    {log.loai_doi_tuong?.label}
                                                </div>
                                                {log.ma_doi_tuong && (
                                                    <div className="text-xs font-mono text-slate-500 dark:text-slate-400">
                                                        #{log.ma_doi_tuong}
                                                    </div>
                                                )}
                                            </td>

                                            {/* Mô tả chi tiết */}
                                            <td className="py-3 px-4 max-w-md">
                                                <p className="text-xs text-slate-700 dark:text-slate-300 line-clamp-2" title={log.mo_ta}>
                                                    {log.mo_ta}
                                                </p>
                                            </td>

                                            {/* Địa chỉ IP */}
                                            <td className="py-3 px-4 whitespace-nowrap font-mono text-xs text-slate-500">
                                                {log.dia_chi_ip || 'N/A'}
                                            </td>

                                            {/* Thao tác Xem chi tiết */}
                                            <td className="py-3 px-4 text-right whitespace-nowrap">
                                                <button
                                                    onClick={() => setActiveLog(log)}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/50 rounded-lg transition"
                                                >
                                                    <Eye className="w-3.5 h-3.5" />
                                                    <span>Chi tiết</span>
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Phân trang */}
                    {logs && (
                        <div className="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                            <div className="text-xs text-slate-500">
                                Hiển thị {logs.from || 0} - {logs.to || 0} trên tổng số {logs.total || 0} bản ghi
                            </div>
                            <Pagination links={logs.links} />
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Xem chi tiết bản ghi & Diff Snapshot */}
            {activeLog && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
                    <div className="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col max-h-[90vh]">
                        {/* Header modal */}
                        <div className="p-4 px-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                    <ClipboardList className="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900 dark:text-white">
                                        Chi tiết nhật ký kiểm toán #{activeLog.id}
                                    </h3>
                                    <p className="text-xs text-slate-500">
                                        Ghi nhận lúc {activeLog.thoi_gian} ({activeLog.thoi_gian_truoc})
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={() => setActiveLog(null)}
                                className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Nội dung chi tiết modal */}
                        <div className="p-6 overflow-y-auto space-y-5 text-sm">
                            {/* Khối tóm tắt */}
                            <div className="grid grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800">
                                <div>
                                    <span className="text-xs text-slate-400 font-medium block">HÀNH ĐỘNG</span>
                                    <span
                                        className={`inline-block mt-1 px-2.5 py-1 rounded-full text-xs font-semibold border ${getBadgeStyle(
                                            activeLog.hanh_dong?.color
                                        )}`}
                                    >
                                        {activeLog.hanh_dong?.label}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-xs text-slate-400 font-medium block">ĐỐI TƯỢNG TÁC ĐỘNG</span>
                                    <span className="font-semibold text-slate-800 dark:text-slate-200 mt-1 block">
                                        {activeLog.loai_doi_tuong?.label}{' '}
                                        {activeLog.ma_doi_tuong ? `(#${activeLog.ma_doi_tuong})` : ''}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-xs text-slate-400 font-medium block">NGƯỜI THỰC HIỆN</span>
                                    <span className="font-semibold text-slate-800 dark:text-slate-200 mt-1 block">
                                        {activeLog.nguoi_thuc_hien
                                            ? `${activeLog.nguoi_thuc_hien.ho_ten} (${activeLog.nguoi_thuc_hien.email})`
                                            : 'Hệ thống'}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-xs text-slate-400 font-medium block">ĐỊA CHỈ IP</span>
                                    <span className="font-mono text-slate-700 dark:text-slate-300 mt-1 block">
                                        {activeLog.dia_chi_ip || '127.0.0.1'}
                                    </span>
                                </div>
                            </div>

                            {/* Mô tả */}
                            <div>
                                <label className="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">
                                    Nội dung mô tả nghiệp vụ
                                </label>
                                <div className="p-3 rounded-lg bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 text-slate-800 dark:text-slate-200 text-sm">
                                    {activeLog.mo_ta}
                                </div>
                            </div>

                            {/* So sánh Dữ liệu Cũ và Mới (Diff Snapshot) */}
                            {(activeLog.du_lieu_cu || activeLog.du_lieu_moi) && (
                                <div className="space-y-3">
                                    <label className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">
                                        Dữ liệu trạng thái (State Snapshot)
                                    </label>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        {/* Trạng thái cũ */}
                                        <div className="p-3 rounded-xl border border-rose-200 dark:border-rose-900/40 bg-rose-50/30 dark:bg-rose-950/20">
                                            <div className="text-xs font-semibold text-rose-700 dark:text-rose-400 mb-2 flex items-center gap-1.5">
                                                <span className="w-2 h-2 rounded-full bg-rose-500"></span>
                                                Dữ liệu trước thay đổi (Old Data)
                                            </div>
                                            <pre className="text-xs font-mono p-2 rounded bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 overflow-x-auto max-h-48 border border-slate-200 dark:border-slate-800">
                                                {activeLog.du_lieu_cu
                                                    ? JSON.stringify(activeLog.du_lieu_cu, null, 2)
                                                    : '// Không có dữ liệu cũ'}
                                            </pre>
                                        </div>

                                        {/* Trạng thái mới */}
                                        <div className="p-3 rounded-xl border border-emerald-200 dark:border-emerald-900/40 bg-emerald-50/30 dark:bg-emerald-950/20">
                                            <div className="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-2 flex items-center gap-1.5">
                                                <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                Dữ liệu sau thay đổi (New Data)
                                            </div>
                                            <pre className="text-xs font-mono p-2 rounded bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 overflow-x-auto max-h-48 border border-slate-200 dark:border-slate-800">
                                                {activeLog.du_lieu_moi
                                                    ? JSON.stringify(activeLog.du_lieu_moi, null, 2)
                                                    : '// Không có dữ liệu mới'}
                                            </pre>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* User Agent / Thiết bị */}
                            {activeLog.user_agent && (
                                <div>
                                    <label className="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">
                                        User Agent / Thiết bị
                                    </label>
                                    <p className="text-xs font-mono p-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 break-all">
                                        {activeLog.user_agent}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Footer modal */}
                        <div className="p-4 px-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 flex justify-end">
                            <button
                                onClick={() => setActiveLog(null)}
                                className="px-4 py-2 text-sm font-medium rounded-lg bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-white transition"
                            >
                                Đóng
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

