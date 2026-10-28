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
                    className={`px-2.5 py-1 text-xs rounded-md border ${link.active ? 'bg-brand-primary text-white border-brand-primary' : 'bg-white border-border-default text-text-secondary'} ${!link.url ? 'opacity-40 pointer-events-none' : ''}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}

// Hiển thị lỗi validation ngay bên dưới ô nhập liệu tương ứng.
function FieldError({ error }) {
    return error ? <p className="mt-1 text-xs text-semantic-danger">{error}</p> : null;
}

// Nút chuyển đổi giữa vô hiệu hóa và kích hoạt lại một bản ghi.
function StatusButton({ inactive, onClick, label }) {
    return (
        <button type="button" onClick={onClick} className={`inline-flex items-center gap-1 px-2 py-1 text-xs rounded-md border ${inactive ? 'border-semantic-success text-semantic-success' : 'border-semantic-danger text-semantic-danger'}`}>
            {inactive ? <RotateCcw size={13} /> : <Archive size={13} />}
            {label || (inactive ? 'Kích hoạt' : 'Vô hiệu hóa')}
        </button>
    );
}

// Khung giao diện dùng chung cho từng nhóm: Khoa, Chuyên ngành, Môn học.
function Section({ title, icon: Icon, children }) {
    return (
        <section className="bg-white rounded-xl border border-border-default shadow-xs p-5">
            <div className="flex items-center gap-2 mb-4">
                <span className="p-2 rounded-lg bg-blue-50 text-brand-primary"><Icon size={18} /></span>
                <h2 className="font-semibold text-text-primary">{title}</h2>
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
    const inputClass = 'w-full h-11 min-h-11 box-border rounded-md border border-border-default bg-white px-3 py-2 text-sm leading-6 text-text-primary placeholder:text-slate-400 focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20';
    const textareaClass = `${inputClass} h-auto min-h-24 resize-y`;
    const fieldClass = (form, field) => `${inputClass} ${form.errors[field] ? 'border-semantic-danger focus:border-semantic-danger focus:ring-semantic-danger/20' : ''}`;

    return (
        <AdminLayout title="Quản lý danh mục học thuật">
            <Head title="Danh mục học thuật - AGU Hub Admin" />
            <div className="space-y-5">
                <div>
                    <h1 className="text-2xl font-bold text-text-primary">Quản trị danh mục học thuật</h1>
                    <p className="mt-1 text-sm text-text-secondary">Quản lý Khoa, Chuyên ngành và Môn học; các liên kết tài liệu, review và wishlist được giữ nguyên.</p>
                </div>

                <form onSubmit={applyFilters} className="bg-white rounded-xl border border-border-default p-4 flex flex-col md:flex-row gap-3">
                    <div className="relative flex-1">
                        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary" />
                        <input value={search} onChange={(e) => setSearch(e.target.value)} className={`${inputClass} pl-9`} placeholder="Tìm mã học phần, tên môn, mã khoa..." />
                    </div>
                    <select value={facultyFilter} onChange={(e) => { setFacultyFilter(e.target.value); applyFilterValues({ ma_khoa: e.target.value }); }} className={inputClass}>
                        <option value="">Tất cả khoa</option>
                        {facultyOptions.map((faculty) => <option key={faculty.ma_khoa} value={faculty.ma_khoa}>{faculty.ky_hieu_khoa} - {faculty.ten_khoa}</option>)}
                    </select>
                    <select value={status} onChange={(e) => { setStatus(e.target.value); applyFilterValues({ trang_thai: e.target.value }); }} className={inputClass}>
                        <option value="active">Đang hoạt động</option>
                        <option value="inactive">Đã vô hiệu hóa</option>
                        <option value="all">Tất cả trạng thái</option>
                    </select>
                </form>

                <Section title={editing?.type === 'faculty' ? 'Sửa Khoa' : 'Thêm Khoa'} icon={Building2}>
                    <form onSubmit={submit('faculty', facultyForm, 'admin.catalog.faculties.store', 'admin.catalog.faculties.update')} className="grid md:grid-cols-[180px_1fr_auto] gap-3 items-start">
                        <div><input value={facultyForm.data.ky_hieu_khoa} onChange={(e) => facultyForm.setData('ky_hieu_khoa', e.target.value)} className={inputClass} placeholder="Ký hiệu (CNTT)" /><FieldError error={facultyForm.errors.ky_hieu_khoa || errors.ky_hieu_khoa} /></div>
                        <div><input value={facultyForm.data.ten_khoa} onChange={(e) => facultyForm.setData('ten_khoa', e.target.value)} className={inputClass} placeholder="Tên khoa" /><FieldError error={facultyForm.errors.ten_khoa || errors.ten_khoa} /></div>
                        <div className="flex gap-2"><button className="inline-flex items-center gap-1 px-4 py-2 rounded-md bg-brand-primary text-white text-sm"><Plus size={16} />{editing?.type === 'faculty' ? 'Lưu' : 'Thêm'}</button>{editing?.type === 'faculty' && <button type="button" onClick={resetForms} className="p-2 border rounded-md"><X size={16} /></button>}</div>
                    </form>
                    <div className="overflow-x-auto mt-5"><table className="w-full text-sm"><thead><tr className="text-left text-text-secondary border-b"><th className="py-2">Ký hiệu</th><th>Tên khoa</th><th>Chuyên ngành</th><th>Môn học</th><th /></tr></thead><tbody>{faculties.data.map((faculty) => <tr key={faculty.ma_khoa} className="border-b last:border-0"><td className="py-3 font-mono">{faculty.ky_hieu_khoa}</td><td>{faculty.ten_khoa}</td><td>{faculty.chuyen_nganhs_count}</td><td>{faculty.mon_hocs_count}</td><td className="text-right space-x-2"><button onClick={() => startEdit('faculty', faculty)} className="inline-flex p-1 text-brand-primary"><Edit3 size={15} /></button><StatusButton inactive={Boolean(faculty.ngay_xoa_tam)} onClick={() => toggle('admin.catalog.faculties.toggle', faculty.ma_khoa)} /></td></tr>)}</tbody></table><Pager paginator={faculties} /></div>
                </Section>

                <Section title={editing?.type === 'major' ? 'Sửa Chuyên ngành' : 'Thêm Chuyên ngành'} icon={Tags}>
                    <form onSubmit={submit('major', majorForm, 'admin.catalog.majors.store', 'admin.catalog.majors.update')} className="grid md:grid-cols-4 gap-3 items-start">
                        <div>
                            <select value={majorForm.data.ma_khoa} onChange={(e) => { majorForm.setData('ma_khoa', e.target.value); majorForm.clearErrors('ma_khoa'); }} className={fieldClass(majorForm, 'ma_khoa')}><option value="">Chọn khoa</option>{facultyOptions.map((faculty) => <option key={faculty.ma_khoa} value={faculty.ma_khoa}>{faculty.ky_hieu_khoa}</option>)}</select>
                            <FieldError error={majorForm.errors.ma_khoa || errors.ma_khoa} />
                        </div>
                        <div>
                            <input value={majorForm.data.ky_hieu_nganh} onChange={(e) => { majorForm.setData('ky_hieu_nganh', e.target.value); majorForm.clearErrors('ky_hieu_nganh'); }} className={fieldClass(majorForm, 'ky_hieu_nganh')} placeholder="Mã ngành (DXX, ví dụ DMK)" aria-invalid={Boolean(majorForm.errors.ky_hieu_nganh)} aria-describedby="major-code-hint" />
                            <p id="major-code-hint" className="mt-1 text-[11px] text-text-secondary">Bắt buộc dạng DXX, ví dụ DMK</p>
                            <FieldError error={majorForm.errors.ky_hieu_nganh || errors.ky_hieu_nganh} />
                        </div>
                        <div>
                            <input value={majorForm.data.ten_chuyen_nganh} onChange={(e) => { majorForm.setData('ten_chuyen_nganh', e.target.value); majorForm.clearErrors('ten_chuyen_nganh'); }} className={fieldClass(majorForm, 'ten_chuyen_nganh')} placeholder="Tên chuyên ngành" />
                            <FieldError error={majorForm.errors.ten_chuyen_nganh || errors.ten_chuyen_nganh} />
                        </div>
                        <div className="flex gap-2"><button className="inline-flex items-center gap-1 px-4 py-2 rounded-md bg-brand-primary text-white text-sm"><Plus size={16} />{editing?.type === 'major' ? 'Lưu' : 'Thêm'}</button>{editing?.type === 'major' && <button type="button" onClick={resetForms} className="p-2 border rounded-md"><X size={16} /></button>}</div>
                        <div className="md:col-span-3">
                            <textarea value={majorForm.data.mo_ta} onChange={(e) => { majorForm.setData('mo_ta', e.target.value); majorForm.clearErrors('mo_ta'); }} className={`${textareaClass} ${majorForm.errors.mo_ta ? 'border-semantic-danger focus:border-semantic-danger' : ''}`} placeholder="Mô tả chuyên ngành (không bắt buộc)" />
                            <FieldError error={majorForm.errors.mo_ta || errors.mo_ta} />
                        </div>
                        {majorForm.errors.major && <p className="md:col-span-4 text-sm text-semantic-danger">{majorForm.errors.major}</p>}
                    </form>
                    <div className="overflow-x-auto mt-5"><table className="w-full text-sm"><thead><tr className="text-left text-text-secondary border-b"><th className="py-2">Mã</th><th>Tên chuyên ngành</th><th>Khoa</th><th /></tr></thead><tbody>{majors.data.map((major) => <tr key={major.ma_chuyen_nganh} className="border-b last:border-0"><td className="py-3 font-mono">{major.ky_hieu_nganh}</td><td>{major.ten_chuyen_nganh}</td><td>{major.khoa?.ten_khoa}</td><td className="text-right space-x-2"><button onClick={() => startEdit('major', major)} className="inline-flex p-1 text-brand-primary"><Edit3 size={15} /></button><StatusButton inactive={Boolean(major.ngay_xoa_tam)} onClick={() => toggle('admin.catalog.majors.toggle', major.ma_chuyen_nganh)} /></td></tr>)}</tbody></table><Pager paginator={majors} /></div>
                </Section>

                <Section title={editing?.type === 'course' ? 'Sửa Môn học' : 'Thêm Môn học'} icon={BookOpen}>
                    <form onSubmit={submit('course', courseForm, 'admin.catalog.courses.store', 'admin.catalog.courses.update')} className="grid md:grid-cols-5 gap-3 items-start">
                        <select value={courseForm.data.ma_khoa} onChange={(e) => courseForm.setData('ma_khoa', e.target.value)} className={inputClass}><option value="">Chọn khoa</option>{facultyOptions.map((faculty) => <option key={faculty.ma_khoa} value={faculty.ma_khoa}>{faculty.ky_hieu_khoa}</option>)}</select>
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
                            <p id="course-code-hint" className="mt-1 text-[11px] text-text-secondary">Đúng 3 chữ cái + 3 chữ số, ví dụ PKD101</p>
                            <FieldError error={courseForm.errors.ma_hoc_phan || errors.ma_hoc_phan} />
                        </div>
                        <div>
                            <input value={courseForm.data.ten_mon_hoc} onChange={(e) => { courseForm.setData('ten_mon_hoc', e.target.value); courseForm.clearErrors('ten_mon_hoc'); }} className={fieldClass(courseForm, 'ten_mon_hoc')} placeholder="Tên môn học" />
                            <FieldError error={courseForm.errors.ten_mon_hoc || errors.ten_mon_hoc} />
                        </div>
                        <div>
                            <label htmlFor="so_tin_chi" className="mb-1 block text-xs font-semibold text-text-secondary">
                                Số tín chỉ
                            </label>
                            <input
                                id="so_tin_chi"
                                type="number"
                                min="1"
                                max="8"
                                step="1"
                                value={courseForm.data.so_tin_chi}
                                onChange={(e) => { courseForm.setData('so_tin_chi', e.target.value); courseForm.clearErrors('so_tin_chi'); }}
                                className={fieldClass(courseForm, 'so_tin_chi')}
                                aria-describedby="so_tin_chi_hint"
                            />
                            <p id="so_tin_chi_hint" className="mt-1 text-[11px] text-text-secondary">
                                Từ 1 đến 8 tín chỉ
                            </p>
                            <FieldError error={courseForm.errors.so_tin_chi || errors.so_tin_chi} />
                        </div>
                        <div className="flex gap-2"><button className="inline-flex items-center gap-1 px-4 py-2 rounded-md bg-brand-primary text-white text-sm"><Plus size={16} />{editing?.type === 'course' ? 'Lưu' : 'Thêm'}</button>{editing?.type === 'course' && <button type="button" onClick={resetForms} className="p-2 border rounded-md"><X size={16} /></button>}</div>
                        <textarea value={courseForm.data.mo_ta} onChange={(e) => { courseForm.setData('mo_ta', e.target.value); courseForm.clearErrors('mo_ta'); }} className={`${textareaClass} md:col-span-3 ${courseForm.errors.mo_ta ? 'border-semantic-danger focus:border-semantic-danger' : ''}`} placeholder="Mô tả môn học (không bắt buộc)" />
                        <div className="md:col-span-2"><label className="text-xs font-medium text-text-secondary">Gán chuyên ngành</label><select multiple value={courseForm.data.ma_chuyen_nganh} onChange={(e) => { courseForm.setData('ma_chuyen_nganh', [...e.target.selectedOptions].map((option) => option.value)); courseForm.clearErrors('ma_chuyen_nganh'); }} className={`${inputClass} mt-1 h-28 min-h-28 py-2 ${courseForm.errors.ma_chuyen_nganh ? 'border-semantic-danger' : ''}`}>{majorOptions.map((major) => <option className="py-1" key={major.ma_chuyen_nganh} value={major.ma_chuyen_nganh}>{major.ky_hieu_nganh} - {major.ten_chuyen_nganh}</option>)}</select><FieldError error={courseForm.errors.ma_chuyen_nganh || errors.ma_chuyen_nganh} /></div>
                    </form>
                    <p className="mt-2 text-xs text-text-secondary">Mã học phần phải duy nhất. Môn học có tài liệu, review hoặc yêu cầu tài liệu sẽ không thể vô hiệu hóa.</p>
                    {errors.course && <p className="mt-2 text-sm text-semantic-danger">{errors.course}</p>}
                    <div className="overflow-x-auto mt-5"><table className="w-full text-sm"><thead><tr className="text-left text-text-secondary border-b"><th className="py-2">Mã học phần</th><th>Tên môn học</th><th>Khoa</th><th>Tín chỉ</th><th>Chuyên ngành</th><th /></tr></thead><tbody>{courses.data.map((course) => <tr key={course.ma_mon_hoc} className="border-b last:border-0"><td className="py-3 font-mono">{course.ma_hoc_phan}</td><td>{course.ten_mon_hoc}</td><td>{course.khoa?.ky_hieu_khoa}</td><td>{course.so_tin_chi}</td><td>{course.chuyen_nganhs?.map((major) => major.ky_hieu_nganh).join(', ') || '-'}</td><td className="text-right whitespace-nowrap space-x-2"><button onClick={() => startEdit('course', course)} className="inline-flex p-1 text-brand-primary"><Edit3 size={15} /></button><StatusButton inactive={Boolean(course.ngay_xoa_tam)} onClick={() => toggle('admin.catalog.courses.toggle', course.ma_mon_hoc)} /></td></tr>)}</tbody></table><Pager paginator={courses} /></div>
                </Section>
            </div>
        </AdminLayout>
    );
}
