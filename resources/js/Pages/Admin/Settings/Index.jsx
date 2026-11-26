import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    Settings,
    Save,
    HardDrive,
    Shield,
    FileText,
    Sliders,
    CheckCircle2,
    AlertTriangle,
    Info,
    RotateCcw
} from 'lucide-react';

export default function SettingsIndex({ settingsGrouped = {} }) {
    const { flash } = usePage().props;
    const [submittingKey, setSubmittingKey] = useState(null);
    const [formValues, setFormValues] = useState(() => {
        const initial = {};
        Object.values(settingsGrouped).flat().forEach((setting) => {
            initial[setting.khoa] = setting.gia_tri;
        });
        return initial;
    });

    const handleValueChange = (key, value) => {
        setFormValues((prev) => ({
            ...prev,
            [key]: value,
        }));
    };

    const handleSave = (key) => {
        setSubmittingKey(key);
        router.put(
            route('admin.settings.update', key),
            { gia_tri: formValues[key] },
            {
                preserveScroll: true,
                onFinish: () => setSubmittingKey(null),
            }
        );
    };

    const getGroupIcon = (group) => {
        switch (group) {
            case 'tai_lieu':
                return FileText;
            case 'kiem_duyet':
                return Shield;
            case 'luu_tru':
                return HardDrive;
            default:
                return Sliders;
        }
    };

    const getGroupTitle = (group) => {
        switch (group) {
            case 'tai_lieu':
                return 'Quản trị Tài liệu & Tải lên';
            case 'kiem_duyet':
                return 'Quy chuẩn Kiểm duyệt & Báo cáo';
            case 'luu_tru':
                return 'Hạn mức & Giám sát Lưu trữ';
            default:
                return 'Cấu hình Hệ thống Chung';
        }
    };

    return (
        <AdminLayout title="Cấu hình hệ thống">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-purple-600/10 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <Settings className="w-6 h-6" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                Cấu hình hệ thống
                            </h1>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Điều chỉnh các tham số vận hành nghiệp vụ. Mọi thay đổi đều được ghi nhật ký kiểm toán.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Thông báo Alert về Audit */}
                <div className="p-4 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50/70 dark:bg-blue-950/20 flex items-start gap-3 text-sm text-blue-800 dark:text-blue-300">
                    <Info className="w-5 h-5 flex-shrink-0 mt-0.5 text-blue-600 dark:text-blue-400" />
                    <div>
                        <span className="font-semibold">Bảo đảm toàn vẹn cấu hình:</span> Việc thay đổi các thông số dưới đây sẽ ảnh hưởng trực tiếp đến quy trình tự động ẩn tài liệu, thời gian lưu thùng rác và phân phối tài nguyên. Mọi thao tác lưu đều được ghi nhận vào <strong>Nhật ký kiểm toán nghiệp vụ</strong> kèm mã quản trị viên và thời điểm.
                    </div>
                </div>

                {/* Danh sách các nhóm cấu hình */}
                <div className="space-y-6">
                    {Object.entries(settingsGrouped).map(([groupKey, settings]) => {
                        const Icon = getGroupIcon(groupKey);
                        return (
                            <div
                                key={groupKey}
                                className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden"
                            >
                                <div className="p-4 px-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 flex items-center gap-3">
                                    <div className="p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                                        <Icon className="w-4 h-4" />
                                    </div>
                                    <h2 className="text-base font-bold text-slate-900 dark:text-white">
                                        {getGroupTitle(groupKey)}
                                    </h2>
                                </div>

                                <div className="p-6 divide-y divide-slate-100 dark:divide-slate-800">
                                    {settings.map((item) => (
                                        <div
                                            key={item.khoa}
                                            className="py-4 first:pt-0 last:pb-0 flex flex-col md:flex-row md:items-center justify-between gap-4"
                                        >
                                            <div className="max-w-xl">
                                                <div className="font-semibold text-slate-900 dark:text-white text-sm">
                                                    {item.tieu_de}
                                                </div>
                                                <div className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                    {item.mo_ta}
                                                </div>
                                                <div className="text-[11px] font-mono text-slate-400 mt-1">
                                                    Khóa: {item.khoa}
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-3 self-end md:self-center">
                                                {item.kieu_du_lieu === 'boolean' ? (
                                                    <select
                                                        value={formValues[item.khoa] ?? '1'}
                                                        onChange={(e) => handleValueChange(item.khoa, e.target.value)}
                                                        className="py-2 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                                    >
                                                        <option value="1">Bật (Hoạt động)</option>
                                                        <option value="0">Tắt (Vô hiệu)</option>
                                                    </select>
                                                ) : (
                                                    <input
                                                        type={item.kieu_du_lieu === 'integer' ? 'number' : 'text'}
                                                        value={formValues[item.khoa] ?? ''}
                                                        onChange={(e) => handleValueChange(item.khoa, e.target.value)}
                                                        className="w-36 py-2 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500"
                                                    />
                                                )}

                                                <button
                                                    onClick={() => handleSave(item.khoa)}
                                                    disabled={submittingKey === item.khoa || String(formValues[item.khoa]) === String(item.gia_tri)}
                                                    className={`inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg transition ${
                                                        String(formValues[item.khoa]) !== String(item.gia_tri)
                                                            ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-sm'
                                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-400 cursor-not-allowed'
                                                    }`}
                                                >
                                                    <Save className="w-3.5 h-3.5" />
                                                    <span>{submittingKey === item.khoa ? 'Đang lưu...' : 'Lưu'}</span>
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AdminLayout>
    );
}

