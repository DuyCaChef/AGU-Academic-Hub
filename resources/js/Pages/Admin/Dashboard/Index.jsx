import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    AlertTriangle,
    ArrowRight,
    FileWarning,
    Flag,
    FileText,
    ShieldCheck,
    Users,
} from 'lucide-react';

const queueCards = [
    {
        key: 'pendingKyc',
        label: 'Duyệt hồ sơ KYC',
        description: 'Xác thực thẻ sinh viên và cựu sinh viên',
        routeName: 'admin.kyc.index',
        fallbackUrl: '/quan-tri/duyet-kyc',
        icon: ShieldCheck,
        color: 'blue',
    },
    {
        key: 'pendingReports',
        label: 'Báo cáo vi phạm',
        description: 'Kiểm tra các báo cáo đang chờ xử lý',
        routeName: 'admin.reports.index',
        fallbackUrl: '/quan-tri/bao-cao',
        icon: Flag,
        color: 'rose',
    },
    {
        key: 'documentsToReview',
        label: 'Kiểm tra tài liệu',
        description: 'Tài liệu bị đưa vào hàng đợi kiểm tra',
        routeName: 'admin.documents.review-queue',
        fallbackUrl: '/quan-tri/tai-lieu/cho-kiem-tra',
        icon: AlertTriangle,
        color: 'amber',
    },
    {
        key: 'pendingRemovalRequests',
        label: 'Yêu cầu gỡ tài liệu',
        description: 'Xét duyệt yêu cầu gỡ tài liệu cộng đồng',
        routeName: 'admin.documents.removal-requests.index',
        fallbackUrl: '/quan-tri/yeu-cau-go-tai-lieu',
        icon: FileWarning,
        color: 'violet',
    },
];

const cardStyles = {
    blue: 'border-blue-100 bg-blue-50 text-blue-700',
    rose: 'border-rose-100 bg-rose-50 text-rose-700',
    amber: 'border-amber-100 bg-amber-50 text-amber-700',
    violet: 'border-violet-100 bg-violet-50 text-violet-700',
};

export default function AdminDashboard({ stats }) {
    const { auth } = usePage().props;
    const isAdmin = Boolean(auth?.user?.la_quan_tri);

    return (
        <AdminLayout title="Tổng quan quản trị">
            <Head title="Tổng quan quản trị - AGU Hub Admin" />
            <div className="space-y-6">
                <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <p className="text-sm font-semibold text-[#005baa]">AGU Academic Hub</p>
                    <h1 className="mt-1 text-2xl font-bold text-slate-900">Tổng quan quản trị</h1>
                    <p className="mt-2 max-w-2xl text-sm text-slate-500">
                        Theo dõi các hàng đợi cần xử lý và truy cập nhanh đến các nghiệp vụ quản trị.
                    </p>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {queueCards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <Link
                                key={card.key}
                                href={route(card.routeName)}
                                className="group rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <span className={`rounded-xl border p-2.5 ${cardStyles[card.color]}`}>
                                        <Icon className="h-5 w-5" />
                                    </span>
                                    <span className="text-2xl font-bold text-slate-900">{stats?.[card.key] ?? 0}</span>
                                </div>
                                <h2 className="mt-5 font-bold text-slate-900">{card.label}</h2>
                                <p className="mt-1 min-h-10 text-xs leading-5 text-slate-500">{card.description}</p>
                                <span className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa]">
                                    Mở hàng đợi <ArrowRight className="h-3.5 w-3.5 transition group-hover:translate-x-1" />
                                </span>
                            </Link>
                        );
                    })}
                </section>

                <section className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                        <div className="flex items-center gap-3">
                            <span className="rounded-xl bg-slate-100 p-2.5 text-slate-600"><Users className="h-5 w-5" /></span>
                            <div><p className="text-xs text-slate-500">Tổng người dùng</p><p className="text-2xl font-bold text-slate-900">{stats?.totalUsers ?? 0}</p></div>
                        </div>
                        {isAdmin && <Link href={route('admin.users.index')} className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa]">Quản lý người dùng <ArrowRight className="h-3.5 w-3.5" /></Link>}
                    </div>
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                        <div className="flex items-center gap-3">
                            <span className="rounded-xl bg-slate-100 p-2.5 text-slate-600"><FileText className="h-5 w-5" /></span>
                            <div><p className="text-xs text-slate-500">Tổng tài liệu</p><p className="text-2xl font-bold text-slate-900">{stats?.totalDocuments ?? 0}</p></div>
                        </div>
                        {isAdmin && <Link href={route('admin.catalog.index')} className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-[#005baa]">Quản lý danh mục <ArrowRight className="h-3.5 w-3.5" /></Link>}
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}
