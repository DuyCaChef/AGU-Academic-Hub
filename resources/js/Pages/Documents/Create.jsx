import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import StudentLayout from '@/Layouts/StudentLayout';
import axios from 'axios';
import {
    Upload,
    FileText,
    CheckCircle2,
    AlertCircle,
    X,
    BookOpen,
    HelpCircle,
    ArrowLeft,
    ShieldAlert,
    FileType,
    Loader2,
} from 'lucide-react';

export default function Create({ faculties = [] }) {
    const [selectedFacultyId, setSelectedFacultyId] = useState('');
    const [availableCourses, setAvailableCourses] = useState([]);
    const [fileHash, setFileHash] = useState('');
    const [isHashing, setIsHashing] = useState(false);
    const [isCheckingDuplicate, setIsCheckingDuplicate] = useState(false);
    const [duplicateResult, setDuplicateResult] = useState(null); // { is_duplicate: boolean, message: string, document?: any }
    const [isDragging, setIsDragging] = useState(false);

    const fileInputRef = useRef(null);

    const { data, setData, post, processing, errors, progress, reset } = useForm({
        ma_mon_hoc: '',
        tieu_de: '',
        phan_loai: 'BAI_GIANG',
        mo_ta: '',
        file: null,
    });

    // When faculty changes, update available courses
    useEffect(() => {
        if (!selectedFacultyId) {
            setAvailableCourses([]);
            setData('ma_mon_hoc', '');
            return;
        }

        const faculty = faculties.find(
            (f) => String(f.ma_khoa) === String(selectedFacultyId)
        );

        if (faculty) {
            const courses = faculty.mon_hocs || faculty.monHocs || [];
            setAvailableCourses(courses);
            if (courses.length > 0) {
                // If current selected course is not in this faculty, clear or set first
                const exists = courses.some((c) => String(c.ma_mon_hoc) === String(data.ma_mon_hoc));
                if (!exists) {
                    setData('ma_mon_hoc', '');
                }
            } else {
                setData('ma_mon_hoc', '');
            }
        }
    }, [selectedFacultyId, faculties]);

    // Compute SHA-256 hash using Web Crypto API
    const computeSha256 = async (file) => {
        setIsHashing(true);
        setDuplicateResult(null);

        try {
            const arrayBuffer = await file.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
            setFileHash(hashHex);
            return hashHex;
        } catch (err) {
            console.error('Error computing SHA-256 hash:', err);
            return null;
        } finally {
            setIsHashing(false);
        }
    };

    // Trigger duplicate check when both file and ma_mon_hoc are present
    const checkDuplicate = async (courseId, hash) => {
        if (!courseId || !hash) return;

        setIsCheckingDuplicate(true);
        try {
            const response = await axios.post(route('documents.check-duplicate'), {
                ma_mon_hoc: courseId,
                ma_bam_file: hash,
            });

            setDuplicateResult({
                is_duplicate: false,
                message: response.data.message || 'Tệp tài liệu hợp lệ, không phát hiện trùng lặp.',
            });
        } catch (err) {
            if (err.response && err.response.status === 409) {
                setDuplicateResult({
                    is_duplicate: true,
                    message: err.response.data.message || 'Tài liệu với nội dung này đã tồn tại trong môn học.',
                    document: err.response.data.document,
                });
            } else {
                console.error('Lỗi khi kiểm tra trùng lặp:', err);
            }
        } finally {
            setIsCheckingDuplicate(false);
        }
    };

    // Handle File Selection
    const handleFileChange = async (file) => {
        if (!file) return;

        // Auto-fill title if empty
        if (!data.tieu_de) {
            const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
            setData('tieu_de', nameWithoutExt);
        }

        setData('file', file);
        const hash = await computeSha256(file);

        if (data.ma_mon_hoc && hash) {
            checkDuplicate(data.ma_mon_hoc, hash);
        }
    };

    // When course changes, if we already have a hash, re-check duplicate
    const handleCourseChange = (e) => {
        const courseId = e.target.value;
        setData('ma_mon_hoc', courseId);

        if (courseId && fileHash) {
            checkDuplicate(courseId, fileHash);
        }
    };

    // Drag & Drop handlers
    const handleDragOver = (e) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleFileChange(e.dataTransfer.files[0]);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (duplicateResult?.is_duplicate) {
            return;
        }

        post(route('documents.store'), {
            forceFormData: true,
            onError: (errs) => {
                console.error('Validation errors:', errs);
            },
        });
    };

    const formatFileSize = (bytes) => {
        if (!bytes) return '0 B';
        const kb = bytes / 1024;
        if (kb >= 1024) {
            return `${(kb / 1024).toFixed(1)} MB`;
        }
        return `${Math.round(kb)} KB`;
    };

    return (
        <StudentLayout title="Đăng tải tài liệu học tập">
            <Head title="Đăng tải tài liệu học tập - AGU Academic Hub" />

            {/* Top Navigation */}
            <div className="flex items-center justify-between">
                <Link
                    href={route('courses.index')}
                    className="inline-flex items-center gap-1.5 text-xs font-semibold text-text-secondary hover:text-brand-primary transition-colors"
                >
                    <ArrowLeft size={14} />
                    <span>Quay lại kho môn học</span>
                </Link>
            </div>

            {/* Header Card */}
            <div className="bg-surface-default border border-border-default rounded-xl p-6 shadow-2xs">
                <div className="flex items-center gap-3">
                    <span className="p-3 bg-brand-primary/10 text-brand-primary rounded-xl">
                        <Upload size={24} />
                    </span>
                    <div>
                        <h1 className="text-xl sm:text-2xl font-bold text-text-primary">
                            Đóng góp tài liệu học tập
                        </h1>
                        <p className="text-xs sm:text-sm text-text-secondary mt-0.5">
                            Chia sẻ bài giảng, đề thi và tài liệu tham khảo chính xác cho cộng đồng sinh viên AGU.
                        </p>
                    </div>
                </div>
            </div>

            {/* Main Form Container */}
            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    {/* LEFT (2 Columns): File Upload & Core Metadata */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Section 1: Dropzone File Upload */}
                        <div className="bg-surface-default border border-border-default rounded-xl p-6 shadow-2xs space-y-4">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text-primary flex items-center gap-2">
                                <FileType size={16} className="text-brand-primary" />
                                <span>1. Chọn tệp tài liệu</span>
                                <span className="text-semantic-danger">*</span>
                            </h2>

                            {/* Dropzone Area */}
                            <div
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                                onDrop={handleDrop}
                                onClick={() => fileInputRef.current?.click()}
                                className={`border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all ${
                                    isDragging
                                        ? 'border-brand-primary bg-brand-primary/5'
                                        : data.file
                                        ? 'border-emerald-500/50 bg-emerald-500/5'
                                        : 'border-border-default hover:border-brand-primary/50 hover:bg-surface-page/50'
                                }`}
                            >
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    className="hidden"
                                    accept=".pdf,.docx,.doc,.xlsx,.xls,.pptx,.ppt,.zip,.rar"
                                    onChange={(e) => {
                                        if (e.target.files && e.target.files[0]) {
                                            handleFileChange(e.target.files[0]);
                                        }
                                    }}
                                />

                                {data.file ? (
                                    <div className="space-y-2">
                                        <div className="w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center mx-auto">
                                            <FileText size={24} />
                                        </div>
                                        <div className="font-semibold text-text-primary text-sm">
                                            {data.file.name}
                                        </div>
                                        <div className="text-xs text-text-secondary">
                                            Dung lượng: {formatFileSize(data.file.size)}
                                        </div>
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setData('file', null);
                                                setFileHash('');
                                                setDuplicateResult(null);
                                            }}
                                            className="inline-flex items-center gap-1 text-xs text-semantic-danger hover:underline mt-2"
                                        >
                                            <X size={13} />
                                            <span>Chọn tệp khác</span>
                                        </button>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        <div className="w-12 h-12 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center mx-auto">
                                            <Upload size={24} />
                                        </div>
                                        <div className="font-semibold text-text-primary text-sm">
                                            Kéo thả tệp vào đây hoặc{' '}
                                            <span className="text-brand-primary underline">nhấp để duyệt</span>
                                        </div>
                                        <p className="text-xs text-text-secondary">
                                            Hỗ trợ PDF, Word (.docx), PowerPoint (.pptx), Excel (.xlsx), Tệp nén (.zip, .rar). Tối đa 50MB.
                                        </p>
                                    </div>
                                )}
                            </div>
                            {errors.file && (
                                <p className="text-xs text-semantic-danger font-medium">{errors.file}</p>
                            )}

                            {/* SHA-256 Pipeline & Duplicate Status */}
                            {isHashing && (
                                <div className="flex items-center gap-2 p-3 bg-surface-page border border-border-default rounded-lg text-xs text-text-secondary">
                                    <Loader2 size={16} className="animate-spin text-brand-primary" />
                                    <span>Đang tính toán mã băm SHA-256 để kiểm định tính duy nhất...</span>
                                </div>
                            )}

                            {isCheckingDuplicate && (
                                <div className="flex items-center gap-2 p-3 bg-surface-page border border-border-default rounded-lg text-xs text-text-secondary">
                                    <Loader2 size={16} className="animate-spin text-brand-primary" />
                                    <span>Đang đối chiếu dữ liệu với kho lưu trữ môn học...</span>
                                </div>
                            )}

                            {duplicateResult && duplicateResult.is_duplicate && (
                                <div className="p-4 bg-semantic-danger/10 border border-semantic-danger/30 rounded-xl space-y-2">
                                    <div className="flex items-start gap-2 text-semantic-danger">
                                        <ShieldAlert size={18} className="shrink-0 mt-0.5" />
                                        <div>
                                            <h4 className="font-bold text-sm">
                                                Cảnh báo: Tài liệu đã tồn tại trong môn học
                                            </h4>
                                            <p className="text-xs mt-0.5 leading-relaxed text-semantic-danger/90">
                                                {duplicateResult.message}
                                            </p>
                                            {duplicateResult.document && (
                                                <div className="mt-2 p-2 bg-white/70 rounded border border-semantic-danger/20 text-xs text-text-primary">
                                                    <span className="font-semibold">Tài liệu đã có: </span>
                                                    {duplicateResult.document.tieu_de} (Lượt tải: {duplicateResult.document.luot_tai})
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {duplicateResult && !duplicateResult.is_duplicate && (
                                <div className="flex items-center gap-2 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-xs text-emerald-700">
                                    <CheckCircle2 size={16} className="shrink-0" />
                                    <span>{duplicateResult.message}</span>
                                </div>
                            )}

                            {/* Upload Progress Bar */}
                            {progress && (
                                <div className="space-y-1">
                                    <div className="flex justify-between text-xs text-text-secondary">
                                        <span>Đang tải lên máy chủ...</span>
                                        <span>{progress.percentage}%</span>
                                    </div>
                                    <div className="w-full bg-border-default rounded-full h-2 overflow-hidden">
                                        <div
                                            className="bg-brand-primary h-2 transition-all duration-300"
                                            style={{ width: `${progress.percentage}%` }}
                                        />
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Section 2: Metadata */}
                        <div className="bg-surface-default border border-border-default rounded-xl p-6 shadow-2xs space-y-4">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text-primary flex items-center gap-2">
                                <BookOpen size={16} className="text-brand-primary" />
                                <span>2. Thông tin chi tiết tài liệu</span>
                            </h2>

                            {/* Title */}
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">
                                    Tiêu đề tài liệu <span className="text-semantic-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.tieu_de}
                                    onChange={(e) => setData('tieu_de', e.target.value)}
                                    placeholder="VD: Giáo trình Cấu trúc dữ liệu và giải thuật - Thầy Tuấn 2024"
                                    maxLength={255}
                                    className="w-full text-sm bg-surface-page border border-border-default rounded-lg p-2.5 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                                />
                                {errors.tieu_de && (
                                    <p className="mt-1 text-xs text-semantic-danger">{errors.tieu_de}</p>
                                )}
                            </div>

                            {/* Category */}
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">
                                    Phân loại tài liệu <span className="text-semantic-danger">*</span>
                                </label>
                                <select
                                    value={data.phan_loai}
                                    onChange={(e) => setData('phan_loai', e.target.value)}
                                    className="w-full text-sm bg-surface-page border border-border-default rounded-lg p-2.5 focus:outline-none focus:border-brand-primary"
                                >
                                    <option value="BAI_GIANG">Bài giảng / Slide bài học</option>
                                    <option value="DE_THI">Đề thi / Đề kiểm tra (Kèm đáp án)</option>
                                    <option value="BAI_TAP">Bài tập / Tiểu luận mẫu</option>
                                    <option value="DO_AN">Đồ án / Khóa luận tốt nghiệp</option>
                                    <option value="KHAC">Tài liệu tham khảo khác</option>
                                </select>
                                {errors.phan_loai && (
                                    <p className="mt-1 text-xs text-semantic-danger">{errors.phan_loai}</p>
                                )}
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">
                                    Mô tả tài liệu <span className="text-text-muted font-normal">(Tùy chọn)</span>
                                </label>
                                <textarea
                                    value={data.mo_ta}
                                    onChange={(e) => setData('mo_ta', e.target.value)}
                                    rows={4}
                                    maxLength={5000}
                                    placeholder="Tóm tắt nội dung chính, chương mục, năm học hoặc lưu ý quan trọng khi ôn tập..."
                                    className="w-full text-sm bg-surface-page border border-border-default rounded-lg p-2.5 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                                />
                                {errors.mo_ta && (
                                    <p className="mt-1 text-xs text-semantic-danger">{errors.mo_ta}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* RIGHT (1 Column): Faculty / Course Select & Guidelines */}
                    <div className="space-y-6">
                        {/* Course & Faculty Picker Card */}
                        <div className="bg-surface-default border border-border-default rounded-xl p-5 shadow-2xs space-y-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-text-secondary pb-2 border-b border-border-default">
                                Đơn vị đào tạo & Môn học
                            </h3>

                            {/* Khoa */}
                            <div>
                                <label className="block text-xs font-medium text-text-secondary mb-1">
                                    Khoa / Bộ môn
                                </label>
                                <select
                                    value={selectedFacultyId}
                                    onChange={(e) => setSelectedFacultyId(e.target.value)}
                                    className="w-full text-sm bg-surface-page border border-border-default rounded-lg p-2.5 focus:outline-none focus:border-brand-primary"
                                >
                                    <option value="">-- Chọn Khoa quản lý --</option>
                                    {faculties.map((f) => (
                                        <option key={f.ma_khoa} value={f.ma_khoa}>
                                            {f.ten_khoa}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Môn học */}
                            <div>
                                <label className="block text-xs font-bold text-text-secondary mb-1">
                                    Môn học áp dụng <span className="text-semantic-danger">*</span>
                                </label>
                                <select
                                    value={data.ma_mon_hoc}
                                    onChange={handleCourseChange}
                                    disabled={!selectedFacultyId || availableCourses.length === 0}
                                    className="w-full text-sm bg-surface-page border border-border-default rounded-lg p-2.5 focus:outline-none focus:border-brand-primary disabled:opacity-50"
                                >
                                    <option value="">-- Chọn Môn học --</option>
                                    {availableCourses.map((c) => (
                                        <option key={c.ma_mon_hoc} value={c.ma_mon_hoc}>
                                            {c.ma_hoc_phan} - {c.ten_mon_hoc}
                                        </option>
                                    ))}
                                </select>
                                {errors.ma_mon_hoc && (
                                    <p className="mt-1 text-xs text-semantic-danger">{errors.ma_mon_hoc}</p>
                                )}
                                {!selectedFacultyId && (
                                    <p className="text-[11px] text-text-muted mt-1">
                                        Vui lòng chọn Khoa trước để tải danh sách môn học.
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Submission Guidelines Card */}
                        <div className="bg-surface-default border border-border-default rounded-xl p-5 shadow-2xs space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-text-secondary pb-2 border-b border-border-default flex items-center gap-1.5">
                                <HelpCircle size={15} className="text-brand-primary" />
                                <span>Quy chuẩn đăng tải AGU</span>
                            </h3>

                            <ul className="text-xs text-text-secondary space-y-2 list-disc list-inside">
                                <li>Không đăng tải đề thi chưa kết thúc đợt thi học phần.</li>
                                <li>Không tải lên tài liệu cá nhân hoặc thông tin riêng tư của giảng viên.</li>
                                <li>Tài liệu đạt từ 10 lượt tải sẽ tự động chuyển thành <span className="font-semibold text-text-primary">Sở hữu cộng đồng</span>.</li>
                                <li>Mỗi tài liệu hợp lệ được kiểm duyệt thành công sẽ đóng góp vào điểm uy tín của bạn.</li>
                            </ul>
                        </div>

                        {/* Submit Button */}
                        <div className="bg-surface-default border border-border-default rounded-xl p-5 shadow-2xs space-y-3">
                            <button
                                type="submit"
                                disabled={
                                    processing ||
                                    isHashing ||
                                    isCheckingDuplicate ||
                                    !data.file ||
                                    !data.ma_mon_hoc ||
                                    !data.tieu_de ||
                                    duplicateResult?.is_duplicate
                                }
                                className="w-full flex items-center justify-center gap-2 bg-brand-primary hover:bg-blue-800 disabled:opacity-50 text-white font-bold text-sm py-3 px-4 rounded-xl shadow-xs transition-all"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 size={18} className="animate-spin" />
                                        <span>Đang đăng tải...</span>
                                    </>
                                ) : (
                                    <>
                                        <Upload size={18} />
                                        <span>Hoàn tất đăng tải tài liệu</span>
                                    </>
                                )}
                            </button>

                            <p className="text-[11px] text-center text-text-muted">
                                Bằng việc nhấn đăng tải, bạn đồng ý với Quy chế sử dụng và Liêm chính học thuật của AGU Academic Hub.
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </StudentLayout>
    );
}

