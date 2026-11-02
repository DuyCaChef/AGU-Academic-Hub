import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    HardDrive,
    AlertTriangle,
    ShieldAlert,
    CheckCircle2,
    Trash2,
    Copy,
    FileText,
    TrendingUp,
    Building2,
    GraduationCap,
    User,
    Lock,
    Search,
    RefreshCw,
    Info,
    Calendar,
    ChevronDown,
    ChevronRight,
    ArrowUpRight,
    Sparkles,
    AlertCircle,
    Server,
} from 'lucide-react';

export default function StorageIndex({
    totalStats,
    facultyStats = [],
    courseStats = [],
    typeStats = { theo_muc_dich: [], theo_dinh_dang: [] },
    uploaderStats = [],
    trashStats,
    kycStats,
    largeFiles,
    duplicates,
    reclaimable,
    alert,
    growth,
    cachedAt,
    filters = {},
}) {
    const [activeTab, setActiveTab] = useState('tong_quan');
    const [courseSearch, setCourseSearch] = useState(filters.course_search || '');
    const [uploaderSearch, setUploaderSearch] = useState(filters.uploader_search || '');
    const [selectedThresholdMb, setSelectedThresholdMb] = useState(filters.large_file_threshold_mb || 20);
    const [expandedCluster, setExpandedCluster] = useState(null);
    const [isPurging, setIsPurging] = useState(false);
    const [showPurgeModal, setShowPurgeModal] = useState(false);

    // Xử lý tìm kiếm Môn học
    const handleCourseSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.storage.index'),
            {
                ...filters,
                course_search: courseSearch,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Xử lý tìm kiếm Người đăng
    const handleUploaderSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.storage.index'),
            {
                ...filters,
                uploader_search: uploaderSearch,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Xử lý làm mới dữ liệu và xóa bộ nhớ đệm (Cache Invalidation on Demand)
    const handleRefresh = () => {
        router.get(
            route('admin.storage.index'),
            {
                ...filters,
                refresh: 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };
    const handleThresholdChange = (mb) => {
        setSelectedThresholdMb(mb);
        router.get(
            route('admin.storage.index'),
            {
                ...filters,
                large_file_threshold_mb: mb,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Xử lý dọn dẹp thùng rác hết hạn
    const handlePurgeExpiredTrash = () => {
        setIsPurging(true);
        router.post(
            route('admin.storage.purge-expired-trash'),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsPurging(false);
                    setShowPurgeModal(false);
                },
            }
        );
    };

    // Helper trạng thái màu sắc
    const getStatusTheme = (status) => {
        switch (status) {
            case 'NGUY_HIEM':
                return {
                    bg: 'bg-rose-50 border-rose-200 text-rose-800',
                    badge: 'bg-rose-100 text-rose-800 border-rose-200',
                    bar: 'bg-rose-500',
                    icon: ShieldAlert,
                };
            case 'CANH_BAO':
                return {
                    bg: 'bg-amber-50 border-amber-200 text-amber-800',
                    badge: 'bg-amber-100 text-amber-800 border-amber-200',
                    bar: 'bg-amber-500',
                    icon: AlertTriangle,
                };
            default:
                return {
                    bg: 'bg-emerald-50 border-emerald-200 text-emerald-800',
                    badge: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                    bar: 'bg-[#005baa]',
                    icon: CheckCircle2,
                };
        }
    };

    const statusTheme = getStatusTheme(totalStats.trang_thai);
    const StatusIcon = statusTheme.icon;

    // Tính toán chiều cao tương đối cho biểu đồ tăng trưởng
    const maxMonthlyGrowthKb = Math.max(
        ...growth.dong_thoi_gian.map((t) => t.dung_luong_moi_kb),
        1
    );

    return (
        <AdminLayout title="Theo dõi & Quản lý dung lượng lưu trữ">
            <Head title="Quản lý Lưu trữ - AGU Admin" />

            <div className="space-y-6">
                {/* 1. HEADER & THAO TÁC NHANH */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-[#005baa]">
                                <HardDrive className="h-3.5 w-3.5" /> AGU Storage Ops
                            </span>
                            <span className="text-xs text-slate-400">•</span>
                            <span className="text-xs text-slate-500">Giám sát tài nguyên máy chủ</span>
                        </div>
                        <h1 className="mt-1.5 text-2xl font-bold tracking-tight text-slate-900">
                            Theo dõi & Quản lý dung lượng lưu trữ
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Theo dõi chi tiết mức sử dụng file, phân loại theo Khoa/Môn, phát hiện file bất thường và dọn dẹp dung lượng thừa.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        {cachedAt && (
                            <span className="text-[11px] text-slate-400 bg-slate-50 border border-slate-200 px-2.5 py-1.5 rounded-xl">
                                Đệm lúc: {new Date(cachedAt).toLocaleTimeString('vi-VN')}
                            </span>
                        )}

                        <button
                            onClick={handleRefresh}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                            title="Xóa cache và nạp lại số liệu mới nhất"
                        >
                            <RefreshCw className="h-3.5 w-3.5 text-slate-500" />
                            <span>Làm mới cache</span>
                        </button>

                        <button
                            onClick={() => setShowPurgeModal(true)}
                            disabled={trashStats?.da_het_han?.so_luong === 0}
                            className={`inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold shadow-xs transition ${
                                trashStats?.da_het_han?.so_luong > 0
                                    ? 'bg-rose-600 text-white hover:bg-rose-700'
                                    : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                            }`}
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                            <span>Dọn thùng rác hết hạn ({trashStats?.da_het_han?.dung_luong_dinh_dang || '0 KB'})</span>
                        </button>
                    </div>
                </div>

                {/* 2. CẢNH BÁO KHI DUNG LƯỢNG VƯỢT NGƯỠNG (11. Cảnh báo khi dung lượng vượt ngưỡng) */}
                {alert?.can_canh_bao ? (
                    <div className={`rounded-2xl border p-5 transition-all ${statusTheme.bg}`}>
                        <div className="flex items-start gap-3.5">
                            <StatusIcon className="h-6 w-6 shrink-0 mt-0.5" />
                            <div className="flex-1 space-y-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <h3 className="font-bold text-base leading-snug">{alert.tieu_de}</h3>
                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase ${statusTheme.badge}`}>
                                        {totalStats.trang_thai} • {totalStats.ty_le_su_dung_thuc_te}%
                                    </span>
                                </div>
                                <p className="text-sm leading-relaxed opacity-90">{alert.thong_diep}</p>

                                {alert.khuyen_nghi && alert.khuyen_nghi.length > 0 && (
                                    <div className="pt-2 border-t border-current/15">
                                        <p className="text-xs font-bold uppercase tracking-wider mb-1.5 opacity-80">Khuyến nghị xử lý ngay:</p>
                                        <ul className="list-disc list-inside text-xs space-y-1 opacity-90">
                                            {alert.khuyen_nghi.map((kn, idx) => (
                                                <li key={idx}>{kn}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="flex items-center justify-between gap-3 rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3 text-xs text-emerald-800">
                        <div className="flex items-center gap-2">
                            <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                            <span>
                                <strong>Hệ thống vận hành an toàn:</strong> Dung lượng hiện tại đang chiếm{' '}
                                <strong>{totalStats.ty_le_su_dung_thuc_te}%</strong> so với hạn mức cấp phát ({totalStats.tong_dung_luong_dinh_dang} / {totalStats.han_muc_dinh_dang}).
                            </span>
                        </div>
                        <span className="font-semibold text-emerald-700">Ngưỡng cảnh báo: 80%</span>
                    </div>
                )}

                {/* 3. TỔNG QUAN CHỈ SỐ LƯU TRỮ CHÍNH (KPI CARDS) */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Card 1: Tổng dung lượng sử dụng */}
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-xs font-medium text-slate-500">1. Tổng dung lượng file đang dùng</span>
                                <span className={`inline-flex px-2 py-0.5 rounded text-[11px] font-bold ${statusTheme.badge}`}>
                                    {totalStats.ty_le_su_dung_phan_tram}%
                                </span>
                            </div>
                            <div className="mt-2 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-slate-900">
                                    {totalStats.tong_dung_luong_dinh_dang}
                                </span>
                                <span className="text-xs text-slate-400">/ {totalStats.han_muc_dinh_dang}</span>
                            </div>

                            {/* Thanh tiến trình hạn mức */}
                            <div className="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className={`h-full transition-all duration-500 ${statusTheme.bar}`}
                                    style={{ width: `${Math.min(100, Math.max(3, totalStats.ty_le_su_dung_phan_tram))}%` }}
                                />
                            </div>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Còn trống:</span>
                            <span className="font-semibold text-slate-700">{totalStats.dung_luong_con_lai_dinh_dang}</span>
                        </div>
                    </div>

                    {/* Card 2: Dung lượng có thể giải phóng */}
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-xs font-medium text-slate-500">10. Có thể giải phóng ngay</span>
                                <span className="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    {reclaimable?.giai_phong_ngay?.ty_le_phan_tram ?? 0}% tổng
                                </span>
                            </div>
                            <div className="mt-2 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-amber-600">
                                    {reclaimable?.giai_phong_ngay?.dung_luong_dinh_dang ?? '0 KB'}
                                </span>
                            </div>
                            <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                                Gồm {reclaimable?.giai_phong_ngay?.so_file_thung_rac_het_han ?? 0} file hết hạn và{' '}
                                {reclaimable?.giai_phong_ngay?.so_file_trung_lap ?? 0} file thừa trùng lặp.
                            </p>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span className="text-slate-500">Tối đa cả thùng rác:</span>
                            <span className="font-semibold text-slate-700">
                                {reclaimable?.giai_phong_toi_da?.dung_luong_dinh_dang ?? '0 KB'}
                            </span>
                        </div>
                    </div>

                    {/* Card 3: Minh chứng Alumni KYC (Private) */}
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-xs font-medium text-slate-500">7. Khu vực Private Alumni KYC</span>
                                <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">
                                    <Lock className="h-3 w-3 text-slate-400" /> Private
                                </span>
                            </div>
                            <div className="mt-2 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-slate-900">
                                    {kycStats?.tong_dung_luong_dinh_dang ?? '0 KB'}
                                </span>
                                <span className="text-xs text-slate-400">({kycStats?.tong_so_tep ?? 0} hồ sơ)</span>
                            </div>
                            <p className="mt-2 text-xs text-slate-500">
                                Phân vùng: <span className="text-sky-700 font-semibold bg-sky-50 px-1.5 py-0.5 rounded">Private Storage (Bảo mật)</span>
                            </p>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span className="text-slate-500">Trung bình / file:</span>
                            <span className="font-semibold text-slate-700">{kycStats?.kich_thuoc_trung_binh_dinh_dang ?? '0 KB'}</span>
                        </div>
                    </div>

                    {/* Card 4: Thùng rác tài liệu (Xóa mềm) */}
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-xs font-medium text-slate-500">6. Tài liệu đã xóa mềm</span>
                                <span className="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700">
                                    {trashStats?.tong_so_tai_lieu ?? 0} file
                                </span>
                            </div>
                            <div className="mt-2 flex items-baseline gap-2">
                                <span className="text-2xl font-bold tracking-tight text-slate-900">
                                    {trashStats?.tong_dung_luong_dinh_dang ?? '0 KB'}
                                </span>
                            </div>
                            <div className="mt-2 flex items-center gap-2 text-xs text-slate-500">
                                <span className="inline-flex items-center gap-1 text-rose-600 font-medium">
                                    <AlertCircle className="h-3 w-3" />
                                    {trashStats?.da_het_han?.so_luong ?? 0} hết hạn
                                </span>
                                <span>•</span>
                                <span>{trashStats?.trong_an_han?.so_luong ?? 0} trong ân hạn</span>
                            </div>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span className="text-slate-500">Thời hạn lưu thùng rác:</span>
                            <span className="font-semibold text-slate-700">{trashStats?.thoi_han_luu_ngay ?? 60} ngày</span>
                        </div>
                    </div>
                </div>

                {/* 4. TAB NAVIGATION */}
                <div className="border-b border-slate-200">
                    <nav className="-mb-px flex space-x-2 overflow-x-auto pb-1" aria-label="Tabs">
                        {[
                            { id: 'tong_quan', label: 'Tổng quan & Tăng trưởng', icon: TrendingUp },
                            { id: 'khoa_mon_hoc', label: 'Theo Khoa & Môn học', icon: Building2 },
                            { id: 'loai_nguoi_dang', label: 'Loại tài liệu & Người đăng', icon: User },
                            {
                                id: 'file_bat_thuong',
                                label: `File lớn bất thường (${largeFiles?.tong_so_tep_lon ?? 0})`,
                                icon: AlertTriangle,
                                badge: largeFiles?.tong_so_tep_lon > 0,
                            },
                            {
                                id: 'file_trung_lap',
                                label: `File trùng lặp (${duplicates?.tong_so_cum_trung_lap ?? 0})`,
                                icon: Copy,
                                badge: duplicates?.tong_so_ban_trung_thua > 0,
                            },
                            { id: 'kyc_thung_rac', label: 'Khu vực KYC & Thùng rác', icon: Lock },
                        ].map((tab) => {
                            const Icon = tab.icon;
                            const isActive = activeTab === tab.id;
                            return (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`whitespace-nowrap flex items-center gap-2 py-3 px-4 border-b-2 font-semibold text-sm transition ${
                                        isActive
                                            ? 'border-[#005baa] text-[#005baa]'
                                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                                    }`}
                                >
                                    <Icon className={`h-4 w-4 ${isActive ? 'text-[#005baa]' : 'text-slate-400'}`} />
                                    <span>{tab.label}</span>
                                    {tab.badge && (
                                        <span className="h-2 w-2 rounded-full bg-rose-500 shrink-0" />
                                    )}
                                </button>
                            );
                        })}
                    </nav>
                </div>

                {/* 5. TAB 1: TỔNG QUAN & TỐC ĐỘ TĂNG DUNG LƯỢNG THEO THỜI GIAN (12) */}
                {activeTab === 'tong_quan' && (
                    <div className="space-y-6">
                        {/* Biểu đồ & Tốc độ tăng trưởng theo thời gian */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-5 border-b border-slate-100">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <TrendingUp className="h-5 w-5 text-[#005baa]" />
                                        <h2 className="text-base font-bold text-slate-900">
                                            12. Thống kê tốc độ tăng dung lượng theo thời gian
                                        </h2>
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500">
                                        Khối lượng file nạp vào hệ thống qua từng tháng và xu hướng tích lũy dung lượng.
                                    </p>
                                </div>

                                <div className="flex items-center gap-4 text-xs">
                                    <div className="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2 text-right">
                                        <p className="text-slate-400">Tốc độ trung bình / tháng</p>
                                        <p className="font-bold text-slate-800">{growth.toc_do_trung_binh_thang_dinh_dang}</p>
                                    </div>
                                    <div className="rounded-xl bg-slate-50 border border-slate-100 px-3 py-2 text-right">
                                        <p className="text-slate-400">Tốc độ trung bình / ngày</p>
                                        <p className="font-bold text-slate-800">{growth.toc_do_trung_binh_ngay_dinh_dang}</p>
                                    </div>
                                    {growth.du_bao_so_ngay_con_lai !== null && (
                                        <div className="rounded-xl bg-sky-50 border border-sky-100 px-3 py-2 text-right">
                                            <p className="text-sky-600">Dự báo chạm hạn mức</p>
                                            <p className="font-bold text-[#005baa]">~{growth.du_bao_so_ngay_con_lai} ngày</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Cột trực quan dữ liệu từng tháng */}
                            <div className="mt-6">
                                <div className="grid grid-cols-6 gap-3 items-end h-56 pt-6 pb-2">
                                    {growth.dong_thoi_gian.map((item, idx) => {
                                        const heightPercent =
                                            maxMonthlyGrowthKb > 0
                                                ? Math.max(8, Math.round((item.dung_luong_moi_kb / maxMonthlyGrowthKb) * 100))
                                                : 8;

                                        return (
                                            <div key={idx} className="flex flex-col items-center h-full justify-end group">
                                                {/* Tooltip hiển thị khi hover */}
                                                <div className="mb-2 text-[11px] font-semibold text-slate-700 opacity-90 group-hover:scale-105 transition-transform text-center">
                                                    <div>{item.dung_luong_moi_dinh_dang}</div>
                                                    <div className="text-[10px] text-slate-400 font-normal">({item.so_file_moi} file)</div>
                                                </div>

                                                {/* Cột Bar */}
                                                <div className="w-full max-w-[48px] bg-slate-100 rounded-t-lg overflow-hidden flex flex-col justify-end h-full">
                                                    <div
                                                        className="w-full bg-[#005baa] group-hover:bg-[#004785] transition-all rounded-t-lg"
                                                        style={{ height: `${heightPercent}%` }}
                                                    />
                                                </div>

                                                {/* Nhãn tháng */}
                                                <div className="mt-2 text-center">
                                                    <p className="text-xs font-semibold text-slate-700">{item.nhan_thoi_gian}</p>
                                                    {item.toc_do_tang_truong_phan_tram !== null && (
                                                        <span
                                                            className={`text-[10px] font-bold ${
                                                                item.toc_do_tang_truong_phan_tram > 0
                                                                    ? 'text-emerald-600'
                                                                    : item.toc_do_tang_truong_phan_tram < 0
                                                                    ? 'text-rose-600'
                                                                    : 'text-slate-400'
                                                            }`}
                                                        >
                                                            {item.toc_do_tang_truong_phan_tram > 0 ? '+' : ''}
                                                            {item.toc_do_tang_truong_phan_tram}%
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                        {/* Phân bổ lưu trữ theo các thành phần hệ thống */}
                        <div className="grid gap-6 sm:grid-cols-2">
                            {/* Khối phân rã thành phần lưu trữ */}
                            <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                                <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-[#005baa]" />
                                    Cơ cấu tài nguyên đang sử dụng
                                </h3>

                                <div className="mt-4 space-y-4">
                                    <div>
                                        <div className="flex justify-between text-xs mb-1">
                                            <span className="font-medium text-slate-700">Tài liệu học thuật hoạt động</span>
                                            <span className="font-bold text-slate-900">
                                                {totalStats.chi_tiet?.tai_lieu_hoat_dong?.dung_luong_dinh_dang} (
                                                {totalStats.chi_tiet?.tai_lieu_hoat_dong?.ty_le_phan_tram}%)
                                            </span>
                                        </div>
                                        <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                            <div
                                                className="h-full bg-[#005baa]"
                                                style={{ width: `${totalStats.chi_tiet?.tai_lieu_hoat_dong?.ty_le_phan_tram ?? 0}%` }}
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <div className="flex justify-between text-xs mb-1">
                                            <span className="font-medium text-slate-700">Tài liệu trong Thùng rác (Xóa mềm)</span>
                                            <span className="font-bold text-slate-900">
                                                {totalStats.chi_tiet?.tai_lieu_xoa_mem?.dung_luong_dinh_dang} (
                                                {totalStats.chi_tiet?.tai_lieu_xoa_mem?.ty_le_phan_tram}%)
                                            </span>
                                        </div>
                                        <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                            <div
                                                className="h-full bg-rose-500"
                                                style={{ width: `${totalStats.chi_tiet?.tai_lieu_xoa_mem?.ty_le_phan_tram ?? 0}%` }}
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <div className="flex justify-between text-xs mb-1">
                                            <span className="font-medium text-slate-700">Minh chứng Alumni KYC (Private)</span>
                                            <span className="font-bold text-slate-900">
                                                {totalStats.chi_tiet?.minh_chung_kyc?.dung_luong_dinh_dang} (
                                                {totalStats.chi_tiet?.minh_chung_kyc?.ty_le_phan_tram}%)
                                            </span>
                                        </div>
                                        <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                            <div
                                                className="h-full bg-amber-500"
                                                style={{ width: `${totalStats.chi_tiet?.minh_chung_kyc?.ty_le_phan_tram ?? 0}%` }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Khối thông tin Ổ đĩa vật lý của máy chủ */}
                            <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                                <h3 className="font-bold text-slate-900 text-sm flex items-center gap-2">
                                    <Server className="h-4 w-4 text-[#005baa]" />
                                    Tài nguyên ổ đĩa máy chủ (Physical Server Disk)
                                </h3>

                                {totalStats.o_dia_vat_ly ? (
                                    <div className="mt-4 space-y-3">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="text-slate-500">Tổng dung lượng đĩa:</span>
                                            <span className="font-bold text-slate-800">{totalStats.o_dia_vat_ly.tong_dung_luong_o_dia_dinh_dang}</span>
                                        </div>
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="text-slate-500">Đã sử dụng toàn máy chủ:</span>
                                            <span className="font-bold text-slate-800">
                                                {totalStats.o_dia_vat_ly.da_dung_dinh_dang} ({totalStats.o_dia_vat_ly.ty_le_da_dung_phan_tram}%)
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="text-slate-500">Còn trống khả dụng:</span>
                                            <span className="font-bold text-emerald-600">{totalStats.o_dia_vat_ly.con_trong_dinh_dang}</span>
                                        </div>
                                        <div className="h-2 w-full bg-slate-100 rounded-full overflow-hidden mt-2">
                                            <div
                                                className="h-full bg-slate-700"
                                                style={{ width: `${totalStats.o_dia_vat_ly.ty_le_da_dung_phan_tram}%` }}
                                            />
                                        </div>
                                        <p className="text-[11px] text-slate-400 mt-2">
                                            * Thư mục giám sát: <code className="bg-slate-100 px-1 py-0.5 rounded text-slate-600">storage/app/private</code>
                                        </p>
                                    </div>
                                ) : (
                                    <div className="mt-4 text-xs text-slate-400">
                                        Không phát hiện được thông số ổ đĩa vật lý trực tiếp từ môi trường runtime này.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* 6. TAB 2: THEO KHOA & THEO MÔN HỌC (2 & 3) */}
                {activeTab === 'khoa_mon_hoc' && (
                    <div className="space-y-6">
                        {/* 2. Dung lượng theo Khoa */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <Building2 className="h-4 w-4 text-[#005baa]" />
                                        2. Dung lượng theo Khoa
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Tổng hợp lưu trữ của tất cả môn học trực thuộc từng Khoa / Bộ môn.
                                    </p>
                                </div>
                                <span className="text-xs font-semibold text-slate-500">{facultyStats.length} Khoa</span>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-slate-50 text-slate-600 border-y border-slate-200">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Khoa / Bộ môn</th>
                                            <th className="py-3 px-4 font-semibold text-center">Tổng file</th>
                                            <th className="py-3 px-4 font-semibold text-center">Xóa mềm</th>
                                            <th className="py-3 px-4 font-semibold">Dung lượng sử dụng</th>
                                            <th className="py-3 px-4 font-semibold">Tỷ lệ</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {facultyStats.length > 0 ? (
                                            facultyStats.map((fac, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/60 transition">
                                                    <td className="py-3.5 px-4 font-semibold text-slate-900">
                                                        <div className="flex items-center gap-2">
                                                            <span className="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-[#005baa]">
                                                                {fac.ky_hieu_khoa}
                                                            </span>
                                                            <span>{fac.ten_khoa}</span>
                                                        </div>
                                                    </td>
                                                    <td className="py-3.5 px-4 text-center text-slate-700 font-medium">{fac.so_tai_lieu}</td>
                                                    <td className="py-3.5 px-4 text-center text-rose-600 font-medium">
                                                        {fac.so_tai_lieu_xoa_mem > 0 ? fac.so_tai_lieu_xoa_mem : '-'}
                                                    </td>
                                                    <td className="py-3.5 px-4 font-bold text-slate-900">{fac.dung_luong_dinh_dang}</td>
                                                    <td className="py-3.5 px-4">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                                <div
                                                                    className="h-full bg-[#005baa]"
                                                                    style={{ width: `${Math.min(100, fac.ty_le_phan_tram)}%` }}
                                                                />
                                                            </div>
                                                            <span className="text-[11px] text-slate-500 font-semibold">{fac.ty_le_phan_tram}%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={5} className="py-8 text-center text-slate-400">
                                                    Chưa có dữ liệu dung lượng theo Khoa.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* 3. Dung lượng theo Môn học */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <GraduationCap className="h-4 w-4 text-[#005baa]" />
                                        3. Dung lượng theo Môn học (Top chiếm dụng lớn nhất)
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Môn học có tổng tài nguyên file nạp lên nhiều nhất trên hệ thống.
                                    </p>
                                </div>

                                <form onSubmit={handleCourseSearch} className="flex items-center gap-2">
                                    <div className="relative">
                                        <Search className="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            type="text"
                                            value={courseSearch}
                                            onChange={(e) => setCourseSearch(e.target.value)}
                                            placeholder="Tìm mã / tên môn học..."
                                            className="w-56 pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        className="rounded-xl bg-[#005baa] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#004785] transition"
                                    >
                                        Lọc
                                    </button>
                                </form>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-slate-50 text-slate-600 border-y border-slate-200">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Mã học phần</th>
                                            <th className="py-3 px-4 font-semibold">Tên môn học</th>
                                            <th className="py-3 px-4 font-semibold">Khoa</th>
                                            <th className="py-3 px-4 font-semibold text-center">Số file</th>
                                            <th className="py-3 px-4 font-semibold">Dung lượng</th>
                                            <th className="py-3 px-4 font-semibold">Tỷ lệ</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {courseStats.length > 0 ? (
                                            courseStats.map((course, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/60 transition">
                                                    <td className="py-3 px-4">
                                                        <span className="font-mono font-bold text-slate-800 bg-slate-100 px-1.5 py-0.5 rounded">
                                                            {course.ma_hoc_phan}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4 font-semibold text-slate-900">{course.ten_mon_hoc}</td>
                                                    <td className="py-3 px-4 text-slate-600">{course.ten_khoa}</td>
                                                    <td className="py-3 px-4 text-center font-medium text-slate-700">{course.so_tai_lieu}</td>
                                                    <td className="py-3 px-4 font-bold text-slate-900">{course.dung_luong_dinh_dang}</td>
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-2">
                                                            <div className="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                                <div
                                                                    className="h-full bg-sky-600"
                                                                    style={{ width: `${Math.min(100, course.ty_le_phan_tram)}%` }}
                                                                />
                                                            </div>
                                                            <span className="text-[11px] text-slate-500 font-semibold">{course.ty_le_phan_tram}%</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={6} className="py-8 text-center text-slate-400">
                                                    Không tìm thấy môn học nào phù hợp.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* 7. TAB 3: THEO LOẠI TÀI LIỆU & THEO NGƯỜI ĐĂNG (4 & 5) */}
                {activeTab === 'loai_nguoi_dang' && (
                    <div className="space-y-6">
                        {/* 4. Dung lượng theo loại tài liệu */}
                        <div className="grid gap-6 sm:grid-cols-2">
                            {/* Phân loại theo mục đích học tập */}
                            <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                                <h3 className="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-[#005baa]" />
                                    4.1 Dung lượng theo Mục đích tài liệu (Phân loại)
                                </h3>
                                <p className="text-xs text-slate-500 mb-4">Đề thi, bài giảng, đồ án, bài tập...</p>

                                <div className="space-y-3">
                                    {typeStats.theo_muc_dich?.map((item, idx) => (
                                        <div key={idx} className="rounded-xl border border-slate-100 p-3 bg-slate-50/50">
                                            <div className="flex justify-between items-center text-xs mb-1.5">
                                                <span className="font-bold text-slate-800">{item.ten_loai}</span>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-slate-400 font-normal">({item.so_tai_lieu} file)</span>
                                                    <span className="font-bold text-slate-900">{item.dung_luong_dinh_dang}</span>
                                                </div>
                                            </div>
                                            <div className="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                                                <div className="h-full bg-[#005baa]" style={{ width: `${item.ty_le_phan_tram}%` }} />
                                            </div>
                                            <div className="text-right text-[10px] text-slate-500 mt-1">{item.ty_le_phan_tram}% tổng tài liệu</div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Phân loại theo định dạng file */}
                            <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                                <h3 className="text-sm font-bold text-slate-900 mb-1 flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-[#005baa]" />
                                    4.2 Dung lượng theo Định dạng tệp (Extension)
                                </h3>
                                <p className="text-xs text-slate-500 mb-4">PDF, DOCX, ZIP, PPTX...</p>

                                <div className="space-y-3">
                                    {typeStats.theo_dinh_dang?.map((item, idx) => (
                                        <div key={idx} className="rounded-xl border border-slate-100 p-3 bg-slate-50/50">
                                            <div className="flex justify-between items-center text-xs mb-1.5">
                                                <div className="flex items-center gap-1.5">
                                                    <span className="font-mono uppercase font-bold text-sky-800 bg-sky-100 px-1.5 py-0.5 rounded text-[10px]">
                                                        .{item.dinh_dang_tep}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-slate-400 font-normal">({item.so_tai_lieu} file)</span>
                                                    <span className="font-bold text-slate-900">{item.dung_luong_dinh_dang}</span>
                                                </div>
                                            </div>
                                            <div className="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                                                <div className="h-full bg-sky-600" style={{ width: `${item.ty_le_phan_tram}%` }} />
                                            </div>
                                            <div className="text-right text-[10px] text-slate-500 mt-1">{item.ty_le_phan_tram}% tổng dung lượng</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* 5. Dung lượng theo người đăng */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <User className="h-4 w-4 text-[#005baa]" />
                                        5. Dung lượng theo người đăng (Top contributors / uploaders)
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Tài khoản sinh viên hoặc giảng viên đăng tải dung lượng file lớn nhất.
                                    </p>
                                </div>

                                <form onSubmit={handleUploaderSearch} className="flex items-center gap-2">
                                    <div className="relative">
                                        <Search className="h-3.5 w-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            type="text"
                                            value={uploaderSearch}
                                            onChange={(e) => setUploaderSearch(e.target.value)}
                                            placeholder="Tìm tên / email / mssv..."
                                            className="w-56 pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        className="rounded-xl bg-[#005baa] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#004785] transition"
                                    >
                                        Lọc
                                    </button>
                                </form>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-slate-50 text-slate-600 border-y border-slate-200">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Người đăng</th>
                                            <th className="py-3 px-4 font-semibold">Khoa / Vai trò</th>
                                            <th className="py-3 px-4 font-semibold text-center">Tổng file</th>
                                            <th className="py-3 px-4 font-semibold text-center">Đang hoạt động</th>
                                            <th className="py-3 px-4 font-semibold text-center">Đã xóa</th>
                                            <th className="py-3 px-4 font-semibold">Tổng dung lượng</th>
                                            <th className="py-3 px-4 font-semibold">Tỷ lệ</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {uploaderStats.length > 0 ? (
                                            uploaderStats.map((u, idx) => (
                                                <tr key={idx} className="hover:bg-slate-50/60 transition">
                                                    <td className="py-3 px-4">
                                                        <div className="font-semibold text-slate-900">{u.ho_ten}</div>
                                                        <div className="text-[11px] text-slate-400">{u.email}</div>
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <div className="text-slate-700">{u.ten_khoa}</div>
                                                        <span className="text-[10px] font-bold text-slate-500 uppercase">{u.vai_tro}</span>
                                                    </td>
                                                    <td className="py-3 px-4 text-center font-bold text-slate-800">{u.tong_so_file}</td>
                                                    <td className="py-3 px-4 text-center text-emerald-600 font-medium">{u.so_file_hoat_dong}</td>
                                                    <td className="py-3 px-4 text-center text-rose-500 font-medium">
                                                        {u.so_file_xoa_mem > 0 ? u.so_file_xoa_mem : '-'}
                                                    </td>
                                                    <td className="py-3 px-4 font-bold text-slate-900">{u.dung_luong_dinh_dang}</td>
                                                    <td className="py-3 px-4 font-semibold text-slate-600">{u.ty_le_phan_tram}%</td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={7} className="py-8 text-center text-slate-400">
                                                    Không tìm thấy người đăng phù hợp.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* 8. TAB 4: FILE DUNG LƯỢNG LỚN BẤT THƯỜNG (8) */}
                {activeTab === 'file_bat_thuong' && (
                    <div className="space-y-6">
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <AlertTriangle className="h-5 w-5 text-amber-500" />
                                        8. Danh sách file có dung lượng lớn bất thường
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Phát hiện các tệp tải lên có kích thước vượt ngưỡng định mức để kiểm tra tối ưu hoặc nén tệp.
                                    </p>
                                </div>

                                {/* Nút chọn nhanh ngưỡng lọc */}
                                <div className="flex items-center gap-2">
                                    <span className="text-xs text-slate-500 font-medium">Ngưỡng lọc:</span>
                                    {[10, 20, 50].map((mb) => (
                                        <button
                                            key={mb}
                                            onClick={() => handleThresholdChange(mb)}
                                            className={`rounded-lg px-2.5 py-1 text-xs font-semibold transition ${
                                                selectedThresholdMb === mb
                                                    ? 'bg-[#005baa] text-white shadow-xs'
                                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                            }`}
                                        >
                                            &gt; {mb} MB
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Thống kê nhanh file lớn */}
                            <div className="my-4 flex items-center justify-between rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                                <div>
                                    Đang áp dụng ngưỡng: <strong>&gt;= {largeFiles?.nguong_dinh_dang}</strong> • Tìm thấy:{' '}
                                    <strong>{largeFiles?.tong_so_tep_lon ?? 0} file</strong>
                                </div>
                                <div>
                                    Tổng dung lượng tệp lớn:{' '}
                                    <strong className="text-rose-600">{largeFiles?.tong_dung_luong_tep_lon_dinh_dang}</strong>
                                </div>
                            </div>

                            {/* Danh sách table */}
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-slate-50 text-slate-600 border-y border-slate-200">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Tên tài liệu & Tệp</th>
                                            <th className="py-3 px-4 font-semibold">Kích thước</th>
                                            <th className="py-3 px-4 font-semibold">Mức cảnh báo</th>
                                            <th className="py-3 px-4 font-semibold">Môn học & Khoa</th>
                                            <th className="py-3 px-4 font-semibold">Người đăng</th>
                                            <th className="py-3 px-4 font-semibold">Ngày đăng</th>
                                            <th className="py-3 px-4 font-semibold text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {largeFiles?.danh_sach?.length > 0 ? (
                                            largeFiles.danh_sach.map((file) => (
                                                <tr key={file.ma_tai_lieu} className="hover:bg-slate-50/60 transition">
                                                    <td className="py-3.5 px-4">
                                                        <div className="font-semibold text-slate-900 line-clamp-1 max-w-xs" title={file.tieu_de}>
                                                            {file.tieu_de}
                                                        </div>
                                                        <div className="flex items-center gap-1.5 mt-0.5">
                                                            <span className="font-mono text-[10px] uppercase font-bold text-sky-700 bg-sky-50 px-1 rounded">
                                                                .{file.dinh_dang_tep}
                                                            </span>
                                                            {file.da_xoa_tam && (
                                                                <span className="text-[10px] font-bold text-rose-600 bg-rose-50 px-1 rounded">
                                                                    Đã xóa mềm
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="py-3.5 px-4 font-bold text-slate-900 text-sm">
                                                        {file.dung_luong_dinh_dang}
                                                    </td>
                                                    <td className="py-3.5 px-4">
                                                        {file.muc_do_canh_bao === 'critical' ? (
                                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                                                <AlertCircle className="h-3 w-3" /> Rất lớn (&gt;=50MB)
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                                <AlertTriangle className="h-3 w-3" /> Lớn (&gt;=20MB)
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3.5 px-4">
                                                        <div className="font-semibold text-slate-800">
                                                            {file.ma_hoc_phan} - {file.ten_mon_hoc}
                                                        </div>
                                                        <div className="text-[11px] text-slate-400">{file.ten_khoa}</div>
                                                    </td>
                                                    <td className="py-3.5 px-4">
                                                        <div className="text-slate-800 font-medium">{file.nguoi_dang?.ho_ten}</div>
                                                        <div className="text-[11px] text-slate-400">{file.nguoi_dang?.email}</div>
                                                    </td>
                                                    <td className="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                                        {new Date(file.created_at).toLocaleDateString('vi-VN')}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-center">
                                                        <a
                                                            href={route('admin.documents.review-detail', file.ma_tai_lieu)}
                                                            className="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-slate-600 hover:bg-[#005baa] hover:text-white transition font-medium"
                                                        >
                                                            <span>Xem chi tiết</span>
                                                            <ArrowUpRight className="h-3 w-3" />
                                                        </a>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={7} className="py-10 text-center text-slate-400">
                                                    Không có file nào vượt ngưỡng dung lượng {largeFiles?.nguong_dinh_dang}.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* 9. TAB 5: FILE TRÙNG LẶP & DUNG LƯỢNG LÃNG PHÍ (9 & 10) */}
                {activeTab === 'file_trung_lap' && (
                    <div className="space-y-6">
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <Copy className="h-5 w-5 text-indigo-600" />
                                        9. Danh sách file trùng lặp (Phát hiện theo SHA-256)
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Các file có cùng mã băm nội dung nhị phân (100% identical file), gây lãng phí tài nguyên lưu trữ.
                                    </p>
                                </div>

                                <div className="rounded-xl bg-amber-50 border border-amber-200 px-3.5 py-2 text-right">
                                    <p className="text-[11px] text-amber-700">Dung lượng lãng phí từ file thừa</p>
                                    <p className="text-lg font-bold text-amber-800">{duplicates?.tong_dung_luong_lang_phi_dinh_dang}</p>
                                </div>
                            </div>

                            {/* Tóm tắt */}
                            <div className="my-4 flex items-center gap-4 text-xs text-slate-600">
                                <span>
                                    Tổng số cụm trùng: <strong>{duplicates?.tong_so_cum_trung_lap ?? 0}</strong>
                                </span>
                                <span>•</span>
                                <span>
                                    Số bản sao trùng thừa: <strong>{duplicates?.tong_so_ban_trung_thua ?? 0} bản</strong>
                                </span>
                            </div>

                            {/* Danh sách các cụm trùng */}
                            <div className="space-y-3">
                                {duplicates?.cum_trung_lap?.length > 0 ? (
                                    duplicates.cum_trung_lap.map((cluster, cIdx) => {
                                        const isExpanded = expandedCluster === cIdx;
                                        return (
                                            <div
                                                key={cIdx}
                                                className="rounded-xl border border-slate-200 overflow-hidden bg-white transition"
                                            >
                                                {/* Header cụm */}
                                                <div
                                                    onClick={() => setExpandedCluster(isExpanded ? null : cIdx)}
                                                    className="p-4 bg-slate-50/70 hover:bg-slate-100/70 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <span className="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                                                            <Copy className="h-4 w-4" />
                                                        </span>
                                                        <div>
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-mono text-xs font-bold text-slate-800">
                                                                    SHA-256: {cluster.ma_bam_file.substring(0, 16)}...
                                                                </span>
                                                                <span className="font-mono text-[10px] uppercase font-bold text-slate-600 bg-slate-200 px-1 py-0.5 rounded">
                                                                    .{cluster.dinh_dang_tep}
                                                                </span>
                                                            </div>
                                                            <div className="text-xs text-slate-500 mt-0.5">
                                                                Kích thước mỗi file: <strong>{cluster.dung_luong_file_dinh_dang}</strong> • Có{' '}
                                                                <strong className="text-slate-800">{cluster.so_ban_sao} bản sao</strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div className="flex items-center gap-3">
                                                        <div className="text-right">
                                                            <span className="text-[10px] text-slate-400 block">Lãng phí thừa:</span>
                                                            <span className="text-xs font-bold text-rose-600">
                                                                +{cluster.dung_luong_lang_phi_dinh_dang}
                                                            </span>
                                                        </div>
                                                        {isExpanded ? (
                                                            <ChevronDown className="h-4 w-4 text-slate-400" />
                                                        ) : (
                                                            <ChevronRight className="h-4 w-4 text-slate-400" />
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Danh sách các file trong cụm (mở rộng) */}
                                                {isExpanded && (
                                                    <div className="p-4 border-t border-slate-200 bg-white">
                                                        <p className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                                            Các tài liệu có cùng nội dung mã băm:
                                                        </p>
                                                        <div className="divide-y divide-slate-100">
                                                            {cluster.danh_sach_tai_lieu?.map((doc, dIdx) => (
                                                                <div
                                                                    key={dIdx}
                                                                    className="py-2.5 flex items-center justify-between text-xs gap-3"
                                                                >
                                                                    <div>
                                                                        <div className="flex items-center gap-2">
                                                                            <span className="font-semibold text-slate-900">
                                                                                #{doc.ma_tai_lieu} - {doc.tieu_de}
                                                                            </span>
                                                                            {doc.da_xoa_tam && (
                                                                                <span className="text-[10px] text-rose-600 font-bold bg-rose-50 px-1 rounded">
                                                                                    Đã xóa mềm
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                        <div className="text-[11px] text-slate-400 mt-0.5">
                                                                            Môn: {doc.ma_hoc_phan} • Người đăng: {doc.nguoi_dang} • Ngày:{' '}
                                                                            {new Date(doc.created_at).toLocaleDateString('vi-VN')}
                                                                        </div>
                                                                    </div>

                                                                    <a
                                                                        href={route('admin.documents.review-detail', doc.ma_tai_lieu)}
                                                                        className="text-xs font-semibold text-[#005baa] hover:underline whitespace-nowrap"
                                                                    >
                                                                        Xem tài liệu
                                                                    </a>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="py-10 text-center text-slate-400 text-xs">
                                        Không phát hiện file nào bị trùng lặp trên hệ thống.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* 10. TAB 6: MINH CHỨNG KYC & THÙNG RÁC (6 & 7) */}
                {activeTab === 'kyc_thung_rac' && (
                    <div className="space-y-6">
                        {/* 7. Dung lượng khu vực private chứa minh chứng Alumni KYC */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <Lock className="h-5 w-5 text-sky-600" />
                                        7. Dung lượng khu vực Private chứa minh chứng Alumni KYC
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Ảnh thẻ sinh viên, bằng tốt nghiệp xác minh Cựu sinh viên lưu trữ tại thư mục private không công khai.
                                    </p>
                                </div>
                                <span className="font-bold text-slate-900 text-base">{kycStats?.tong_dung_luong_dinh_dang}</span>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3 my-4">
                                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <p className="text-xs text-slate-500">Chờ duyệt (Pending)</p>
                                    <p className="text-lg font-bold text-amber-600 mt-1">
                                        {kycStats?.theo_trang_thai?.CHO_DUYET?.dung_luong_dinh_dang || '0 KB'}
                                    </p>
                                    <p className="text-[11px] text-slate-400 mt-1">
                                        {kycStats?.theo_trang_thai?.CHO_DUYET?.so_luong ?? 0} hồ sơ
                                    </p>
                                </div>

                                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <p className="text-xs text-slate-500">Đã duyệt (Approved)</p>
                                    <p className="text-lg font-bold text-emerald-600 mt-1">
                                        {kycStats?.theo_trang_thai?.DA_DUYET?.dung_luong_dinh_dang || '0 KB'}
                                    </p>
                                    <p className="text-[11px] text-slate-400 mt-1">
                                        {kycStats?.theo_trang_thai?.DA_DUYET?.so_luong ?? 0} hồ sơ
                                    </p>
                                </div>

                                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <p className="text-xs text-slate-500">Từ chối (Rejected)</p>
                                    <p className="text-lg font-bold text-slate-700 mt-1">
                                        {kycStats?.theo_trang_thai?.TU_CHOI?.dung_luong_dinh_dang || '0 KB'}
                                    </p>
                                    <p className="text-[11px] text-slate-400 mt-1">
                                        {kycStats?.theo_trang_thai?.TU_CHOI?.so_luong ?? 0} hồ sơ
                                    </p>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500">
                                <span>Chính sách: <strong className="text-slate-700">Bảo vệ PII</strong> — Minh chứng chỉ truy cập qua Signed URL 30 phút</span>
                                <Link href={route('admin.kyc.index')} className="font-semibold text-[#005baa] hover:underline flex items-center gap-1">
                                    Mở hàng đợi duyệt KYC →
                                </Link>
                            </div>
                        </div>

                        {/* 6. Dung lượng tài liệu đã xóa mềm */}
                        <div className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                        <Trash2 className="h-5 w-5 text-rose-500" />
                                        6. Quản lý dung lượng Thùng rác (Xóa mềm)
                                    </h3>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Tài liệu đã xóa mềm đang chờ hết hạn ân hạn lưu trữ trước khi xóa vĩnh viễn.
                                    </p>
                                </div>
                                <span className="font-bold text-slate-900 text-base">{trashStats?.tong_dung_luong_dinh_dang}</span>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2 my-4">
                                <div className="rounded-xl border border-rose-100 bg-rose-50/50 p-4 flex flex-col justify-between">
                                    <div>
                                        <span className="text-xs font-semibold text-rose-800">Đã hết hạn lưu trữ (Có thể xóa vĩnh viễn)</span>
                                        <div className="text-2xl font-bold text-rose-600 mt-2">
                                            {trashStats?.da_het_han?.dung_luong_dinh_dang}
                                        </div>
                                        <p className="text-xs text-slate-600 mt-1">
                                            Gồm {trashStats?.da_het_han?.so_luong} tài liệu đã quá thời hạn lưu trữ {trashStats?.thoi_han_luu_ngay} ngày.
                                        </p>
                                    </div>

                                    <button
                                        onClick={() => setShowPurgeModal(true)}
                                        disabled={trashStats?.da_het_han?.so_luong === 0}
                                        className={`mt-4 w-full rounded-xl py-2 text-xs font-semibold transition ${
                                            trashStats?.da_het_han?.so_luong > 0
                                                ? 'bg-rose-600 text-white hover:bg-rose-700'
                                                : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                        }`}
                                    >
                                        Dọn dẹp & Xóa vĩnh viễn ngay
                                    </button>
                                </div>

                                <div className="rounded-xl border border-slate-100 bg-slate-50 p-4 flex flex-col justify-between">
                                    <div>
                                        <span className="text-xs font-semibold text-slate-700">Đang trong thời gian ân hạn</span>
                                        <div className="text-2xl font-bold text-slate-800 mt-2">
                                            {trashStats?.trong_an_han?.dung_luong_dinh_dang}
                                        </div>
                                        <p className="text-xs text-slate-500 mt-1">
                                            Gồm {trashStats?.trong_an_han?.so_luong} tài liệu vẫn có thể khôi phục lại kho tài liệu.
                                        </p>
                                    </div>

                                    <Link
                                        href={route('admin.documents.deleted')}
                                        className="mt-4 inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                                    >
                                        Xem danh sách Thùng rác
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* MODAL XÁC NHẬN DỌN DẸP THÙNG RÁC */}
            {showPurgeModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                        <div className="flex items-center gap-3">
                            <span className="p-3 rounded-xl bg-rose-100 text-rose-600">
                                <Trash2 className="h-6 w-6" />
                            </span>
                            <div>
                                <h3 className="text-base font-bold text-slate-900">Xác nhận dọn dẹp Thùng rác</h3>
                                <p className="text-xs text-slate-500">Hành động này không thể hoàn tác</p>
                            </div>
                        </div>

                        <div className="mt-4 rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-xs text-rose-800 space-y-1">
                            <p>
                                Hệ thống sẽ xóa vĩnh viễn <strong>{trashStats?.da_het_han?.so_luong} tài liệu</strong> đã quá hạn lưu trữ.
                            </p>
                            <p>
                                Dung lượng dự kiến giải phóng: <strong>{trashStats?.da_het_han?.dung_luong_dinh_dang}</strong>.
                            </p>
                            <p className="text-[11px] opacity-80 pt-1">
                                Tệp tin vật lý trên đĩa máy chủ sẽ bị xóa hoàn toàn và ghi nhật ký kiểm toán vào hệ thống.
                            </p>
                        </div>

                        <div className="mt-6 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                onClick={() => setShowPurgeModal(false)}
                                disabled={isPurging}
                                className="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                            >
                                Hủy bỏ
                            </button>
                            <button
                                type="button"
                                onClick={handlePurgeExpiredTrash}
                                disabled={isPurging}
                                className="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700 transition flex items-center gap-1.5"
                            >
                                {isPurging ? (
                                    <>
                                        <RefreshCw className="h-3.5 w-3.5 animate-spin" />
                                        <span>Đang dọn dẹp...</span>
                                    </>
                                ) : (
                                    <>
                                        <Trash2 className="h-3.5 w-3.5" />
                                        <span>Xác nhận xóa vĩnh viễn</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

