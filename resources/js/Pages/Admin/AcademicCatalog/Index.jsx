import React, { useEffect, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Archive, BookOpen, Building2, Edit3, Plus, RotateCcw, Search, Tags, X } from 'lucide-react';

const emptyFaculty = { ky_hieu_khoa: '', ten_khoa: '' };
const emptyMajor = { ma_khoa: '', ky_hieu_nganh: '', ten_chuyen_nganh: '', mo_ta: '' };
const emptyCourse = { ma_khoa: '', ma_hoc_phan: '', ten_mon_hoc: '', so_tin_chi: 3, mo_ta: '', ma_chuyen_nganh: [] };

// Hiển thị các nút chuyển trang dùng chung cho ba bảng danh mục.
function Pager({ paginator }) {
    if (!paginator?.links?.length || paginator.last_page <= 1) return null;

    return (
        <div className="flex flex-wrap gap-1 mt-4">
            {paginator.links.map((link, index) => (
                <Link
                    key={`${link.label}-${index}`}
                    href={link.url || '#'}
                    className={`px-3 py-1 text-xs font-medium rounded-lg border transition-colors ${link.active ? 'bg-[#005baa] text-white border-[#005baa]' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'} ${!link.url ? 'opacity-40 pointer-events-none' : ''}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}

// Hiển thị lỗi validation ngay bên dưới ô nhập liệu tương ứng.
function FieldError({ error }) {
    return error ? <p className="mt-1 text-xs text-rose-600 font-medium">{error}</p> : null;
}

// Nút chuyển đổi giữa vô hiệu hóa và kích hoạt lại một bản ghi.
function StatusButton({ inactive, onClick, label }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg border transition-colors ${
                inactive
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'
            }`}
        >
            {inactive ? <RotateCcw size={13} /> : <Archive size={13} />}
            {label || (inactive ? 'Kích hoạt' : 'Vô hiệu hóa')}
        </button>
    );
}

// Khung giao diện dùng chung cho từng nhóm: Khoa, Chuyên ngành, Môn học.
function Section({ title, icon: Icon, children }) {
    return (
        <section className="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 md:p-6 space-y-4">
            <div className="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                <span className="p-2 rounded-lg bg-blue-50 text-[#005baa] border border-blue-100/80">
                    <Icon size={18} />
                </span>
                <h2 className="font-bold text-slate-900 text-base">{title}</h2>
            </div>
            {children}
        </section>
    );
}

export default function AcademicCatalogIndex({ faculties, majors, courses, facultyOptions = [], majorOptions = [], filters = {} }) {
    const { errors = {} } = usePage().props;
    const [editing, setEditing] = useState(null);
    const [search, setSearch] = useState(filters.search || '');
    const [facultyFilter, setFacultyFilter] = useState(filters.ma_khoa || '');
    const [status, setStatus] = useState(filters.trang_thai || 'active');

    const facultyForm = useForm(emptyFaculty);
    const majorForm = useForm(emptyMajor);
    const courseForm = useForm(emptyCourse);

    // Gửi bộ lọc hiện tại lên server và giữ nguyên trạng thái trang.
    const applyFilters = (event) => {
        event?.preventDefault();
        router.get(route('admin.catalog.index'), {
            search,
            ma_khoa: facultyFilter,
            trang_thai: status,
        }, { preserveState: true, replace: true });
    };

    // Gửi bộ lọc ngay sau khi người dùng đổi dropdown Khoa/trạng thái.
    const applyFilterValues = (values) => {
        router.get(route('admin.catalog.index'), {
            search: values.search ?? search,
            ma_khoa: values.ma_khoa ?? facultyFilter,
            trang_thai: values.trang_thai ?? status,
        }, { preserveState: true, replace: true });
    };

    // Debounce ô tìm kiếm để tránh gửi request cho từng ký tự.
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters.search || '')) applyFilters();
        }, 350);
        return () => clearTimeout(timer);
    }, [search]);

    // Đưa dữ liệu bản ghi được chọn vào biểu mẫu chỉnh sửa.
    const startEdit = (type, item) => {
        setEditing({ type, id: item[type === 'faculty' ? 'ma_khoa' : type === 'major' ? 'ma_chuyen_nganh' : 'ma_mon_hoc'] });
        if (type === 'faculty') facultyForm.setData({ ky_hieu_khoa: item.ky_hieu_khoa, ten_khoa: item.ten_khoa });
        if (type === 'major') majorForm.setData({ ma_khoa: item.ma_khoa, ky_hieu_nganh: item.ky_hieu_nganh, ten_chuyen_nganh: item.ten_chuyen_nganh, mo_ta: item.mo_ta || '' });
        if (type === 'course') courseForm.setData({ ma_khoa: item.ma_khoa, ma_hoc_phan: item.ma_hoc_phan, ten_mon_hoc: item.ten_mon_hoc, so_tin_chi: item.so_tin_chi, mo_ta: item.mo_ta || '', ma_chuyen_nganh: item.chuyen_nganhs?.map((major) => major.ma_chuyen_nganh) || [] });
    };

    // Xóa dữ liệu biểu mẫu và thoát chế độ chỉnh sửa.
    const resetForms = () => {
        setEditing(null);
        facultyForm.reset(); majorForm.reset(); courseForm.reset();
        facultyForm.clearErrors(); majorForm.clearErrors(); courseForm.clearErrors();
    };

    // Tạo hoặc cập nhật tùy theo biểu mẫu đang ở chế độ thêm hay sửa.
    const submit = (type, form, storeRoute, updateRoute) => (event) => {
        event.preventDefault();
        const routeName = editing?.type === type ? updateRoute : storeRoute;
        const id = editing?.type === type ? editing.id : undefined;
        form[editing?.type === type ? 'put' : 'post'](id ? route(routeName, id) : route(routeName), {
            preserveScroll: true,
            onSuccess: resetForms,
        });
    };

    // Gọi API đổi trạng thái mà không làm mất vị trí cuộn hiện tại.
    const toggle = (routeName, id) => router.put(route(routeName, id), {}, { preserveScroll: true });

    // Kích thước thống nhất giúp nội dung và con trỏ luôn dễ nhìn khi nhập.
    const inputClass = 'w-full h-10 min-h-10 box-border rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm leading-6 text-slate-900 placeholder:text-slate-400 focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-all';
    const textareaClass = 'w-full h-auto min-h-24 resize-y box-border rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#005baa] focus:ring-2 focus:ring-[#005baa]/20 transition-all';
    const fieldClass = (form, field) => `${inputClass} ${form.errors[field] ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20' : ''}`;

    return (
        <AdminLayout title="Quản lý danh mục học thuật">
            <Head title="Danh mục học thuật - AGU Hub Admin" />
            <div className="space-y-6">
                {/* Header chuẩn */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 md:p-6 rounded-xl border border-slate-200/90 shadow-xs">
                    <div className="flex items-center gap-3">
                        <span className="p-2.5 bg-blue-50 text-[#005baa] rounded-lg border border-blue-100/80 flex items-center justify-center shrink-0">
                            <Building2 className="w-5 h-5" />
                        </span>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-slate-900">
                                Quản trị danh mục học thuật
                            </h1>
                            <p className="text-xs text-slate-500 mt-1">
                                Quản lý Khoa, Chuyên ngành và Môn học; các liên kết tài liệu, review và wishlist được bảo toàn.
                            </p>
                        </div>
                    </div>

                    <div className="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200 self-start sm:self-auto shrink-0">
                        {faculties?.total || faculties?.data?.length || 0} Khoa • {majors?.total || majors?.data?.length || 0} Ngành • {courses?.total || courses?.data?.length || 0} Môn
                    </div>
                </div>

                {/* Thanh tìm kiếm và bộ lọc */}
                <form onSubmit={applyFilters} className="bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 flex flex-col md:flex-row gap-3">
                    <div className="relative flex-1">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input value={search} onChange={(e) => setSearch(e.target.value)} className={`${inputClass} pl-9`} placeholder="Tìm mã học phần, tên môn, mã khoa..." />
                    </div>
                    <select value={facultyFilter} onChange={(e) => { setFacultyFilter(e.target.value); applyFilterValues({ ma_khoa: e.target.value }); }} className={`${inputClass} md:w-56`}>
                        <option value="">Tất cả khoa</option>
                        {facultyOptions.map((faculty) => <option key={faculty.ma_khoa} value={faculty.ma_khoa}>{faculty.ky_hieu_khoa} - {faculty.ten_khoa}</option>)}
                    </select>
                    <select value={status} onChange={(e) => { setStatus(e.target.value); applyFilterValues({ trang_thai: e.target.value }); }} className={`${inputClass} md:w-44`}>
                        <option value="active">Đang hoạt động</option>
                        <option value="inactive">Đã vô hiệu hóa</option>
                        <option value="all">Tất cả trạng thái</option>
                    </select>
                </form>

                {/* Section Khoa */}
                <Section title={editing?.type === 'faculty' ? 'Sửa Khoa' : 'Thêm Khoa mới'} icon={Building2}>
                    <form onSubmit={submit('faculty', facultyForm, 'admin.catalog.faculties.store', 'admin.catalog.faculties.update')} className="grid md:grid-cols-[180px_1fr_auto] gap-3 items-start">
                        <div>
                            <input value={facultyForm.data.ky_hieu_khoa} onChange={(e) => facultyForm.setData('ky_hieu_khoa', e.target.value)} className={fieldClass(facultyForm, 'ky_hieu_khoa')} placeholder="Ký hiệu (CNTT)" />
                            <FieldError error={facultyForm.errors.ky_hieu_khoa || errors.ky_hieu_khoa} />
                        </div>
                        <div>
                            <input value={facultyForm.data.ten_khoa} onChange={(e) => facultyForm.setData('ten_khoa', e.target.value)} className={fieldClass(facultyForm, 'ten_khoa')} placeholder="Tên khoa" />
                            <FieldError error={facultyForm.errors.ten_khoa || errors.ten_khoa} />
                        </div>
                        <div className="flex gap-2">
                            <button className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#005baa] hover:bg-[#004885] text-white font-semibold text-xs md:text-sm shadow-xs transition-colors">
                                <Plus size={16} />
                                {editing?.type === 'faculty' ? 'Lưu' : 'Thêm'}
                            </button>
                            {editing?.type === 'faculty' && (
                                <button type="button" onClick={resetForms} className="p-2 border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-50 transition-colors">
                                    <X size={16} />
                                </button>
                            )}
                        </div>
                    </form>
                    <div className="overflow-x-auto mt-4 rounded-lg border border-slate-200/90">
                        <table className="w-full text-left text-sm text-slate-700">
                            <thead className="bg-slate-50/80 border-b border-slate-200/90 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                <tr>
                                    <th className="py-3 px-4">Ký hiệu</th>
                                    <th className="py-3 px-4">Tên khoa</th>
                                    <th className="py-3 px-4">Chuyên ngành</th>
                                    <th className="py-3 px-4">Môn học</th>
                                    <th className="py-3 px-4 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {faculties.data.map((faculty) => (
                                    <tr key={faculty.ma_khoa} className="hover:bg-slate-50/60 transition-colors">
                                        <td className="py-3 px-4 font-mono font-semibold text-[#005baa]">{faculty.ky_hieu_khoa}</td>
                                        <td className="py-3 px-4 font-medium text-slate-800">{faculty.ten_khoa}</td>
                                        <td className="py-3 px-4 text-slate-600">{faculty.chuyen_nganhs_count}</td>
                                        <td className="py-3 px-4 text-slate-600">{faculty.mon_hocs_count}</td>
                                        <td className="py-3 px-4 text-right whitespace-nowrap space-x-2">
                                            <button
                                                onClick={() => startEdit('faculty', faculty)}
                                                className="p-1.5 text-slate-500 hover:text-[#005baa] hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition-colors"
                                                title="Sửa khoa"
                                            >
                                                <Edit3 size={15} />
                                            </button>
                                            <StatusButton inactive={Boolean(faculty.ngay_xoa_tam)} onClick={() => toggle('admin.catalog.faculties.toggle', faculty.ma_khoa)} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pager paginator={faculties} />
                </Section>

                {/* Section Chuyên ngành */}
                <Section title={editing?.type === 'major' ? 'Sửa Chuyên ngành' : 'Thêm Chuyên ngành mới'} icon={Tags}>
                    <form onSubmit={submit('major', majorForm, 'admin.catalog.majors.store', 'admin.catalog.majors.update')} className="grid md:grid-cols-4 gap-3 items-start">
                        <div>
                            <select value={majorForm.data.ma_khoa} onChange={(e) => { majorForm.setData('ma_khoa', e.target.value); majorForm.clearErrors('ma_khoa'); }} className={fieldClass(majorForm, 'ma_khoa')}>
                                <option value="">Chọn khoa</option>
                                {facultyOptions.map((faculty) => <option key={faculty.ma_khoa} value={faculty.ma_khoa}>{faculty.ky_hieu_khoa}</option>)}
                            </select>
                            <FieldError error={majorForm.errors.ma_khoa || errors.ma_khoa} />
                        </div>
                        <div>
                            <input value={majorForm.data.ky_hieu_nganh} onChange={(e) => { majorForm.setData('ky_hieu_nganh', e.target.value); majorForm.clearErrors('ky_hieu_nganh'); }} className={fieldClass(majorForm, 'ky_hieu_nganh')} placeholder="Mã ngành (DXX, ví dụ DMK)" aria-invalid={Boolean(majorForm.errors.ky_hieu_nganh)} aria-describedby="major-code-hint" />
                            <p id="major-code-hint" className="mt-1 text-[11px] text-slate-500">Bắt buộc dạng DXX, ví dụ DMK</p>
                            <FieldError error={majorForm.errors.ky_hieu_nganh || errors.ky_hieu_nganh} />
                        </div>
                        <div>
                            <input value={majorForm.data.ten_chuyen_nganh} onChange={(e) => { majorForm.setData('ten_chuyen_nganh', e.target.value); majorForm.clearErrors('ten_chuyen_nganh'); }} className={fieldClass(majorForm, 'ten_chuyen_nganh')} placeholder="Tên chuyên ngành" />
                            <FieldError error={majorForm.errors.ten_chuyen_nganh || errors.ten_chuyen_nganh} />
                        </div>
                        <div className="flex gap-2">
                            <button className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#005baa] hover:bg-[#004885] text-white font-semibold text-xs md:text-sm shadow-xs transition-colors">
                                <Plus size={16} />
                                {editing?.type === 'major' ? 'Lưu' : 'Thêm'}
                            </button>
                            {editing?.type === 'major' && (
                                <button type="button" onClick={resetForms} className="p-2 border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-50 transition-colors">
                                    <X size={16} />
                                </button>
                            )}
                        </div>
                        <div className="md:col-span-3">
                            <textarea value={majorForm.data.mo_ta} onChange={(e) => { majorForm.setData('mo_ta', e.target.value); majorForm.clearErrors('mo_ta'); }} className={`${textareaClass} ${majorForm.errors.mo_ta ? 'border-rose-400' : ''}`} placeholder="Mô tả chuyên ngành (không bắt buộc)" />
                            <FieldError error={majorForm.errors.mo_ta || errors.mo_ta} />
                        </div>
                        {majorForm.errors.major && <p className="md:col-span-4 text-sm text-rose-600 font-medium">{majorForm.errors.major}</p>}
                    </form>
                    <div className="overflow-x-auto mt-4 rounded-lg border border-slate-200/90">
                        <table className="w-full text-left text-sm text-slate-700">
                            <thead className="bg-slate-50/80 border-b border-slate-200/90 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                <tr>
                                    <th className="py-3 px-4">Mã</th>
                                    <th className="py-3 px-4">Tên chuyên ngành</th>
                                    <th className="py-3 px-4">Khoa</th>
                                    <th className="py-3 px-4 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {majors.data.map((major) => (
                                    <tr key={major.ma_chuyen_nganh} className="hover:bg-slate-50/60 transition-colors">
                                        <td className="py-3 px-4 font-mono font-semibold text-[#005baa]">{major.ky_hieu_nganh}</td>
                                        <td className="py-3 px-4 font-medium text-slate-800">{major.ten_chuyen_nganh}</td>
                                        <td className="py-3 px-4 text-slate-600">{major.khoa?.ten_khoa}</td>
                                        <td className="py-3 px-4 text-right whitespace-nowrap space-x-2">
                                            <button
                                                onClick={() => startEdit('major', major)}
                                                className="p-1.5 text-slate-500 hover:text-[#005baa] hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition-colors"
                                                title="Sửa chuyên ngành"
                                            >
                                                <Edit3 size={15} />
                                            </button>
                                            <StatusButton inactive={Boolean(major.ngay_xoa_tam)} onClick={() => toggle('admin.catalog.majors.toggle', major.ma_chuyen_nganh)} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pager paginator={majors} />
                </Section>

                {/* Section Môn học */}
                <Section title={editing?.type === 'course' ? 'Sửa Môn học' : 'Thêm Môn học mới'} icon={BookOpen}>
                    <form onSubmit={submit('course', courseForm, 'admin.catalog.courses.store', 'admin.catalog.courses.update')} className="grid md:grid-cols-5 gap-3 items-start">
                        <select value={courseForm.data.ma_khoa} onChange={(e) => courseForm.setData('ma_khoa', e.target.value)} className={inputClass}>
                            <option value="">Chọn khoa</option>
                            {facultyOptions.map((faculty) => <option key={faculty.ma_khoa} value={faculty.ma_khoa}>{faculty.ky_hieu_khoa}</option>)}
                        </select>
                        <div>
                            <input
                                value={courseForm.data.ma_hoc_phan}
                                onChange={(e) => { courseForm.setData('ma_hoc_phan', e.target.value.toUpperCase()); courseForm.clearErrors('ma_hoc_phan'); }}
                                onBlur={() => {
                                    if (courseForm.data.ma_hoc_phan && !/^[A-Z]{3}[0-9]{3}$/.test(courseForm.data.ma_hoc_phan)) {
                                        courseForm.setError('ma_hoc_phan', 'Mã học phần phải gồm đúng 3 chữ cái và 3 chữ số, ví dụ PKD101.');
                                    }
                                }}
                                className={`${fieldClass(courseForm, 'ma_hoc_phan')} font-mono`}
                                placeholder="Mã học phần (PKD101)"
                                maxLength="6"
                                pattern="[A-Za-z]{3}[0-9]{3}"
                                inputMode="text"
                                required
                                aria-invalid={Boolean(courseForm.errors.ma_hoc_phan)}
                                aria-describedby="course-code-hint"
                            />
                            <p id="course-code-hint" className="mt-1 text-[11px] text-slate-500">Đúng 3 chữ cái + 3 chữ số, ví dụ PKD101</p>
                            <FieldError error={courseForm.errors.ma_hoc_phan || errors.ma_hoc_phan} />
                        </div>
                        <div>
                            <input value={courseForm.data.ten_mon_hoc} onChange={(e) => { courseForm.setData('ten_mon_hoc', e.target.value); courseForm.clearErrors('ten_mon_hoc'); }} className={fieldClass(courseForm, 'ten_mon_hoc')} placeholder="Tên môn học" />
                            <FieldError error={courseForm.errors.ten_mon_hoc || errors.ten_mon_hoc} />
                        </div>
                        <div>
                            <input
                                id="so_tin_chi"
                                type="number"
                                min="1"
                                max="8"
                                step="1"
                                value={courseForm.data.so_tin_chi}
                                onChange={(e) => { courseForm.setData('so_tin_chi', e.target.value); courseForm.clearErrors('so_tin_chi'); }}
                                className={fieldClass(courseForm, 'so_tin_chi')}
                                placeholder="Số tín chỉ"
                                aria-describedby="so_tin_chi_hint"
                            />
                            <p id="so_tin_chi_hint" className="mt-1 text-[11px] text-slate-500">
                                Từ 1 đến 8 tín chỉ
                            </p>
                            <FieldError error={courseForm.errors.so_tin_chi || errors.so_tin_chi} />
                        </div>
                        <div className="flex gap-2">
                            <button className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#005baa] hover:bg-[#004885] text-white font-semibold text-xs md:text-sm shadow-xs transition-colors">
                                <Plus size={16} />
                                {editing?.type === 'course' ? 'Lưu' : 'Thêm'}
                            </button>
                            {editing?.type === 'course' && (
                                <button type="button" onClick={resetForms} className="p-2 border border-slate-200 rounded-lg text-slate-500 hover:bg-slate-50 transition-colors">
                                    <X size={16} />
                                </button>
                            )}
                        </div>
                        <textarea value={courseForm.data.mo_ta} onChange={(e) => { courseForm.setData('mo_ta', e.target.value); courseForm.clearErrors('mo_ta'); }} className={`${textareaClass} md:col-span-3 ${courseForm.errors.mo_ta ? 'border-rose-400' : ''}`} placeholder="Mô tả môn học (không bắt buộc)" />
                        <div className="md:col-span-2">
                            <label className="text-xs font-semibold text-slate-700 block mb-1">Gán chuyên ngành</label>
                            <select multiple value={courseForm.data.ma_chuyen_nganh} onChange={(e) => { courseForm.setData('ma_chuyen_nganh', [...e.target.selectedOptions].map((option) => option.value)); courseForm.clearErrors('ma_chuyen_nganh'); }} className={`${inputClass} h-28 min-h-28 py-2 ${courseForm.errors.ma_chuyen_nganh ? 'border-rose-400' : ''}`}>
                                {majorOptions.map((major) => <option className="py-1" key={major.ma_chuyen_nganh} value={major.ma_chuyen_nganh}>{major.ky_hieu_nganh} - {major.ten_chuyen_nganh}</option>)}
                            </select>
                            <FieldError error={courseForm.errors.ma_chuyen_nganh || errors.ma_chuyen_nganh} />
                        </div>
                    </form>
                    <p className="mt-2 text-xs text-slate-500">Mã học phần phải duy nhất. Môn học có tài liệu, review hoặc yêu cầu tài liệu sẽ không thể vô hiệu hóa.</p>
                    {errors.course && <p className="mt-2 text-sm text-rose-600 font-medium">{errors.course}</p>}
                    <div className="overflow-x-auto mt-4 rounded-lg border border-slate-200/90">
                        <table className="w-full text-left text-sm text-slate-700">
                            <thead className="bg-slate-50/80 border-b border-slate-200/90 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                <tr>
                                    <th className="py-3 px-4">Mã học phần</th>
                                    <th className="py-3 px-4">Tên môn học</th>
                                    <th className="py-3 px-4">Khoa</th>
                                    <th className="py-3 px-4">Tín chỉ</th>
                                    <th className="py-3 px-4">Chuyên ngành</th>
                                    <th className="py-3 px-4 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {courses.data.map((course) => (
                                    <tr key={course.ma_mon_hoc} className="hover:bg-slate-50/60 transition-colors">
                                        <td className="py-3 px-4 font-mono font-semibold text-[#005baa]">{course.ma_hoc_phan}</td>
                                        <td className="py-3 px-4 font-medium text-slate-800">{course.ten_mon_hoc}</td>
                                        <td className="py-3 px-4 text-slate-600">{course.khoa?.ky_hieu_khoa}</td>
                                        <td className="py-3 px-4 text-slate-600">{course.so_tin_chi}</td>
                                        <td className="py-3 px-4 text-slate-600">{course.chuyen_nganhs?.map((major) => major.ky_hieu_nganh).join(', ') || '-'}</td>
                                        <td className="py-3 px-4 text-right whitespace-nowrap space-x-2">
                                            <button
                                                onClick={() => startEdit('course', course)}
                                                className="p-1.5 text-slate-500 hover:text-[#005baa] hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition-colors"
                                                title="Sửa môn học"
                                            >
                                                <Edit3 size={15} />
                                            </button>
                                            <StatusButton inactive={Boolean(course.ngay_xoa_tam)} onClick={() => toggle('admin.catalog.courses.toggle', course.ma_mon_hoc)} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pager paginator={courses} />
                </Section>
            </div>
        </AdminLayout>
    );
}
