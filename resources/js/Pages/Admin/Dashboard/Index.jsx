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

/** Tiêu đề phân vùng có đường kẻ phân cách */
function SectionHeading({ children, badge }) {
    return (
        <div className="flex items-center gap-3 pb-2">
            <h2 className="text-sm font-bold uppercase tracking-widest text-slate-400">
                {children}
            </h2>
            {badge && (
                <span className="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-600">
                    {badge}
                </span>
            )}
            <div className="h-px flex-1 bg-slate-100" />
        </div>
    );
}

/** Card số liệu KPI đơn giản */
function KpiCard({ label, value, sub, subColor = 'slate', icon: Icon, iconColor }) {
    const subColors = {
        emerald: 'text-emerald-600',
        rose: 'text-rose-600',
        amber: 'text-amber-600',
        slate: 'text-slate-500',
    };
    return (
        <div className="flex flex-col gap-1 rounded-xl border border-slate-100 bg-slate-50 px-3.5 py-3">
            <div className="flex items-center gap-1.5">
                {Icon && <Icon className={`h-3.5 w-3.5 ${iconColor ?? 'text-slate-400'}`} />}
                <span className="text-[11px] font-medium text-slate-500">{label}</span>
            </div>
            <span className="text-xl font-black text-slate-900">{value}</span>
            {sub && (
                <span className={`text-[11px] font-medium ${subColors[subColor]}`}>{sub}</span>
            )}
        </div>
    );
}

/** Thẻ hàng đợi tác vụ — layout ngang nhỏ gọn */
function QueueItem({ card, isAdmin }) {
    if (card.adminOnly && !isAdmin) return null;
    const Icon = card.icon;

    const styles = {
        blue:   { wrap: 'border-blue-100 hover:border-blue-300 hover:bg-blue-50/40',   icon: 'bg-blue-50 text-blue-600 border-blue-100',   count: 'text-blue-700',  badge: 'bg-blue-100 text-blue-700' },
        rose:   { wrap: 'border-rose-100 hover:border-rose-300 hover:bg-rose-50/40',   icon: 'bg-rose-50 text-rose-600 border-rose-100',   count: 'text-rose-700',  badge: 'bg-rose-100 text-rose-700' },
        amber:  { wrap: 'border-amber-100 hover:border-amber-300 hover:bg-amber-50/40', icon: 'bg-amber-50 text-amber-600 border-amber-100', count: 'text-amber-700', badge: 'bg-amber-100 text-amber-700' },
        violet: { wrap: 'border-violet-100 hover:border-violet-300 hover:bg-violet-50/40', icon: 'bg-violet-50 text-violet-600 border-violet-100', count: 'text-violet-700', badge: 'bg-violet-100 text-violet-700' },
        slate:  { wrap: 'border-slate-200 hover:border-slate-300 hover:bg-slate-50',   icon: 'bg-slate-100 text-slate-500 border-slate-200', count: 'text-slate-700', badge: 'bg-slate-200 text-slate-600' },
    };
    const s = styles[card.color] ?? styles.slate;
    const hasExpired = card.subCount != null && card.subCount > 0;

    return (
        <Link
            href={getSafeRoute(card.routeName, card.fallbackUrl)}
            className={`group flex items-center gap-4 rounded-2xl border bg-white px-4 py-3.5 shadow-xs transition hover:-translate-y-0.5 hover:shadow-md ${s.wrap}`}
        >
            {/* Icon */}
            <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border ${s.icon}`}>
                <Icon className="h-5 w-5" />
            </span>

            {/* Label + mô tả */}
            <div className="min-w-0 flex-1">
                <p className="text-sm font-bold text-slate-900 group-hover:text-[#005baa] transition leading-tight">
                    {card.label}
                </p>
                <p className="mt-0.5 truncate text-xs text-slate-400">{card.description}</p>
                {hasExpired && (
                    <span className={`mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ${s.badge}`}>
                        {card.subCount} {card.subLabel}
                    </span>
                )}
            </div>

            {/* Count badge */}
            <div className="flex shrink-0 flex-col items-end gap-1">
                <span className={`text-2xl font-black tabular-nums ${s.count}`}>
                    {card.count}
                </span>
                <ArrowRight className="h-3.5 w-3.5 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[#005baa]" />
            </div>
        </Link>
    );
}

/** Cảnh báo nghiệp vụ dạng dải ngang */
function AlertBanner({ alert }) {
    const isCritical = alert.severity === 'critical';
    const isWarning  = alert.severity === 'warning';

    const styles = {
        critical: {
            wrap:   'border-rose-200 bg-rose-50',
            icon:   'bg-rose-100 text-rose-600 border-rose-200',
            badge:  'bg-rose-600 text-white',
            label:  'text-rose-800',
            msg:    'text-rose-700',
            btn:    'bg-rose-600 hover:bg-rose-700 text-white',
            tag:    'KHẨN CẤP',
        },
        warning: {
            wrap:   'border-amber-200 bg-amber-50',
            icon:   'bg-amber-100 text-amber-600 border-amber-200',
            badge:  'bg-amber-500 text-white',
            label:  'text-amber-900',
            msg:    'text-amber-700',
            btn:    'bg-amber-600 hover:bg-amber-700 text-white',
            tag:    'CẢNH BÁO',
        },
        info: {
            wrap:   'border-sky-200 bg-sky-50',
            icon:   'bg-sky-100 text-sky-600 border-sky-200',
            badge:  'bg-sky-500 text-white',
            label:  'text-sky-900',
            msg:    'text-sky-700',
            btn:    'bg-sky-600 hover:bg-sky-700 text-white',
            tag:    'THÔNG BÁO',
        },
    };
    const s = styles[alert.severity] ?? styles.info;
    const AlertIcon = isCritical ? AlertOctagon : isWarning ? AlertTriangle : Info;

    return (
        <div className={`flex items-center gap-3 rounded-xl border px-4 py-3 ${s.wrap}`}>
            {/* Icon */}
            <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border ${s.icon}`}>
                <AlertIcon className="h-4 w-4" />
            </span>

            {/* Tag + nội dung */}
            <div className="min-w-0 flex-1">
                <div className="flex items-baseline gap-2">
                    <span className={`text-[10px] font-black tracking-widest uppercase ${s.label}`}>
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
                <span className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-black ${s.badge}`}>
                    {alert.count}
                </span>
            )}

            {/* CTA Button */}
            <Link
                href={getSafeRoute(alert.actionRoute)}
                className={`shrink-0 inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition ${s.btn}`}
            >
                {alert.actionLabel}
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
            <span className={`font-bold ${highlight ? 'text-rose-600' : 'text-slate-800'}`}>
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
            color: 'blue',
        },
        {
            key: 'reports',
            count: stats?.moderation?.pendingReports ?? stats?.pendingReports ?? 0,
            subCount: null,
            subLabel: null,
            label: 'Báo cáo vi phạm',
            description: 'Nội dung và tài liệu bị cộng đồng báo cáo',
            routeName: 'admin.reports.index',
            fallbackUrl: '/quan-tri/bao-cao',
            icon: Flag,
            color: 'rose',
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
            color: 'amber',
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
            color: 'violet',
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
            color: 'slate',
            adminOnly: true,
        },
    ];

    // Màu progress bar theo trạng thái lưu trữ
    const storageBarColor =
        stats?.storage?.trang_thai === 'NGUY_HIEM' ? 'bg-rose-500' :
        stats?.storage?.trang_thai === 'CANH_BAO'  ? 'bg-amber-500' :
        'bg-[#005baa]';

    const storageBadgeStyle =
        stats?.storage?.trang_thai === 'NGUY_HIEM'
            ? 'bg-rose-100 text-rose-700 border-rose-200'
            : stats?.storage?.trang_thai === 'CANH_BAO'
            ? 'bg-amber-100 text-amber-700 border-amber-200'
            : 'bg-emerald-100 text-emerald-700 border-emerald-200';

    const storageBadgeLabel =
        stats?.storage?.trang_thai === 'NGUY_HIEM' ? 'Nguy cấp' :
        stats?.storage?.trang_thai === 'CANH_BAO'  ? 'Cảnh báo' :
        'An toàn';

    return (
        <AdminLayout title="Tổng quan Quản trị">
            <Head title="Tổng quan Quản trị - AGU Academic Hub" />

            <div className="space-y-7">

                {/* ══════════════════════════════════════════════════
                    ROW 1 — Tiêu đề trang + nút điều khiển
                ══════════════════════════════════════════════════ */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-extrabold tracking-tight text-slate-900">
                            Tổng quan hệ thống
                        </h1>
                        <p className="mt-0.5 text-sm text-slate-500">
                            Trạng thái vận hành, hàng đợi kiểm duyệt và cảnh báo theo thời gian thực.
                        </p>
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            onClick={handleRefresh}
                            disabled={isRefreshing}
                            className="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-xs hover:bg-slate-50 disabled:opacity-50 transition"
                        >
                            <RefreshCw className={`h-3.5 w-3.5 ${isRefreshing ? 'animate-spin' : ''}`} />
                            {isRefreshing ? 'Đang tải...' : 'Làm mới'}
                        </button>
                        {isAdmin && (
                            <Link
                                href={getSafeRoute('admin.storage.index', '/quan-tri/luu-tru')}
                                className="inline-flex items-center gap-1.5 rounded-xl bg-[#005baa] px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#004885] transition"
                            >
                                <HardDrive className="h-3.5 w-3.5" />
                                Lưu trữ
                            </Link>
                        )}
                    </div>
                </div>

                {/* ══════════════════════════════════════════════════
                    ROW 2 — Cảnh báo nghiệp vụ
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2">
                    <SectionHeading badge={alerts.length > 0 ? `${alerts.length} cảnh báo` : null}>
                        Cảnh báo cần xử lý
                    </SectionHeading>

                    {alerts.length > 0 ? (
                        <div className="space-y-2">
                            {alerts.map((alert) => (
                                <AlertBanner key={alert.id} alert={alert} />
                            ))}
                        </div>
                    ) : (
                        <div className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-500" />
                            <div>
                                <p className="text-sm font-semibold text-emerald-800">Hệ thống hoạt động an toàn</p>
                                <p className="text-xs text-emerald-600">Không phát hiện cảnh báo vi phạm hoặc vượt ngưỡng tồn đọng.</p>
                            </div>
                        </div>
                    )}
                </section>

                {/* ══════════════════════════════════════════════════
                    ROW 3 — Hàng đợi kiểm duyệt (2 cột)
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2">
                    <SectionHeading>Hàng đợi kiểm duyệt &amp; xử lý tác vụ</SectionHeading>
                    <div className="grid gap-2.5 sm:grid-cols-2">
                        {queueCards.map((card) => (
                            <QueueItem key={card.key} card={card} isAdmin={isAdmin} />
                        ))}
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════
                    ROW 4 — Thống kê tài khoản / SV raw / KYC (3 cột)
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2">
                    <SectionHeading>Tài khoản người dùng &amp; Xác thực danh tính</SectionHeading>

                    <div className="grid gap-4 lg:grid-cols-3">

                        {/* ── 4.1 Tài khoản người dùng ── */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                            {/* Header card */}
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-[#005baa]">
                                        <Users className="h-4.5 w-4.5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-bold text-slate-900">Tài khoản người dùng</p>
                                        <p className="text-[11px] text-slate-400">Phân bố trạng thái và vai trò</p>
                                    </div>
                                </div>
                                <span className="text-2xl font-black text-slate-900">
                                    {stats?.users?.total ?? stats?.totalUsers ?? 0}
                                </span>
                            </div>

                            {/* Trạng thái tài khoản — 4 KPI ô nhỏ */}
                            <div className="mt-4 grid grid-cols-2 gap-2">
                                <KpiCard label="Đang hoạt động" value={stats?.users?.active ?? 0}
                                    icon={UserCheck} iconColor="text-emerald-500" />
                                <KpiCard label="Bị tạm khóa" value={stats?.users?.locked ?? 0}
                                    icon={UserX} iconColor="text-rose-500"
                                    sub={stats?.users?.locked > 0 ? 'Cần xem xét' : null} subColor="rose" />
                                <KpiCard label="Chờ kích hoạt" value={stats?.users?.unverified ?? 0}
                                    icon={UserMinus} iconColor="text-amber-500" />
                                <KpiCard label="Vi phạm ≥ 3 lần" value={stats?.users?.highViolations ?? 0}
                                    icon={ShieldAlert} iconColor="text-slate-400"
                                    sub={stats?.users?.highViolations > 0 ? 'Rủi ro cao' : null} subColor="amber" />
                            </div>

                            {/* Cơ cấu vai trò */}
                            <div className="mt-4 space-y-0 divide-y divide-slate-100 border-t border-slate-100 pt-3">
                                <p className="pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Cơ cấu vai trò</p>
                                <StatRow label="Sinh viên chính quy" value={stats?.users?.byRole?.students ?? 0} />
                                <StatRow label="Cựu sinh viên AGU" value={stats?.users?.byRole?.alumni ?? 0} />
                                <StatRow label="Ban kiểm duyệt & Quản trị"
                                    value={(stats?.users?.byRole?.moderators ?? 0) + (stats?.users?.byRole?.admins ?? 0)} />
                            </div>

                            {isAdmin && (
                                <Link
                                    href={getSafeRoute('admin.users.index', '/quan-tri/nguoi-dung')}
                                    className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                >
                                    Quản lý người dùng <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            )}
                        </div>

                        {/* ── 4.2 Dữ liệu sinh viên nạp trước ── */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-[#00a896]">
                                        <FileText className="h-4.5 w-4.5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-bold text-slate-900">Sinh viên nạp trước</p>
                                        <p className="text-[11px] text-slate-400">Đối soát danh sách raw (MSSV)</p>
                                    </div>
                                </div>
                                <span className="text-2xl font-black text-slate-900">
                                    {stats?.rawStudents?.total ?? 0}
                                </span>
                            </div>

                            {/* Progress kích hoạt */}
                            <div className="mt-5">
                                <div className="mb-1.5 flex items-center justify-between text-xs">
                                    <span className="font-medium text-slate-500">Tỷ lệ kích hoạt tài khoản</span>
                                    <span className="font-black text-[#00a896]">
                                        {stats?.rawStudents?.activationRate ?? 0}%
                                    </span>
                                </div>
                                <div className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        className="h-full rounded-full bg-gradient-to-r from-[#00a896] to-emerald-400 transition-all duration-700"
                                        style={{ width: `${Math.min(100, stats?.rawStudents?.activationRate ?? 0)}%` }}
                                    />
                                </div>
                            </div>

                            {/* Đã kích hoạt / Chưa đăng nhập */}
                            <div className="mt-4 grid grid-cols-2 gap-2">
                                <KpiCard label="Đã kích hoạt" value={stats?.rawStudents?.activated ?? 0}
                                    icon={CheckCircle2} iconColor="text-emerald-500" />
                                <KpiCard label="Chưa đăng nhập" value={stats?.rawStudents?.unactivated ?? 0}
                                    icon={Clock} iconColor="text-slate-400"
                                    sub={stats?.rawStudents?.unactivated > 0 ? 'Chờ lần đầu' : null} />
                            </div>

                            <p className="mt-4 text-[11px] leading-relaxed text-slate-400">
                                Sinh viên được đối soát MSSV và kích hoạt sau khi đổi mật khẩu lần đầu thành công.
                            </p>
                        </div>

                        {/* ── 4.3 Hồ sơ KYC ── */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                        <ShieldCheck className="h-4.5 w-4.5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-bold text-slate-900">Hồ sơ xác thực KYC</p>
                                        <p className="text-[11px] text-slate-400">Minh chứng tốt nghiệp & Thẻ SV</p>
                                    </div>
                                </div>
                                <span className="text-2xl font-black text-slate-900">
                                    {stats?.kyc?.total ?? 0}
                                </span>
                            </div>

                            {/* Cựu sinh viên chờ duyệt — nổi bật */}
                            <div className="mt-4 flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5">
                                <span className="text-xs font-semibold text-amber-800">Cựu sinh viên chờ duyệt</span>
                                <span className="text-lg font-black text-amber-900">
                                    {stats?.kyc?.pendingAlumni ?? 0}
                                </span>
                            </div>

                            {/* Đã duyệt / Từ chối */}
                            <div className="mt-3 grid grid-cols-2 gap-2">
                                <KpiCard label="Đã phê duyệt" value={stats?.kyc?.approved ?? 0} iconColor="text-emerald-500" />
                                <KpiCard label="Bị từ chối" value={stats?.kyc?.rejected ?? 0} iconColor="text-rose-500" />
                            </div>

                            {/* Tỷ lệ duyệt */}
                            <div className="mt-4 space-y-0 divide-y divide-slate-100 border-t border-slate-100 pt-3">
                                <StatRow label="Tổng hồ sơ đã xử lý" value={stats?.kyc?.total ?? 0} />
                                <StatRow label="Tỷ lệ duyệt thành công" value={`${stats?.kyc?.approvalRate ?? 0}%`} />
                            </div>

                            <Link
                                href={getSafeRoute('admin.kyc.index', '/quan-tri/duyet-kyc')}
                                className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                            >
                                Đến hàng đợi duyệt KYC <ArrowRight className="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════
                    ROW 5 — Danh mục đào tạo / Tài liệu / Lưu trữ
                ══════════════════════════════════════════════════ */}
                <section className="space-y-2">
                    <SectionHeading>Cấu trúc đào tạo, kho tài liệu &amp; dung lượng lưu trữ</SectionHeading>

                    <div className="grid gap-4 lg:grid-cols-3">

                        {/* ── 5.1 Danh mục đào tạo ── */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                            <div className="flex items-center gap-2.5">
                                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-[#005baa]">
                                    <BookOpen className="h-4.5 w-4.5" />
                                </span>
                                <div>
                                    <p className="text-sm font-bold text-slate-900">Danh mục đào tạo</p>
                                    <p className="text-[11px] text-slate-400">Cây phân cấp học thuật AGU</p>
                                </div>
                            </div>

                            {/* 3 chỉ số lớn — grid 3 cột */}
                            <div className="mt-5 grid grid-cols-3 gap-3 text-center">
                                <div className="rounded-xl border border-slate-100 bg-slate-50 py-3">
                                    <Building2 className="mx-auto mb-1 h-4 w-4 text-[#005baa]" />
                                    <p className="text-2xl font-black text-slate-900">{stats?.catalog?.faculties ?? 0}</p>
                                    <p className="text-[11px] font-semibold text-slate-500">Khoa</p>
                                </div>
                                <div className="rounded-xl border border-slate-100 bg-slate-50 py-3">
                                    <Layers className="mx-auto mb-1 h-4 w-4 text-[#005baa]" />
                                    <p className="text-2xl font-black text-slate-900">{stats?.catalog?.majors ?? 0}</p>
                                    <p className="text-[11px] font-semibold text-slate-500">Chuyên ngành</p>
                                </div>
                                <div className="rounded-xl border border-slate-100 bg-slate-50 py-3">
                                    <BookOpen className="mx-auto mb-1 h-4 w-4 text-[#005baa]" />
                                    <p className="text-2xl font-black text-slate-900">{stats?.catalog?.courses ?? 0}</p>
                                    <p className="text-[11px] font-semibold text-slate-500">Môn học</p>
                                </div>
                            </div>

                            {isAdmin && (
                                <Link
                                    href={getSafeRoute('admin.catalog.index', '/quan-tri/danh-muc')}
                                    className="mt-5 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                >
                                    Quản lý danh mục học thuật <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            )}
                        </div>

                        {/* ── 5.2 Tài liệu & Tương tác ── */}
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                        <FileCheck className="h-4.5 w-4.5" />
                                    </span>
                                    <div>
                                        <p className="text-sm font-bold text-slate-900">Tài liệu học thuật</p>
                                        <p className="text-[11px] text-slate-400">Kho tài liệu và lượt tải</p>
                                    </div>
                                </div>
                                <span className="text-2xl font-black text-slate-900">
                                    {stats?.documents?.total ?? stats?.totalDocuments ?? 0}
                                </span>
                            </div>

                            {/* Lượt tải — highlight chính */}
                            <div className="mt-4 flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50 px-3.5 py-2.5">
                                <div className="flex items-center gap-2 text-indigo-700">
                                    <Download className="h-4 w-4" />
                                    <span className="text-xs font-semibold">Tổng lượt tải toàn hệ thống</span>
                                </div>
                                <span className="text-lg font-black text-indigo-900">
                                    {stats?.documents?.totalDownloads ?? 0}
                                </span>
                            </div>

                            <div className="mt-3 grid grid-cols-2 gap-2">
                                <KpiCard label="Đang công khai" value={stats?.documents?.active ?? 0} />
                                <KpiCard label="Trong thùng rác" value={stats?.documents?.trashed ?? 0} />
                            </div>

                            {isAdmin && (
                                <Link
                                    href={getSafeRoute('admin.documents.review-queue', '/quan-tri/tai-lieu/cho-kiem-tra')}
                                    className="mt-5 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                >
                                    Kiểm duyệt tài liệu <ArrowRight className="h-3.5 w-3.5" />
                                </Link>
                            )}
                        </div>

                        {/* ── 5.3 Dung lượng lưu trữ ── */}
                        {stats?.storage && (
                            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2.5">
                                        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-[#005baa]">
                                            <HardDrive className="h-4.5 w-4.5" />
                                        </span>
                                        <div>
                                            <p className="text-sm font-bold text-slate-900">Dung lượng lưu trữ</p>
                                            <p className="text-[11px] text-slate-400">Tài nguyên ổ đĩa máy chủ</p>
                                        </div>
                                    </div>
                                    <span className={`rounded-lg border px-2 py-0.5 text-xs font-bold ${storageBadgeStyle}`}>
                                        {storageBadgeLabel}
                                    </span>
                                </div>

                                {/* Dung lượng đã dùng + thanh tiến trình */}
                                <div className="mt-5">
                                    <div className="flex items-baseline justify-between">
                                        <span className="text-2xl font-black text-slate-900">
                                            {stats.storage.tong_dung_luong_dinh_dang}
                                        </span>
                                        <span className="text-xs font-medium text-slate-400">
                                            / {stats.storage.han_muc_luu_tru_dinh_dang}
                                        </span>
                                    </div>
                                    <div className="mt-2 flex items-center justify-between text-[11px] text-slate-400">
                                        <span>Đã sử dụng</span>
                                        <span className="font-bold text-slate-700">
                                            {stats.storage.ty_le_su_dung_phan_tram}%
                                        </span>
                                    </div>
                                    <div className="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            className={`h-full rounded-full transition-all duration-700 ${storageBarColor}`}
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

                                {isAdmin && (
                                    <Link
                                        href={getSafeRoute('admin.storage.index', '/quan-tri/luu-tru')}
                                        className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa] hover:underline"
                                    >
                                        Chi tiết lưu trữ & dọn dẹp <ArrowRight className="h-3.5 w-3.5" />
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
