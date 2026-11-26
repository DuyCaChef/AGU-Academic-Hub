import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    Coins,
    Lock,
    ShieldCheck,
    Clock,
    History,
    ArrowUpRight,
    ArrowDownLeft,
    ArrowRight,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    RotateCcw,
    ExternalLink,
    Filter
} from 'lucide-react';

export default function WalletIndex({
    wallet = null,
    transactions = { data: [] },
    filters = {}
}) {
    const { auth } = usePage().props;
    const user = auth?.user;

    // Ưu tiên đọc từ prop wallet do controller truyền sang, fallback sang auth.user
    const availablePoints = wallet?.diem_kha_dung ?? user?.vi_diem?.diem_kha_dung ?? 0;
    const frozenPoints = wallet?.diem_tam_giu ?? user?.vi_diem?.diem_tam_giu ?? 0;
    const totalPoints = wallet?.tong_diem ?? (availablePoints + frozenPoints);
    const reputationScore = wallet?.diem_uy_tin ?? user?.diem_uy_tin ?? 100;

    const [activeFilter, setActiveFilter] = useState(filters?.type || 'ALL');

    const transactionList = transactions?.data || [];
    const paginationLinks = transactions?.links || [];

    const handleFilterChange = (type) => {
        setActiveFilter(type);
        router.get(
            route('wallet.index'),
            { type },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Helper format nhãn giao dịch dựa trên LoaiGiaoDichDiem backend
    const getTransactionBadge = (type, tx) => {
        switch (type) {
            case 'CONG_DIEM':
            case 'TRA_THUONG_BOUNTY':
            case 'NHAN_THUONG_BOUNTY':
                return {
                    label: tx?.ten_loai_giao_dich || 'Cộng điểm thưởng',
                    className: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    icon: ArrowDownLeft,
                };
            case 'HOAN_TRA':
            case 'HOAN_DIEM_BOUNTY':
                return {
                    label: tx?.ten_loai_giao_dich || 'Hoàn trả điểm',
                    className: 'bg-blue-50 text-blue-700 border-blue-200',
                    icon: RotateCcw,
                };
            case 'TAM_GIU':
            case 'DONG_BANG_BOUNTY':
                return {
                    label: tx?.ten_loai_giao_dich || 'Tạm giữ điểm Bounty',
                    className: 'bg-amber-50 text-amber-700 border-amber-200',
                    icon: Lock,
                };
            case 'GIAI_PHONG':
                return {
                    label: tx?.ten_loai_giao_dich || 'Giải phóng điểm tạm giữ',
                    className: 'bg-purple-50 text-purple-700 border-purple-200',
                    icon: CheckCircle2,
                };
            case 'SU_DUNG':
                return {
                    label: tx?.ten_loai_giao_dich || 'Sử dụng điểm ví',
                    className: 'bg-rose-50 text-rose-700 border-rose-200',
                    icon: ArrowUpRight,
                };
            default:
                return {
                    label: tx?.ten_loai_giao_dich || 'Biến động ví',
                    className: 'bg-slate-50 text-slate-700 border-slate-200',
                    icon: History,
                };
        }
    };

    return (
        <StudentLayout title="Ví Điểm Cá Nhân">
            <Head title="Ví Điểm Cá Nhân - AGU Academic Hub" />

            <div className="space-y-6">
                {/* 1. Header Banner */}
                <div className="bg-gradient-to-r from-[#005baa] to-blue-800 text-white rounded-2xl p-6 sm:p-8 shadow-sm relative overflow-hidden">
                    <div className="pointer-events-none absolute -right-6 -bottom-6 w-56 h-56 bg-white/5 rounded-full blur-2xl" />

                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                        <div className="space-y-2">
                            <div className="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-blue-100">
                                <Coins size={14} className="text-amber-300" />
                                HỆ THỐNG ĐIỂM THƯỞNG HỌC THUẬT AGU
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                Ví Điểm Cá Nhân
                            </h1>
                            <p className="text-blue-100 text-sm max-w-xl leading-relaxed">
                                Quản lý số dư điểm thưởng, điểm ký quỹ đóng băng và theo dõi toàn bộ lịch sử biến động giao dịch từ các hoạt động chia sẻ tài liệu.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3 shrink-0">
                            <Link
                                href={route('document-requests.index')}
                                className="inline-flex items-center gap-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold px-4 py-2.5 rounded-xl text-sm shadow-xs transition active:scale-95"
                            >
                                <Coins size={17} />
                                Săn thưởng Bounty
                            </Link>

                            <Link
                                href={route('courses.index')}
                                className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold px-4 py-2.5 rounded-xl text-sm border border-white/15 transition"
                            >
                                <Sparkles size={17} />
                                Khám phá môn học
                            </Link>
                        </div>
                    </div>
                </div>

                {/* 2. Three Main Stat Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Thẻ 1: Điểm khả dụng */}
                    <div className="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs relative overflow-hidden group hover:border-[#005baa]/40 transition">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Điểm Khả Dụng
                            </span>
                            <div className="w-10 h-10 rounded-xl bg-blue-50 text-[#005baa] flex items-center justify-center font-bold">
                                <Coins size={20} />
                            </div>
                        </div>
                        <div className="mt-4 flex items-baseline gap-2">
                            <span className="text-3xl sm:text-4xl font-extrabold text-[#005baa] tracking-tight">
                                {availablePoints}
                            </span>
                            <span className="text-sm font-semibold text-slate-500">pts</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                            Điểm sẵn sàng sử dụng để đặt cọc Bounty yêu cầu tìm tài liệu.
                        </p>
                    </div>

                    {/* Thẻ 2: Điểm đang tạm giữ */}
                    <div className="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs relative overflow-hidden group hover:border-amber-400 transition">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Điểm Đang Tạm Giữ
                            </span>
                            <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                                <Lock size={19} />
                            </div>
                        </div>
                        <div className="mt-4 flex items-baseline gap-2">
                            <span className="text-3xl sm:text-4xl font-extrabold text-amber-600 tracking-tight">
                                {frozenPoints}
                            </span>
                            <span className="text-sm font-semibold text-slate-500">pts</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                            Điểm đang ký quỹ trong các yêu cầu tài liệu chờ nghiệm thu.
                        </p>
                    </div>

                    {/* Thẻ 3: Điểm uy tín */}
                    <div className="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-2xs relative overflow-hidden group hover:border-emerald-400 transition">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                Điểm Uy Tín
                            </span>
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <ShieldCheck size={20} />
                            </div>
                        </div>
                        <div className="mt-4 flex items-baseline gap-2">
                            <span className="text-3xl sm:text-4xl font-extrabold text-emerald-600 tracking-tight">
                                {reputationScore}
                            </span>
                            <span className="text-sm font-semibold text-slate-500">điểm</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                            Chỉ số uy tín học thuật tích lũy từ đóng góp tài liệu và giải quyết yêu cầu.
                        </p>
                    </div>
                </div>

                {/* 3. Lịch Sử Biến Động Điểm Ví */}
                <div className="bg-white border border-slate-200/80 rounded-2xl shadow-2xs overflow-hidden">
                    <div className="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="flex items-center gap-2.5">
                            <div className="p-2 bg-slate-100 text-slate-700 rounded-lg">
                                <History size={18} />
                            </div>
                            <div>
                                <h2 className="text-base font-bold text-slate-900">
                                    Lịch Sử Biến Động Điểm
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Theo dõi các giao dịch nhận thưởng, phong tỏa và hoàn cọc
                                </p>
                            </div>
                        </div>

                        {/* Filter Tabs */}
                        <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                            {[
                                { id: 'ALL', label: 'Tất cả' },
                                { id: 'REWARD', label: 'Nhận thưởng (+)' },
                                { id: 'FROZEN', label: 'Đóng băng / Chi tiêu (-)' },
                            ].map((tab) => (
                                <button
                                    key={tab.id}
                                    type="button"
                                    onClick={() => handleFilterChange(tab.id)}
                                    className={`px-3 py-1.5 rounded-lg transition-all ${
                                        activeFilter === tab.id
                                            ? 'bg-white text-slate-900 shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-slate-900'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Table / Empty State */}
                    {transactionList.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-slate-50/70 border-b border-slate-100 text-slate-600 uppercase font-bold text-[11px] tracking-wider">
                                    <tr>
                                        <th className="py-3.5 px-4 sm:px-6">Thời gian</th>
                                        <th className="py-3.5 px-4">Loại giao dịch</th>
                                        <th className="py-3.5 px-4">Diễn giải</th>
                                        <th className="py-3.5 px-4 text-right">Khả dụng</th>
                                        <th className="py-3.5 px-4 sm:px-6 text-right">Tạm giữ</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {transactionList.map((tx) => {
                                        const badge = getTransactionBadge(tx.loai_giao_dich, tx);
                                        const BadgeIcon = badge.icon;
                                        const dateStr = tx.thoi_gian_tao || tx.created_at;

                                        return (
                                            <tr key={tx.ma_giao_dich || tx.id} className="hover:bg-slate-50/60 transition-colors">
                                                <td className="py-3.5 px-4 sm:px-6 text-slate-500 whitespace-nowrap">
                                                    {dateStr ? new Date(dateStr).toLocaleString('vi-VN') : '—'}
                                                </td>
                                                <td className="py-3.5 px-4 whitespace-nowrap">
                                                    <span className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border ${badge.className}`}>
                                                        <BadgeIcon size={12} />
                                                        {badge.label}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 text-slate-700 max-w-xs truncate">
                                                    {tx.mo_ta || 'Giao dịch điểm hệ thống'}
                                                </td>
                                                <td className="py-3.5 px-4 text-right whitespace-nowrap font-bold">
                                                    {tx.thay_doi_kha_dung > 0 ? (
                                                        <span className="text-emerald-600">+{tx.thay_doi_kha_dung} pts</span>
                                                    ) : tx.thay_doi_kha_dung < 0 ? (
                                                        <span className="text-rose-600">{tx.thay_doi_kha_dung} pts</span>
                                                    ) : (
                                                        <span className="text-slate-400">0 pts</span>
                                                    )}
                                                </td>
                                                <td className="py-3.5 px-4 sm:px-6 text-right whitespace-nowrap font-semibold">
                                                    {tx.thay_doi_tam_giu > 0 ? (
                                                        <span className="text-amber-600">+{tx.thay_doi_tam_giu} pts</span>
                                                    ) : tx.thay_doi_tam_giu < 0 ? (
                                                        <span className="text-slate-600">{tx.thay_doi_tam_giu} pts</span>
                                                    ) : (
                                                        <span className="text-slate-400">0 pts</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>

                            {/* Pagination */}
                            {paginationLinks.length > 3 && (
                                <Pagination links={paginationLinks} className="border-t border-slate-100" />
                            )}
                        </div>
                    ) : (
                        <div className="py-16 px-4 text-center space-y-4">
                            <div className="w-16 h-16 rounded-2xl bg-blue-50 text-[#005baa] flex items-center justify-center mx-auto">
                                <Coins size={30} />
                            </div>
                            <div className="max-w-md mx-auto space-y-1">
                                <h3 className="text-base font-bold text-slate-800">
                                    Chưa có biến động điểm nào phát sinh
                                </h3>
                                <p className="text-xs text-slate-500 leading-relaxed">
                                    Số dư điểm ví sẽ biến động khi bạn tạo yêu cầu tìm tài liệu (Bounty) hoặc đóng góp tài liệu thành công giải quyết yêu cầu của bạn học.
                                </p>
                            </div>
                            <div className="pt-2">
                                <Link
                                    href={route('document-requests.index')}
                                    className="inline-flex items-center gap-2 bg-[#005baa] hover:bg-blue-800 text-white font-semibold text-xs px-4 py-2.5 rounded-xl transition shadow-xs"
                                >
                                    Khám phá thị trường săn thưởng &rarr;
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </StudentLayout>
    );
}
