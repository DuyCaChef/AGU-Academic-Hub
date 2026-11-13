import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    Bell,
    CheckCheck,
    CheckCircle2,
    AlertTriangle,
    Coins,
    FileText,
    ShieldAlert,
    UserCheck,
    Info,
    Trash2,
    ExternalLink,
    Filter,
    Clock,
    Inbox
} from 'lucide-react';

export default function NotificationsIndex({ notifications, unread_count = 0, filters = {} }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const isAdminOrMod = ['QUAN_TRI_VIEN', 'KIEM_DUYET_VIEN'].includes(user?.vai_tro);

    const [currentFilter, setCurrentFilter] = useState(filters.filter || 'all');
    const [currentCategory, setCurrentCategory] = useState(filters.category || '');
    const [unreadCountState, setUnreadCountState] = useState(unread_count);
    const [processingId, setProcessingId] = useState(null);

    // Xử lý chuyển tab lọc
    const handleFilterChange = (newFilter, newCategory = currentCategory) => {
        setCurrentFilter(newFilter);
        router.get(
            route('notifications.index'),
            {
                filter: newFilter,
                ...(newCategory ? { category: newCategory } : {}),
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Xử lý lọc nhóm danh mục (Personal vs Work Item)
    const handleCategoryChange = (newCategory) => {
        const cat = currentCategory === newCategory ? '' : newCategory;
        setCurrentCategory(cat);
        handleFilterChange(currentFilter, cat);
    };

    // Đánh dấu 1 thông báo là đã đọc
    const handleMarkAsRead = async (item, e) => {
        e?.stopPropagation();
        if (item.is_read || processingId) return;

        setProcessingId(item.id);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/thong-bao/${item.id}/doc`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (res.ok) {
                const data = await res.json();
                setUnreadCountState(data.unread_count ?? Math.max(0, unreadCountState - 1));
                router.reload({ only: ['notifications', 'unread_count', 'auth'], preserveScroll: true });
            }
        } catch (err) {
            console.error('Lỗi khi đánh dấu đã đọc:', err);
        } finally {
            setProcessingId(null);
        }
    };

    // Đánh dấu tất cả là đã đọc
    const handleMarkAllAsRead = async () => {
        if (unreadCountState === 0) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/thong-bao/doc-tat-ca', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (res.ok) {
                setUnreadCountState(0);
                router.reload({ only: ['notifications', 'unread_count', 'auth'], preserveScroll: true });
            }
        } catch (err) {
            console.error('Lỗi khi đánh dấu tất cả đã đọc:', err);
        }
    };

    // Xóa một thông báo
    const handleDeleteNotification = async (item, e) => {
        e?.stopPropagation();
        if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/thong-bao/${item.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (res.ok) {
                const data = await res.json();
                setUnreadCountState(data.unread_count ?? unreadCountState);
                router.reload({ only: ['notifications', 'unread_count', 'auth'], preserveScroll: true });
            }
        } catch (err) {
            console.error('Lỗi khi xóa thông báo:', err);
        }
    };

    // Điều hướng khi click vào thông báo (An toàn nội bộ)
    const handleNotificationClick = async (item) => {
        const targetUrl = item.action_url || item.url;
        if (!item.is_read) {
            await handleMarkAsRead(item);
        }

        if (targetUrl) {
            router.visit(targetUrl);
        }
    };

    // Icon tương ứng theo nhóm thông báo
    const renderIcon = (item) => {
        const type = (item.type || '').toUpperCase();
        const title = (item.title || '').toLowerCase();

        if (type.includes('BOUNTY')) {
            return <Coins className="w-5 h-5 text-amber-500" />;
        }
        if (type.includes('KYC')) {
            return <UserCheck className="w-5 h-5 text-indigo-500" />;
        }
        if (type.includes('REPORT') || type.includes('REVIEW_REPORTED')) {
            return <ShieldAlert className="w-5 h-5 text-rose-500" />;
        }
        if (title.includes('phê duyệt') || title.includes('thành công')) {
            return <CheckCircle2 className="w-5 h-5 text-emerald-500" />;
        }
        if (title.includes('từ chối') || title.includes('hủy')) {
            return <AlertTriangle className="w-5 h-5 text-amber-500" />;
        }
        if (type.includes('DOCUMENT')) {
            return <FileText className="w-5 h-5 text-blue-500" />;
        }

        return <Info className="w-5 h-5 text-slate-500" />;
    };

    const items = notifications?.data || [];

    // Nội dung trang trung tâm thông báo
    const content = (
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <Head title="Trung tâm thông báo" />

            {/* Header */}
            <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3.5">
                        <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-[#005baa] flex items-center justify-center text-white shadow-xs">
                            <Bell className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold text-slate-900">Trung tâm thông báo</h1>
                                {unreadCountState > 0 && (
                                    <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500 text-white shadow-xs animate-pulse">
                                        {unreadCountState} chưa đọc
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Theo dõi và cập nhật toàn bộ hoạt động học thuật, tài liệu và săn thưởng của bạn
                            </p>
                        </div>
                    </div>

                    {unreadCountState > 0 && (
                        <button
                            type="button"
                            onClick={handleMarkAllAsRead}
                            className="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all shadow-xs active:scale-95 shrink-0"
                        >
                            <CheckCheck className="w-4 h-4 text-emerald-600" />
                            <span>Đã đọc tất cả</span>
                        </button>
                    )}
                </div>

                {/* Bộ lọc tab */}
                <div className="flex flex-wrap items-center justify-between gap-3 pt-6 mt-6 border-t border-slate-100">
                    <div className="flex items-center gap-1.5 bg-slate-100/80 p-1 rounded-xl">
                        <button
                            type="button"
                            onClick={() => handleFilterChange('all')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                currentFilter === 'all'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Tất cả
                        </button>
                        <button
                            type="button"
                            onClick={() => handleFilterChange('unread')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 ${
                                currentFilter === 'unread'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <span>Chưa đọc</span>
                            {unreadCountState > 0 && (
                                <span className="px-1.5 py-0.2 rounded-full text-[10px] bg-rose-100 text-rose-700 font-bold">
                                    {unreadCountState}
                                </span>
                            )}
                        </button>
                        <button
                            type="button"
                            onClick={() => handleFilterChange('read')}
                            className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                currentFilter === 'read'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            Đã đọc
                        </button>
                    </div>

                    {/* Lọc Category (Personal vs Work Item) */}
                    <div className="flex items-center gap-2">
                        <span className="text-xs text-slate-400 flex items-center gap-1">
                            <Filter className="w-3.5 h-3.5" /> Phân loại:
                        </span>
                        <button
                            type="button"
                            onClick={() => handleCategoryChange('PERSONAL')}
                            className={`px-2.5 py-1 rounded-lg text-xs font-medium border transition-all ${
                                currentCategory === 'PERSONAL'
                                    ? 'bg-blue-50 border-blue-200 text-blue-700 font-semibold'
                                    : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'
                            }`}
                        >
                            Cá nhân
                        </button>
                        <button
                            type="button"
                            onClick={() => handleCategoryChange('WORK_ITEM')}
                            className={`px-2.5 py-1 rounded-lg text-xs font-medium border transition-all ${
                                currentCategory === 'WORK_ITEM'
                                    ? 'bg-amber-50 border-amber-200 text-amber-700 font-semibold'
                                    : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'
                            }`}
                        >
                            Tác vụ xử lý
                        </button>
                    </div>
                </div>
            </div>

            {/* Danh sách thông báo */}
            {items.length === 0 ? (
                <div className="bg-white rounded-2xl border border-slate-200/80 p-12 text-center shadow-xs">
                    <div className="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
                        <Inbox className="w-8 h-8" />
                    </div>
                    <h3 className="text-base font-bold text-slate-800">Không có thông báo nào</h3>
                    <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        {currentFilter === 'unread'
                            ? 'Tuyệt vời! Bạn đã đọc toàn bộ thông báo trong hộp thư.'
                            : 'Hiện tại bạn chưa có thông báo nào trong danh mục đã chọn.'}
                    </p>
                </div>
            ) : (
                <div className="space-y-3">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            onClick={() => handleNotificationClick(item)}
                            className={`bg-white rounded-2xl border transition-all duration-150 p-4 sm:p-5 flex items-start gap-4 cursor-pointer hover:shadow-md hover:border-blue-200 ${
                                !item.is_read
                                    ? 'border-blue-200/80 bg-blue-50/20 ring-1 ring-blue-500/10'
                                    : 'border-slate-200/80'
                            }`}
                        >
                            {/* Icon bên trái */}
                            <div className="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center shrink-0 mt-0.5">
                                {renderIcon(item)}
                            </div>

                            {/* Khối thông tin trung tâm */}
                            <div className="flex-1 min-w-0">
                                <div className="flex flex-wrap items-center justify-between gap-2 mb-1">
                                    <div className="flex items-center gap-2">
                                        <h4 className={`text-sm ${!item.is_read ? 'font-bold text-slate-900' : 'font-semibold text-slate-700'}`}>
                                            {item.title}
                                        </h4>
                                        {!item.is_read && (
                                            <span className="w-2 h-2 rounded-full bg-[#005baa] shrink-0" title="Chưa đọc" />
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {item.category === 'WORK_ITEM' ? (
                                            <span className="px-2 py-0.5 text-[10px] font-bold rounded-md bg-amber-100 text-amber-800">
                                                Tác vụ xử lý
                                            </span>
                                        ) : (
                                            <span className="px-2 py-0.5 text-[10px] font-medium rounded-md bg-slate-100 text-slate-600">
                                                Cá nhân
                                            </span>
                                        )}
                                        <span className="text-[11px] text-slate-400 flex items-center gap-1">
                                            <Clock className="w-3 h-3" />
                                            {item.created_at_human}
                                        </span>
                                    </div>
                                </div>

                                <p className="text-xs text-slate-600 leading-relaxed mb-3">
                                    {item.message}
                                </p>

                                {/* Thanh tác vụ của item */}
                                <div className="flex items-center justify-between pt-2 border-t border-slate-100/80">
                                    {item.action_url ? (
                                        <span className="inline-flex items-center gap-1 text-xs text-[#005baa] hover:underline font-medium">
                                            <span>Mở chi tiết liên kết</span>
                                            <ExternalLink className="w-3 h-3" />
                                        </span>
                                    ) : (
                                        <span />
                                    )}

                                    <div className="flex items-center gap-2">
                                        {!item.is_read && (
                                            <button
                                                type="button"
                                                onClick={(e) => handleMarkAsRead(item, e)}
                                                className="text-xs text-slate-500 hover:text-[#005baa] font-medium px-2 py-1 rounded-md hover:bg-slate-100 transition-colors"
                                            >
                                                Đánh dấu đã đọc
                                            </button>
                                        )}
                                        <button
                                            type="button"
                                            onClick={(e) => handleDeleteNotification(item, e)}
                                            className="text-xs text-slate-400 hover:text-rose-600 p-1 rounded-md hover:bg-rose-50 transition-colors"
                                            title="Xóa thông báo này"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}

                    {/* Phân trang */}
                    {notifications?.links && (
                        <div className="mt-6 bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
                            <Pagination links={notifications.links} />
                        </div>
                    )}
                </div>
            )}
        </div>
    );

    // Bọc trong Layout tương ứng theo quyền người dùng
    if (isAdminOrMod) {
        return <AdminLayout>{content}</AdminLayout>;
    }

    return <StudentLayout>{content}</StudentLayout>;
}

