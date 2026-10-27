import React, { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    Users,
    Search,
    Lock,
    Unlock,
    Coins,
    Award,
    School,
    AlertTriangle,
    RotateCcw,
    X,
    Loader2
} from 'lucide-react';

export default function UsersIndex({ users, filters = {}, faculties = [] }) {
    const { auth } = usePage().props;
    const items = users?.data || [];

    // State phục vụ tìm kiếm & bộ lọc
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedRole, setSelectedRole] = useState(filters.vai_tro || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.trang_thai || '');
    const [selectedFaculty, setSelectedFaculty] = useState(filters.ma_khoa || '');

    // State Modal Khóa / Mở khóa
    const [targetUser, setTargetUser] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const isTargetSuspended = targetUser?.trang_thai === 'TAM_KHOA' || targetUser?.trang_thai === 'SUSPENDED';

    // Gửi request lọc
    const applyFilters = (newParams = {}) => {
        const queryParams = {
            search: searchTerm,
            vai_tro: selectedRole,
            trang_thai: selectedStatus,
            ma_khoa: selectedFaculty,
            ...newParams,
        };

        Object.keys(queryParams).forEach((key) => {
            if (!queryParams[key]) delete queryParams[key];
        });

        router.get(route('admin.users.index'), queryParams, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Áp dụng Debounce cho ô tìm kiếm
    useEffect(() => {
        const delayDebounce = setTimeout(() => {
            if (searchTerm !== (filters.search || '')) {
                applyFilters({ search: searchTerm });
            }
        }, 400);

        return () => clearTimeout(delayDebounce);
    }, [searchTerm]);

    // Xóa bộ lọc
    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedRole('');
        setSelectedStatus('');
        setSelectedFaculty('');
        router.get(route('admin.users.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Khóa / Mở khóa tài khoản người dùng
    const handleToggleStatus = () => {
        if (!targetUser) return;

        const userId = targetUser.ma_nguoi_dung || targetUser.id;
        setIsSubmitting(true);
        router.put(
            route('admin.users.status', userId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setTargetUser(null),
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    // Badge Vai trò
    const getRoleBadge = (role) => {
        switch (role) {
            case 'ADMIN':
            case 'QUAN_TRI_VIEN':
                return { text: 'Quản trị viên', badge: 'bg-purple-50 text-purple-700 border-purple-200' };
            case 'MODERATOR':
            case 'KIEM_DUYET_VIEN':
                return { text: 'Kiểm duyệt viên', badge: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
            case 'ALUMNI':
            case 'CUU_SINH_VIEN':
                return { text: 'Cựu sinh viên', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
            default:
                return { text: 'Sinh viên', badge: 'bg-blue-50 text-blue-700 border-blue-200' };
        }
    };

    // Badge Trạng thái
    const getStatusBadge = (status) => {
        switch (status) {
            case 'ACTIVE':
            case 'HOAT_DONG':
            case 'DANG_HOAT_DONG':
                return { text: 'Đang hoạt động', badge: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
            case 'SUSPENDED':
            case 'TAM_KHOA':
                return { text: 'Đã tạm khóa', badge: 'bg-rose-50 text-rose-700 border-rose-200' };
            default:
                return { text: 'Chờ xác thực', badge: 'bg-amber-50 text-amber-700 border-amber-200' };
        }
    };

    return (
        <AdminLayout title="Quản lý Người dùng">
            <Head title="Quản lý Người dùng - AGU Hub Admin" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 bg-blue-50 text-[#005baa] rounded-xl border border-blue-100">
                                <Users className="w-5 h-5" />
                            </span>
                            <h1 className="text-xl font-bold text-slate-900">
                                Quản lý Tài khoản & Phân quyền Người dùng
                            </h1>
                        </div>
                        <p className="text-xs text-slate-500 mt-1.5 ml-9">
                            Tra cứu hồ sơ thành viên toàn trường, kiểm tra ví điểm và thực hiện khóa/mở khóa tài khoản vi phạm.
                        </p>
                    </div>

                    <div className="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200 self-start sm:self-auto">
                        Tổng số: <strong className="text-slate-900">{users?.total || items.length}</strong> thành viên
                    </div>
                </div>

                {/* Thanh công cụ tìm kiếm và lọc */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div className="relative lg:col-span-2">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Tìm theo tên, email, MSSV..."
                                className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-slate-200 focus:border-[#005baa] focus:ring-[#005baa]"
                            />
                        </div>

                        <div>
                            <select
                                value={selectedRole}
                                onChange={(e) => {
                                    setSelectedRole(e.target.value);
                                    applyFilters({ vai_tro: e.target.value });
                                }}
                                className="w-full py-2 px-3 text-xs rounded-xl border-slate-200 focus:border-[#005baa] focus:ring-[#005baa]"
                            >
                                <option value="">Tất cả vai trò</option>
                                <option value="SINH_VIEN">Sinh viên</option>
                                <option value="CUU_SINH_VIEN">Cựu sinh viên</option>
                                <option value="KIEM_DUYET_VIEN">Kiểm duyệt viên</option>
                                <option value="QUAN_TRI_VIEN">Quản trị viên</option>
                            </select>
                        </div>

                        <div>
                            <select
                                value={selectedStatus}
                                onChange={(e) => {
                                    setSelectedStatus(e.target.value);
                                    applyFilters({ trang_thai: e.target.value });
                                }}
                                className="w-full py-2 px-3 text-xs rounded-xl border-slate-200 focus:border-[#005baa] focus:ring-[#005baa]"
                            >
                                <option value="">Tất cả trạng thái</option>
                                <option value="DANG_HOAT_DONG">Đang hoạt động</option>
                                <option value="CHUA_XAC_THUC">Chờ xác thực</option>
                                <option value="TAM_KHOA">Đã tạm khóa</option>
                            </select>
                        </div>

                        <div>
                            <select
                                value={selectedFaculty}
                                onChange={(e) => {
                                    setSelectedFaculty(e.target.value);
                                    applyFilters({ ma_khoa: e.target.value });
                                }}
                                className="w-full py-2 px-3 text-xs rounded-xl border-slate-200 focus:border-[#005baa] focus:ring-[#005baa]"
                            >
                                <option value="">Tất cả khoa</option>
                                {faculties.map((f) => (
                                    <option key={f.ma_khoa} value={f.ma_khoa}>
                                        {f.ky_hieu_khoa} - {f.ten_khoa}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {(searchTerm || selectedRole || selectedStatus || selectedFaculty) && (
                        <div className="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                            <span className="text-slate-500">Đang lọc danh sách theo tiêu chí tùy chỉnh</span>
                            <button
                                type="button"
                                onClick={handleResetFilters}
                                className="inline-flex items-center gap-1 text-[#005baa] hover:underline font-semibold"
                            >
                                <RotateCcw className="w-3.5 h-3.5" />
                                Xóa bộ lọc
                            </button>
                        </div>
                    )}
                </div>

                {/* Danh sách bảng người dùng */}
                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    {items.length === 0 ? (
                        <div className="p-12 text-center text-slate-500 text-xs">
                            Không tìm thấy tài khoản người dùng phù hợp với tiêu chí lọc.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-slate-600">
                                <thead className="bg-slate-50/75 border-b border-slate-200/80 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <tr>
                                        <th className="px-6 py-4">Thành viên</th>
                                        <th className="px-6 py-4">Vai trò / Khoa</th>
                                        <th className="px-6 py-4">Trạng thái</th>
                                        <th className="px-6 py-4">Ví điểm / Uy tín</th>
                                        <th className="px-6 py-4">Ngày tạo</th>
                                        <th className="px-6 py-4 text-right">Khóa / Mở</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {items.map((u) => {
                                        const role = getRoleBadge(u.vai_tro);
                                        const status = getStatusBadge(u.trang_thai);
                                        const currentUserId = auth?.user?.id || auth?.user?.ma_nguoi_dung;
                                        const isSelf = currentUserId === (u.id || u.ma_nguoi_dung);
                                        const isSuspended = u.trang_thai === 'SUSPENDED' || u.trang_thai === 'TAM_KHOA';

                                        return (
                                            <tr key={u.id || u.ma_nguoi_dung} className="hover:bg-slate-50/50 transition-colors">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-[#005baa] to-[#00a896] text-white font-bold flex items-center justify-center uppercase text-xs shadow-xs">
                                                            {u.ho_ten ? u.ho_ten.charAt(0) : 'U'}
                                                        </div>
                                                        <div>
                                                            <div className="font-semibold text-slate-900 text-sm flex items-center gap-1.5">
                                                                {u.ho_ten}
                                                                {isSelf && (
                                                                    <span className="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200 font-normal">
                                                                        Bạn
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <div className="text-xs text-slate-500 flex items-center gap-2">
                                                                <span>{u.email}</span>
                                                                {u.mssv && (
                                                                    <span className="font-mono text-[11px] text-[#005baa] font-semibold">
                                                                        • {u.mssv}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${role.badge}`}>
                                                        {role.text}
                                                    </span>
                                                    {u.khoa && (
                                                        <div className="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                                            <School className="w-3.5 h-3.5 text-slate-400" />
                                                            {u.khoa.ten_khoa}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="px-6 py-4">
                                                    <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border ${status.badge}`}>
                                                        <span className="w-1.5 h-1.5 rounded-full bg-current" />
                                                        {status.text}
                                                    </span>
                                                </td>

                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-1.5 text-xs font-semibold text-amber-700">
                                                        <Coins className="w-3.5 h-3.5 text-amber-500" />
                                                        {u.vi_diem?.diem_kha_dung ?? 0} đ
                                                        {u.vi_diem?.diem_tam_giu > 0 && (
                                                            <span className="text-[10px] text-slate-400 font-normal">
                                                                (+{u.vi_diem.diem_tam_giu} giữ)
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-1 text-[11px] text-slate-500 mt-0.5">
                                                        <Award className="w-3 h-3 text-slate-400" />
                                                        Uy tín: {u.diem_uy_tin ?? 100}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                                    {u.ngay_tao}
                                                </td>

                                                <td className="px-6 py-4 text-right">
                                                    {isSelf ? (
                                                        <span className="text-xs text-slate-400 italic">
                                                            Không thể khóa
                                                        </span>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() => setTargetUser(u)}
                                                            className={`inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors border ${isSuspended
                                                                    ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200'
                                                                    : 'text-rose-700 bg-rose-50 hover:bg-rose-100 border-rose-200'
                                                                }`}
                                                        >
                                                            {isSuspended ? (
                                                                <>
                                                                    <Unlock className="w-3.5 h-3.5" />
                                                                    Mở khóa
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Lock className="w-3.5 h-3.5" />
                                                                    Khóa TK
                                                                </>
                                                            )}
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={users?.links} />
                </div>
            </div>

            {/* MODAL XÁC NHẬN KHÓA / MỞ KHÓA TÀI KHOẢN */}
            {targetUser && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-xs animate-in fade-in duration-200">
                    <div className="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div className={`flex items-center gap-2 font-bold text-base ${isTargetSuspended ? 'text-emerald-600' : 'text-rose-600'
                                }`}>
                                <AlertTriangle className="w-5 h-5" />
                                {isTargetSuspended ? 'Mở khóa tài khoản' : 'Xác nhận khóa tài khoản'}
                            </div>
                            <button
                                type="button"
                                onClick={() => setTargetUser(null)}
                                className="text-slate-400 hover:text-slate-600"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="mt-4 text-xs text-slate-600 space-y-3">
                            <p>
                                Bạn có chắc chắn muốn {isTargetSuspended ? 'mở khóa hoạt động' : 'tạm đình chỉ hoạt động'} đối với thành viên:
                            </p>
                            <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                <div className="font-bold text-slate-900 text-sm">{targetUser.ho_ten}</div>
                                <div className="text-slate-500">{targetUser.email}</div>
                                {targetUser.mssv && (
                                    <div className="font-mono text-[#005baa] mt-0.5">MSSV: {targetUser.mssv}</div>
                                )}
                            </div>
                            <p className="text-slate-500 italic">
                                {isTargetSuspended
                                    ? 'Người dùng này sẽ có thể đăng nhập và truy cập trở lại toàn bộ chức năng cộng đồng.'
                                    : 'Khi bị khóa, tài khoản sẽ lập tức bị chặn đăng nhập vào hệ thống.'}
                            </p>
                        </div>

                        <div className="flex items-center justify-end gap-2 mt-6 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                disabled={isSubmitting}
                                onClick={() => setTargetUser(null)}
                                className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                            >
                                Hủy bỏ
                            </button>
                            <button
                                type="button"
                                disabled={isSubmitting}
                                onClick={handleToggleStatus}
                                className={`inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white rounded-xl transition-colors shadow-xs ${isTargetSuspended
                                        ? 'bg-emerald-600 hover:bg-emerald-700'
                                        : 'bg-rose-600 hover:bg-rose-700'
                                    }`}
                            >
                                {isSubmitting && <Loader2 className="w-3.5 h-3.5 animate-spin" />}
                                {isTargetSuspended ? 'Xác nhận mở khóa' : 'Khóa tài khoản ngay'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}