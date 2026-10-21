import React from 'react';
import GuestLayout from '@/Layouts/GuestLayout';

export default function TestGuest() {
    return (
        <GuestLayout title="Kiểm tra Guest Layout">
            <div className="space-y-4">
                <h2 className="text-xl font-bold text-text-primary text-center">
                    Giao diện Khách vãng lai
                </h2>
                <p className="text-sm text-text-secondary text-center">
                    Đây là nội dung nằm trong hộp Card căn giữa của GuestLayout (khu vực sẽ đặt form Đăng nhập/Đăng ký).
                </p>
                <button
                    type="button"
                    className="w-full py-2.5 px-4 bg-brand-primary hover:bg-blue-800 text-white rounded-md font-medium transition-colors shadow-sm active:scale-[0.98]"
                >
                    Nút hành động mẫu
                </button>
            </div>
        </GuestLayout>
    );
}