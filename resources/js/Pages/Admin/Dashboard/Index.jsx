import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    AlertOctagon,
    AlertTriangle,
    ArrowRight,
    BookOpen,
    Building2,
    CheckCircle2,
    Clock,
    Download,
    FileCheck,
    FileText,
    FileWarning,
    Flag,
    HardDrive,
    Info,
    Layers,
    MessageSquareWarning,
    RefreshCw,
    ShieldAlert,
    ShieldCheck,
    Trash2,
    UserCheck,
    UserMinus,
    Users,
    UserX,
} from 'lucide-react';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function getSafeRoute(routeName, fallbackUrl = '#') {
    try {
        if (typeof route === 'function' && route().has(routeName)) {
            return route(routeName);
        }
    } catch (e) {}
    return fallbackUrl;
}

// ─── Sub-components ───────────────────────────────────────────────────────────

/** Tiêu đề phân vùng theo chuẩn Unslop: Sentence case, yên tĩnh, không vạch kẻ phân đoạn */
function SectionHeading({ children, badge }) {
    return (
        <div className="flex items-center gap-2.5 pb-1">
            <h2 className="text-sm font-semibold text-slate-900 tracking-tight">
                {children}
            </h2>
            {badge && (
                <span className="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 border border-rose-200">
                    {badge}
                </span>
            )}
        </div>
    );
}

/** Card số liệu KPI đơn giản */
function KpiCard({ label, value, sub, subColor = 'slate', icon: Icon, iconColor }) {
    const subColors = {
        emerald: 'text-emerald-700',
        rose: 'text-rose-700',
        amber: 'text-amber-700',
        slate: 'text-slate-500',
    };
    return (
        <div className="flex flex-col gap-1 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3">
            <div className="flex items-center gap-1.5">
                {Icon && <Icon className={`h-3.5 w-3.5 ${iconColor ?? 'text-slate-400'}`} />}
                <span className="text-[11px] font-medium text-slate-500">{label}</span>
            </div>
            <span className="text-lg font-bold font-mono tracking-tight text-slate-900 tabular-nums">{value}</span>
            {sub && (
                <span className={`text-[11px] font-medium ${subColors[subColor]}`}>{sub}</span>
            )}
        </div>
    );
}

/** Thẻ hàng đợi tác vụ — layout ngang chuẩn mực, không nhảy giật chuột */
function QueueItem({ card, isAdmin }) {
    if (card.adminOnly && !isAdmin) return null;
    const Icon = card.icon;
    const hasExpired = card.subCount != null && card.subCount > 0;
    const hasPending = card.count > 0;

    // Thanh lọc Skittle color: chỉ hiển thị màu nhấn khi THỰC SỰ có tác vụ cần làm (> 0)
    let wrapClass = 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/60';
    let iconClass = 'bg-slate-100 text-slate-500 border-slate-200/80';
    let countClass = 'text-slate-400';
    let badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';

    if (hasPending) {
        if (card.key === 'violation-reports') {
            wrapClass = 'border-rose-200 bg-rose-50/30 hover:border-rose-300 hover:bg-rose-50/50';
            iconClass = 'bg-rose-100 text-rose-700 border-rose-200';
            countClass = 'text-rose-700 font-bold';
            badgeClass = 'bg-rose-100 text-rose-800 border-rose-200';
        } else if (card.key === 'reviews') {
            wrapClass = 'border-amber-200 bg-amber-50/30 hover:border-amber-300 hover:bg-amber-50/50';
            iconClass = 'bg-amber-100 text-amber-700 border-amber-200';
            countClass = 'text-amber-700 font-bold';
            badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
        } else if (card.key === 'kyc') {
            wrapClass = 'border-blue-200 bg-blue-50/30 hover:border-blue-300 hover:bg-blue-50/50';
            iconClass = 'bg-blue-100 text-[#005baa] border-blue-200';
            countClass = 'text-[#005baa] font-bold';
            badgeClass = 'bg-blue-100 text-blue-800 border-blue-200';
        } else {
            wrapClass = 'border-slate-300 bg-slate-50/40 hover:border-slate-400';
            iconClass = 'bg-slate-200 text-slate-700 border-slate-300';
            countClass = 'text-slate-800 font-bold';
        }
    }

    return (
        <Link
            href={getSafeRoute(card.routeName, card.fallbackUrl)}
            className={`group flex items-center gap-4 rounded-xl border px-4 py-3.5 shadow-xs transition-colors duration-150 ${wrapClass}`}
        >
            {/* Icon */}
            <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border ${iconClass}`}>
                <Icon className="h-5 w-5" />
            </span>

            {/* Label + mô tả */}
            <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-slate-900 group-hover:text-[#005baa] transition-colors leading-tight">
                    {card.label}
                </p>
                <p className="mt-0.5 truncate text-xs text-slate-500">{card.description}</p>
                {hasExpired && (
                    <span className={`mt-1.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium border ${badgeClass}`}>
                        {card.subCount} {card.subLabel}
                    </span>
                )}
            </div>

            {/* Count badge */}
            <div className="flex shrink-0 flex-col items-end gap-1">
                <span className={`text-2xl font-mono tabular-nums ${countClass}`}>
                    {card.count}
                </span>
                <ArrowRight className="h-3.5 w-3.5 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-[#005baa]" />
            </div>
        </Link>
    );
}

/** Cảnh báo nghiệp vụ dạng dải ngang thanh lịch */
function AlertBanner({ alert }) {
    const isCritical = alert.severity === 'critical';
    const isWarning  = alert.severity === 'warning';

    const styles = {
        critical: {
            wrap:   'border-rose-200 bg-rose-50/80',
            icon:   'bg-rose-100 text-rose-700 border-rose-200',
            badge:  'bg-rose-600 text-white',
            label:  'text-rose-900',
            msg:    'text-rose-700',
            btn:    'bg-rose-600 hover:bg-rose-700 text-white',
            tag:    'Khẩn cấp',
        },
        warning: {
            wrap:   'border-amber-200 bg-amber-50/80',
            icon:   'bg-amber-100 text-amber-700 border-amber-200',
            badge:  'bg-amber-600 text-white',
            label:  'text-amber-900',
            msg:    'text-amber-800',
            btn:    'bg-amber-600 hover:bg-amber-700 text-white',
            tag:    'Cảnh báo',
        },
        info: {
            wrap:   'border-sky-200 bg-sky-50/80',
            icon:   'bg-sky-100 text-sky-700 border-sky-200',
            badge:  'bg-[#005baa] text-white',
            label:  'text-sky-900',
            msg:    'text-sky-800',
            btn:    'bg-[#005baa] hover:bg-[#004885] text-white',
            tag:    'Thông báo',
        },
    };
    const s = styles[alert.severity] ?? styles.info;
    const AlertIcon = isCritical ? AlertOctagon : isWarning ? AlertTriangle : Info;

    return (
        <div className={`flex items-center gap-3.5 rounded-xl border px-4 py-3 shadow-xs ${s.wrap}`}>
            {/* Icon */}
            <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border ${s.icon}`}>
                <AlertIcon className="h-4 w-4" />
            </span>

            {/* Tag + nội dung */}
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <span className={`text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded bg-white/60 border border-slate-200/50 ${s.label}`}>
                        {s.tag}
                    </span>
                    <span className={`text-sm font-semibold ${s.label} truncate`}>
                        {alert.title}
                    </span>
                </div>
                <p className={`mt-0.5 text-xs leading-relaxed ${s.msg} line-clamp-1`}>
                    {alert.message}
                </p>
            </div>

            {/* Count */}
            {alert.count > 0 && (
                <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-mono font-bold ${s.badge}`}>
                    {alert.count}
                </span>
            )}

            {/* CTA Button */}
            <Link
                href={getSafeRoute(alert.actionRoute)}
                className={`shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold shadow-xs transition-colors ${s.btn}`}
            >
                <span>{alert.actionLabel}</span>
                <ArrowRight className="h-3 w-3" />
            </Link>
        </div>
    );
}

/** Dải thống kê số nhỏ inline */
function StatRow({ label, value, highlight }) {
    return (
        <div className="flex items-center justify-between py-1.5 text-xs">
            <span className="text-slate-500">{label}</span>
            <span className={`font-mono font-medium tabular-nums ${highlight ? 'text-rose-600 font-bold' : 'text-slate-800'}`}>
                {value}
            </span>
        </div>
    );
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function AdminDashboard({ stats }) {
    const { auth } = usePage().props;
    const isAdmin = Boolean(auth?.user?.la_quan_tri);
    const [isRefreshing, setIsRefreshing] = useState(false);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({ only: ['stats'], onFinish: () => setIsRefreshing(false) });
    };

    const alerts = stats?.alerts ?? [];

    const queueCards = [
        {
            key: 'kyc',
            count: stats?.kyc?.pending ?? stats?.pendingKyc ?? 0,
            subCount: stats?.kyc?.pendingAlumni ?? 0,
            subLabel: 'Cựu sinh viên',
            label: 'Duyệt hồ sơ KYC',
            description: 'Xác thực cựu SV & sinh viên chưa khớp dữ liệu',
            routeName: 'admin.kyc.index',
            fallbackUrl: '/quan-tri/duyet-kyc',
            icon: ShieldCheck,
        },
        {
            key: 'violation-reports',
            count: (stats?.moderation?.pendingReports ?? stats?.pendingReports ?? 0) + (stats?.moderation?.pendingReviewReports ?? stats?.pendingReviewReports ?? 0),
            subCount: null,
            subLabel: null,
            label: 'Hàng đợi Báo cáo Vi phạm',
            description: 'Phản ánh vi phạm đánh giá & tài liệu học thuật',
            routeName: 'admin.review-reports.index',
            fallbackUrl: '/quan-tri/bao-cao-danh-gia',
            icon: MessageSquareWarning,
        },
        {
            key: 'reviews',
            count: stats?.moderation?.documentsToReview ?? stats?.documentsToReview ?? 0,
            subCount: null,
            subLabel: null,
            label: 'Kiểm tra tài liệu',
            description: 'Tài liệu bị tạm ẩn kiểm tra hoặc có tranh chấp',
            routeName: 'admin.documents.review-queue',
            fallbackUrl: '/quan-tri/tai-lieu/cho-kiem-tra',
            icon: AlertTriangle,
        },
        {
            key: 'removals',
            count: stats?.moderation?.pendingRemovalRequests ?? stats?.pendingRemovalRequests ?? 0,
            subCount: null,
            subLabel: null,
            label: 'Yêu cầu gỡ tài liệu',
            description: 'Yêu cầu gỡ bỏ tài liệu từ tác giả và người dùng',
            routeName: 'admin.documents.removal-requests.index',
            fallbackUrl: '/quan-tri/yeu-cau-go-tai-lieu',
            icon: FileWarning,
        },
        {
            key: 'trash',
            count: stats?.moderation?.trashedDocuments ?? 0,
            subCount: stats?.moderation?.expiredTrashedDocuments ?? 0,
            subLabel: 'quá hạn 60 ngày',
            label: 'Thùng rác tài liệu',
            description: 'Tài liệu đã xóa mềm, tự động thanh lọc sau 60 ngày',
            routeName: 'admin.documents.deleted',
            fallbackUrl: '/quan-tri/tai-lieu/da-xoa',
            icon: Trash2,
            adminOnly: true,
        },
    ];

    // Màu progress bar theo trạng thái lưu trữ (đơn sắc, không gradient)
    const storageBarColor =
        stats?.storage?.trang_thai === 'NGUY_HIEM' ? 'bg-rose-600' :
        stats?.storage?.trang_thai === 'CANH_BAO'  ? 'bg-amber-500' :
        'bg-[#005baa]';

    const storageBadgeStyle =
        stats?.storage?.trang_thai === 'NGUY_HIEM'
            ? 'bg-rose-50 text-rose-700 border-rose-200'
            : stats?.storage?.trang_thai === 'CANH_BAO'
            ? 'bg-amber-50 text-amber-700 border-amber-200'
            : 'bg-emerald-50 text-emerald-700 border-emerald-200';

    const storageBadgeLabel =
        stats?.storage?.trang_thai === 'NGUY_HIEM' ? 'Nguy cấp' :
        stats?.storage?.trang_thai === 'CANH_BAO'  ? 'Cảnh báo' :
        'An toàn';

    return (
        <AdminLayout title="Tổng quan Quản trị">
            <Head title="Tổng quan Quản trị — AGU Academic Hub" />

            <div className="space-y-6">

                {/* ══════════════════════════════════════════════════
                    ROW 1 — Tiêu đề trang + Trạng thái Live + Điều khiển
                ══════════════════════════════════════════════════ */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200/80">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-xl font-bold tracking-tight text-slate-900">
                                Tổng quan hệ thống
                            </h1>
                            {/* Live System Status Signature */}
                            <div className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-xs text-slate-600 shadow-2xs">
                                <span className={`h-2 w-2 rounded-full ${alerts.length > 0 ? 'bg-amber-500' : 'bg-emerald-500'}`} />
                                <span className="font-medium text-[11px]">
                                    {alerts.length > 0 ? `${alerts.length} cảnh báo` : 'Hệ thống: Bình thường'}
                                </span>
                            </div>
                        </div>
                        <p className="mt-1 text-xs text-slate-500">
                            Trạng thái vận hành, hàng đợi kiểm duyệt và cảnh báo bảo mật thời gian thực.
                        </p>
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            onClick={handleRefresh}
                            disabled={isRefreshing}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 disabled:opacity-50 transition-colors cursor-pointer"
                        >
                            <RefreshCw className={`h-3.5 w-3.5 text-slate-500 ${isRefreshing ? 'animate-spin' : ''}`} />
                            <span>{isRefreshing ? 'Đang tải...' : 'Làm mới'}</span>
                        </button>
                        {isAdmin && (
                            <Link
                                href={getSafeRoute('admin.storage.index', '/quan-tri/luu-tru')}
                                className="inline-flex items-center gap-1.5 rounded-lg bg-[#005baa] px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#004885] transition-colors"
                            >
                                <HardDrive className="h-3.5 w-3.5" />
                                <span>Lưu trữ</span>
                            </Link>
                        )}
                    </div>
                </div>

                {/* ══════════════════════════════════════════════════
                    ROW 2 — Cảnh báo nghiệp vụ (Alerts)
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2.5">
                    <SectionHeading badge={alerts.length > 0 ? `${alerts.length} việc` : null}>
                        Cảnh báo nghiệp vụ
                    </SectionHeading>

                    {alerts.length > 0 ? (
                        <div className="space-y-2">
                            {alerts.map((alert) => (
                                <AlertBanner key={alert.id} alert={alert} />
                            ))}
                        </div>
                    ) : (
                        <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3 text-xs text-emerald-800">
                            <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" />
                            <div>
                                <span className="font-semibold">Hệ thống an toàn:</span> Không ghi nhận cảnh báo vi phạm bản quyền hoặc tồn đọng vượt ngưỡng.
                            </div>
                        </div>
                    )}
                </section>

                {/* ══════════════════════════════════════════════════
                    ROW 3 — Hàng đợi kiểm duyệt (Queue Cards)
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2.5">
                    <SectionHeading>Hàng đợi kiểm duyệt &amp; xử lý</SectionHeading>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {queueCards.map((card) => (
                            <QueueItem key={card.key} card={card} isAdmin={isAdmin} />
                        ))}
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════
                    ROW 4 — Tài khoản & Định danh người dùng
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2.5">
                    <SectionHeading>Tài khoản &amp; Xác thực danh tính</SectionHeading>

                    <div className="grid gap-4 lg:grid-cols-3">

                        {/* ── 4.1 Tài khoản người dùng ── */}
                        <div className="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-[#005baa]">
                                            <Users className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">Tài khoản người dùng</p>
                                            <p className="text-[11px] text-slate-500">Phân bố trạng thái và vai trò</p>
                                        </div>
                                    </div>
                                    <span className="text-2xl font-bold font-mono text-slate-900 tabular-nums">
                                        {stats?.users?.total ?? stats?.totalUsers ?? 0}
                                    </span>
                                </div>

                                {/* 4 KPI ô nhỏ */}
                                <div className="mt-4 grid grid-cols-2 gap-2">
                                    <KpiCard label="Đang hoạt động" value={stats?.users?.active ?? 0}
                                        icon={UserCheck} iconColor="text-emerald-600" />
                                    <KpiCard label="Bị tạm khóa" value={stats?.users?.locked ?? 0}
                                        icon={UserX} iconColor="text-rose-600"
                                        sub={stats?.users?.locked > 0 ? 'Cần xem xét' : null} subColor="rose" />
                                    <KpiCard label="Chờ kích hoạt" value={stats?.users?.unverified ?? 0}
                                        icon={UserMinus} iconColor="text-amber-600" />
                                    <KpiCard label="Vi phạm ≥ 3 lần" value={stats?.users?.highViolations ?? 0}
                                        icon={ShieldAlert} iconColor="text-slate-400"
                                        sub={stats?.users?.highViolations > 0 ? 'Rủi ro cao' : null} subColor="amber" />
                                </div>

                                {/* Cơ cấu vai trò */}
                                <div className="mt-4 space-y-0 divide-y divide-slate-100 border-t border-slate-100 pt-3">
                                    <p className="pb-1 text-[11px] font-semibold text-slate-500">Cơ cấu vai trò</p>
                                    <StatRow label="Sinh viên chính quy" value={stats?.users?.byRole?.students ?? 0} />
                                    <StatRow label="Cựu sinh viên AGU" value={stats?.users?.byRole?.alumni ?? 0} />
                                    <StatRow label="Ban kiểm duyệt & Quản trị"
                                        value={(stats?.users?.byRole?.moderators ?? 0) + (stats?.users?.byRole?.admins ?? 0)} />
                                </div>
                            </div>

                            {isAdmin && (
                                <Link
                                    href={getSafeRoute('admin.users.index', '/quan-tri/nguoi-dung')}
                                    className="mt-4 pt-3 border-t border-slate-100 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                >
                                    <span>Quản lý danh sách người dùng</span>
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            )}
                        </div>

                        {/* ── 4.2 Dữ liệu sinh viên nạp trước ── */}
                        <div className="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                            <FileText className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">Sinh viên nạp trước</p>
                                            <p className="text-[11px] text-slate-500">Đối soát danh sách raw (MSSV)</p>
                                        </div>
                                    </div>
                                    <span className="text-2xl font-bold font-mono text-slate-900 tabular-nums">
                                        {stats?.rawStudents?.total ?? 0}
                                    </span>
                                </div>

                                {/* Progress kích hoạt — Đơn sắc chuẩn Unslop */}
                                <div className="mt-4">
                                    <div className="mb-1.5 flex items-center justify-between text-xs">
                                        <span className="font-medium text-slate-500">Tỷ lệ kích hoạt tài khoản</span>
                                        <span className="font-bold font-mono text-[#005baa]">
                                            {stats?.rawStudents?.activationRate ?? 0}%
                                        </span>
                                    </div>
                                    <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            className="h-full rounded-full bg-[#005baa] transition-all duration-500"
                                            style={{ width: `${Math.min(100, stats?.rawStudents?.activationRate ?? 0)}%` }}
                                        />
                                    </div>
                                </div>

                                {/* Đã kích hoạt / Chưa đăng nhập */}
                                <div className="mt-4 grid grid-cols-2 gap-2">
                                    <KpiCard label="Đã kích hoạt" value={stats?.rawStudents?.activated ?? 0}
                                        icon={CheckCircle2} iconColor="text-emerald-600" />
                                    <KpiCard label="Chưa đăng nhập" value={stats?.rawStudents?.unactivated ?? 0}
                                        icon={Clock} iconColor="text-slate-400"
                                        sub={stats?.rawStudents?.unactivated > 0 ? 'Chờ lần đầu' : null} />
                                </div>

                                <p className="mt-4 text-[11px] leading-relaxed text-slate-500">
                                    Sinh viên đối soát MSSV thành công sẽ kích hoạt tài khoản sau khi hoàn tất đổi mật khẩu lần đầu.
                                </p>
                            </div>

                            {isAdmin && (
                                <Link
                                    href={getSafeRoute('admin.raw-students.index', '/quan-tri/sinh-vien-raw')}
                                    className="mt-4 pt-3 border-t border-slate-100 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                >
                                    <span>Nạp danh sách SV thô</span>
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            )}
                        </div>

                        {/* ── 4.3 Hồ sơ KYC ── */}
                        <div className="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                            <ShieldCheck className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">Hồ sơ xác thực KYC</p>
                                            <p className="text-[11px] text-slate-500">Minh chứng tốt nghiệp &amp; thẻ SV</p>
                                        </div>
                                    </div>
                                    <span className="text-2xl font-bold font-mono text-slate-900 tabular-nums">
                                        {stats?.kyc?.total ?? 0}
                                    </span>
                                </div>

                                {/* Chỉ số cựu SV chờ duyệt */}
                                <div className="mt-4 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50/80 px-3.5 py-2">
                                    <span className="text-xs font-medium text-slate-700">Cựu sinh viên chờ duyệt</span>
                                    <span className={`text-base font-bold font-mono tabular-nums ${stats?.kyc?.pendingAlumni > 0 ? 'text-amber-700' : 'text-slate-600'}`}>
                                        {stats?.kyc?.pendingAlumni ?? 0}
                                    </span>
                                </div>

                                {/* Đã duyệt / Từ chối */}
                                <div className="mt-3 grid grid-cols-2 gap-2">
                                    <KpiCard label="Đã phê duyệt" value={stats?.kyc?.approved ?? 0} iconColor="text-emerald-600" />
                                    <KpiCard label="Bị từ chối" value={stats?.kyc?.rejected ?? 0} iconColor="text-rose-600" />
                                </div>

                                {/* Tỷ lệ duyệt */}
                                <div className="mt-4 space-y-0 divide-y divide-slate-100 border-t border-slate-100 pt-3">
                                    <StatRow label="Tổng hồ sơ đã xử lý" value={stats?.kyc?.total ?? 0} />
                                    <StatRow label="Tỷ lệ duyệt thành công" value={`${stats?.kyc?.approvalRate ?? 0}%`} />
                                </div>
                            </div>

                            <Link
                                href={getSafeRoute('admin.kyc.index', '/quan-tri/duyet-kyc')}
                                className="mt-4 pt-3 border-t border-slate-100 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                            >
                                <span>Hàng đợi duyệt KYC</span>
                                <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════
                    ROW 5 — Đào tạo, Tài liệu & Dung lượng lưu trữ
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2.5">
                    <SectionHeading>Hệ thống, Học liệu &amp; Lưu trữ</SectionHeading>

                    <div className="grid gap-4 lg:grid-cols-3">

                        {/* ── 5.1 Danh mục đào tạo ── */}
                        <div className="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-[#005baa]">
                                            <BookOpen className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">Danh mục đào tạo</p>
                                            <p className="text-[11px] text-slate-500">Cây phân cấp học thuật AGU</p>
                                        </div>
                                    </div>
                                </div>

                                {/* 3 chỉ số đào tạo */}
                                <div className="mt-4 grid grid-cols-3 gap-2.5 text-center">
                                    <div className="rounded-lg border border-slate-200 bg-slate-50/70 py-3">
                                        <Building2 className="mx-auto mb-1 h-4 w-4 text-[#005baa]" />
                                        <p className="text-xl font-bold font-mono text-slate-900 tabular-nums">{stats?.catalog?.faculties ?? 0}</p>
                                        <p className="text-[11px] font-medium text-slate-600">Khoa</p>
                                    </div>
                                    <div className="rounded-lg border border-slate-200 bg-slate-50/70 py-3">
                                        <Layers className="mx-auto mb-1 h-4 w-4 text-[#005baa]" />
                                        <p className="text-xl font-bold font-mono text-slate-900 tabular-nums">{stats?.catalog?.majors ?? 0}</p>
                                        <p className="text-[11px] font-medium text-slate-600">Chuyên ngành</p>
                                    </div>
                                    <div className="rounded-lg border border-slate-200 bg-slate-50/70 py-3">
                                        <BookOpen className="mx-auto mb-1 h-4 w-4 text-[#005baa]" />
                                        <p className="text-xl font-bold font-mono text-slate-900 tabular-nums">{stats?.catalog?.courses ?? 0}</p>
                                        <p className="text-[11px] font-medium text-slate-600">Môn học</p>
                                    </div>
                                </div>

                                <p className="mt-4 text-[11px] leading-relaxed text-slate-500">
                                    Phân cấp học thuật phục vụ việc gắn kết tài liệu, đề thi và đánh giá môn học chính xác.
                                </p>
                            </div>

                            {isAdmin && (
                                <Link
                                    href={getSafeRoute('admin.catalog.index', '/quan-tri/danh-muc')}
                                    className="mt-4 pt-3 border-t border-slate-100 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                >
                                    <span>Quản lý danh mục học thuật</span>
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            )}
                        </div>

                        {/* ── 5.2 Tài liệu & Tương tác ── */}
                        <div className="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                            <FileCheck className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">Tài liệu học thuật</p>
                                            <p className="text-[11px] text-slate-500">Kho tài liệu và tương tác tải</p>
                                        </div>
                                    </div>
                                    <span className="text-2xl font-bold font-mono text-slate-900 tabular-nums">
                                        {stats?.documents?.total ?? stats?.totalDocuments ?? 0}
                                    </span>
                                </div>

                                {/* Lượt tải */}
                                <div className="mt-4 flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50/80 px-3.5 py-2">
                                    <div className="flex items-center gap-2 text-slate-700 text-xs font-medium">
                                        <Download className="h-3.5 w-3.5 text-slate-500" />
                                        <span>Lượt tải toàn hệ thống</span>
                                    </div>
                                    <span className="text-base font-bold font-mono text-slate-900 tabular-nums">
                                        {stats?.documents?.totalDownloads ?? 0}
                                    </span>
                                </div>

                                <div className="mt-3 grid grid-cols-2 gap-2">
                                    <KpiCard label="Đang công khai" value={stats?.documents?.active ?? 0} />
                                    <KpiCard label="Trong thùng rác" value={stats?.documents?.trashed ?? 0} />
                                </div>
                            </div>

                            {isAdmin && (
                                <Link
                                    href={getSafeRoute('admin.documents.review-queue', '/quan-tri/tai-lieu/cho-kiem-tra')}
                                    className="mt-4 pt-3 border-t border-slate-100 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                >
                                    <span>Hàng đợi kiểm duyệt file</span>
                                    <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            )}
                        </div>

                        {/* ── 5.3 Dung lượng lưu trữ ── */}
                        {stats?.storage && (
                            <div className="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-[#005baa]">
                                                <HardDrive className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold text-slate-900">Dung lượng lưu trữ</p>
                                                <p className="text-[11px] text-slate-500">Tài nguyên ổ đĩa máy chủ</p>
                                            </div>
                                        </div>
                                        <span className={`rounded-md border px-2 py-0.5 text-[11px] font-medium ${storageBadgeStyle}`}>
                                            {storageBadgeLabel}
                                        </span>
                                    </div>

                                    {/* Dung lượng đã dùng + thanh tiến trình */}
                                    <div className="mt-4">
                                        <div className="flex items-baseline justify-between">
                                            <span className="text-2xl font-bold font-mono text-slate-900 tabular-nums">
                                                {stats.storage.tong_dung_luong_dinh_dang}
                                            </span>
                                            <span className="text-xs font-medium text-slate-400 font-mono">
                                                / {stats.storage.han_muc_luu_tru_dinh_dang}
                                            </span>
                                        </div>
                                        <div className="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                                            <span>Đã sử dụng</span>
                                            <span className="font-bold font-mono text-slate-700 tabular-nums">
                                                {stats.storage.ty_le_su_dung_phan_tram}%
                                            </span>
                                        </div>
                                        <div className="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                className={`h-full rounded-full transition-all duration-500 ${storageBarColor}`}
                                                style={{ width: `${Math.min(100, stats.storage.ty_le_su_dung_phan_tram ?? 0)}%` }}
                                            />
                                        </div>
                                    </div>

                                    {/* Phân bổ loại dữ liệu */}
                                    <div className="mt-4 space-y-0 divide-y divide-slate-100 border-t border-slate-100 pt-3">
                                        <StatRow label="File tài liệu hoạt động" value={stats.storage.dung_luong_hoat_dong_dinh_dang ?? '0 MB'} />
                                        <StatRow label="Tài liệu trong thùng rác" value={stats.storage.dung_luong_thung_rac_dinh_dang ?? '0 MB'} />
                                        <StatRow label="Ảnh minh chứng KYC" value={stats.storage.dung_luong_kyc_dinh_dang ?? '0 MB'} />
                                    </div>
                                </div>

                                {isAdmin && (
                                    <Link
                                        href={getSafeRoute('admin.storage.index', '/quan-tri/luu-tru')}
                                        className="mt-4 pt-3 border-t border-slate-100 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                    >
                                        <span>Chi tiết lưu trữ &amp; dọn dẹp</span>
                                        <ArrowRight className="h-3.5 w-3.5" />
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>
                </section>

            </div>
        </AdminLayout>
    );
}
