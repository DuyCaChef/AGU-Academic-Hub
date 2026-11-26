import React from 'react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function TestAdmin() {
    return (
        <AdminLayout title="Bảng điều khiển Quản trị">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="p-6 bg-surface-default rounded-xl border border-border-default shadow-xs">
                    <h3 className="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        Hồ sơ chờ duyệt
                    </h3>
                    <p className="text-3xl font-bold text-text-primary mt-2">12</p>
                </div>
                <div className="p-6 bg-surface-default rounded-xl border border-border-default shadow-xs">
                    <h3 className="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        Báo cáo vi phạm
                    </h3>
                    <p className="text-3xl font-bold text-semantic-warning mt-2">3</p>
                </div>
                <div className="p-6 bg-surface-default rounded-xl border border-border-default shadow-xs">
                    <h3 className="text-xs font-semibold text-text-secondary uppercase tracking-wider">
                        Tài liệu đã xóa mềm
                    </h3>
                    <p className="text-3xl font-bold text-semantic-danger mt-2">5</p>
                </div>
            </div>

            <div className="mt-6 p-6 bg-surface-default rounded-xl border border-border-default">
                <p className="text-text-secondary leading-relaxed">
                    Vùng nội dung quản trị chính. Sidebar bên trái cố định 280px nền tối (<code className="px-1.5 py-0.5 rounded bg-slate-100 font-mono text-xs text-brand-primary">bg-surface-admin</code>).
                </p>
            </div>
        </AdminLayout>
    );
}