import React, { useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ArrowLeft, AlertTriangle, History, Save, Shield, UserRound } from 'lucide-react';

const roles = [
    ['SINH_VIEN', 'Sinh viên'],
    ['CUU_SINH_VIEN', 'Cựu sinh viên'],
    ['KIEM_DUYET_VIEN', 'Kiểm duyệt viên'],
    ['QUAN_TRI_VIEN', 'Quản trị viên'],
];

export default function UserShow({ user, faculties = [], statusHistory = [], roleHistory = [], permissions = {} }) {
    const { auth } = usePage().props;
    const [role, setRole] = useState(user.vai_tro);
    const [adminLevel, setAdminLevel] = useState(user.cap_quan_tri || 'THUONG');
    const [faculty, setFaculty] = useState(user.khoa?.ma_khoa || '');
    const [permissionReason, setPermissionReason] = useState('');
    const [statusReason, setStatusReason] = useState('');
    const [violationReason, setViolationReason] = useState('');
    const [saving, setSaving] = useState(false);
    const canHaveFaculty = role === 'SINH_VIEN' || role === 'CUU_SINH_VIEN';

    useEffect(() => {
        if (!canHaveFaculty) {
            setFaculty('');
        }
    }, [canHaveFaculty]);

    const submitPermissions = (event) => {
        event.preventDefault();
        setSaving(true);
        router.put(route('admin.users.permissions', user.ma_nguoi_dung), {
            vai_tro: role,
            cap_quan_tri: role === 'QUAN_TRI_VIEN' ? adminLevel : null,
            ma_khoa: faculty || null,
            ly_do: permissionReason || null,
        }, { preserveScroll: true, onFinish: () => setSaving(false) });
    };

    const toggleStatus = () => {
        router.put(route('admin.users.status', user.ma_nguoi_dung), { ly_do: statusReason || null }, { preserveScroll: true });
    };

    const recordViolation = (event) => {
        event.preventDefault();
        router.post(route('admin.users.violations', user.ma_nguoi_dung), { ly_do: violationReason }, {
            preserveScroll: true,
            onSuccess: () => setViolationReason(''),
        });
    };

    return (
        <AdminLayout title="Chi tiết tài khoản">
            <Head title={`Tài khoản ${user.ho_ten}`} />
            <div className="max-w-6xl mx-auto space-y-6">
                <Link href={route('admin.users.index')} className="inline-flex items-center gap-2 text-sm text-[#005baa] hover:underline">
                    <ArrowLeft className="w-4 h-4" /> Quay lại danh sách
                </Link>
                <div className="bg-white rounded-2xl border border-slate-200 p-6 flex items-start gap-4">
                    <div className="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#005baa] to-[#00a896] text-white text-xl font-bold flex items-center justify-center">
                        {user.ho_ten?.charAt(0) || 'U'}
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-slate-900">{user.ho_ten}</h1>
                        <p className="text-sm text-slate-500">{user.email} {user.mssv && `• ${user.mssv}`}</p>
                        <p className="text-xs text-slate-400 mt-1">Tạo lúc {user.ngay_tao}</p>
                    </div>
                </div>
                <div className="grid lg:grid-cols-2 gap-6">
                    <form onSubmit={submitPermissions} className="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                        <h2 className="font-bold text-slate-900 flex items-center gap-2"><Shield className="w-5 h-5 text-[#005baa]" /> Vai trò và Khoa</h2>
                        {!permissions.can_manage_target && <p className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Tài khoản này chỉ có thể được quản lý bởi Super Admin.</p>}
                        <label className="block text-sm font-medium text-slate-700">Vai trò
                            <select disabled={!permissions.can_manage_target} value={role} onChange={(e) => setRole(e.target.value)} className="mt-1 h-12 w-full rounded-xl border-slate-200 px-4 text-base text-slate-800 focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 disabled:bg-slate-100 disabled:text-slate-500">
                                {roles.filter(([value]) => auth?.user?.cap_quan_tri === 'TOI_CAO' || ['SINH_VIEN', 'CUU_SINH_VIEN'].includes(value) || value === role).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </select>
                        </label>
                        {role === 'QUAN_TRI_VIEN' && (
                            <label className="block text-sm font-medium text-slate-700">Cấp quản trị
                                <select disabled={!permissions.can_manage_admin} value={adminLevel} onChange={(e) => setAdminLevel(e.target.value)} className="mt-1 h-12 w-full rounded-xl border-slate-200 px-4 text-base text-slate-800 focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 disabled:bg-slate-100 disabled:text-slate-500">
                                    <option value="THUONG">Admin thường</option>
                                    <option value="TOI_CAO">Super Admin</option>
                                </select>
                            </label>
                        )}
                        {canHaveFaculty ? (
                            <label className="block text-sm font-medium text-slate-700">Khoa
                                <select value={faculty} onChange={(e) => setFaculty(e.target.value)} className="mt-1 h-12 w-full rounded-xl border-slate-200 px-4 text-base text-slate-800 focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20">
                                    <option value="">Không gán Khoa</option>
                                    {faculties.map((item) => <option key={item.ma_khoa} value={item.ma_khoa}>{item.ten_khoa}</option>)}
                                </select>
                            </label>
                        ) : (
                            <p className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Vai trò quản trị hệ thống không thuộc Khoa.
                            </p>
                        )}
                        <textarea value={permissionReason} onChange={(e) => setPermissionReason(e.target.value)} placeholder="Lý do thay đổi (tùy chọn)" className="min-h-32 w-full resize-y rounded-xl border-slate-200 px-4 py-3 text-base leading-6 text-slate-800 caret-black focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20" rows="4" />
                        <button disabled={saving || !permissions.can_manage_target} className="inline-flex items-center gap-2 rounded-xl bg-[#005baa] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"><Save className="w-4 h-4" /> Lưu phân quyền</button>
                    </form>
                    <div className="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                        <h2 className="font-bold text-slate-900 flex items-center gap-2"><UserRound className="w-5 h-5 text-[#005baa]" /> Trạng thái tài khoản</h2>
                        {user.canh_bao_vi_pham && (
                            <div className="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium leading-6 text-amber-900">
                                {user.canh_bao_vi_pham}
                            </div>
                        )}
                        <p className="text-sm">Trạng thái: <strong>{user.ten_trang_thai}</strong></p>
                        {user.ly_do_khoa && <p className="text-sm text-rose-700">Lý do khóa: {user.ly_do_khoa}</p>}
                        <p className="text-sm">Số lần vi phạm: <strong>{user.so_lan_vi_pham}</strong> {user.muc_canh_bao && `(${user.muc_canh_bao})`}</p>
                        <textarea value={statusReason} onChange={(e) => setStatusReason(e.target.value)} placeholder="Lý do khóa/mở khóa (tùy chọn)" className="min-h-32 w-full resize-y rounded-xl border-slate-200 px-4 py-3 text-base leading-6 text-slate-800 caret-black focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20" rows="4" />
                        <button disabled={!permissions.can_manage_target} onClick={toggleStatus} className="rounded-xl border border-slate-300 px-4 py-3 text-base font-semibold hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">{user.trang_thai === 'TAM_KHOA' ? 'Mở khóa và kích hoạt lại' : 'Tạm khóa tài khoản'}</button>
                        <form onSubmit={recordViolation} className="pt-4 border-t border-slate-100 space-y-2">
                            <label className="text-sm font-medium text-slate-700">Ghi nhận vi phạm</label>
                            <textarea required value={violationReason} onChange={(e) => setViolationReason(e.target.value)} placeholder="Mô tả vi phạm" className="min-h-32 w-full resize-y rounded-xl border-slate-200 px-4 py-3 text-base leading-6 text-slate-800 caret-black focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20" rows="4" />
                            <button disabled={!permissions.can_manage_target} className="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-3 text-base font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"><AlertTriangle className="w-4 h-4" /> Ghi nhận cảnh báo</button>
                        </form>
                    </div>
                </div>
                <div className="grid lg:grid-cols-2 gap-6">
                    <Audit title="Lịch sử trạng thái" items={statusHistory} role={false} />
                    <Audit title="Lịch sử phân quyền" items={roleHistory} role />
                </div>
            </div>
        </AdminLayout>
    );
}

function Audit({ title, items, role }) {
    return <div className="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 className="font-bold text-slate-900 flex items-center gap-2 mb-4"><History className="w-5 h-5 text-[#005baa]" /> {title}</h2>
        {items.length === 0 ? <p className="text-sm text-slate-500">Chưa có lịch sử.</p> : <div className="space-y-3">{items.map((item, index) => <div key={index} className="border-l-2 border-blue-200 pl-3 text-sm"><p className="font-semibold">{role ? `${item.vai_tro_cu || '—'} → ${item.vai_tro_moi}` : `${item.trang_thai_cu || '—'} → ${item.trang_thai_moi}`}</p><p className="text-xs text-slate-500">{item.nguoi_thuc_hien} • {item.thoi_diem}</p>{item.ly_do && <p className="text-xs text-slate-600 mt-1">{item.ly_do}</p>}</div>)}</div>}
    </div>;
}
