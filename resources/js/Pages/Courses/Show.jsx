import React, { useState, useEffect, useCallback, Component } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import StudentLayout from "@/Layouts/StudentLayout";
import DocumentCard from "@/Components/domain/DocumentCard";
import Pagination from "@/Components/Common/Pagination";
import CreateReviewModal from "@/Components/Reviews/CreateReviewModal";
import CreateRequestModal from "@/Components/Wishlist/CreateRequestModal";
import FulfillRequestModal from "@/Components/Wishlist/FulfillRequestModal";
import {
    BookOpen,
    ArrowLeft,
    Building2,
    Layers,
    GraduationCap,
    FileText,
    Info,
    Calendar,
    Compass,
    Sparkles,
    AlertCircle,
    Search,
    Filter,
    Upload,
    FolderSearch,
    Loader2,
    X,
    TrendingUp,
    Star,
    MessageSquarePlus,
    ThumbsUp,
    ThumbsDown,
    ShieldCheck,
    User,
    Trash2,
    Flag,
    CheckCircle2,
    BarChart3,
    Clock,
    Coins,
    Award,
    Plus,
    Tag,
    Inbox,
    ExternalLink,
    Send,
    HelpCircle,
    UserCheck,
    CheckCircle,
} from "lucide-react";

// Error Boundary bảo vệ trang tránh lỗi trắng trang khi render runtime
class CourseDetailErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error("CourseShow ErrorBoundary caught error:", error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <StudentLayout title="Đã có lỗi xảy ra">
                    <Head title="Lỗi hiển thị - AGU Academic Hub" />
                    <div className="rounded-2xl border border-red-200 bg-red-50/50 p-8 text-center max-w-xl mx-auto my-12 shadow-xs">
                        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-100 text-red-600 mb-4">
                            <AlertCircle size={32} />
                        </div>
                        <h2 className="text-lg font-bold text-slate-900">
                            Đã xảy ra lỗi khi hiển thị môn học
                        </h2>
                        <p className="mt-2 text-xs text-slate-600 leading-relaxed">
                            Hệ thống gặp sự cố khi xử lý dữ liệu môn học hoặc danh sách đánh giá/tài liệu/yêu cầu. Vui lòng thử tải lại trang hoặc quay về kho môn học.
                        </p>
                        <div className="mt-6 flex items-center justify-center gap-3">
                            <button
                                type="button"
                                onClick={() => window.location.reload()}
                                className="px-4 py-2.5 rounded-xl text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs"
                            >
                                Tải lại trang
                            </button>
                            <Link
                                href={route("courses.index")}
                                className="px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs"
                            >
                                Về Kho môn học
                            </Link>
                        </div>
                    </div>
                </StudentLayout>
            );
        }

        return this.props.children;
    }
}

function CourseShowInner({
    course,
    documents = { data: [], total: 0, links: [] },
    reviews = { data: [], total: 0, links: [] },
    thong_ke = {},
    wishlist_requests = { data: [], total: 0, links: [] },
    filters = {},
}) {
    const pageProps = usePage().props;
    const currentUser = pageProps?.auth?.user || null;
    const availablePoints = currentUser?.vi_diem?.diem_kha_dung || 0;

    // 1. CHUẨN HÓA DỮ LIỆU ĐẦU VÀO
    const safeFilters = (!Array.isArray(filters) && filters && typeof filters === "object") ? filters : {};
    const safeDocuments = documents || { data: [], total: 0, links: [] };
    const safeReviews = reviews || { data: [], total: 0, links: [] };
    const safeWishlists = wishlist_requests || { data: [], total: 0, links: [] };
    const safeStats = thong_ke || {};

    const initialSearch = typeof safeFilters.search === "string" ? safeFilters.search : "";
    const initialCategory = typeof safeFilters.category === "string" && safeFilters.category ? safeFilters.category : "ALL";
    const initialSort = typeof safeFilters.sort === "string" && safeFilters.sort ? safeFilters.sort : "latest";
    const initialTab = safeFilters.tab === "wishlist"
        ? "wishlist"
        : (safeFilters.tab === "reviews" ? "reviews" : "documents");

    // 2. KHỞI TẠO TẤT CẢ REACT HOOKS Ở ĐẦU COMPONENT
    const [activeTab, setActiveTab] = useState(initialTab);
    const [search, setSearch] = useState(initialSearch);
    const [category, setCategory] = useState(initialCategory);
    const [sort, setSort] = useState(initialSort);
    const [isLoading, setIsLoading] = useState(false);

    // Reviews & Voting states (Phase 3B2)
    const [isReviewModalOpen, setIsReviewModalOpen] = useState(false);
    const [votingIds, setVotingIds] = useState(new Set());
    const [localReviewScores, setLocalReviewScores] = useState({});

    // Wishlist & Bounty states (Phase 3B3)
    const [isWishlistModalOpen, setIsWishlistModalOpen] = useState(false);
    const [isFulfillModalOpen, setIsFulfillModalOpen] = useState(false);
    const [selectedWishlistId, setSelectedWishlistId] = useState(null);
    const [wishlistStatus, setWishlistStatus] = useState(safeFilters.wishlist_status || "TAT_CA");
    const [wishlistCategory, setWishlistCategory] = useState(safeFilters.wishlist_category || "ALL");
    const [wishlistSort, setWishlistSort] = useState(safeFilters.wishlist_sort || "moi_nhat");

    // Kích hoạt truy vấn lọc & phân trang tới backend
    const applyFilters = useCallback(
        (newFilters = {}) => {
            if (!course?.ma_hoc_phan) return;

            const targetTab = newFilters.tab || activeTab;

            const queryParams = {
                search: typeof newFilters.search === "string" ? newFilters.search : search,
                category: typeof newFilters.category === "string" ? newFilters.category : category,
                sort: typeof newFilters.sort === "string" ? newFilters.sort : sort,
                tab: targetTab,
                wishlist_status: typeof newFilters.wishlist_status === "string" ? newFilters.wishlist_status : wishlistStatus,
                wishlist_category: typeof newFilters.wishlist_category === "string" ? newFilters.wishlist_category : wishlistCategory,
                wishlist_sort: typeof newFilters.wishlist_sort === "string" ? newFilters.wishlist_sort : wishlistSort,
            };

            if (!queryParams.search) delete queryParams.search;
            if (!queryParams.category || queryParams.category === "ALL") delete queryParams.category;
            if (!queryParams.sort || queryParams.sort === "latest") delete queryParams.sort;
            if (queryParams.tab === "documents") delete queryParams.tab;
            if (!queryParams.wishlist_status || queryParams.wishlist_status === "TAT_CA") delete queryParams.wishlist_status;
            if (!queryParams.wishlist_category || queryParams.wishlist_category === "ALL") delete queryParams.wishlist_category;
            if (!queryParams.wishlist_sort || queryParams.wishlist_sort === "moi_nhat") delete queryParams.wishlist_sort;

            setIsLoading(true);
            router.get(
                route("courses.show", course.ma_hoc_phan),
                queryParams,
                {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => setIsLoading(false),
                }
            );
        },
        [course?.ma_hoc_phan, search, category, sort, activeTab, wishlistStatus, wishlistCategory, wishlistSort]
    );

    // Debounced search cho tài liệu
    useEffect(() => {
        const timer = setTimeout(() => {
            const currentFilterSearch = typeof safeFilters.search === "string" ? safeFilters.search : "";
            if (search !== currentFilterSearch) {
                applyFilters({ search });
            }
        }, 350);

        return () => clearTimeout(timer);
    }, [search]);

    const handleCategorySelect = (catKey) => {
        setCategory(catKey);
        applyFilters({ category: catKey });
    };

    const handleSortChange = (e) => {
        const newSort = e.target.value;
        setSort(newSort);
        applyFilters({ sort: newSort });
    };

    const handleClearSearch = () => {
        setSearch("");
        applyFilters({ search: "" });
    };

    // Tương tác bộ lọc Wishlist
    const handleWishlistStatusChange = (status) => {
        setWishlistStatus(status);
        applyFilters({ wishlist_status: status, tab: "wishlist" });
    };

    const handleWishlistCategoryChange = (cat) => {
        setWishlistCategory(cat);
        applyFilters({ wishlist_category: cat, tab: "wishlist" });
    };

    const handleWishlistSortChange = (e) => {
        const val = e.target.value;
        setWishlistSort(val);
        applyFilters({ wishlist_sort: val, tab: "wishlist" });
    };

    // Tương tác Community Voting (Phase 3B2)
    const handleCommunityVote = (reviewItem, targetVote) => {
        if (!currentUser) {
            router.visit(route("login"));
            return;
        }

        const revId = reviewItem.id || reviewItem.ma_danh_gia;
        const isAuthor = reviewItem.co_quyen_chinh_sua || reviewItem.can_modify || reviewItem.is_my_review;

        if (isAuthor) {
            alert("Bạn không thể tự bình chọn đánh giá của chính mình.");
            return;
        }

        if (votingIds.has(revId)) return;

        setVotingIds((prev) => new Set(prev).add(revId));

        const currentData = localReviewScores[revId] || {
            my_vote: reviewItem.my_vote,
            review_score: reviewItem.review_score ?? 0,
            upvotes_count: reviewItem.upvotes_count ?? 0,
            downvotes_count: reviewItem.downvotes_count ?? 0,
        };

        const currentVote = currentData.my_vote;
        let nextVote = targetVote;
        let nextScore = currentData.review_score;
        let nextUpvotes = currentData.upvotes_count;
        let nextDownvotes = currentData.downvotes_count;

        if (currentVote === targetVote) {
            nextVote = null;
            if (targetVote === 1) {
                nextScore -= 1;
                nextUpvotes = Math.max(0, nextUpvotes - 1);
            } else {
                nextScore += 1;
                nextDownvotes = Math.max(0, nextDownvotes - 1);
            }
        } else if (currentVote === null || currentVote === undefined) {
            nextVote = targetVote;
            if (targetVote === 1) {
                nextScore += 1;
                nextUpvotes += 1;
            } else {
                nextScore -= 1;
                nextDownvotes += 1;
            }
        } else {
            nextVote = targetVote;
            if (targetVote === 1) {
                nextScore += 2;
                nextUpvotes += 1;
                nextDownvotes = Math.max(0, nextDownvotes - 1);
            } else {
                nextScore -= 2;
                nextUpvotes = Math.max(0, nextUpvotes - 1);
                nextDownvotes += 1;
            }
        }

        setLocalReviewScores((prev) => ({
            ...prev,
            [revId]: {
                my_vote: nextVote,
                review_score: nextScore,
                upvotes_count: nextUpvotes,
                downvotes_count: nextDownvotes,
            },
        }));

        const voteUrl = typeof route === "function" && route().has("reviews.vote")
            ? route("reviews.vote", revId)
            : `/danh-gia/${revId}/vote`;

        router.post(
            voteUrl,
            { vote_type: targetVote, loai_binh_chon: targetVote },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    setVotingIds((prev) => {
                        const next = new Set(prev);
                        next.delete(revId);
                        return next;
                    });
                },
                onError: () => {
                    setLocalReviewScores((prev) => {
                        const next = { ...prev };
                        delete next[revId];
                        return next;
                    });
                },
            }
        );
    };

    // Xóa bài đánh giá của chính mình
    const handleDeleteReview = (reviewId) => {
        if (!confirm("Bạn có chắc chắn muốn xóa bài đánh giá này không?")) return;

        router.delete(route("reviews.destroy", reviewId), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    // Báo cáo vi phạm đánh giá
    const handleReportReview = (reviewId) => {
        if (!currentUser) {
            router.visit(route("login"));
            return;
        }

        const reason = prompt("Vui lòng nhập lý do báo cáo bài đánh giá này:");
        if (!reason || !reason.trim()) return;

        router.post(
            route("reviews.report", reviewId),
            { ly_do: reason, chi_tiet: reason },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => alert("Đã gửi báo cáo vi phạm tới Ban Kiểm duyệt."),
            }
        );
    };

    // Hủy yêu cầu tài liệu Wishlist & Hoàn điểm (Phase 3B3)
    const handleCancelWishlist = (requestId) => {
        if (!confirm("Bạn có chắc chắn muốn hủy yêu cầu này? Điểm tạm giữ sẽ được hoàn lại 100% vào ví của bạn.")) {
            return;
        }

        router.delete(route("document-requests.destroy", requestId), {
            data: { redirect_to: "back" },
            preserveScroll: true,
            preserveState: true,
        });
    };

    // Mở modal đóng góp tài liệu phản hồi (Phase 3B3)
    const handleOpenFulfillModal = (requestId) => {
        if (!currentUser) {
            router.visit(route("login"));
            return;
        }
        setSelectedWishlistId(requestId);
        setIsFulfillModalOpen(true);
    };

    // 3. XỬ LÝ TRƯỜNG HỢP KHÔNG CÓ DỮ LIỆU MÔN HỌC
    if (!course) {
        return (
            <StudentLayout title="Không tìm thấy môn học">
                <Head title="Không tìm thấy môn học - AGU Academic Hub" />
                <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center max-w-xl mx-auto my-12">
                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-500 mb-4">
                        <AlertCircle size={32} />
                    </div>
                    <h2 className="text-lg font-bold text-slate-800">
                        Không tìm thấy thông tin môn học
                    </h2>
                    <p className="mt-2 text-sm text-slate-500">
                        Mã học phần bạn vừa truy cập không tồn tại hoặc đã tạm dừng đào tạo trong hệ thống.
                    </p>
                    <div className="mt-6">
                        <Link
                            href={route("courses.index")}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs"
                        >
                            <ArrowLeft size={14} />
                            Quay lại Kho môn học
                        </Link>
                    </div>
                </div>
            </StudentLayout>
        );
    }

    const majors = Array.isArray(course.chuyen_nganhs) ? course.chuyen_nganhs : [];
    const faculty = course.khoa || null;

    const categoryTabs = [
        { key: "ALL", label: "Tất cả" },
        { key: "DE_THI", label: "Đề thi" },
        { key: "BAI_GIANG", label: "Bài giảng" },
        { key: "DO_AN", label: "Đồ án" },
        { key: "BAI_TAP", label: "Bài tập" },
    ];

    // Trích xuất danh sách tài liệu
    const documentList = Array.isArray(safeDocuments)
        ? safeDocuments
        : (Array.isArray(safeDocuments.data) ? safeDocuments.data : []);
    const totalDocs = typeof safeDocuments.total === "number"
        ? safeDocuments.total
        : documentList.length;
    const documentPaginationLinks = Array.isArray(safeDocuments.links) ? safeDocuments.links : [];

    // Trích xuất danh sách đánh giá
    const reviewList = Array.isArray(safeReviews)
        ? safeReviews
        : (Array.isArray(safeReviews.data) ? safeReviews.data : []);
    const totalReviews = typeof safeReviews.total === "number"
        ? safeReviews.total
        : (typeof safeStats.tong_so_danh_gia === "number" ? safeStats.tong_so_danh_gia : reviewList.length);
    const reviewPaginationLinks = Array.isArray(safeReviews.links) ? safeReviews.links : [];

    // Trích xuất danh sách yêu cầu tài liệu Wishlist
    const wishlistList = Array.isArray(safeWishlists)
        ? safeWishlists
        : (Array.isArray(safeWishlists.data) ? safeWishlists.data : []);
    const totalWishlists = typeof safeWishlists.total === "number"
        ? safeWishlists.total
        : wishlistList.length;
    const wishlistPaginationLinks = Array.isArray(safeWishlists.links) ? safeWishlists.links : [];

    const getWishlistCategoryBadge = (cat) => {
        const map = {
            DE_THI: { label: "Đề thi", bg: "bg-indigo-50 text-indigo-700 border-indigo-200" },
            BAI_GIANG: { label: "Bài giảng", bg: "bg-emerald-50 text-emerald-700 border-emerald-200" },
            DO_AN: { label: "Đồ án", bg: "bg-purple-50 text-purple-700 border-purple-200" },
            BAI_TAP: { label: "Bài tập", bg: "bg-amber-50 text-amber-700 border-amber-200" },
            KHAC: { label: "Khác", bg: "bg-slate-100 text-slate-700 border-slate-200" },
        };
        return map[cat] || { label: "Tài liệu", bg: "bg-slate-100 text-slate-700 border-slate-200" };
    };

    const getWishlistStatusBadge = (status) => {
        if (status === "MO") {
            return { label: "Đang tìm kiếm", bg: "bg-blue-50 text-[#005baa] border-blue-200" };
        }
        if (status === "DA_HOAN_THANH") {
            return { label: "Đã có tài liệu", bg: "bg-emerald-50 text-emerald-700 border-emerald-200" };
        }
        if (status === "DA_HUY") {
            return { label: "Đã hủy", bg: "bg-slate-100 text-slate-500 border-slate-200" };
        }
        return { label: status, bg: "bg-slate-100 text-slate-600 border-slate-200" };
    };

    return (
        <StudentLayout title={`${course.ma_hoc_phan} - ${course.ten_mon_hoc}`}>
            <Head title={`${course.ma_hoc_phan} - ${course.ten_mon_hoc} | AGU Academic Hub`} />

            <div className="space-y-6">
                {/* 1. Header & Breadcrumb */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <nav className="flex items-center gap-2 text-xs text-slate-500 flex-wrap">
                        <Link
                            href={route("dashboard")}
                            className="hover:text-[#005baa] transition-colors"
                        >
                            Trang chủ
                        </Link>
                        <span>/</span>
                        <Link
                            href={route("courses.index")}
                            className="hover:text-[#005baa] transition-colors"
                        >
                            Kho Môn Học
                        </Link>
                        <span>/</span>
                        <span className="font-semibold text-slate-800 font-mono">
                            {course.ma_hoc_phan}
                        </span>
                    </nav>

                    <Link
                        href={route("courses.index")}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 hover:bg-slate-50 hover:text-[#005baa] transition-colors shadow-2xs self-start sm:self-auto"
                    >
                        <ArrowLeft size={14} />
                        <span>Quay lại Kho môn học</span>
                    </Link>
                </div>

                {/* 2. Hero Header Card */}
                <section className="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs space-y-5">
                    {/* Hàng Badges học thuật */}
                    <div className="flex flex-wrap items-center gap-2.5">
                        <span className="font-mono text-xs font-bold px-3 py-1.5 rounded-xl bg-blue-50 text-[#005baa] border border-blue-200/90 uppercase tracking-wider shadow-2xs">
                            {course.ma_hoc_phan}
                        </span>

                        <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                            <BookOpen size={14} className="text-[#005baa]" />
                            {course.so_tin_chi} tín chỉ
                        </span>

                        {faculty && (
                            <span className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                                <Building2 size={14} className="text-teal-600" />
                                {faculty.ten_khoa} {faculty.ky_hieu_khoa ? `(${faculty.ky_hieu_khoa})` : ""}
                            </span>
                        )}

                        {safeStats.diem_tong_quan > 0 && (
                            <span className="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200 shadow-2xs">
                                <Star size={14} className="fill-amber-400 text-amber-500" />
                                <span>{safeStats.diem_tong_quan} / 5</span>
                                <span className="text-[11px] font-normal text-amber-700">({totalReviews} đánh giá)</span>
                            </span>
                        )}
                    </div>

                    {/* Tên môn học */}
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            {course.ten_mon_hoc}
                        </h1>
                        <p className="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-4xl">
                            {course.mo_ta || "Học phần chính thức thuộc chương trình đào tạo của Trường Đại học An Giang. Sinh viên có thể tra cứu tài liệu học tập, tham khảo đánh giá khách quan và gửi yêu cầu tìm tài liệu có treo thưởng Bounty."}
                        </p>
                    </div>
                </section>

                {/* 3. Phân Khoa & Chuyên Ngành Đào Tạo */}
                <section className="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-4">
                    <div className="flex items-center gap-2 pb-3 border-b border-slate-100">
                        <Layers size={18} className="text-[#005baa]" />
                        <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wide">
                            Khoa & Chuyên ngành đào tạo áp dụng
                        </h2>
                    </div>

                    {majors.length > 0 ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                            {majors.map((major) => (
                                <div
                                    key={major.ma_chuyen_nganh}
                                    className="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50 hover:bg-slate-50 transition-colors shadow-2xs"
                                >
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100/70 text-amber-800 shrink-0">
                                        <GraduationCap size={16} />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        {major.ky_hieu_nganh && (
                                            <span className="text-[10px] font-mono font-bold text-amber-800 uppercase tracking-wider block">
                                                {major.ky_hieu_nganh}
                                            </span>
                                        )}
                                        <p className="text-xs font-semibold text-slate-800 truncate" title={major.ten_chuyen_nganh}>
                                            {major.ten_chuyen_nganh}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-4 text-xs text-blue-900 flex items-start gap-3">
                            <Info size={17} className="text-[#005baa] shrink-0 mt-0.5" />
                            <div>
                                <p className="font-semibold text-slate-800">
                                    Học phần đại cương / Cơ bản
                                </p>
                                <p className="mt-0.5 text-slate-600">
                                    Môn học này thuộc khối kiến thức cơ bản hoặc đại cương dùng chung cho sinh viên toàn trường, không phân biệt chuyên ngành đào tạo cụ thể.
                                </p>
                            </div>
                        </div>
                    )}
                </section>

                {/* 4. TABS ĐIỀU HƯỚNG CHÍNH: TÀI LIỆU | ĐÁNH GIÁ | WISHLIST (PHASE 3B1 + 3B2 + 3B3) */}
                <div className="flex items-center gap-1 sm:gap-2 border-b border-slate-200/80 pb-0 overflow-x-auto">
                    {/* Tab 1: Tài liệu */}
                    <button
                        type="button"
                        onClick={() => setActiveTab("documents")}
                        className={`relative pb-3.5 px-3 sm:px-4 text-xs sm:text-sm font-bold flex items-center gap-2 transition-all shrink-0 ${
                            activeTab === "documents"
                                ? "text-[#005baa] border-b-2 border-[#005baa]"
                                : "text-slate-500 hover:text-slate-900 border-b-2 border-transparent"
                        }`}
                    >
                        <BookOpen size={16} />
                        <span>Tài Liệu Môn Học</span>
                        <span className={`text-[11px] font-mono px-2 py-0.5 rounded-full ${
                            activeTab === "documents" ? "bg-blue-100 text-[#005baa]" : "bg-slate-100 text-slate-600"
                        }`}>
                            {totalDocs}
                        </span>
                    </button>

                    {/* Tab 2: Đánh giá */}
                    <button
                        type="button"
                        onClick={() => setActiveTab("reviews")}
                        className={`relative pb-3.5 px-3 sm:px-4 text-xs sm:text-sm font-bold flex items-center gap-2 transition-all shrink-0 ${
                            activeTab === "reviews"
                                ? "text-[#005baa] border-b-2 border-[#005baa]"
                                : "text-slate-500 hover:text-slate-900 border-b-2 border-transparent"
                        }`}
                    >
                        <Star size={16} className={activeTab === "reviews" ? "fill-amber-400 text-amber-500" : ""} />
                        <span>Đánh Giá & Nhận Xét</span>
                        <span className={`text-[11px] font-mono px-2 py-0.5 rounded-full ${
                            activeTab === "reviews" ? "bg-amber-100 text-amber-800" : "bg-slate-100 text-slate-600"
                        }`}>
                            {totalReviews}
                        </span>
                    </button>

                    {/* Tab 3: Wishlist / Yêu cầu tìm tài liệu (Phase 3B3) */}
                    <button
                        type="button"
                        onClick={() => setActiveTab("wishlist")}
                        className={`relative pb-3.5 px-3 sm:px-4 text-xs sm:text-sm font-bold flex items-center gap-2 transition-all shrink-0 ${
                            activeTab === "wishlist"
                                ? "text-[#005baa] border-b-2 border-[#005baa]"
                                : "text-slate-500 hover:text-slate-900 border-b-2 border-transparent"
                        }`}
                    >
                        <Coins size={16} className={activeTab === "wishlist" ? "text-amber-500" : ""} />
                        <span>Yêu Cầu Tìm Tài Liệu</span>
                        <span className={`text-[11px] font-mono px-2 py-0.5 rounded-full ${
                            activeTab === "wishlist" ? "bg-amber-100 text-amber-800" : "bg-slate-100 text-slate-600"
                        }`}>
                            {totalWishlists}
                        </span>
                    </button>
                </div>

                {/* 5A. TAB TÀI LIỆU HỌC TẬP (PHASE 3B1) */}
                {activeTab === "documents" && (
                    <section className="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs space-y-6">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#005baa]">
                                    <BookOpen size={20} />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h2 className="text-lg sm:text-xl font-bold text-slate-900">
                                            Kho Tài Liệu Học Phần
                                        </h2>
                                        <span className="font-mono text-xs font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-[#005baa] border border-blue-200">
                                            {totalDocs} tài liệu
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Danh sách bài giảng, đề thi, đồ án và tài liệu ôn tập của học phần {course.ma_hoc_phan}.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route("documents.create")}
                                className="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs shrink-0"
                            >
                                <Upload size={14} />
                                <span>Đăng tải tài liệu</span>
                            </Link>
                        </div>

                        {/* Toolbar: Tìm kiếm & Lọc */}
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 pt-1">
                            <div className="relative flex-1 max-w-md">
                                <Search
                                    size={16}
                                    className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"
                                />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Tìm kiếm tiêu đề tài liệu..."
                                    className="w-full pl-9 pr-9 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa] text-slate-800 placeholder-slate-400 transition-all"
                                />
                                {search && (
                                    <button
                                        type="button"
                                        onClick={handleClearSearch}
                                        className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1"
                                        title="Xóa tìm kiếm"
                                    >
                                        <X size={14} />
                                    </button>
                                )}
                            </div>

                            <div className="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-xl overflow-x-auto max-w-full">
                                    {categoryTabs.map((tab) => (
                                        <button
                                            key={tab.key}
                                            type="button"
                                            onClick={() => handleCategorySelect(tab.key)}
                                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-all shrink-0 ${
                                                category === tab.key
                                                    ? "bg-white text-[#005baa] shadow-2xs"
                                                    : "text-slate-600 hover:text-slate-900"
                                            }`}
                                        >
                                            {tab.label}
                                        </button>
                                    ))}
                                </div>

                                <select
                                    value={sort}
                                    onChange={handleSortChange}
                                    className="text-xs font-medium border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa] shrink-0"
                                >
                                    <option value="latest">Mới nhất</option>
                                    <option value="downloads">Lượt tải nhiều</option>
                                    <option value="upvotes">Được thích nhiều</option>
                                </select>
                            </div>
                        </div>

                        {/* Vùng danh sách tài liệu */}
                        <div className="relative min-h-[160px]">
                            {isLoading && (
                                <div className="absolute inset-0 bg-white/70 backdrop-blur-[1px] flex items-center justify-center z-10 rounded-xl">
                                    <div className="flex items-center gap-2 px-4 py-2 rounded-xl bg-white shadow-md border border-slate-200 text-xs font-semibold text-[#005baa]">
                                        <Loader2 size={16} className="animate-spin" />
                                        <span>Đang tải dữ liệu...</span>
                                    </div>
                                </div>
                            )}

                            {documentList.length > 0 ? (
                                <div className="space-y-6">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {documentList.map((doc) => (
                                            <DocumentCard
                                                key={doc.ma_tai_lieu || Math.random()}
                                                document={doc}
                                                showCourseInfo={false}
                                            />
                                        ))}
                                    </div>

                                    {documentPaginationLinks.length > 3 && (
                                        <Pagination
                                            links={documentPaginationLinks}
                                            className="pt-4 border-t border-slate-100"
                                        />
                                    )}
                                </div>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 sm:p-12 text-center">
                                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-[#005baa] mb-3">
                                        <FolderSearch size={28} />
                                    </div>
                                    <h3 className="text-base font-bold text-slate-800">
                                        Chưa có tài liệu nào thuộc môn học này
                                    </h3>
                                    <p className="mt-1.5 text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                                        {search || category !== "ALL"
                                            ? "Không tìm thấy tài liệu phù hợp với bộ lọc hiện tại. Thử xóa bộ lọc hoặc tìm với từ khóa khác."
                                            : "Chưa có sinh viên hoặc giảng viên nào đăng tải đề thi, bài giảng hay tài liệu cho học phần này."}
                                    </p>
                                    <div className="mt-5 flex items-center justify-center gap-3">
                                        {search || category !== "ALL" ? (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setSearch("");
                                                    setCategory("ALL");
                                                    applyFilters({ search: "", category: "ALL" });
                                                }}
                                                className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs"
                                            >
                                                <X size={14} />
                                                <span>Xóa bộ lọc</span>
                                            </button>
                                        ) : (
                                            <Link
                                                href={route("documents.create")}
                                                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs"
                                            >
                                                <Upload size={14} />
                                                <span>Đóng góp tài liệu đầu tiên</span>
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </section>
                )}

                {/* 5B. TAB ĐÁNH GIÁ MÔN HỌC & COMMUNITY VOTING (PHASE 3B2) */}
                {activeTab === "reviews" && (
                    <section className="space-y-6">
                        <div className="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-7 shadow-xs space-y-6">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                                <div>
                                    <div className="flex items-center gap-2.5">
                                        <h2 className="text-lg sm:text-xl font-bold text-slate-900">
                                            Đánh Giá Từ Cộng Đồng Sinh Viên
                                        </h2>
                                        <span className="font-mono text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                                            {totalReviews} nhận xét
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-500 mt-1">
                                        Đánh giá khách quan theo 3 tiêu chí: Độ khó, Tính thực tiễn và Khối lượng học tập.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={() => {
                                        if (!currentUser) {
                                            router.visit(route("login"));
                                            return;
                                        }
                                        setIsReviewModalOpen(true);
                                    }}
                                    className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs shrink-0"
                                >
                                    <MessageSquarePlus size={15} />
                                    <span>Viết đánh giá môn học</span>
                                </button>
                            </div>

                            {/* Khối thống kê 3 tiêu chí */}
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                                <div className="p-4 rounded-xl border border-amber-200/80 bg-amber-50/40 flex flex-col justify-between">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-amber-900">Điểm tổng quan</span>
                                        <Star size={16} className="fill-amber-400 text-amber-500" />
                                    </div>
                                    <div className="mt-3">
                                        <span className="text-2xl sm:text-3xl font-extrabold text-amber-950 font-mono">
                                            {safeStats.diem_tong_quan || 0}
                                        </span>
                                        <span className="text-xs text-amber-800 ml-1">/ 5★</span>
                                    </div>
                                    <p className="text-[11px] text-amber-800/80 mt-1">Trung bình cộng 3 tiêu chí</p>
                                </div>

                                <div className="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 flex flex-col justify-between">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-slate-700">Độ khó</span>
                                        <BarChart3 size={16} className="text-rose-500" />
                                    </div>
                                    <div className="mt-3">
                                        <span className="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono">
                                            {safeStats.avg_do_kho || 0}
                                        </span>
                                        <span className="text-xs text-slate-500 ml-1">/ 5★</span>
                                    </div>
                                    <p className="text-[11px] text-slate-500 mt-1">1: Dễ qua – 5: Rất khó</p>
                                </div>

                                <div className="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 flex flex-col justify-between">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-slate-700">Tính thực tiễn</span>
                                        <Sparkles size={16} className="text-teal-600" />
                                    </div>
                                    <div className="mt-3">
                                        <span className="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono">
                                            {safeStats.avg_thuc_tien || 0}
                                        </span>
                                        <span className="text-xs text-slate-500 ml-1">/ 5★</span>
                                    </div>
                                    <p className="text-[11px] text-slate-500 mt-1">1: Lý thuyết – 5: Thực hành cao</p>
                                </div>

                                <div className="p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 flex flex-col justify-between">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-slate-700">Khối lượng học tập</span>
                                        <Clock size={16} className="text-blue-600" />
                                    </div>
                                    <div className="mt-3">
                                        <span className="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono">
                                            {safeStats.avg_khoi_luong || 0}
                                        </span>
                                        <span className="text-xs text-slate-500 ml-1">/ 5★</span>
                                    </div>
                                    <p className="text-[11px] text-slate-500 mt-1">1: Nhẹ nhàng – 5: Bài tập nhiều</p>
                                </div>
                            </div>
                        </div>

                        {/* Danh sách Bài đánh giá */}
                        <div className="space-y-4">
                            {reviewList.length > 0 ? (
                                reviewList.map((review) => {
                                    const revId = review.id || review.ma_danh_gia;
                                    const author = review.tac_gia || review.author || {};
                                    const isAnon = review.an_danh || review.is_anonymous || author.la_an_danh;
                                    const authorName = author.ten_hien_thi || author.display_name || "Sinh viên AGU";
                                    const authorDept = author.ten_khoa || author.department;
                                    const isAuthor = review.co_quyen_chinh_sua || review.can_modify || review.is_my_review;
                                    const content = review.noi_dung || review.nhan_xet || review.content || "";
                                    const dateFormatted = review.ngay_tao || review.created_at || "Gần đây";
                                    const academicTerm = review.hoc_ky || review.academic_term || "";

                                    const voteData = localReviewScores[revId] || {
                                        my_vote: review.my_vote,
                                        review_score: review.review_score ?? 0,
                                        upvotes_count: review.upvotes_count ?? 0,
                                        downvotes_count: review.downvotes_count ?? 0,
                                    };

                                    const isUpvoted = voteData.my_vote === 1;
                                    const isDownvoted = voteData.my_vote === -1;
                                    const reviewScore = voteData.review_score;
                                    const isVoting = votingIds.has(revId);

                                    return (
                                        <div
                                            key={revId}
                                            className="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs hover:border-slate-300 transition-colors space-y-4"
                                        >
                                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0 border border-slate-200/70">
                                                        {isAnon ? <User size={18} /> : (authorName.charAt(0).toUpperCase() || "A")}
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2 flex-wrap">
                                                            <span className="font-bold text-xs sm:text-sm text-slate-900">
                                                                {authorName}
                                                            </span>
                                                            {authorDept && !isAnon && (
                                                                <span className="text-[11px] text-slate-500 font-medium">
                                                                    • {authorDept}
                                                                </span>
                                                            )}
                                                            {isAuthor && (
                                                                <span className="text-[10px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
                                                                    Bài viết của bạn
                                                                </span>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                                            <span>{dateFormatted}</span>
                                                            {academicTerm && (
                                                                <>
                                                                    <span>•</span>
                                                                    <span className="font-mono text-slate-500 font-medium">
                                                                        Học kỳ: {academicTerm}
                                                                    </span>
                                                                </>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="text-xs bg-slate-50 text-slate-700 px-2.5 py-1 rounded-lg font-medium border border-slate-200">
                                                        Độ khó: <strong className="text-rose-600">{review.diem_do_kho || review.difficulty_score}★</strong>
                                                    </span>
                                                    <span className="text-xs bg-slate-50 text-slate-700 px-2.5 py-1 rounded-lg font-medium border border-slate-200">
                                                        Thực tiễn: <strong className="text-teal-600">{review.diem_thuc_tien || review.practicality_score}★</strong>
                                                    </span>
                                                    <span className="text-xs bg-slate-50 text-slate-700 px-2.5 py-1 rounded-lg font-medium border border-slate-200">
                                                        Khối lượng: <strong className="text-blue-600">{review.diem_khoi_luong || review.diem_ap_luc_bai_tap || review.workload_score}★</strong>
                                                    </span>
                                                </div>
                                            </div>

                                            <p className="text-xs sm:text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                                                {content}
                                            </p>

                                            <div className="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100">
                                                <div className="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 self-start">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleCommunityVote(review, 1)}
                                                        disabled={isVoting || isAuthor}
                                                        title={isAuthor ? "Bạn không thể tự vote bài của mình" : "Bình chọn đánh giá này hữu ích (Upvote)"}
                                                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                                            isUpvoted
                                                                ? "bg-emerald-600 text-white shadow-xs"
                                                                : "text-slate-600 hover:text-emerald-700 hover:bg-white"
                                                        } ${isAuthor ? "opacity-50 cursor-not-allowed" : ""}`}
                                                    >
                                                        <ThumbsUp size={13} className={isUpvoted ? "fill-white" : ""} />
                                                        <span>Thích</span>
                                                    </button>

                                                    <span
                                                        className={`px-2.5 py-1 text-xs font-mono font-extrabold rounded-md ${
                                                            reviewScore > 0
                                                                ? "text-emerald-700 bg-emerald-50 border border-emerald-200/80"
                                                                : reviewScore < 0
                                                                ? "text-rose-700 bg-rose-50 border border-rose-200/80"
                                                                : "text-slate-600 bg-white border border-slate-200"
                                                        }`}
                                                        title={`Điểm cộng đồng: ${voteData.upvotes_count} Upvote / ${voteData.downvotes_count} Downvote`}
                                                    >
                                                        {reviewScore > 0 ? `+${reviewScore}` : reviewScore}
                                                    </span>

                                                    <button
                                                        type="button"
                                                        onClick={() => handleCommunityVote(review, -1)}
                                                        disabled={isVoting || isAuthor}
                                                        title={isAuthor ? "Bạn không thể tự vote bài của mình" : "Bình chọn đánh giá này chưa hữu ích (Downvote)"}
                                                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                                            isDownvoted
                                                                ? "bg-rose-600 text-white shadow-xs"
                                                                : "text-slate-600 hover:text-rose-700 hover:bg-white"
                                                        } ${isAuthor ? "opacity-50 cursor-not-allowed" : ""}`}
                                                    >
                                                        <ThumbsDown size={13} className={isDownvoted ? "fill-white" : ""} />
                                                        <span>Không thích</span>
                                                    </button>
                                                </div>

                                                <div className="flex items-center gap-2 text-xs">
                                                    {isAuthor ? (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleDeleteReview(revId)}
                                                            className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors"
                                                            title="Xóa đánh giá này"
                                                        >
                                                            <Trash2 size={13} />
                                                            <span>Xóa bài</span>
                                                        </button>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleReportReview(revId)}
                                                            className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                                            title="Báo cáo vi phạm tiêu chuẩn cộng đồng"
                                                        >
                                                            <Flag size={13} />
                                                            <span>Báo cáo</span>
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 sm:p-12 text-center">
                                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-3">
                                        <MessageSquarePlus size={28} />
                                    </div>
                                    <h3 className="text-base font-bold text-slate-800">
                                        Chưa có bài đánh giá nào cho học phần này
                                    </h3>
                                    <p className="mt-1.5 text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                                        Hãy là người đầu tiên chia sẻ trải nghiệm về độ khó, khối lượng bài tập và giảng viên để giúp các bạn sinh viên khóa sau có kế hoạch học tập tốt hơn.
                                    </p>
                                    <div className="mt-5">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (!currentUser) {
                                                    router.visit(route("login"));
                                                    return;
                                                }
                                                setIsReviewModalOpen(true);
                                            }}
                                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs"
                                        >
                                            <MessageSquarePlus size={14} />
                                            <span>Viết đánh giá đầu tiên</span>
                                        </button>
                                    </div>
                                </div>
                            )}

                            {reviewPaginationLinks.length > 3 && (
                                <Pagination
                                    links={reviewPaginationLinks}
                                    className="pt-4 border-t border-slate-100"
                                />
                            )}
                        </div>
                    </section>
                )}

                {/* 5C. TAB YÊU CẦU TÀI LIỆU (WISHLIST & BOUNTY) (PHASE 3B3) */}
                {activeTab === "wishlist" && (
                    <section className="space-y-6">
                        {/* 1. Header Banner & CTA Tạo yêu cầu */}
                        <div className="bg-gradient-to-r from-[#005baa] to-[#008f88] rounded-2xl p-6 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                            <div>
                                <div className="inline-flex items-center gap-1.5 px-3 py-1 bg-white/15 backdrop-blur-md rounded-full text-xs font-semibold tracking-wide mb-2">
                                    <Coins size={14} className="text-amber-300" />
                                    <span>Treo Thưởng Bounty Cho Môn Học Này</span>
                                </div>
                                <h2 className="text-xl sm:text-2xl font-extrabold">
                                    Yêu Cầu Tìm Tài Liệu Học Phần
                                </h2>
                                <p className="text-xs sm:text-sm text-blue-100 mt-1 max-w-xl leading-relaxed">
                                    Bạn chưa tìm thấy đề thi cũ, slide bài giảng hay bài tập của môn học này? Hãy tạo yêu cầu và treo thưởng Bounty để bạn bè cùng khóa hỗ trợ!
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full md:w-auto shrink-0">
                                {currentUser && (
                                    <div className="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl border border-white/20 text-xs flex items-center gap-2">
                                        <Coins size={15} className="text-amber-300" />
                                        <span>Ví khả dụng: <strong className="text-amber-300 text-sm font-mono">{availablePoints}</strong> điểm</span>
                                    </div>
                                )}

                                <button
                                    type="button"
                                    onClick={() => {
                                        if (!currentUser) {
                                            router.visit(route("login"));
                                            return;
                                        }
                                        setIsWishlistModalOpen(true);
                                    }}
                                    className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-amber-400 text-slate-950 hover:bg-amber-300 transition-colors shadow-sm w-full sm:w-auto"
                                >
                                    <Plus size={15} />
                                    <span>Tạo yêu cầu tài liệu</span>
                                </button>
                            </div>
                        </div>

                        {/* 2. Toolbar & Bộ lọc Wishlist */}
                        <div className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                {/* Lọc trạng thái */}
                                <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl overflow-x-auto">
                                    {[
                                        { key: "TAT_CA", label: "Tất cả" },
                                        { key: "MO", label: "Đang tìm kiếm" },
                                        { key: "DA_HOAN_THANH", label: "Đã có tài liệu" },
                                    ].map((st) => (
                                        <button
                                            key={st.key}
                                            type="button"
                                            onClick={() => handleWishlistStatusChange(st.key)}
                                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-all shrink-0 ${
                                                wishlistStatus === st.key
                                                    ? "bg-white text-[#005baa] shadow-2xs"
                                                    : "text-slate-600 hover:text-slate-900"
                                            }`}
                                        >
                                            {st.label}
                                        </button>
                                    ))}
                                </div>

                                {/* Lọc phân loại & Sắp xếp */}
                                <div className="flex items-center gap-2 flex-wrap">
                                    <select
                                        value={wishlistCategory}
                                        onChange={(e) => handleWishlistCategoryChange(e.target.value)}
                                        className="text-xs font-medium border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa]"
                                    >
                                        <option value="ALL">Tất cả phân loại</option>
                                        <option value="DE_THI">Đề thi / Kiểm tra</option>
                                        <option value="BAI_GIANG">Slide / Bài giảng</option>
                                        <option value="DO_AN">Đồ án / Source code</option>
                                        <option value="BAI_TAP">Bài tập / Lời giải</option>
                                        <option value="KHAC">Khác</option>
                                    </select>

                                    <select
                                        value={wishlistSort}
                                        onChange={handleWishlistSortChange}
                                        className="text-xs font-medium border border-slate-200 rounded-xl px-3 py-1.5 bg-white text-slate-700 focus:border-[#005baa] focus:ring-1 focus:ring-[#005baa]"
                                    >
                                        <option value="moi_nhat">Mới nhất</option>
                                        <option value="diem_cao">Điểm thưởng cao</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {/* 3. Danh sách Yêu cầu tài liệu Wishlist */}
                        <div className="space-y-4">
                            {wishlistList.length > 0 ? (
                                wishlistList.map((req) => {
                                    const reqId = req.ma_yeu_cau;
                                    const creator = req.nguoi_tao || req.nguoiTao || {};
                                    const creatorName = creator.ho_ten || "Thành viên AGU";
                                    const creatorReputation = creator.diem_uy_tin ?? 0;
                                    const isCreator = currentUser && (currentUser.ma_nguoi_dung === (req.ma_nguoi_tao || creator.ma_nguoi_dung));
                                    const categoryBadge = getWishlistCategoryBadge(req.phan_loai);
                                    const statusBadge = getWishlistStatusBadge(req.trang_thai);
                                    const isClosed = req.trang_thai === "DA_HOAN_THANH" || req.trang_thai === "DA_HUY";
                                    const dateFormatted = req.created_at
                                        ? new Date(req.created_at).toLocaleDateString("vi-VN", {
                                              year: "numeric",
                                              month: "short",
                                              day: "numeric",
                                          })
                                        : "Gần đây";

                                    return (
                                        <div
                                            key={reqId}
                                            className="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs hover:border-slate-300 transition-colors space-y-4"
                                        >
                                            {/* Header Card: Badges & Bounty */}
                                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className={`text-[11px] font-bold px-2.5 py-0.5 rounded-full border ${categoryBadge.bg}`}>
                                                        {categoryBadge.label}
                                                    </span>

                                                    <span className={`text-[11px] font-semibold px-2.5 py-0.5 rounded-full border ${statusBadge.bg}`}>
                                                        {statusBadge.label}
                                                    </span>

                                                    {req.ten_giang_vien && (
                                                        <span className="text-xs text-slate-500 font-medium">
                                                            GV: <strong className="text-slate-700">{req.ten_giang_vien}</strong>
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Điểm thưởng Bounty Badge */}
                                                <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 text-amber-800 border border-amber-200/90 font-mono font-extrabold text-xs shadow-2xs self-start sm:self-auto">
                                                    <Coins size={14} className="text-amber-500" />
                                                    <span>+{req.diem_thuong} Bounty</span>
                                                </div>
                                            </div>

                                            {/* Tiêu đề & Mô tả */}
                                            <div>
                                                <h3 className="text-base font-bold text-slate-900 leading-snug">
                                                    {req.tieu_de}
                                                </h3>
                                                {req.mo_ta && (
                                                    <p className="mt-1.5 text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">
                                                        {req.mo_ta}
                                                    </p>
                                                )}
                                            </div>

                                            {/* Tài liệu đáp ứng nếu đã hoàn thành */}
                                            {req.trang_thai === "DA_HOAN_THANH" && req.tai_lieu_dap_ung && (
                                                <div className="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between gap-3">
                                                    <div className="flex items-center gap-2 min-w-0">
                                                        <CheckCircle size={16} className="text-emerald-600 shrink-0" />
                                                        <span className="text-xs font-semibold text-emerald-900 truncate">
                                                            Tài liệu đã được duyệt: <strong>{req.tai_lieu_dap_ung.tieu_de}</strong>
                                                        </span>
                                                    </div>
                                                    <Link
                                                        href={route("documents.show", req.tai_lieu_dap_ung.ma_tai_lieu)}
                                                        className="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1 shrink-0"
                                                    >
                                                        <span>Xem tài liệu</span>
                                                        <ExternalLink size={12} />
                                                    </Link>
                                                </div>
                                            )}

                                            {/* Footer tương tác & Thông tin người yêu cầu */}
                                            <div className="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 text-xs">
                                                <div className="flex items-center gap-2.5">
                                                    <div className="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[11px] shrink-0 border border-slate-200">
                                                        {creator.anh_dai_dien ? (
                                                            <img src={creator.anh_dai_dien} alt={creatorName} className="w-full h-full rounded-full object-cover" />
                                                        ) : (
                                                            creatorName.charAt(0).toUpperCase() || "A"
                                                        )}
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-1.5">
                                                            <span className="font-semibold text-slate-800">
                                                                {creatorName}
                                                            </span>
                                                            {isCreator && (
                                                                <span className="text-[10px] font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.2 rounded border border-blue-200">
                                                                    Bạn
                                                                </span>
                                                            )}
                                                        </div>
                                                        <span className="text-[11px] text-slate-400">
                                                            Đăng ngày {dateFormatted} • {req.dap_ungs_count || 0} phản hồi
                                                        </span>
                                                    </div>
                                                </div>

                                                {/* Action buttons */}
                                                <div className="flex items-center gap-2 self-start sm:self-auto">
                                                    <Link
                                                        href={route("document-requests.show", reqId)}
                                                        className="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-2xs"
                                                    >
                                                        <span>Chi tiết</span>
                                                        <ExternalLink size={12} />
                                                    </Link>

                                                    {req.trang_thai === "MO" && !isCreator && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleOpenFulfillModal(reqId)}
                                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-[#005baa] text-white hover:bg-blue-800 transition-colors shadow-2xs"
                                                        >
                                                            <Upload size={13} />
                                                            <span>Đóng góp tài liệu</span>
                                                        </button>
                                                    )}

                                                    {req.trang_thai === "MO" && (isCreator || currentUser?.la_quan_tri) && (
                                                        <button
                                                            type="button"
                                                            onClick={() => handleCancelWishlist(reqId)}
                                                            className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition-colors"
                                                            title="Hủy yêu cầu và hoàn lại 100% điểm vào ví"
                                                        >
                                                            <Trash2 size={13} />
                                                            <span>Hủy yêu cầu</span>
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 sm:p-12 text-center">
                                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 mb-3">
                                        <Coins size={28} />
                                    </div>
                                    <h3 className="text-base font-bold text-slate-800">
                                        Chưa có yêu cầu tài liệu nào cho học phần này
                                    </h3>
                                    <p className="mt-1.5 text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                                        Bạn đang cần tìm đề thi các năm trước, đề cương chi tiết hay bài tập lớn? Hãy tạo yêu cầu và treo thưởng Bounty ngay để các bạn sinh viên hỗ trợ.
                                    </p>
                                    <div className="mt-5">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (!currentUser) {
                                                    router.visit(route("login"));
                                                    return;
                                                }
                                                setIsWishlistModalOpen(true);
                                            }}
                                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#005baa] text-white hover:bg-blue-700 transition-colors shadow-xs"
                                        >
                                            <Plus size={14} />
                                            <span>Tạo yêu cầu đầu tiên</span>
                                        </button>
                                    </div>
                                </div>
                            )}

                            {wishlistPaginationLinks.length > 3 && (
                                <Pagination
                                    links={wishlistPaginationLinks}
                                    className="pt-4 border-t border-slate-100"
                                />
                            )}
                        </div>
                    </section>
                )}

                {/* 6. Footer Navigation Actions */}
                <div className="pt-2 flex items-center justify-between flex-wrap gap-3">
                    <Link
                        href={route("courses.index")}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs"
                    >
                        <ArrowLeft size={14} />
                        <span>Xem tất cả môn học khác</span>
                    </Link>

                    <Link
                        href={route("dashboard")}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-[#005baa] bg-blue-50 hover:bg-blue-100 transition-colors border border-blue-200"
                    >
                        <Compass size={14} />
                        <span>Trang chủ sinh viên</span>
                    </Link>
                </div>
            </div>

            {/* Modal Viết đánh giá môn học (Phase 3B2) */}
            <CreateReviewModal
                isOpen={isReviewModalOpen}
                onClose={() => setIsReviewModalOpen(false)}
                course={course}
            />

            {/* Modal Tạo yêu cầu tìm tài liệu (Phase 3B3) */}
            <CreateRequestModal
                isOpen={isWishlistModalOpen}
                onClose={() => setIsWishlistModalOpen(false)}
                defaultCourse={course}
            />

            {/* Modal Đóng góp tài liệu săn thưởng (Phase 3B3) */}
            <FulfillRequestModal
                isOpen={isFulfillModalOpen}
                onClose={() => setIsFulfillModalOpen(false)}
                requestId={selectedWishlistId}
                course={course}
            />
        </StudentLayout>
    );
}

export default function CourseShow(props) {
    return (
        <CourseDetailErrorBoundary>
            <CourseShowInner {...props} />
        </CourseDetailErrorBoundary>
    );
}
