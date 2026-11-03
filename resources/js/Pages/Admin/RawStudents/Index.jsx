import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Common/Pagination';
import {
    UserCheck,
    Search,
    UploadCloud,
    Edit2,
    Trash2,
    RotateCcw,
    X,
    CheckCircle2,
    AlertCircle,
    UserPlus,
    School,
    FileSpreadsheet
} from 'lucide-react';

export default function RawStudentsIndex({ students, filters = {}, faculties = [] }) {
    const items = students?.data || [];

    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.da_kich_hoat ?? '');
    const [selectedFaculty, setSelectedFaculty] = useState(filters.ma_khoa || '');

    // State Modal Import
    const [showImportModal, setShowImportModal] = useState(false);
    const [rawText, setRawText] = useState('');
    const [isImporting, setIsImporting] = useState(false);

    // State Modal Edit
    const [editingStudent, setEditingStudent] = useState(null);
    const [editForm, setEditForm] = useState({ ho_ten: '', email_sv: '', ma_khoa: '', mat_khau_mac_dinh: '' });

    // State Modal Delete
    const [deletingStudent, setDeletingStudent] = useState(null);

    // Filter submit
    const applyFilters = (newParams = {}) => {
        const queryParams = {
            search: searchTerm,
            da_kich_hoat: selectedStatus,
            ma_khoa: selectedFaculty,
            ...newParams,
        };

        Object.keys(queryParams).forEach((key) => {
            if (queryParams[key] === '' || queryParams[key] === null || queryParams[key] === undefined) {
                delete queryParams[key];
            }
        });

        router.get(route('admin.raw-students.index'), queryParams, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchTerm !== (filters.search || '')) {
                applyFilters({ search: searchTerm });
            }
        }, 400);

        return () => clearTimeout(timer);
    }, [searchTerm]);

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedStatus('');
        setSelectedFaculty('');

        router.get(route('admin.raw-students.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Xử lý nạp dữ liệu từ văn bản (CSV / Tab separated / Lines)
    const handleBatchImport = (e) => {
        e.preventDefault();
        if (!rawText.trim()) return;

        // Tách các dòng: hỗ trợ định dạng: MSSV, Họ tên, Email, Mã Khoa
        const lines = rawText.trim().split('\n');
        const records = [];

        lines.forEach((line) => {
            const parts = line.split(/[,\t]+/).map((p) => p.trim());
            if (parts.length >= 2 && parts[0]) {
                records.push({
                    mssv: parts[0],
                    ho_ten: parts[1] || '',
                    email_sv: parts[2] || `${parts[0].toLowerCase()}@student.agu.edu.vn`,
                    ma_khoa: parts[3] || null,
                });
            }
        });

        if (records.length === 0) {
            alert('Vui lòng nhập đúng định dạng: MSSV, Họ tên, Email (tùy chọn), Mã khoa (tùy chọn).');
            return;
        }

        setIsImporting(true);
        router.post(
            route('admin.raw-students.import'),
            { records },
            {
                onSuccess: () => {
                    setShowImportModal(false);
                    setRawText('');
                },
                onFinish: () => setIsImporting(false),
            }
        );
    };

    const handleOpenEdit = (student) => {
        setEditingStudent(student);
        setEditForm({
            ho_ten: student.ho_ten || '',
            email_sv: student.email_sv || '',
            ma_khoa: student.ma_khoa || '',
            mat_khau_mac_dinh: '',
        });
    };

    const handleSaveEdit = (e) => {
        e.preventDefault();
        if (!editingStudent) return;

        router.put(
            route('admin.raw-students.update', editingStudent.ma_ban_ghi),
            editForm,
            {
                onSuccess: () => setEditingStudent(null),
            }
        );
    };

    const handleConfirmDelete = () => {
        if (!deletingStudent) return;

        router.delete(
            route('admin.raw-students.destroy', deletingStudent.ma_ban_ghi),
            {
                onSuccess: () => setDeletingStudent(null),
            }
        );
    };

    return (
        <AdminLayout title="Quản lý danh sách sinh viên thô">
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-indigo-600/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <UserCheck className="w-6 h-6" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                Dữ liệu sinh viên thô (Raw Roster)
                            </h1>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Dữ liệu định danh nạp trước cho sinh viên đang học. Mọi hành vi nạp, sửa, xóa đều được lưu audit log.
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={() => setShowImportModal(true)}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm shadow-sm transition"
                    >
                        <UploadCloud className="w-4 h-4" />
                        <span>Nạp dữ liệu SV (Import)</span>
                    </button>
                </div>

                {/* Bộ lọc */}
                <div className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div className="md:col-span-6 relative">
                        <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Tìm kiếm theo MSSV, Họ tên, Email..."
                            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div className="md:col-span-3">
                        <select
                            value={selectedStatus}
                            onChange={(e) => {
                                setSelectedStatus(e.target.value);
                                applyFilters({ da_kich_hoat: e.target.value });
                            }}
                            className="w-full py-2 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Tất cả trạng thái</option>
                            <option value="true">Đã kích hoạt</option>
                            <option value="false">Chưa kích hoạt</option>
                        </select>
                    </div>

                    <div className="md:col-span-3 flex items-center gap-2">
                        <select
                            value={selectedFaculty}
                            onChange={(e) => {
                                setSelectedFaculty(e.target.value);
                                applyFilters({ ma_khoa: e.target.value });
                            }}
                            className="w-full py-2 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Tất cả khoa</option>
                            {faculties.map((f) => (
                                <option key={f.ma_khoa} value={f.ma_khoa}>
                                    {f.ten_khoa}
                                </option>
                            ))}
                        </select>
                        {(searchTerm || selectedStatus || selectedFaculty) && (
                            <button
                                onClick={handleResetFilters}
                                title="Đặt lại"
                                className="p-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            >
                                <RotateCcw className="w-4 h-4" />
                            </button>
                        )}
                    </div>
                </div>

                {/* Bảng dữ liệu */}
                <div className="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                <tr>
                                    <th className="py-3.5 px-4 font-semibold">MSSV</th>
                                    <th className="py-3.5 px-4 font-semibold">Họ và tên</th>
                                    <th className="py-3.5 px-4 font-semibold">Email sinh viên</th>
                                    <th className="py-3.5 px-4 font-semibold">Mã Khoa</th>
                                    <th className="py-3.5 px-4 font-semibold">Trạng thái</th>
                                    <th className="py-3.5 px-4 font-semibold">Thời điểm kích hoạt</th>
                                    <th className="py-3.5 px-4 font-semibold text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {items.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="py-12 text-center text-slate-400">
                                            Không có bản ghi sinh viên thô nào phù hợp.
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((stu) => (
                                        <tr
                                            key={stu.ma_ban_ghi}
                                            className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition"
                                        >
                                            <td className="py-3 px-4 font-mono font-semibold text-slate-900 dark:text-white">
                                                {stu.mssv}
                                            </td>
                                            <td className="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">
                                                {stu.ho_ten}
                                            </td>
                                            <td className="py-3 px-4 text-xs font-mono text-slate-500">
                                                {stu.email_sv || 'N/A'}
                                            </td>
                                            <td className="py-3 px-4 text-xs text-slate-600 dark:text-slate-400">
                                                {stu.ma_khoa || 'Chưa gán'}
                                            </td>
                                            <td className="py-3 px-4">
                                                {stu.da_kich_hoat ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        <CheckCircle2 className="w-3 h-3" />
                                                        Đã kích hoạt
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        Chưa kích hoạt
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 px-4 text-xs text-slate-400 whitespace-nowrap">
                                                {stu.kich_hoat_luc || '—'}
                                            </td>
                                            <td className="py-3 px-4 text-right whitespace-nowrap">
                                                <div className="inline-flex items-center gap-2">
                                                    <button
                                                        onClick={() => handleOpenEdit(stu)}
                                                        className="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg transition"
                                                        title="Sửa"
                                                    >
                                                        <Edit2 className="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => setDeletingStudent(stu)}
                                                        className="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition"
                                                        title="Xóa"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {students && (
                        <div className="p-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                            <div className="text-xs text-slate-500">
                                Hiển thị {students.from || 0} - {students.to || 0} trên tổng số {students.total || 0} bản ghi
                            </div>
                            <Pagination links={students.links} />
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Import */}
            {showImportModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                    <div className="w-full max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div className="p-4 px-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <UploadCloud className="w-5 h-5 text-indigo-600" />
                                Nạp dữ liệu sinh viên thô hàng loạt
                            </h3>
                            <button
                                onClick={() => setShowImportModal(false)}
                                className="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <form onSubmit={handleBatchImport} className="p-6 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Dữ liệu danh sách (mỗi sinh viên 1 dòng, định dạng: MSSV, Họ tên, Email, Mã Khoa)
                                </label>
                                <textarea
                                    rows="8"
                                    value={rawText}
                                    onChange={(e) => setRawText(e.target.value)}
                                    placeholder="DTH215001, Nguyen Van A, dth215001@student.agu.edu.vn, CNTT&#10;DTH215002, Tran Thi B, dth215002@student.agu.edu.vn, SPTOAN"
                                    className="w-full p-3 font-mono text-xs rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 hover:border-slate-400 dark:hover:border-slate-500 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/15 dark:focus:ring-indigo-500/30 transition-all duration-200 shadow-sm"
                                    required
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowImportModal(false)}
                                    className="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                >
                                    Hủy
                                </button>
                                <button
                                    type="submit"
                                    disabled={isImporting}
                                    className="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm"
                                >
                                    {isImporting ? 'Đang nạp...' : 'Tiến hành nạp'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Edit */}
            {editingStudent && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                    <div className="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <div className="p-4 px-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">
                                Cập nhật sinh viên {editingStudent.mssv}
                            </h3>
                            <button
                                onClick={() => setEditingStudent(null)}
                                className="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        <form onSubmit={handleSaveEdit} className="p-6 space-y-4 text-sm">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Họ và tên
                                </label>
                                <input
                                    type="text"
                                    value={editForm.ho_ten}
                                    onChange={(e) => setEditForm({ ...editForm, ho_ten: e.target.value })}
                                    className="w-full py-2.5 px-3.5 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 hover:border-slate-400 dark:hover:border-slate-500 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/15 dark:focus:ring-indigo-500/30 transition-all duration-200 shadow-sm"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Email sinh viên
                                </label>
                                <input
                                    type="email"
                                    value={editForm.email_sv}
                                    onChange={(e) => setEditForm({ ...editForm, email_sv: e.target.value })}
                                    className="w-full py-2.5 px-3.5 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 hover:border-slate-400 dark:hover:border-slate-500 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/15 dark:focus:ring-indigo-500/30 transition-all duration-200 shadow-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Mã Khoa
                                </label>
                                <input
                                    type="text"
                                    value={editForm.ma_khoa}
                                    onChange={(e) => setEditForm({ ...editForm, ma_khoa: e.target.value })}
                                    className="w-full py-2.5 px-3.5 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 hover:border-slate-400 dark:hover:border-slate-500 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/15 dark:focus:ring-indigo-500/30 transition-all duration-200 shadow-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Mật khẩu mặc định mới (để trống nếu không đổi)
                                </label>
                                <input
                                    type="password"
                                    value={editForm.mat_khau_mac_dinh}
                                    onChange={(e) => setEditForm({ ...editForm, mat_khau_mac_dinh: e.target.value })}
                                    className="w-full py-2.5 px-3.5 text-sm rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 hover:border-slate-400 dark:hover:border-slate-500 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/15 dark:focus:ring-indigo-500/30 transition-all duration-200 shadow-sm"
                                    placeholder="Tối thiểu 6 ký tự"
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-3">
                                <button
                                    type="button"
                                    onClick={() => setEditingStudent(null)}
                                    className="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                                >
                                    Hủy
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm"
                                >
                                    Lưu thay đổi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Delete */}
            {deletingStudent && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                    <div className="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
                        <div className="w-12 h-12 rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center mx-auto">
                            <Trash2 className="w-6 h-6" />
                        </div>
                        <div className="text-center">
                            <h3 className="text-base font-bold text-slate-900 dark:text-white">
                                Xác nhận xóa sinh viên thô?
                            </h3>
                            <p className="text-xs text-slate-500 mt-1">
                                Bạn có chắc chắn muốn xóa bản ghi của MSSV <strong>{deletingStudent.mssv}</strong> ({deletingStudent.ho_ten})? Hành động này sẽ được ghi vào nhật ký kiểm toán.
                            </p>
                        </div>
                        <div className="flex justify-center gap-3 pt-2">
                            <button
                                onClick={() => setDeletingStudent(null)}
                                className="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"
                            >
                                Hủy
                            </button>
                            <button
                                onClick={handleConfirmDelete}
                                className="px-4 py-2 text-xs font-semibold rounded-lg bg-rose-600 hover:bg-rose-700 text-white shadow-sm"
                            >
                                Xóa vĩnh viễn
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

