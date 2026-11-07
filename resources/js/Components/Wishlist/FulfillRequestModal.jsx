import React, { useState, useEffect, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import {
    X,
    UploadCloud,
    FileText,
    Send,
    Search,
    CheckCircle2,
    AlertCircle,
    Loader2,
    PlusCircle,
    ExternalLink,
    Upload,
    Check,
    FileCheck,
    ShieldAlert,
    AlertTriangle
} from 'lucide-react';

export default function FulfillRequestModal({ isOpen, onClose, requestId, course = null }) {
    // Mode: 'SELECT' (Method A) | 'UPLOAD' (Method B)
    const [mode, setMode] = useState('SELECT');

    // Form for Method A: Chọn tài liệu có sẵn
    const {
        data: selectData,
        setData: setSelectData,
        post: postSelect,
        processing: processingSelect,
        errors: selectErrors,
        reset: resetSelect,
        clearErrors: clearSelectErrors,
    } = useForm({
        ma_tai_lieu: '',
        ghi_chu_dong_gop: '',
    });

    // Form for Method B: Upload file mới từ máy
    const {
        data: uploadData,
        setData: setUploadData,
        post: postUpload,
        processing: processingUpload,
        errors: uploadErrors,
        reset: resetUpload,
        clearErrors: clearUploadErrors,
    } = useForm({
        file: null,
        tieu_de: '',
        phan_loai: 'DE_THI',
        ghi_chu_dong_gop: '',
    });

    const [myDocuments, setMyDocuments] = useState([]);
    const [isLoadingDocs, setIsLoadingDocs] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [isManualIdMode, setIsManualIdMode] = useState(false);

    // Upload & Hash states for Method B
    const fileInputRef = useRef(null);
    const [dragActive, setDragActive] = useState(false);
    const [isHashing, setIsHashing] = useState(false);
    const [fileHash, setFileHash] = useState(null);
    const [duplicateResult, setDuplicateResult] = useState(null);
    const [isCheckingDuplicate, setIsCheckingDuplicate] = useState(false);

    // Khi mở modal: nạp tài liệu cá nhân và reset state
    useEffect(() => {
        if (isOpen) {
            fetchMyDocuments();
        } else {
            resetSelect();
            resetUpload();
            clearSelectErrors();
            clearUploadErrors();
            setSearchQuery('');
            setIsManualIdMode(false);
            setFileHash(null);
            setDuplicateResult(null);
            setMode('SELECT');
        }
    }, [isOpen]);

    const fetchMyDocuments = async () => {
        setIsLoadingDocs(true);
        try {
            const response = await axios.get(route('documents.my-documents'));
            const docs = response.data || [];
            setMyDocuments(docs);
            // Nếu người dùng chưa có tài liệu nào, chuyển thẳng sang mode Tải file từ máy
            if (docs.length === 0) {
                setMode('UPLOAD');
            }
        } catch (error) {
            console.error('Lỗi khi tải danh sách tài liệu cá nhân:', error);
            setMode('UPLOAD');
        } finally {
            setIsLoadingDocs(false);
        }
    };

    if (!isOpen) return null;

    // Tính toán mã băm SHA-256 nhị phân phía client qua Web Crypto API
    const computeSha256 = async (file) => {
        setIsHashing(true);
        setDuplicateResult(null);

        try {
            const arrayBuffer = await file.arrayBuffer();
            const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
            setFileHash(hashHex);

            // Kiểm tra trùng lặp tức thì nếu có thông tin môn học
            if (course?.ma_mon_hoc) {
                checkDuplicate(course.ma_mon_hoc, hashHex);
            }
            return hashHex;
        } catch (err) {
            console.error('Lỗi khi tính mã băm SHA-256:', err);
            return null;
        } finally {
            setIsHashing(false);
        }
    };

    // Kiểm tra trước trùng lặp file qua API
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
                    message: err.response.data.message || 'Tài liệu có nội dung giống 100% đã tồn tại trong môn học.',
                });
            } else {
                console.error('Lỗi kiểm tra trùng lặp:', err);
            }
        } finally {
            setIsCheckingDuplicate(false);
        }
    };

    // Xử lý chọn file từ máy
    const handleFileChange = (file) => {
        if (!file) return;

        // Tự động điền tiêu đề từ tên file nếu chưa có
        const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
        setUploadData((prev) => ({
            ...prev,
            file: file,
            tieu_de: prev.tieu_de || nameWithoutExt,
        }));

        computeSha256(file);
    };

    // Kéo thả file
    const handleDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === 'dragenter' || e.type === 'dragover') {
            setDragActive(true);
        } else if (e.type === 'dragleave') {
            setDragActive(false);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileChange(e.dataTransfer.files[0]);
        }
    };

    // Lọc danh sách tài liệu cá nhân
    const filteredDocs = myDocuments.filter((doc) => {
        const query = searchQuery.toLowerCase();
        return (
            (doc.tieu_de || '').toLowerCase().includes(query) ||
            (doc.mon_hoc?.ten_mon_hoc || '').toLowerCase().includes(query) ||
            (doc.mon_hoc?.ma_hoc_phan || '').toLowerCase().includes(query)
        );
    });

    // Submit Method A
    const handleSubmitSelect = (e) => {
        e.preventDefault();
        postSelect(route('document-requests.fulfill', requestId), {
            preserveScroll: true,
            onSuccess: () => {
                resetSelect();
                onClose();
            },
        });
    };

    // Submit Method B
    const handleSubmitUpload = (e) => {
        e.preventDefault();
        postUpload(route('document-requests.fulfill', requestId), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                resetUpload();
                onClose();
            },
        });
    };

    const formatFileSize = (bytes) => {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div className="relative bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-[#00a896]">
                            <UploadCloud className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-800">
                                Đóng Góp Tài Liệu Săn Thưởng
                            </h3>
                            <p className="text-[11px] text-slate-500">
                                {course ? `Học phần: ${course.ma_hoc_phan} — ${course.ten_mon_hoc}` : `Yêu cầu #${requestId}`}
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                        aria-label="Đóng modal"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Tabs Chuyển đổi phương thức: Chọn có sẵn vs Tải từ máy */}
                <div className="mt-4 flex rounded-xl bg-slate-100 p-1 border border-slate-200/80">
                    <button
                        type="button"
                        onClick={() => setMode('SELECT')}
                        className={`flex-1 flex items-center justify-center gap-2 py-2 text-xs font-bold rounded-lg transition-all ${
                            mode === 'SELECT'
                                ? 'bg-white text-slate-800 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <FileText className="w-4 h-4 text-[#005baa]" />
                        <span>Kho tài liệu cá nhân ({myDocuments.length})</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setMode('UPLOAD')}
                        className={`flex-1 flex items-center justify-center gap-2 py-2 text-xs font-bold rounded-lg transition-all ${
                            mode === 'UPLOAD'
                                ? 'bg-white text-slate-800 shadow-xs'
                                : 'text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <Upload className="w-4 h-4 text-[#00a896]" />
                        <span>Tải file mới từ máy</span>
                    </button>
                </div>

                {/* ========================================================================= */}
                {/* PHƯƠNG THỨC A: CHỌN TỪ KHO CÁ NHÂN */}
                {/* ========================================================================= */}
                {mode === 'SELECT' && (
                    <form onSubmit={handleSubmitSelect} className="mt-4 space-y-4">
                        <div>
                            <div className="flex items-center justify-between mb-1.5">
                                <label className="block text-xs font-semibold text-slate-700 uppercase">
                                    Chọn tài liệu của bạn <span className="text-rose-500">*</span>
                                </label>
                                {myDocuments.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={() => setIsManualIdMode(!isManualIdMode)}
                                        className="text-[11px] text-[#005baa] hover:underline font-medium"
                                    >
                                        {isManualIdMode ? 'Chọn từ danh sách' : 'Nhập mã ID'}
                                    </button>
                                )}
                            </div>

                            {isManualIdMode ? (
                                <div className="space-y-1">
                                    <input
                                        type="number"
                                        placeholder="Nhập mã ID tài liệu của bạn..."
                                        value={selectData.ma_tai_lieu}
                                        onChange={(e) => setSelectData('ma_tai_lieu', e.target.value)}
                                        className="w-full text-sm rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-800 focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20"
                                    />
                                    <p className="text-[11px] text-slate-400">
                                        Chỉ chấp nhận ID tài liệu do chính bạn đăng tải và đã được duyệt.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {isLoadingDocs ? (
                                        <div className="p-6 border border-slate-200 rounded-xl bg-slate-50 flex items-center justify-center gap-2 text-xs text-slate-500">
                                            <Loader2 size={16} className="animate-spin text-[#00a896]" />
                                            <span>Đang nạp danh sách tài liệu cá nhân...</span>
                                        </div>
                                    ) : myDocuments.length === 0 ? (
                                        <div className="p-4 border border-amber-200 bg-amber-50/70 rounded-xl space-y-2 text-xs text-slate-700">
                                            <div className="flex items-center gap-2 text-amber-700 font-semibold">
                                                <AlertCircle size={16} />
                                                <span>Bạn chưa có tài liệu nào trong kho cá nhân</span>
                                            </div>
                                            <p className="text-slate-600">
                                                Bạn có thể chuyển sang tab <strong>"Tải file mới từ máy"</strong> để upload trực tiếp.
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() => setMode('UPLOAD')}
                                                className="inline-flex items-center gap-1.5 font-bold text-[#00a896] hover:underline"
                                            >
                                                <span>Tải file mới ngay &rarr;</span>
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <div className="relative">
                                                <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                                <input
                                                    type="text"
                                                    value={searchQuery}
                                                    onChange={(e) => setSearchQuery(e.target.value)}
                                                    placeholder="Tìm kiếm theo tên tài liệu hoặc mã học phần..."
                                                    className="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:border-[#00a896]"
                                                />
                                            </div>

                                            <div className="max-h-48 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 bg-white">
                                                {filteredDocs.length > 0 ? (
                                                    filteredDocs.map((doc) => {
                                                        const isSelected = String(selectData.ma_tai_lieu) === String(doc.ma_tai_lieu);
                                                        const isCourseMatch = course?.ma_mon_hoc && doc.ma_mon_hoc === course.ma_mon_hoc;

                                                        return (
                                                            <div
                                                                key={doc.ma_tai_lieu}
                                                                onClick={() => setSelectData('ma_tai_lieu', doc.ma_tai_lieu)}
                                                                className={`p-3 cursor-pointer transition-colors flex items-start justify-between gap-3 ${
                                                                    isSelected
                                                                        ? 'bg-teal-50/80 border-l-4 border-l-[#00a896]'
                                                                        : 'hover:bg-slate-50'
                                                                }`}
                                                            >
                                                                <div className="min-w-0 flex-1">
                                                                    <div className="flex items-center gap-1.5 mb-1 flex-wrap">
                                                                        <span className="font-mono text-[10px] font-bold bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">
                                                                            ID: {doc.ma_tai_lieu}
                                                                        </span>
                                                                        {doc.mon_hoc && (
                                                                            <span className={`font-mono text-[10px] font-semibold px-1.5 py-0.5 rounded ${
                                                                                isCourseMatch ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-50 text-[#005baa]'
                                                                            }`}>
                                                                                {doc.mon_hoc.ma_hoc_phan}
                                                                            </span>
                                                                        )}
                                                                        <span className="uppercase text-[10px] text-slate-500 font-mono">
                                                                            {doc.dinh_dang_tep || 'PDF'}
                                                                        </span>
                                                                        <span className="text-[10px] text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded font-medium">
                                                                            Đã duyệt
                                                                        </span>
                                                                    </div>
                                                                    <div className="text-xs font-semibold text-slate-800 line-clamp-1">
                                                                        {doc.tieu_de}
                                                                    </div>
                                                                </div>

                                                                {isSelected && (
                                                                    <CheckCircle2 size={18} className="text-[#00a896] shrink-0 mt-1" />
                                                                )}
                                                            </div>
                                                        );
                                                    })
                                                ) : (
                                                    <div className="p-4 text-center text-xs text-slate-400">
                                                        Không tìm thấy tài liệu phù hợp từ khóa
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {selectErrors.ma_tai_lieu && (
                                <p className="text-rose-500 text-xs mt-1">{selectErrors.ma_tai_lieu}</p>
                            )}
                        </div>

                        {/* Ghi chú */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Ghi chú gửi người yêu cầu <span className="text-slate-400 font-normal lowercase">(tùy chọn)</span>
                            </label>
                            <textarea
                                rows={2}
                                placeholder="VD: Mình gửi bạn tài liệu slide và đề thi của thầy năm ngoái..."
                                value={selectData.ghi_chu_dong_gop}
                                onChange={(e) => setSelectData('ghi_chu_dong_gop', e.target.value)}
                                className="w-full text-sm rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20 transition-colors"
                            />
                            {selectErrors.ghi_chu_dong_gop && (
                                <p className="text-rose-500 text-xs mt-1">{selectErrors.ghi_chu_dong_gop}</p>
                            )}
                        </div>

                        <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                onClick={onClose}
                                className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                            >
                                Đóng
                            </button>
                            <button
                                type="submit"
                                disabled={processingSelect || !selectData.ma_tai_lieu}
                                className="px-4 py-2 text-xs font-bold text-white bg-[#00a896] hover:bg-teal-700 rounded-xl flex items-center gap-1.5 disabled:bg-slate-300 transition-colors shadow-xs"
                            >
                                <Send className="w-3.5 h-3.5" />
                                <span>{processingSelect ? 'Đang gửi...' : 'Nộp Tài Liệu'}</span>
                            </button>
                        </div>
                    </form>
                )}

                {/* ========================================================================= */}
                {/* PHƯƠNG THỨC B: TẢI FILE MỚI TỪ MÁY LOCAL */}
                {/* ========================================================================= */}
                {mode === 'UPLOAD' && (
                    <form onSubmit={handleSubmitUpload} className="mt-4 space-y-4">
                        {/* Khu vực Drag & Drop File */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                Tệp tài liệu từ máy tính <span className="text-rose-500">*</span>
                            </label>

                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".pdf,.docx,.pptx,.zip"
                                className="hidden"
                                onChange={(e) => {
                                    if (e.target.files && e.target.files[0]) {
                                        handleFileChange(e.target.files[0]);
                                    }
                                }}
                            />

                            {!uploadData.file ? (
                                <div
                                    onDragEnter={handleDrag}
                                    onDragLeave={handleDrag}
                                    onDragOver={handleDrag}
                                    onDrop={handleDrop}
                                    onClick={() => fileInputRef.current?.click()}
                                    className={`border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all ${
                                        dragActive
                                            ? 'border-[#00a896] bg-teal-50/50'
                                            : 'border-slate-300 hover:border-slate-400 bg-slate-50/50'
                                    }`}
                                >
                                    <div className="w-10 h-10 rounded-full bg-teal-100 text-[#00a896] mx-auto flex items-center justify-center mb-2">
                                        <Upload className="w-5 h-5" />
                                    </div>
                                    <p className="text-xs font-semibold text-slate-700">
                                        Nhấp để chọn tệp hoặc kéo thả file vào đây
                                    </p>
                                    <p className="text-[11px] text-slate-400 mt-1">
                                        Hỗ trợ định dạng PDF, DOCX, PPTX, ZIP (Tối đa 50MB)
                                    </p>
                                </div>
                            ) : (
                                <div className="p-3.5 border border-slate-200 bg-slate-50 rounded-2xl space-y-2">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2.5 min-w-0">
                                            <div className="w-8 h-8 rounded-lg bg-teal-100 text-[#00a896] flex items-center justify-center shrink-0">
                                                <FileCheck className="w-4 h-4" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-xs font-bold text-slate-800 truncate">
                                                    {uploadData.file.name}
                                                </p>
                                                <p className="text-[11px] text-slate-500 font-mono">
                                                    {formatFileSize(uploadData.file.size)}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setUploadData((prev) => ({ ...prev, file: null, tieu_de: '' }));
                                                setFileHash(null);
                                                setDuplicateResult(null);
                                                if (fileInputRef.current) fileInputRef.current.value = '';
                                            }}
                                            className="text-xs text-rose-500 hover:text-rose-700 font-semibold px-2 py-1"
                                        >
                                            Thay đổi
                                        </button>
                                    </div>

                                    {/* Trạng thái tính Hash & Kiểm tra trùng */}
                                    <div className="text-[11px] pt-1">
                                        {isHashing ? (
                                            <span className="flex items-center gap-1.5 text-slate-500">
                                                <Loader2 size={12} className="animate-spin text-[#00a896]" />
                                                <span>Đang tính toán mã băm SHA-256 xác thực tệp...</span>
                                            </span>
                                        ) : isCheckingDuplicate ? (
                                            <span className="flex items-center gap-1.5 text-slate-500">
                                                <Loader2 size={12} className="animate-spin text-[#005baa]" />
                                                <span>Đang kiểm tra trùng lặp trên môn học...</span>
                                            </span>
                                        ) : duplicateResult ? (
                                            duplicateResult.is_duplicate ? (
                                                <div className="flex items-start gap-1.5 text-rose-600 bg-rose-50 border border-rose-200 p-2 rounded-lg">
                                                    <AlertTriangle size={14} className="shrink-0 mt-0.5" />
                                                    <span>{duplicateResult.message}</span>
                                                </div>
                                            ) : (
                                                <div className="flex items-center gap-1.5 text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1.5 rounded-lg">
                                                    <Check size={14} />
                                                    <span>{duplicateResult.message}</span>
                                                </div>
                                            )
                                        ) : null}
                                    </div>
                                </div>
                            )}

                            {uploadErrors.file && (
                                <p className="text-rose-500 text-xs mt-1">{uploadErrors.file}</p>
                            )}
                        </div>

                        {/* Tiêu đề tài liệu */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Tiêu đề tài liệu <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                placeholder="VD: Đề thi cuối kỳ môn Lập trình Căn bản 2024 có đáp án..."
                                value={uploadData.tieu_de}
                                onChange={(e) => setUploadData('tieu_de', e.target.value)}
                                className="w-full text-sm rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20 transition-colors"
                            />
                            {uploadErrors.tieu_de && (
                                <p className="text-rose-500 text-xs mt-1">{uploadErrors.tieu_de}</p>
                            )}
                        </div>

                        {/* Phân loại tài liệu */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Phân loại tài liệu
                            </label>
                            <select
                                value={uploadData.phan_loai}
                                onChange={(e) => setUploadData('phan_loai', e.target.value)}
                                className="w-full text-sm rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-800 focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20"
                            >
                                <option value="DE_THI">Đề thi / Kiểm tra</option>
                                <option value="BAI_GIANG">Slide bài giảng</option>
                                <option value="DO_AN">Đồ án / Báo cáo</option>
                                <option value="BAI_TAP">Bài tập & Lời giải</option>
                                <option value="KHAC">Khác</option>
                            </select>
                        </div>

                        {/* Ghi chú */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                Ghi chú gửi người yêu cầu <span className="text-slate-400 font-normal lowercase">(tùy chọn)</span>
                            </label>
                            <textarea
                                rows={2}
                                placeholder="VD: Mình vừa tải lên đề thi mới nhất từ kỳ vừa rồi..."
                                value={uploadData.ghi_chu_dong_gop}
                                onChange={(e) => setUploadData('ghi_chu_dong_gop', e.target.value)}
                                className="w-full text-sm rounded-xl border border-slate-300 px-3.5 py-2.5 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-[#00a896] focus:ring-2 focus:ring-[#00a896]/20 transition-colors"
                            />
                        </div>

                        {/* Banner thông báo quy trình kiểm duyệt */}
                        <div className="p-3 bg-blue-50/70 border border-blue-200/80 rounded-xl text-[11px] text-blue-900 flex items-start gap-2">
                            <ShieldAlert className="w-4 h-4 text-[#005baa] shrink-0 mt-0.5" />
                            <span>
                                <strong>Lưu ý kiểm duyệt:</strong> Tài liệu mới tải lên sẽ được chuyển tới Ban kiểm duyệt để thẩm định an toàn và chất lượng. Sau khi được duyệt, người yêu cầu có thể nghiệm thu và giải ngân điểm thưởng vào ví của bạn.
                            </span>
                        </div>

                        {/* Actions */}
                        <div className="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                onClick={onClose}
                                className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                            >
                                Đóng
                            </button>
                            <button
                                type="submit"
                                disabled={
                                    processingUpload ||
                                    !uploadData.file ||
                                    !uploadData.tieu_de ||
                                    duplicateResult?.is_duplicate
                                }
                                className="px-4 py-2 text-xs font-bold text-white bg-[#00a896] hover:bg-teal-700 rounded-xl flex items-center gap-1.5 disabled:bg-slate-300 transition-colors shadow-xs"
                            >
                                <Upload className="w-3.5 h-3.5" />
                                <span>{processingUpload ? 'Đang tải lên & gửi...' : 'Tải Lên & Đóng Góp'}</span>
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}
