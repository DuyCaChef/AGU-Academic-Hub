import React, { useState, useEffect, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';
import { Bell, CheckCheck, CheckCircle2, AlertTriangle, AlertCircle, Info, Trash2, ShieldAlert, Loader2, ArrowRight } from 'lucide-react';

export default function NotificationDropdown({ variant = 'student' }) {
    const { auth } = usePage().props;
    const initialCount = auth?.unread_notifications_count || 0;

    const [isOpen, setIsOpen] = useState(false);
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(initialCount);
    const [loading, setLoading] = useState(false);
    const dropdownRef = useRef(null);

    // Cập nhật số lượng thông báo chưa đọc khi auth thay đổi
    useEffect(() => {
        setUnreadCount(auth?.unread_notifications_count || 0);
    }, [auth?.unread_notifications_count]);

    // Đóng dropdown khi click ra ngoài hoặc bấm ESC
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        const handleEscape = (e) => {
            if (e.key === 'Escape') setIsOpen(false);
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, []);

    // Tải danh sách thông báo khi mở dropdown
    const fetchNotifications = async () => {
        setLoading(true);
        try {
            const response = await fetch('/thong-bao', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (response.ok) {
                const data = await response.json();
                setNotifications(data.notifications || []);
                setUnreadCount(data.unread_count || 0);
            }
        } catch (error) {
            console.error('Lỗi khi tải thông báo:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleToggle = () => {
        if (!isOpen) {
            fetchNotifications();
        }
        setIsOpen(!isOpen);
    };

    // Đánh dấu 1 thông báo đã đọc
    const handleNotificationClick = async (item) => {
        if (!item.is_read) {
            try {
                await fetch(`/thong-bao/${item.id}/doc`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                // Cập nhật state local
                setNotifications((prev) =>
                    prev.map((n) => (n.id === item.id ? { ...n, is_read: true } : n))
                );
                setUnreadCount((prev) => Math.max(0, prev - 1));
            } catch (err) {
                console.error('Lỗi đánh dấu đã đọc:', err);
            }
        }

        if (item.url) {
            setIsOpen(false);
            router.visit(item.url);
        }
    };

    // Đánh dấu tất cả đã đọc
    const handleMarkAllRead = async () => {
        try {
            await fetch('/thong-bao/doc-tat-ca', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
            setUnreadCount(0);
        } catch (err) {
            console.error('Lỗi đánh dấu tất cả đã đọc:', err);
        }
    };

    // Icon tương ứng theo loại thông báo
    const getNotificationIcon = (title, type) => {
        const text = (title || '').toLowerCase();
        if (text.includes('duyệt') || text.includes('thành công')) {
            return <CheckCircle2 className="w-5 h-5 text-emerald-500 shrink-0" />;
        }
        if (text.includes('từ chối') || text.includes('cảnh báo') || text.includes('lỗi')) {
            return <AlertTriangle className="w-5 h-5 text-amber-500 shrink-0" />;
        }
        if (text.includes('thùng rác') || text.includes('gỡ')) {
            return <Trash2 className="w-5 h-5 text-rose-500 shrink-0" />;
        }
        if (text.includes('vi phạm') || text.includes('kiểm tra')) {
            return <ShieldAlert className="w-5 h-5 text-rose-500 shrink-0" />;
        }
        return <Info className="w-5 h-5 text-blue-500 shrink-0" />;
    };

    const isStudent = variant === 'student';

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Nút chuông thông báo */}
            <button
                type="button"
                onClick={handleToggle}
                className={`relative p-2 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-brand-primary/50 ${isStudent
                        ? 'text-white hover:text-brand-primary hover:bg-surface-page'
                        : 'text-text-secondary hover:text-text-primary hover:bg-slate-100'
                    }`}
                aria-label="Thông báo"
                aria-expanded={isOpen}
            >
                <Bell size={20} />
                {unreadCount > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-rose-500 rounded-full ring-2 ring-white">
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                )}
            </button>

            {/* Panel Dropdown */}
            {isOpen && (
                <div className="absolute right-0 top-full mt-2 w-80 sm:w-96 rounded-2xl bg-white shadow-2xl border border-slate-200/80 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-150">
                    {/* Header */}
                    <div className="flex items-center justify-between px-4 py-3 bg-slate-50/80 border-b border-slate-100">
                        <div className="flex items-center gap-2">
                            <span className="font-bold text-slate-800 text-sm">Thông báo</span>
                            {unreadCount > 0 && (
                                <span className="px-2 py-0.5 text-xs font-semibold rounded-full bg-brand-primary/10 text-brand-primary">
                                    {unreadCount} mới
                                </span>
                            )}
                        </div>
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={handleMarkAllRead}
                                className="flex items-center gap-1 text-xs text-brand-primary hover:text-brand-secondary font-medium transition-colors"
                            >
                                <CheckCheck className="w-3.5 h-3.5" />
                                <span>Đã đọc tất cả</span>
                            </button>
                        )}
                    </div>

                    {/* Danh sách thông báo */}
                    <div className="max-h-[380px] overflow-y-auto divide-y divide-slate-100">
                        {loading && notifications.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-10 text-slate-400">
                                <Loader2 className="w-6 h-6 animate-spin text-brand-primary mb-2" />
                                <span className="text-xs">Đang tải thông báo...</span>
                            </div>
                        ) : notifications.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-10 px-4 text-center">
                                <div className="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                    <Bell className="w-6 h-6" />
                                </div>
                                <p className="text-sm font-semibold text-slate-700">Chưa có thông báo</p>
                                <p className="text-xs text-slate-400 mt-1">Khi có cập nhật mới về tài khoản hoặc tài liệu, bạn sẽ nhận được thông báo tại đây.</p>
                            </div>
                        ) : (
                            notifications.map((item) => (
                                <div
                                    key={item.id}
                                    onClick={() => handleNotificationClick(item)}
                                    className={`p-3.5 flex items-start gap-3 transition-colors cursor-pointer hover:bg-slate-50 ${!item.is_read ? 'bg-blue-50/40' : ''
                                        }`}
                                >
                                    <div className="mt-0.5">
                                        {getNotificationIcon(item.title, item.type)}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className={`text-xs ${!item.is_read ? 'font-bold text-slate-900' : 'font-semibold text-slate-700'}`}>
                                                {item.title}
                                            </p>
                                            {!item.is_read && (
                                                <span className="w-2 h-2 rounded-full bg-brand-primary shrink-0" />
                                            )}
                                        </div>
                                        <p className="text-xs text-slate-600 mt-1 line-clamp-2">
                                            {item.message}
                                        </p>
                                        <span className="inline-block mt-1.5 text-[10px] text-slate-400">
                                            {item.created_at_human || 'Vừa xong'}
                                        </span>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Footer - Điều hướng đến Trung tâm thông báo */}
                    <div className="p-2 bg-slate-50 border-t border-slate-100 text-center">
                        <button
                            type="button"
                            onClick={() => {
                                setIsOpen(false);
                                router.visit('/thong-bao');
                            }}
                            className="w-full py-1.5 px-3 text-xs font-semibold text-brand-primary hover:text-brand-secondary hover:bg-slate-100 rounded-lg transition-colors flex items-center justify-center gap-1.5"
                        >
                            <span>Xem tất cả thông báo</span>
                            <ArrowRight className="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
